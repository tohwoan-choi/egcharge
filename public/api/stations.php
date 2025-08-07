<?php
header('Content-Type: application/json');
session_start();

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

switch($method) {
    case 'GET':
        // 충전소 검색/조회
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        try {
            if($search) {
                $query = "SELECT * FROM charging_stations 
                         WHERE (name LIKE ? OR address LIKE ?) 
                         AND status = 'active' 
                         ORDER BY name 
                         LIMIT ? OFFSET ?";
                $stmt = $db->prepare($query);
                $searchTerm = "%{$search}%";
                $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
            } else {
                $query = "SELECT * FROM charging_stations 
                         WHERE status = 'active' 
                         ORDER BY name 
                         LIMIT ? OFFSET ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$limit, $offset]);
            }

            $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['stations'] = $stations;
            $response['count'] = count($stations);

        } catch(Exception $e) {
            $response['message'] = '충전소 조회 중 오류가 발생했습니다.';
        }
        break;

    case 'POST':
        // 충전소 추가 (관리자만)
        if(!isset($_SESSION['user_id'])) {
            $response['message'] = '로그인이 필요합니다.';
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if(!$input) {
            $response['message'] = '잘못된 요청 데이터입니다.';
            break;
        }

        $name = $input['name'] ?? '';
        $address = $input['address'] ?? '';
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;
        $price = $input['price'] ?? 0;
        $connector_type = $input['connector_type'] ?? '';
        $charging_speed = $input['charging_speed'] ?? '';

        if(empty($name) || empty($address)) {
            $response['message'] = '필수 정보가 누락되었습니다.';
            break;
        }

        try {
            $query = "INSERT INTO charging_stations 
                     (name, address, latitude, longitude, price, connector_type, charging_speed) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);

            if($stmt->execute([$name, $address, $latitude, $longitude, $price, $connector_type, $charging_speed])) {
                $response['success'] = true;
                $response['message'] = '충전소가 추가되었습니다.';
                $response['station_id'] = $db->lastInsertId();
            } else {
                $response['message'] = '충전소 추가에 실패했습니다.';
            }

        } catch(Exception $e) {
            $response['message'] = '충전소 추가 중 오류가 발생했습니다.';
        }
        break;

    default:
        $response['message'] = '지원하지 않는 요청 방법입니다.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>