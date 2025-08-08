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

        // 브라우저 감지
        if (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/Edge/i', $userAgent)) $browser = 'Edge';
        elseif (preg_match('/Opera/i', $userAgent)) $browser = 'Opera';

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

    // 통계 집계 메서드들
    public function generateHourlyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    DATE(created_at) as stat_date,
                    HOUR(created_at) as stat_hour,
                    'hourly' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    COALESCE(AVG(visit_duration), 0) as avg_duration
                  FROM visit_logs 
                  WHERE DATE(created_at) = ?
                  GROUP BY DATE(created_at), HOUR(created_at)
                  ON DUPLICATE KEY UPDATE
                    total_visits = VALUES(total_visits),
                    unique_visitors = VALUES(unique_visitors),
                    page_views = VALUES(page_views),
                    avg_duration = VALUES(avg_duration),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date]);
    }

    public function generateDailyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    DATE(created_at) as stat_date,
                    NULL as stat_hour,
                    'daily' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    COALESCE(AVG(visit_duration), 0) as avg_duration
                  FROM visit_logs 
                  WHERE DATE(created_at) = ?
                  GROUP BY DATE(created_at)
                  ON DUPLICATE KEY UPDATE
                    total_visits = VALUES(total_visits),
                    unique_visitors = VALUES(unique_visitors),
                    page_views = VALUES(page_views),
                    avg_duration = VALUES(avg_duration),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date]);
    }

    public function generateWeeklyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        // 해당 주의 월요일 날짜 계산 (ISO 8601 기준)
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));

        $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    ? as stat_date,
                    NULL as stat_hour,
                    'weekly' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    COALESCE(AVG(visit_duration), 0) as avg_duration
                  FROM visit_logs 
                  WHERE DATE(created_at) BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)
                  ON DUPLICATE KEY UPDATE
                    total_visits = VALUES(total_visits),
                    unique_visitors = VALUES(unique_visitors),
                    page_views = VALUES(page_views),
                    avg_duration = VALUES(avg_duration),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$weekStart, $weekStart, $weekStart]);
    }

    public function generateMonthlyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        // 해당 월의 첫 번째 날 계산
        $monthStart = date('Y-m-01', strtotime($date));

        $query = "INSERT INTO visit_stats (stat_date, stat_hour, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    ? as stat_date,
                    NULL as stat_hour,
                    'monthly' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    COALESCE(AVG(visit_duration), 0) as avg_duration
                  FROM visit_logs 
                  WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
                  ON DUPLICATE KEY UPDATE
                    total_visits = VALUES(total_visits),
                    unique_visitors = VALUES(unique_visitors),
                    page_views = VALUES(page_views),
                    avg_duration = VALUES(avg_duration),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$monthStart, $date]);
    }
    // 이탈률 계산 및 업데이트 메서드 추가
    public function updateBounceRate($date = null, $statType = 'daily') {
        if (!$date) $date = date('Y-m-d');

        // 각 통계 유형별로 별도 처리
        switch($statType) {
            case 'hourly':
                return $this->updateHourlyBounceRate($date);
            case 'daily':
                return $this->updateDailyBounceRate($date);
            case 'weekly':
                return $this->updateWeeklyBounceRate($date);
            case 'monthly':
                return $this->updateMonthlyBounceRate($date);
            default:
                return false;
        }
    }
    private function updateHourlyBounceRate($date) {
        $query = "
            UPDATE visit_stats vs
            SET bounce_rate = (
                SELECT COALESCE(
                    (COUNT(CASE WHEN page_count = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0
                ) FROM (
                    SELECT session_id, COUNT(*) as page_count
                    FROM visit_logs 
                    WHERE DATE(created_at) = ? AND HOUR(created_at) = vs.stat_hour
                    GROUP BY session_id
                ) as session_stats
            )
            WHERE stat_date = ? AND stat_type = 'hourly'";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date, $date]);
    }

    private function updateDailyBounceRate($date) {
        $query = "
            UPDATE visit_stats vs
            SET bounce_rate = (
                SELECT COALESCE(
                    (COUNT(CASE WHEN page_count = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0
                ) FROM (
                    SELECT session_id, COUNT(*) as page_count
                    FROM visit_logs 
                    WHERE DATE(created_at) = ?
                    GROUP BY session_id
                ) as session_stats
            )
            WHERE stat_date = ? AND stat_type = 'daily'";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date, $date]);
    }

    private function updateWeeklyBounceRate($date) {
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));

        $query = "
            UPDATE visit_stats vs
            SET bounce_rate = (
                SELECT COALESCE(
                    (COUNT(CASE WHEN page_count = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0
                ) FROM (
                    SELECT session_id, COUNT(*) as page_count
                    FROM visit_logs 
                    WHERE DATE(created_at) BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)
                    GROUP BY session_id
                ) as session_stats
            )
            WHERE stat_date = ? AND stat_type = 'weekly'";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$weekStart, $weekStart, $weekStart]);
    }

    private function updateMonthlyBounceRate($date) {
        $monthStart = date('Y-m-01', strtotime($date));

        $query = "
            UPDATE visit_stats vs
            SET bounce_rate = (
                SELECT COALESCE(
                    (COUNT(CASE WHEN page_count = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0
                ) FROM (
                    SELECT session_id, COUNT(*) as page_count
                    FROM visit_logs 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
                    GROUP BY session_id
                ) as session_stats
            )
            WHERE stat_date = ? AND stat_type = 'monthly'";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date, $monthStart]);
    }
    // 전체 통계 생성 헬퍼 메서드
    public function generateAllStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        $results = [];
        $results['hourly'] = $this->generateHourlyStats($date);
        $results['daily'] = $this->generateDailyStats($date);
        $results['weekly'] = $this->generateWeeklyStats($date);
        $results['monthly'] = $this->generateMonthlyStats($date);

        // 이탈률 업데이트
        $this->updateBounceRate($date, 'hourly');
        $this->updateBounceRate($date, 'daily');
        $this->updateBounceRate($date, 'weekly');
        $this->updateBounceRate($date, 'monthly');

        return $results;
    }

    // 중복 데이터 정리 메서드
    public function cleanupDuplicateStats() {
        // 중복된 통계 데이터를 정리하는 쿼리
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
    }
}
?>