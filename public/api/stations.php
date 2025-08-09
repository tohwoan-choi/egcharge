<?php
header('Content-Type: application/json');
// 세션이 시작되지 않았다면 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

switch($method) {
    case 'GET':
        // 충전소 검색/조회
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $charge_type = isset($_GET['charge_type']) ? $_GET['charge_type'] : '';
        $connector_type = isset($_GET['connector_type']) ? $_GET['connector_type'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $offer_cd = isset($_GET['offer_cd']) ? $_GET['offer_cd'] : '';
        $cs_id = isset($_GET['cs_id']) ? $_GET['cs_id'] : '';
        $cp_id = isset($_GET['cp_id']) ? $_GET['cp_id'] : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        try {
            // 특정 충전기 조회
            if($offer_cd && $cs_id && $cp_id) {
                $query = "SELECT * FROM eg_charging_stations 
                         WHERE offer_cd = ? AND csId = ? AND cpId = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$offer_cd, $cs_id, $cp_id]);
                $station = $stmt->fetch(PDO::FETCH_ASSOC);

                if($station) {
                    $response['success'] = true;
                    $response['station'] = $station;
                } else {
                    $response['message'] = '충전기를 찾을 수 없습니다.';
                }
                break;
            }

            // 검색 조건 구성
            $conditions = [];
            $params = [];

            if($search) {
                $conditions[] = "(csNm LIKE ? OR addr LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if($charge_type) {
                $conditions[] = "charegTp = ?";
                $params[] = $charge_type;
            }

            if($connector_type) {
                $conditions[] = "cpTp = ?";
                $params[] = $connector_type;
            }

            if($status) {
                $conditions[] = "cpStat = ?";
                $params[] = $status;
            }

            // 기본 쿼리
            $query = "SELECT * FROM eg_charging_stations";

            if(!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $query .= " ORDER BY csNm, cpNm LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['stations'] = $stations;
            $response['count'] = count($stations);

        } catch(Exception $e) {
            $response['message'] = '충전소 조회 중 오류가 발생했습니다: ' . $e->getMessage();
        }
        break;

    default:
        $response['message'] = '지원하지 않는 요청 방법입니다.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>