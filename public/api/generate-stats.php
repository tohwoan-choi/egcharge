<?php
header('Content-Type: application/json');
session_start();

// 관리자 권한 체크
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit();
}

include_once '../../config/database.php';
include_once '../includes/VisitLogger.php';

$database = new Database();
$db = $database->getConnection();
$visitLogger = new VisitLogger($db);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$response = ['success' => false, 'message' => ''];

try {
    switch($action) {
        case 'generate_all':
            // 지난 30일간 통계 생성
            for ($i = 30; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));

                $visitLogger->generateHourlyStats($date);
                $visitLogger->generateDailyStats($date);
                $visitLogger->generateWeeklyStats($date);
                $visitLogger->generateMonthlyStats($date);
            }

            $response['success'] = true;
            $response['message'] = '모든 통계가 생성되었습니다.';
            break;

        case 'generate_daily':
            $date = $input['date'] ?? date('Y-m-d');
            $visitLogger->generateDailyStats($date);
            $response['success'] = true;
            $response['message'] = "{$date} 일별 통계가 생성되었습니다.";
            break;

        case 'generate_weekly':
            $date = $input['date'] ?? date('Y-m-d');
            $visitLogger->generateWeeklyStats($date);
            $response['success'] = true;
            $response['message'] = "{$date} 주간 통계가 생성되었습니다.";
            break;

        case 'generate_monthly':
            $date = $input['date'] ?? date('Y-m-d');
            $visitLogger->generateMonthlyStats($date);
            $response['success'] = true;
            $response['message'] = "{$date} 월간 통계가 생성되었습니다.";
            break;

        default:
            throw new Exception('잘못된 액션입니다.');
    }

} catch(Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>