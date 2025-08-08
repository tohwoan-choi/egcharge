<?php
class AgentManager
{
    private $pdo;
    private $agentId;

    public function __construct($pdo, $agentId)
    {
        $this->pdo = $pdo;
        $this->agentId = $agentId;
    }

    private function getLocalIP()
    {
        // macOS 환경 감지
        if (PHP_OS === 'Darwin') {
            // macOS에서 활성 인터페이스의 IP 가져오기
            $output = shell_exec("route -n get default 2>/dev/null | grep 'interface:' | awk '{print $2}'");
            if ($output) {
                $interface = trim($output);
                $output = shell_exec("ifconfig $interface 2>/dev/null | grep 'inet ' | awk '{print $2}'");
                if ($output && filter_var(trim($output), FILTER_VALIDATE_IP)) {
                    return trim($output);
                }
            }

            // 다른 방법으로 macOS IP 가져오기
            $output = shell_exec("ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -1");
            if ($output && filter_var(trim($output), FILTER_VALIDATE_IP)) {
                return trim($output);
            }
        }

        // Linux/Unix 환경에서 로컬 IP 가져오기
        $output = shell_exec("hostname -I 2>/dev/null | awk '{print $1}'");
        if ($output && filter_var(trim($output), FILTER_VALIDATE_IP)) {
            return trim($output);
        }

        // 다른 방법으로 시도 (Linux)
        $output = shell_exec("ip route get 8.8.8.8 2>/dev/null | awk '{print $7; exit}'");
        if ($output && filter_var(trim($output), FILTER_VALIDATE_IP)) {
            return trim($output);
        }

        // Windows 환경 (PHP가 Windows에서 실행되는 경우)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("ipconfig | findstr /R /C:\"IPv4.*:\" | findstr /V \"127.0.0.1\"");
            if ($output && preg_match('/(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function insertJob($job = null, $jobStatus = null, $jobMessage = null)
    {
        $sql = "INSERT INTO egcharge_agent_job 
                (agent_id, agent_ip, job, job_execute_time, job_status, job_message) 
                VALUES (?, ?, ?, NOW(), ?, ?)";
        $agentIp = $this->getLocalIP() ?? '0.0.0.0';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$this->agentId, $agentIp, $job, $jobStatus, mb_substr($jobMessage, 0, 150)]);
    }
}

?>