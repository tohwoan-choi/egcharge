<?php
// test-stats.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../public/includes/VisitLogger.php';

$database = new Database();
$db = $database->getConnection();
$visitLogger = new VisitLogger($db);

$testDate = '2025-08-08'; // 테스트할 날짜

echo "=== 통계 생성 테스트 ===\n";
echo "테스트 날짜: {$testDate}\n\n";

// 1. visit_logs 테이블에 데이터가 있는지 확인
$checkQuery = "SELECT COUNT(*) as count, MIN(created_at) as min_date, MAX(created_at) as max_date FROM visit_logs";
$stmt = $db->prepare($checkQuery);
$stmt->execute();
$logData = $stmt->fetch(PDO::FETCH_ASSOC);

echo "1. visit_logs 데이터 확인:\n";
echo "   총 레코드 수: {$logData['count']}\n";
echo "   최초 날짜: {$logData['min_date']}\n";
echo "   최신 날짜: {$logData['max_date']}\n\n";

// 특정 날짜 데이터 확인
$dateQuery = "SELECT COUNT(*) as count FROM visit_logs WHERE DATE(created_at) = ?";
$stmt = $db->prepare($dateQuery);
$stmt->execute([$testDate]);
$dateCount = $stmt->fetchColumn();

echo "2. {$testDate} 날짜 데이터: {$dateCount}건\n\n";

if ($dateCount == 0) {
    echo "⚠️  테스트 날짜에 데이터가 없습니다. 다른 날짜를 시도해보세요.\n";

    // 데이터가 있는 날짜 제안
    $availableQuery = "SELECT DATE(created_at) as date, COUNT(*) as count FROM visit_logs GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 5";
    $stmt = $db->prepare($availableQuery);
    $stmt->execute();
    $availableDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "사용 가능한 날짜들:\n";
    foreach ($availableDates as $dateInfo) {
        echo "   {$dateInfo['date']} ({$dateInfo['count']}건)\n";
    }
    exit;
}

// 3. 통계 생성 테스트
echo "3. 통계 생성 테스트:\n";

// 기존 통계 삭제
//$deleteQuery = "DELETE FROM visit_stats WHERE stat_date = ? OR stat_date = ?";
//$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($testDate)));
//$monthStart = date('Y-m-01', strtotime($testDate));
//$stmt = $db->prepare($deleteQuery);
//$stmt->execute([$testDate, $weekStart]);

// 시간별 통계
echo "   - 시간별 통계 생성: ";
$dailyResult = $visitLogger->generateHourlyStats($testDate);
echo $dailyResult ? "✓ 성공" : "✗ 실패";
echo "\n";

// 일별 통계
echo "   - 일별 통계 생성: ";
$dailyResult = $visitLogger->generateDailyStats($testDate);
echo $dailyResult ? "✓ 성공" : "✗ 실패";
echo "\n";

// 주간 통계
echo "   - 주간 통계 생성: ";
$weeklyResult = $visitLogger->generateWeeklyStats($testDate);
echo $weeklyResult ? "✓ 성공" : "✗ 실패";
echo "\n";

// 월간 통계
echo "   - 월간 통계 생성: ";
$monthlyResult = $visitLogger->generateMonthlyStats($testDate);
echo $monthlyResult ? "✓ 성공" : "✗ 실패";
echo "\n\n";

// 4. 생성된 통계 확인
echo "4. 생성된 통계 확인:\n";
$statsQuery = "SELECT stat_type, stat_date, stat_hour, total_visits, unique_visitors, page_views, avg_duration, bounce_rate FROM visit_stats ORDER BY stat_type, stat_date, stat_hour";
$stmt = $db->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($stats)) {
    echo "   ⚠️  생성된 통계가 없습니다.\n";
} else {
    foreach ($stats as $stat) {
        echo sprintf(
            "   %s | %s %s | 방문:%d, 유니크:%d, PV:%d, 체류:%.2f, 이탈:%.2f%%\n",
            $stat['stat_type'],
            $stat['stat_date'],
            $stat['stat_hour'] !== null ? sprintf('(%02d시)', $stat['stat_hour']) : '     ',
            $stat['total_visits'],
            $stat['unique_visitors'],
            $stat['page_views'],
            $stat['avg_duration'],
            $stat['bounce_rate']
        );
    }
}

// 5. 이탈률 업데이트 테스트
echo "\n5. 이탈률 업데이트 테스트:\n";
echo "   - 일별 이탈률: ";
$bounceDaily = $visitLogger->updateBounceRate($testDate, 'daily');
echo $bounceDaily ? "✓ 성공" : "✗ 실패";
echo "\n";

echo "   - 주간 이탈률: ";
$bounceWeekly = $visitLogger->updateBounceRate($testDate, 'weekly');
echo $bounceWeekly ? "✓ 성공" : "✗ 실패";
echo "\n";

echo "   - 월간 이탈률: ";
$bounceMonthly = $visitLogger->updateBounceRate($testDate, 'monthly');
echo $bounceMonthly ? "✓ 성공" : "✗ 실패";
echo "\n\n";

// 6. 최종 결과
echo "6. 최종 통계 결과:\n";
$stmt->execute();
$finalStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($finalStats as $stat) {
    echo sprintf(
        "   %s | %s %s | 방문:%d, 유니크:%d, PV:%d, 체류:%.2f, 이탈:%.2f%%\n",
        $stat['stat_type'],
        $stat['stat_date'],
        $stat['stat_hour'] !== null ? sprintf('(%02d시)', $stat['stat_hour']) : '     ',
        $stat['total_visits'],
        $stat['unique_visitors'],
        $stat['page_views'],
        $stat['avg_duration'],
        $stat['bounce_rate']
    );
}

echo "\n=== 테스트 완료 ===\n";
?>