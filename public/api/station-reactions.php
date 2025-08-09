<?php
// api/station-reactions.php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['station_ids']) && is_array($input['station_ids'])) {
            // 여러 충전소의 반응 데이터 조회
            $stationIds = $input['station_ids'];
            $placeholders = str_repeat('?,', count($stationIds) - 1) . '?';

            // 좋아요/싫어요 카운트
            $stmt = $db->prepare("
                SELECT 
                    station_id,
                    SUM(CASE WHEN reaction_type = 'like' THEN 1 ELSE 0 END) as likes,
                    SUM(CASE WHEN reaction_type = 'dislike' THEN 1 ELSE 0 END) as dislikes
                FROM station_reactions 
                WHERE station_id IN ($placeholders)
                GROUP BY station_id
            ");
            $stmt->execute($stationIds);
            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 한줄평 카운트
            $stmt = $db->prepare("
                SELECT station_id, COUNT(*) as reviews_count 
                FROM station_reviews 
                WHERE station_id IN ($placeholders)
                GROUP BY station_id
            ");
            $stmt->execute($stationIds);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 결과 정리
            $result = [];
            foreach ($stationIds as $stationId) {
                $result[$stationId] = [
                    'likes' => 0,
                    'dislikes' => 0,
                    'reviews_count' => 0
                ];
            }

            foreach ($reactions as $reaction) {
                $result[$reaction['station_id']]['likes'] = intval($reaction['likes']);
                $result[$reaction['station_id']]['dislikes'] = intval($reaction['dislikes']);
            }

            foreach ($reviews as $review) {
                $result[$review['station_id']]['reviews_count'] = intval($review['reviews_count']);
            }

            $response['success'] = true;
            $response['reactions'] = $result;
        } else {
            $response['message'] = '잘못된 요청입니다.';
        }
    } else {
        $response['message'] = '지원하지 않는 요청 방법입니다.';
    }
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>