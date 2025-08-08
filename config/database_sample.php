<?php
/**
 * 데이터베이스 설정 템플릿
 * 이 파일을 database.php로 복사하고 실제 값으로 수정하세요
 */
class Database {
    private $host = 'localhost';
    private $port = '3306';                    // MySQL 기본 포트
    private $db_name = 'egcharge';
    private $username = 'your_username';
    private $password = 'your_password';
    private $charset = 'utf8mb4';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // 포트 정보를 포함한 DSN 구성
            $dsn = "mysql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->db_name .
                ";charset=" . $this->charset;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",  // UTF8MB4 명시적 설정
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            // 에러 로깅 추가
            error_log("데이터베이스 연결 오류: " . $exception->getMessage());

            // 개발 환경에서만 에러 출력, 운영 환경에서는 일반적인 메시지
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                echo "데이터베이스 연결 오류: " . $exception->getMessage();
            } else {
                echo "데이터베이스 연결에 문제가 발생했습니다. 관리자에게 문의하세요.";
            }
        }

        return $this->conn;
    }

    /**
     * 연결 상태 확인
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT 1");
                return $stmt !== false;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 데이터베이스 설정 정보 반환 (비밀번호 제외)
     */
    public function getConnectionInfo() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->db_name,
            'username' => $this->username,
            'charset' => $this->charset
        ];
    }
}

// 환경 설정 예제 (config.php 또는 별도 파일에서 정의)
// define('ENVIRONMENT', 'development'); // 또는 'production'
?>