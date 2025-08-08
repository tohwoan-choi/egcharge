<?php
class VisitLogger {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function log($userId = null, $pageTitle = '') {
        $sessionId = session_id();
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // 디바이스 정보 분석
        $deviceInfo = $this->analyzeUserAgent($userAgent);

        // 방문로그 저장
        $query = "INSERT INTO visit_logs 
                  (user_id, session_id, ip_address, user_agent, page_url, page_title, 
                   referer, device_type, browser, os) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $userId, $sessionId, $ipAddress, $userAgent, $pageUrl, $pageTitle,
            $referer, $deviceInfo['device'], $deviceInfo['browser'], $deviceInfo['os']
        ]);

        return $this->db->lastInsertId();
    }

    public function updateDuration($logId, $duration) {
        $query = "UPDATE visit_logs SET visit_duration = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$duration, $logId]);
    }

    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }

        return '0.0.0.0';
    }

    private function analyzeUserAgent($userAgent) {
        $device = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';

        // 모바일/태블릿 감지
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                $device = 'tablet';
            } else {
                $device = 'mobile';
            }
        }

        // 브라우저 감지 (순서 중요: Edge를 Chrome보다 먼저 체크)
        if (preg_match('/Edg/i', $userAgent)) $browser = 'Edge';
        elseif (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/Opera|OPR/i', $userAgent)) $browser = 'Opera';

        // OS 감지
        if (preg_match('/Windows/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) $os = 'macOS';
        elseif (preg_match('/Linux/i', $userAgent)) $os = 'Linux';
        elseif (preg_match('/Android/i', $userAgent)) $os = 'Android';
        elseif (preg_match('/iPhone|iPad/i', $userAgent)) $os = 'iOS';

        return [
            'device' => $device,
            'browser' => $browser,
            'os' => $os
        ];
    }

    // 시간별 통계 생성 (수정됨)
    public function generateHourlyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        try {
            // 먼저 데이터가 있는지 확인
            $checkQuery = "SELECT COUNT(*) FROM visit_logs WHERE DATE(created_at) = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$date]);

            if ($checkStmt->fetchColumn() == 0) {
                error_log("No visit_logs data found for date: {$date}");
                return false;
            }

            // 기존 시간별 통계 삭제
            $deleteQuery = "DELETE FROM visit_stats WHERE stat_date = ? AND stat_type = 'hourly'";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([$date]);

            // 새로운 시간별 통계 생성
            $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                      SELECT 
                        DATE(created_at) as stat_date,
                        HOUR(created_at) as stat_hour,
                        'hourly' as stat_type,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT session_id) as unique_visitors,
                        COUNT(*) as page_views,
                        COALESCE(AVG(NULLIF(visit_duration, 0)), 0) as avg_duration
                      FROM visit_logs 
                      WHERE DATE(created_at) = ?
                      GROUP BY DATE(created_at), HOUR(created_at)";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$date]);

            if ($result) {
                error_log("Hourly stats generated successfully for {$date}. Rows affected: " . $stmt->rowCount());
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error generating hourly stats for {$date}: " . $e->getMessage());
            return false;
        }
    }

    // 일별 통계 생성 (수정됨)
    public function generateDailyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        try {
            // 데이터 존재 확인
            $checkQuery = "SELECT COUNT(*) FROM visit_logs WHERE DATE(created_at) = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$date]);

            if ($checkStmt->fetchColumn() == 0) {
                error_log("No visit_logs data found for date: {$date}");
                return false;
            }

            // 기존 일별 통계 삭제 후 재생성
            $deleteQuery = "DELETE FROM visit_stats WHERE stat_date = ? AND stat_type = 'daily'";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([$date]);

            $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                      SELECT 
                        DATE(created_at) as stat_date,
                        NULL as stat_hour,
                        'daily' as stat_type,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT session_id) as unique_visitors,
                        COUNT(*) as page_views,
                        COALESCE(AVG(NULLIF(visit_duration, 0)), 0) as avg_duration
                      FROM visit_logs 
                      WHERE DATE(created_at) = ?
                      GROUP BY DATE(created_at)";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$date]);

            if ($result) {
                error_log("Daily stats generated successfully for {$date}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error generating daily stats for {$date}: " . $e->getMessage());
            return false;
        }
    }

    // 주간 통계 생성 (수정됨)
    public function generateWeeklyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        try {
            $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

            // 데이터 존재 확인
            $checkQuery = "SELECT COUNT(*) FROM visit_logs WHERE DATE(created_at) BETWEEN ? AND ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$weekStart, $weekEnd]);

            if ($checkStmt->fetchColumn() == 0) {
                error_log("No visit_logs data found for week: {$weekStart} to {$weekEnd}");
                return false;
            }

            // 기존 주간 통계 삭제
            $deleteQuery = "DELETE FROM visit_stats WHERE stat_date = ? AND stat_type = 'weekly'";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([$weekStart]);

            $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                      SELECT 
                        ? as stat_date,
                        NULL as stat_hour,
                        'weekly' as stat_type,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT session_id) as unique_visitors,
                        COUNT(*) as page_views,
                        COALESCE(AVG(NULLIF(visit_duration, 0)), 0) as avg_duration
                      FROM visit_logs 
                      WHERE DATE(created_at) BETWEEN ? AND ?";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$weekStart, $weekStart, $weekEnd]);

            if ($result) {
                error_log("Weekly stats generated successfully for week starting {$weekStart}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error generating weekly stats for {$date}: " . $e->getMessage());
            return false;
        }
    }

    // 월간 통계 생성 (수정됨)
    public function generateMonthlyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        try {
            $monthStart = date('Y-m-01', strtotime($date));
            $monthEnd = date('Y-m-t', strtotime($date));

            // 데이터 존재 확인
            $checkQuery = "SELECT COUNT(*) FROM visit_logs WHERE DATE(created_at) BETWEEN ? AND ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$monthStart, $monthEnd]);

            if ($checkStmt->fetchColumn() == 0) {
                error_log("No visit_logs data found for month: {$monthStart} to {$monthEnd}");
                return false;
            }

            // 기존 월간 통계 삭제
            $deleteQuery = "DELETE FROM visit_stats WHERE stat_date = ? AND stat_type = 'monthly'";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([$monthStart]);

            $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                      SELECT 
                        ? as stat_date,
                        NULL as stat_hour,
                        'monthly' as stat_type,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT session_id) as unique_visitors,
                        COUNT(*) as page_views,
                        COALESCE(AVG(NULLIF(visit_duration, 0)), 0) as avg_duration
                      FROM visit_logs 
                      WHERE DATE(created_at) BETWEEN ? AND ?";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$monthStart, $monthStart, $monthEnd]);

            if ($result) {
                error_log("Monthly stats generated successfully for month starting {$monthStart}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error generating monthly stats for {$date}: " . $e->getMessage());
            return false;
        }
    }

    // 이탈률 업데이트 (간소화됨)
    public function updateBounceRate($date = null, $statType = 'daily') {
        if (!$date) $date = date('Y-m-d');

        try {
            switch($statType) {
                case 'hourly':
                    $whereClause = "stat_date = ? AND stat_type = 'hourly'";
                    $params = [$date];
                    $dateCondition = "DATE(created_at) = ? AND HOUR(created_at) = vs.stat_hour";
                    break;

                case 'daily':
                    $whereClause = "stat_date = ? AND stat_type = 'daily'";
                    $params = [$date];
                    $dateCondition = "DATE(created_at) = ?";
                    break;

                case 'weekly':
                    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                    $whereClause = "stat_date = ? AND stat_type = 'weekly'";
                    $params = [$weekStart];
                    $dateCondition = "DATE(created_at) BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)";
                    break;

                case 'monthly':
                    $monthStart = date('Y-m-01', strtotime($date));
                    $whereClause = "stat_date = ? AND stat_type = 'monthly'";
                    $params = [$monthStart];
                    $dateCondition = "DATE(created_at) BETWEEN ? AND LAST_DAY(?)";
                    break;

                default:
                    return false;
            }

            $query = "
                UPDATE visit_stats vs
                SET bounce_rate = (
                    SELECT COALESCE(
                        (COUNT(CASE WHEN page_count = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0
                    ) FROM (
                        SELECT session_id, COUNT(*) as page_count
                        FROM visit_logs 
                        WHERE {$dateCondition}
                        GROUP BY session_id
                    ) as session_stats
                )
                WHERE {$whereClause}";

            $stmt = $this->db->prepare($query);

            // 파라미터 준비
            if ($statType === 'weekly') {
                $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $executeParams = array_merge([$weekStart, $weekStart], $params);
            } elseif ($statType === 'monthly') {
                $monthStart = date('Y-m-01', strtotime($date));
                $executeParams = array_merge([$monthStart, $monthStart], $params);
            } else {
                $executeParams = array_merge($params, $params);
            }

            return $stmt->execute($executeParams);

        } catch (Exception $e) {
            error_log("Error updating bounce rate for {$statType} on {$date}: " . $e->getMessage());
            return false;
        }
    }

    // 전체 통계 생성
    public function generateAllStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        $results = [];

        // 시간별 통계는 선택적으로만 생성
        // $results['hourly'] = $this->generateHourlyStats($date);

        $results['daily'] = $this->generateDailyStats($date);
        $results['weekly'] = $this->generateWeeklyStats($date);
        $results['monthly'] = $this->generateMonthlyStats($date);

        // 이탈률 업데이트
        // $this->updateBounceRate($date, 'hourly');
        $this->updateBounceRate($date, 'daily');
        $this->updateBounceRate($date, 'weekly');
        $this->updateBounceRate($date, 'monthly');

        return $results;
    }

    // 통계 조회 메서드 추가
    public function getStats($startDate, $endDate = null, $statType = 'daily') {
        if (!$endDate) $endDate = $startDate;

        $query = "SELECT * FROM visit_stats 
                  WHERE stat_type = ? 
                  AND stat_date BETWEEN ? AND ? 
                  ORDER BY stat_date ASC, stat_hour ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$statType, $startDate, $endDate]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 중복 데이터 정리
    public function cleanupDuplicateStats() {
        try {
            $query = "
                DELETE vs1 FROM visit_stats vs1
                INNER JOIN visit_stats vs2 
                WHERE vs1.id > vs2.id 
                AND vs1.stat_date = vs2.stat_date 
                AND vs1.stat_type = vs2.stat_type
                AND (vs1.stat_hour = vs2.stat_hour OR (vs1.stat_hour IS NULL AND vs2.stat_hour IS NULL))
            ";

            $stmt = $this->db->prepare($query);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error cleaning up duplicate stats: " . $e->getMessage());
            return false;
        }
    }
}
?>