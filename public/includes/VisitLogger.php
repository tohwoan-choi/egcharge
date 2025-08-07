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
                    AVG(visit_duration) as avg_duration
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

        $query = "INSERT INTO visit_stats (stat_date, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    DATE(created_at) as stat_date,
                    'daily' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    AVG(visit_duration) as avg_duration
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

        $query = "INSERT INTO visit_stats (stat_date, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    DATE(DATE_SUB(?, INTERVAL WEEKDAY(?) DAY)) as stat_date,
                    'weekly' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    AVG(visit_duration) as avg_duration
                  FROM visit_logs 
                  WHERE YEARWEEK(created_at, 1) = YEARWEEK(?, 1)
                  ON DUPLICATE KEY UPDATE
                    total_visits = VALUES(total_visits),
                    unique_visitors = VALUES(unique_visitors),
                    page_views = VALUES(page_views),
                    avg_duration = VALUES(avg_duration),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date, $date, $date]);
    }

    public function generateMonthlyStats($date = null) {
        if (!$date) $date = date('Y-m-d');

        $query = "INSERT INTO visit_stats (stat_date, stat_type, total_visits, unique_visitors, page_views, avg_duration)
                  SELECT 
                    DATE(DATE_FORMAT(?, '%Y-%m-01')) as stat_date,
                    'monthly' as stat_type,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    COUNT(*) as page_views,
                    AVG(visit_duration) as avg_duration
                  FROM visit_logs 
                  WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
                  ON DUPLICATE KEY UPDATE
                    total_visits = VALUES(total_visits),
                    unique_visitors = VALUES(unique_visitors),
                    page_views = VALUES(page_views),
                    avg_duration = VALUES(avg_duration),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$date, $date]);
    }
}
?>