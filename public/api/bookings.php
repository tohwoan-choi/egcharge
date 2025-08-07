<?php
header('Content-Type: application/json');
session_start();

include_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];

switch($method) {
    case 'GET':
        // 예약 목록 조회
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        try {
            $query = "SELECT b.*, s.name as station_name, s.address, s.price 
                     FROM bookings b 
                     JOIN charging_stations s ON b.station_id = s.id 
                     WHERE b.user_id = ?";
            $params = [$user_id];

            if($status) {
                $query .= " AND b.status = ?";
                $params[] = $status;
            }

            $query .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['bookings'] = $bookings;
            $response['count'] = count($bookings);

        } catch(Exception $e) {
            $response['message'] = '예약 목록 조회 중 오류가 발생했습니다.';
        }
        break;

    case 'POST':
        // 새 예약 생성
        $input = json_decode(file_get_contents('php://input'), true);

        if(!$input) {
            $response['message'] = '잘못된 요청 데이터입니다.';
            break;
        }

        $station_id = $input['station_id'] ?? 0;
        $start_time = $input['start_time'] ?? date('Y-m-d H:i:s');
        $duration_hours = $input['duration_hours'] ?? 1;

        if(!$station_id) {
            $response['message'] = '충전소를 선택해주세요.';
            break;
        }

        try {
            // 충전소 정보 확인
            $station_query = "SELECT * FROM charging_stations WHERE id = ? AND status = 'active'";
            $station_stmt = $db->prepare($station_query);
            $station_stmt->execute([$station_id]);
            $station = $station_stmt->fetch(PDO::FETCH_ASSOC);

            if(!$station) {
                $response['message'] = '선택한 충전소를 찾을 수 없습니다.';
                break;
            }

            // 시간 충돌 확인
            $end_time = date('Y-m-d H:i:s', strtotime($start_time . " + {$duration_hours} hours"));

            $conflict_query = "SELECT COUNT(*) FROM bookings 
                              WHERE station_id = ? 
                              AND status = 'active' 
                              AND NOT (end_time <= ? OR start_time >= ?)";
            $conflict_stmt = $db->prepare($conflict_query);
            $conflict_stmt->execute([$station_id, $start_time, $end_time]);

            if($conflict_stmt->fetchColumn() > 0) {
                $response['message'] = '선택한 시간대에 이미 예약이 있습니다.';
                break;
            }

            // 총 비용 계산
            $total_cost = $station['price'] * $duration_hours;

            // 예약 생성
            $booking_query = "INSERT INTO bookings 
                             (user_id, station_id, start_time, end_time, total_cost) 
                             VALUES (?, ?, ?, ?, ?)";
            $booking_stmt = $db->prepare($booking_query);

            if($booking_stmt->execute([$user_id, $station_id, $start_time, $end_time, $total_cost])) {
                $booking_id = $db->lastInsertId();

                $response['success'] = true;
                $response['message'] = '예약이 완료되었습니다.';
                $response['booking_id'] = $booking_id;
                $response['total_cost'] = $total_cost;
            } else {
                $response['message'] = '예약 생성에 실패했습니다.';
            }

        } catch(Exception $e) {
            $response['message'] = '예약 처리 중 오류가 발생했습니다.';
        }
        break;

    case 'PUT':
        // 예약 수정
        $input = json_decode(file_get_contents('php://input'), true);

        if(!$input) {
            $response['message'] = '잘못된 요청 데이터입니다.';
            break;
        }

        $booking_id = $input['booking_id'] ?? 0;
        $action = $input['action'] ?? '';

        if(!$booking_id) {
            $response['message'] = '예약 ID가 필요합니다.';
            break;
        }

        try {
            // 예약 존재 및 소유권 확인
            $check_query = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$booking_id, $user_id]);
            $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if(!$booking) {
                $response['message'] = '예약을 찾을 수 없습니다.';
                break;
            }

            if($action === 'complete') {
                // 예약 완료
                $update_query = "UPDATE bookings SET status = 'completed', end_time = NOW() WHERE id = ?";
                $update_stmt = $db->prepare($update_query);

                if($update_stmt->execute([$booking_id])) {
                    $response['success'] = true;
                    $response['message'] = '예약이 완료되었습니다.';
                } else {
                    $response['message'] = '예약 완료 처리에 실패했습니다.';
                }
            }

        } catch(Exception $e) {
            $response['message'] = '예약 수정 중 오류가 발생했습니다.';
        }
        break;

    case 'DELETE':
        // 예약 취소
        $input = json_decode(file_get_contents('php://input'), true);

        if(!$input) {
            $response['message'] = '잘못된 요청 데이터입니다.';
            break;
        }

        $booking_id = $input['booking_id'] ?? 0;

        if(!$booking_id) {
            $response['message'] = '예약 ID가 필요합니다.';
            break;
        }

        try {
            // 예약 존재 및 소유권 확인
            $check_query = "SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'active'";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$booking_id, $user_id]);

            if($check_stmt->rowCount() === 0) {
                $response['message'] = '취소할 수 있는 예약을 찾을 수 없습니다.';
                break;
            }

            // 예약 취소
            $cancel_query = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
            $cancel_stmt = $db->prepare($cancel_query);

            if($cancel_stmt->execute([$booking_id])) {
                $response['success'] = true;
                $response['message'] = '예약이 취소되었습니다.';
            } else {
                $response['message'] = '예약 취소에 실패했습니다.';
            }

        } catch(Exception $e) {
            $response['message'] = '예약 취소 중 오류가 발생했습니다.';
        }
        break;

    default:
        $response['message'] = '지원하지 않는 요청 방법입니다.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>