<?php
// api/reactions.php
session_start();
header('Content-Type: application/json');
include_once '../../config/database.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

$database = new Database();
$db = $database->getConnection();


if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $station_id = $input['station_id'] ?? '';
    $reaction = $input['reaction'] ?? null; // 'like', 'dislike', null

    if (empty($station_id)) {
        echo json_encode(['success' => false, 'message' => '충전소 ID가 필요합니다.']);
        exit;
    }

    try {

        if ($reaction === null) {
            // 반응 삭제
            $stmt = $db->prepare("DELETE FROM station_reactions WHERE user_id = ? AND station_id = ?");
            $stmt->execute([$user_id, $station_id]);
        } else {
            // 기존 반응 확인 및 업데이트/삽입
            $stmt = $db->prepare("SELECT id FROM station_reactions WHERE user_id = ? AND station_id = ?");
            $stmt->execute([$user_id, $station_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                // 업데이트
                $stmt = $db->prepare("UPDATE station_reactions SET reaction_type = ?, created_at = NOW() WHERE user_id = ? AND station_id = ?");
                $stmt->execute([$reaction, $user_id, $station_id]);
            } else {
                // 삽입
                $stmt = $db->prepare("INSERT INTO station_reactions (user_id, station_id, reaction_type, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $station_id, $reaction]);
            }
        }

        echo json_encode(['success' => true, 'message' => '반응이 저장되었습니다.']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
    }

} elseif ($method === 'GET') {
    // 사용자의 반응 상태 조회
    try {

        $stmt = $db->prepare("SELECT station_id, reaction_type FROM station_reactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $user_reactions = [];
        foreach ($reactions as $reaction) {
            $user_reactions[$reaction['station_id']] = $reaction['reaction_type'];
        }

        echo json_encode(['success' => true, 'reactions' => $user_reactions]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '지원하지 않는 요청 방식입니다.']);
}
?>