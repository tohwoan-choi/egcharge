<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

function fetchStationsData($search = '', $charge_type = '', $connector_type = '', $status = '') {
    global $db;
    $stations = [];
    $response = [
        'success' => false,
        'stations' => [],
        'count' => 0
    ];
    try {

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
        $params[] = 50;
        $params[] = 0;

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['stations'] = $stations;
        $response['count'] = count($stations);

    } catch(Exception $e) {
        error_log('충전소 조회 중 오류가 발생했습니다: ' . $e->getMessage());
    }
    return $response;
}

?>