<?php
// CLI 환경에서 실행되는 스크립트
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../public/includes/VisitLogger.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $visitLogger = new VisitLogger($db);

    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // 어제 통계 생성
    $visitLogger->generateHourlyStats($yesterday);
    $visitLogger->generateDailyStats($yesterday);
    $visitLogger->generateWeeklyStats($yesterday);
    $visitLogger->generateMonthlyStats($yesterday);

    echo "Daily stats generated for {$yesterday}\n";

} catch(Exception $e) {
    error_log("Daily stats generation failed: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>