<?php
/**
 * 데이터베이스 설정 템플릿
 * 이 파일을 database.php로 복사하고 실제 값으로 수정하세요
 */
class Database {
    private $host = 'localhost';
    private $db_name = 'egcharge';
    private $username = 'your_username';
    private $password = 'your_password';
    private $charset = 'utf8mb4';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host .
                ";dbname=" . $this->db_name .
                ";charset=" . $this->charset;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            echo "데이터베이스 연결 오류: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>