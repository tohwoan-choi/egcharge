<?php
header('Content-Type: application/json');
session_start();

// 관리자 권한 체크 (필요시)
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit();
}

include_once '../../config/database.php';
include_once '../includes/VisitLogger.php';

$database = new Database();
$db = $database->getConnection();
$visitLogger = new VisitLogger($db);

$response = ['success' => false, 'data' => []];

$type = $_GET['type'] ?? 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    switch($type) {
        case 'hourly':
            // 시간별 통계
            $query = "SELECT 
                        stat_date as date,
                        stat_hour as hour,
                        total_visits,
                        unique_visitors,
                        page_views,
                        avg_duration,
                        bounce_rate
                      FROM visit_stats 
                      WHERE stat_type = 'hourly' 
                        AND stat_date BETWEEN ? AND ?
                      ORDER BY stat_date, stat_hour";
            break;

        case 'daily':
            // 일별 통계
            $query = "SELECT 
                        stat_date as date,
                        total_visits,
                        unique_visitors,
                        page_views,
                        avg_duration,
                        bounce_rate
                      FROM visit_stats 
                      WHERE stat_type = 'daily' 
                        AND stat_date BETWEEN ? AND ?
                      ORDER BY stat_date";
            break;

        case 'weekly':
            // 주간 통계
            $query = "SELECT 
                        stat_date as week_start,
                        DATE_ADD(stat_date, INTERVAL 6 DAY) as week_end,
                        total_visits,
                        unique_visitors,
                        page_views,
                        avg_duration,
                        bounce_rate
                      FROM visit_stats 
                      WHERE stat_type = 'weekly' 
                        AND stat_date BETWEEN ? AND ?
                      ORDER BY stat_date";
            break;

        case 'monthly':
            // 월간 통계
            $query = "SELECT 
                        DATE_FORMAT(stat_date, '%Y-%m') as month,
                        total_visits,
                        unique_visitors,
                        page_views,
                        avg_duration,
                        bounce_rate
                      FROM visit_stats 
                      WHERE stat_type = 'monthly' 
                        AND stat_date BETWEEN ? AND ?
                      ORDER BY stat_date";
            break;

        case 'realtime':
            // 실시간 통계 (직접 로그에서)
            $query = "SELECT 
                        DATE(created_at) as date,
                        HOUR(created_at) as hour,
                        COUNT(*) as visits,
                        COUNT(DISTINCT session_id) as unique_visitors,
                        COUNT(DISTINCT ip_address) as unique_ips
                      FROM visit_logs 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                      GROUP BY DATE(created_at), HOUR(created_at)
                      ORDER BY date DESC, hour DESC";
            break;

        default:
            throw new Exception('잘못된 통계 유형입니다.');
    }

    $stmt = $db->prepare($query);

    if ($type == 'realtime') {
        $stmt->execute();
    } else {
        $stmt->execute([$start_date, $end_date]);
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $data;
    $response['summary'] = [
        'total_visits' => array_sum(array_column($data, 'total_visits') ?: array_column($data, 'visits')),
        'total_unique' => array_sum(array_column($data, 'unique_visitors')),
        'avg_duration' => array_sum(array_column($data, 'avg_duration')) / max(count($data), 1),
        'period' => "$start_date ~ $end_date"
    ];

} catch(Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>