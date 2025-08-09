<?php
header('Content-Type: application/json');
// 세션이 시작되지 않았다면 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../../config/database.php';
include_once '../includes/stations_helper.php'; // 이 라인 추가

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

            // fetchStationsData 함수 사용하도록 수정
            include_once '../includes/stations_helper.php';
            $result = fetchStationsData($search, $charge_type, $connector_type, $status);

            $response = $result; // fetchStationsData가 이미 올바른 형태로 반환

        } catch(Exception $e) {
            $response['message'] = '충전소 조회 중 오류가 발생했습니다: ' . $e->getMessage();
        }
        break;

    default:
        $response['message'] = '지원하지 않는 요청 방법입니다.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>