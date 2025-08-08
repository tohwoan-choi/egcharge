<?php
// 작업 디렉토리를 스크립트 위치로 변경
chdir(__DIR__);

// 설정 파일 경로 확인 및 로드
$configFiles = [
    __DIR__ . '/../config/database.php',
    '/var/www/egcharge/config/database.php',
    dirname(__DIR__, 2) . '/config/database.php',
    __DIR__ . '/../config/config.php',
    '/var/www/egcharge/config/config.php',
    dirname(__DIR__, 2) . '/config/config.php'
];

$configLoaded = false;
foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        require_once $configFile;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    die("설정 파일을 찾을 수 없습니다.\n");
}
require_once 'info_collector.php';
require_once 'agent_manager.php';

$database = new Database();
$db = $database->getConnection();

try {
    $jobName = 'chargeInfo Collector - Kepco';
    // $pdo와 $AGENT_ID가 정의되어 있는지 확인
    if (!isset($db)) {
        throw new Exception('데이터베이스 연결(\$pdo)이 정의되지 않았습니다.');
    }
    if (!isset($AGENT_ID)) {
        // 환경변수 또는 기본값으로 대체 가능
        $AGENT_ID = getenv('AGENT_ID') ?: 'NO_SET_AGENT_ID';
    }

    $agentManager = new AgentManager($db, $AGENT_ID);
    try {
        $collector = new InfoCollector($db,$KEPCO_API_URL, $PUBLIC_API_KEY_ENC);

        $collector->collectInfo();
        $agentManager->insertJob($jobName, 'success', 'Kepco 수집 작업이 성공적으로 완료되었습니다.');
    } catch (Exception $e) {
        echo "시스템 오류: " . $e->getMessage() . "\n";
        $agentManager->insertJob($jobName, 'fail', "시스템 오류: " . $e->getMessage() . "\n");
    }
} catch (Exception $e) {
    echo "시스템 오류: " . $e->getMessage() . "\n";
}

?>