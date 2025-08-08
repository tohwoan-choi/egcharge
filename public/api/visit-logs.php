<?php
header('Content-Type: application/json');
session_start();

// 관리자 권한 체크
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit();
}

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$response = ['success' => false, 'message' => ''];

try {
    switch($action) {
        case 'list':
            // 방문로그 목록 조회
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $sortColumn = $_GET['sort_column'] ?? 'created_at';
            $sortDirection = $_GET['sort_direction'] ?? 'desc';

            // 검색 및 필터 조건
            $search = $_GET['search'] ?? '';
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            $device = $_GET['device'] ?? '';
            $userType = $_GET['user_type'] ?? '';
            $browser = $_GET['browser'] ?? '';
            $pageUrl = $_GET['page_url'] ?? '';

            // WHERE 조건 구성
            $conditions = [];
            $params = [];

            // 날짜 필터
            $conditions[] = "DATE(vl.created_at) BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;

            // 검색 조건
            if ($search) {
                $conditions[] = "(vl.ip_address LIKE ? OR vl.page_title LIKE ? OR vl.page_url LIKE ? OR vl.browser LIKE ? OR u.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }

            // 기기 필터
            if ($device) {
                $conditions[] = "vl.device_type = ?";
                $params[] = $device;
            }

            // 사용자 유형 필터
            if ($userType === 'logged_in') {
                $conditions[] = "vl.user_id IS NOT NULL";
            } elseif ($userType === 'guest') {
                $conditions[] = "vl.user_id IS NULL";
            }

            // 브라우저 필터
            if ($browser) {
                $conditions[] = "vl.browser = ?";
                $params[] = $browser;
            }

            // 페이지 필터
            if ($pageUrl) {
                $conditions[] = "vl.page_url LIKE ?";
                $params[] = "%{$pageUrl}%";
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            // 허용된 정렬 컬럼
            $allowedSortColumns = ['created_at', 'user_name', 'ip_address', 'page_title', 'device_type', 'browser', 'visit_duration'];
            if (!in_array($sortColumn, $allowedSortColumns)) {
                $sortColumn = 'created_at';
            }

            $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

            // 총 개수 조회
            $countQuery = "SELECT COUNT(*) 
                          FROM visit_logs vl 
                          LEFT JOIN users u ON vl.user_id = u.id 
                          {$whereClause}";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();

            // 목록 조회
            $offset = ($page - 1) * $perPage;
            $listQuery = "SELECT vl.*, u.name as user_name, u.email as user_email
                         FROM visit_logs vl 
                         LEFT JOIN users u ON vl.user_id = u.id 
                         {$whereClause}
                         ORDER BY vl.{$sortColumn} {$sortDirection}
                         LIMIT {$perPage} OFFSET {$offset}";

            $listStmt = $db->prepare($listQuery);
            $listStmt->execute($params);
            $logs = $listStmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['logs'] = $logs;
            $response['pagination'] = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage)
            ];
            break;

        case 'detail':
            // 로그 상세 정보 조회
            $logId = (int)($_GET['id'] ?? 0);

            if (!$logId) {
                throw new Exception('로그 ID가 필요합니다.');
            }

            $query = "SELECT vl.*, u.name as user_name, u.email as user_email
                     FROM visit_logs vl 
                     LEFT JOIN users u ON vl.user_id = u.id 
                     WHERE vl.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$logId]);
            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$log) {
                throw new Exception('로그를 찾을 수 없습니다.');
            }

            $response['success'] = true;
            $response['log'] = $log;
            break;

        case 'summary':
            // 요약 통계 조회
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            // 총 방문수 및 순 방문자
            $statsQuery = "SELECT 
                          COUNT(*) as total_logs,
                          COUNT(DISTINCT session_id) as unique_visitors,
                          AVG(visit_duration) as avg_duration
                          FROM visit_logs 
                          WHERE DATE(created_at) BETWEEN ? AND ?";
            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute([$dateFrom, $dateTo]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // 현재 접속자 (최근 5분간 활동)
            $onlineQuery = "SELECT COUNT(DISTINCT session_id) as online_users
                           FROM visit_logs 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
            $onlineStmt = $db->prepare($onlineQuery);
            $onlineStmt->execute();
            $onlineUsers = $onlineStmt->fetchColumn();

            $response['success'] = true;
            $response['summary'] = [
                'total_logs' => (int)$stats['total_logs'],
                'unique_visitors' => (int)$stats['unique_visitors'],
                'avg_duration' => (float)$stats['avg_duration'],
                'online_users' => (int)$onlineUsers
            ];
            break;

        case 'export':
            // CSV 내보내기
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            // 필터 조건 (list와 동일한 로직)
            $conditions = ["DATE(vl.created_at) BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];

            $search = $_GET['search'] ?? '';
            if ($search) {
                $conditions[] = "(vl.ip_address LIKE ? OR vl.page_title LIKE ? OR vl.page_url LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            $device = $_GET['device'] ?? '';
            if ($device) {
                $conditions[] = "vl.device_type = ?";
                $params[] = $device;
            }

            $userType = $_GET['user_type'] ?? '';
            if ($userType === 'logged_in') {
                $conditions[] = "vl.user_id IS NOT NULL";
            } elseif ($userType === 'guest') {
                $conditions[] = "vl.user_id IS NULL";
            }

            $browser = $_GET['browser'] ?? '';
            if ($browser) {
                $conditions[] = "vl.browser = ?";
                $params[] = $browser;
            }

            $pageUrl = $_GET['page_url'] ?? '';
            if ($pageUrl) {
                $conditions[] = "vl.page_url LIKE ?";
                $params[] = "%{$pageUrl}%";
            }

            $whereClause = 'WHERE ' . implode(' AND ', $conditions);

            $exportQuery = "SELECT 
                           vl.created_at as '방문시간',
                           COALESCE(u.name, '비로그인 사용자') as '사용자',
                           vl.ip_address as 'IP주소',
                           vl.page_title as '페이지제목',
                           vl.page_url as '페이지URL',
                           vl.device_type as '기기유형',
                           vl.browser as '브라우저',
                           vl.os as '운영체제',
                           vl.visit_duration as '체류시간',
                           vl.referer as '리퍼러'
                           FROM visit_logs vl 
                           LEFT JOIN users u ON vl.user_id = u.id 
                           {$whereClause}
                           ORDER BY vl.created_at DESC
                           LIMIT 10000";

            $exportStmt = $db->prepare($exportQuery);
            $exportStmt->execute($params);
            $exportData = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

            // CSV 헤더 설정
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="visit_logs_' . $dateFrom . '_' . $dateTo . '.csv"');

            // UTF-8 BOM 추가 (Excel에서 한글 깨짐 방지)
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');

            // 헤더 출력
            if (!empty($exportData)) {
                fputcsv($output, array_keys($exportData[0]));

                // 데이터 출력
                foreach ($exportData as $row) {
                    fputcsv($output, $row);
                }
            }

            fclose($output);
            exit();

        case 'delete_old':
            // 오래된 로그 삭제 (90일 이상)
            $days = (int)($_GET['days'] ?? 90);

            $deleteQuery = "DELETE FROM visit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$days]);

            $deletedCount = $deleteStmt->rowCount();

            $response['success'] = true;
            $response['message'] = "{$deletedCount}개의 오래된 로그가 삭제되었습니다.";
            $response['deleted_count'] = $deletedCount;
            break;

        case 'browser_stats':
            // 브라우저별 통계
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            $browserQuery = "SELECT 
                            browser,
                            COUNT(*) as visits,
                            COUNT(DISTINCT session_id) as unique_visitors,
                            AVG(visit_duration) as avg_duration
                            FROM visit_logs 
                            WHERE DATE(created_at) BETWEEN ? AND ?
                            AND browser IS NOT NULL AND browser != ''
                            GROUP BY browser
                            ORDER BY visits DESC";

            $browserStmt = $db->prepare($browserQuery);
            $browserStmt->execute([$dateFrom, $dateTo]);
            $browserStats = $browserStmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['browser_stats'] = $browserStats;
            break;

        case 'device_stats':
            // 기기별 통계
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            $deviceQuery = "SELECT 
                           device_type,
                           COUNT(*) as visits,
                           COUNT(DISTINCT session_id) as unique_visitors,
                           AVG(visit_duration) as avg_duration
                           FROM visit_logs 
                           WHERE DATE(created_at) BETWEEN ? AND ?
                           AND device_type IS NOT NULL
                           GROUP BY device_type
                           ORDER BY visits DESC";

            $deviceStmt = $db->prepare($deviceQuery);
            $deviceStmt->execute([$dateFrom, $dateTo]);
            $deviceStats = $deviceStmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['device_stats'] = $deviceStats;
            break;

        case 'page_stats':
            // 페이지별 통계
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            $limit = (int)($_GET['limit'] ?? 20);

            $pageQuery = "SELECT 
                         page_url,
                         page_title,
                         COUNT(*) as visits,
                         COUNT(DISTINCT session_id) as unique_visitors,
                         AVG(visit_duration) as avg_duration
                         FROM visit_logs 
                         WHERE DATE(created_at) BETWEEN ? AND ?
                         AND page_url IS NOT NULL
                         GROUP BY page_url, page_title
                         ORDER BY visits DESC
                         LIMIT ?";

            $pageStmt = $db->prepare($pageQuery);
            $pageStmt->execute([$dateFrom, $dateTo, $limit]);
            $pageStats = $pageStmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['page_stats'] = $pageStats;
            break;

        case 'hourly_stats':
            // 시간별 방문 패턴
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            $hourlyQuery = "SELECT 
                           HOUR(created_at) as hour,
                           COUNT(*) as visits,
                           COUNT(DISTINCT session_id) as unique_visitors
                           FROM visit_logs 
                           WHERE DATE(created_at) BETWEEN ? AND ?
                           GROUP BY HOUR(created_at)
                           ORDER BY hour";

            $hourlyStmt = $db->prepare($hourlyQuery);
            $hourlyStmt->execute([$dateFrom, $dateTo]);
            $hourlyStats = $hourlyStmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['hourly_stats'] = $hourlyStats;
            break;

        default:
            throw new Exception('잘못된 액션입니다.');
    }

} catch(Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Visit logs API error: " . $e->getMessage());
}

if ($action !== 'export') {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>