<?php
header('Content-Type: application/json');

include_once '../../config/database.php';
include_once '../includes/VisitLogger.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['log_id']) || !isset($input['duration'])) {
    echo json_encode(['success' => false]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $visitLogger = new VisitLogger($db);

    $logId = (int)$input['log_id'];
    $duration = (int)$input['duration'];

    // 체류시간 업데이트
    $visitLogger->updateDuration($logId, $duration);

    echo json_encode(['success' => true]);

} catch(Exception $e) {
    echo json_encode(['success' => false]);
}
?>