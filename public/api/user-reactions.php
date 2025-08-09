<?php
// api/user-reactions.php
session_start();
header('Content-Type: application/json');
include_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

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
?>