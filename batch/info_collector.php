<?php
require_once '../config/config.php';
require_once '../config/database.php';

class InfoCollector
{
    private $pdo;
    private $exTernalApiUrl;
    private $xApiKey;
    // 충전기 타입 코드 매핑
    private $chargeTypeMap = [
        '1' => '완속',
        '2' => '급속'
    ];

    // 충전기 상태 코드 매핑
    private $chargeStatusMap = [
        '1' => '충전가능',
        '2' => '충전중',
        '3' => '고장/점검',
        '4' => '통신장애',
        '5' => '통신미연결',
        '9' => '운영중지'
    ];

    // 커넥터 타입 코드 매핑
    private $connectorTypeMap = [
        '03' => 'AC완속',
        '07' => 'DC차데모',
        '10' => 'DC콤보'
    ];

    public function __construct($pdo, $EXTERNALAPIURL = '', $XAPIKEY = '')
    {
        $this->pdo = $pdo;
        $this->exTernalApiUrl = $EXTERNALAPIURL;
        $this->xApiKey = $XAPIKEY;
    }

    public function fetchInfoData($url, $xApiKey = '')
    {
        if (empty($url)) {
            throw new Exception("URL이 비어 있습니다.");
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("유효하지 않은 URL입니다: $url");
        }
        // cURL 초기화 및 설정
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $headers = [
            'Domain-Id: www',  // 핵심: Domain이 아니라 Domain-Id 사용!
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: ko-KR,ko;q=0.9,en;q=0.8',
            'Referer: https://www.investing.com/',
            'Origin: https://www.investing.com'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//        echo $url;
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);

//        echo "=== 디버그 정보 ===\n";
//        echo "HTTP Code: $httpCode\n";
//        echo "Content-Type: $contentType\n";
//        echo "Error: $error\n";
//        echo "Response Length: " . strlen($response) . "\n";
//        echo "Response (first 500 chars): " . substr($response, 0, 500) . "\n\n";
//        echo $response;
        if ($httpCode !== 200 || !$response) {
            throw new Exception(" charge Info 데이터 가져오기 실패");
        }
        return $response;
    }

    // JSON 데이터 정리 함수
    function cleanJsonData($jsonString)
    {
        // 1. 앞뒤 공백 제거
        $cleaned = trim($jsonString);

        // 2. 맨 끝의 % 제거
        $cleaned = rtrim($cleaned, '%');

        // 3. 이스케이프된 JSON 문자열 처리
        // API가 "{"key":"value"}" 형태로 반환하는 경우
        if (substr($cleaned, 0, 1) === '"' && substr($cleaned, -1) === '"') {
            // 앞뒤 따옴표 제거
            $cleaned = substr($cleaned, 1, -1);
            // 이스케이프 해제
            $cleaned = stripslashes($cleaned);
            echo "이스케이프된 JSON 문자열 처리 완료\n";
        }

        return $cleaned;
    }

    /**
     * 충전소 데이터 MergeInto (단일 레코드)
     *
     * @param string $offerCd 충전소 코드
     * @param string $csId 충전소 ID
     * @param string $csNm 충전소 이름
     * @param string $addr 충전소 주소
     * @param float|null $lat 위도
     * @param float|null $lng 경도
     * @param string $cpId 충전기 ID
     * @param int|null $charegTp 충전기 타입 코드
     * @param string|null $charegTpNm 충전기 타입명
     * @param string $cpNm 충전기 이름
     * @param int|null $cpStat 충전기 상태 코드
     * @param string|null $cpStatNm 충전기 상태명
     * @param int|null $cpTp 충전기 커넥터 타입 코드
     * @param string|null $cpTpNm 충전기 커넥터 타입명
     * @param string|null $statUpdatetime 충전기 상태 갱신 시각
     * @return bool 성공 여부
     */
    public function mergeChargingStation(
        $offerCd,
        $csId,
        $csNm,
        $addr,
        $lat,
        $lng,
        $cpId,
        $charegTp,
        $charegTpNm,
        $cpNm,
        $cpStat,
        $cpStatNm,
        $cpTp,
        $cpTpNm,
        $statUpdatetime
    )
    {
        $sql = "INSERT INTO eg_charging_stations (
                    offer_cd, csId, csNm, addr, lat, lngi, cpId,
                    charegTp, charegTpNm, cpNm, cpStat, cpStatNm,
                    cpTp, cpTpNm, statUpdatetime
                ) VALUES (
                    :offer_cd, :csId, :csNm, :addr, :lat, :lngi, :cpId,
                    :charegTp, :charegTpNm, :cpNm, :cpStat, :cpStatNm,
                    :cpTp, :cpTpNm, :statUpdatetime
                )
                ON DUPLICATE KEY UPDATE
                    csNm = VALUES(csNm),
                    addr = VALUES(addr),
                    lat = VALUES(lat),
                    lngi = VALUES(lngi),
                    charegTp = VALUES(charegTp),
                    charegTpNm = VALUES(charegTpNm),
                    cpNm = VALUES(cpNm),
                    cpStat = VALUES(cpStat),
                    cpStatNm = VALUES(cpStatNm),
                    cpTp = VALUES(cpTp),
                    cpTpNm = VALUES(cpTpNm),
                    statUpdatetime = VALUES(statUpdatetime),
                    updated_at = CURRENT_TIMESTAMP";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':offer_cd' => $offerCd,
                ':csId' => $csId,
                ':csNm' => $csNm,
                ':addr' => $addr,
                ':lat' => $lat,
                ':lngi' => $lng,
                ':cpId' => $cpId,
                ':charegTp' => $charegTp,
                ':charegTpNm' => $charegTpNm,
                ':cpNm' => $cpNm,
                ':cpStat' => $cpStat,
                ':cpStatNm' => $cpStatNm,
                ':cpTp' => $cpTp,
                ':cpTpNm' => $cpTpNm,
                ':statUpdatetime' => $statUpdatetime
            ]);
        } catch (PDOException $e) {
            error_log("충전소 데이터 저장 오류: " . $e->getMessage());
            return false;
        }
    }

    public function collectInfo()
    {
        try {
            $url = $this->exTernalApiUrl . '?serviceKey=' . $this->xApiKey . '&pageNo=1&numOfRows=5000';
            echo 'Kepco API URL: ' . $url . "\n";
            $data = $this->fetchInfoData($url, '', false, 'xml');
            $kepcoApiResponse = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON 파싱 오류: " . json_last_error_msg());
            }

            // 응답 구조 확인
            if (empty($kepcoApiResponse['response']['header']['resultCode']) ||
                $kepcoApiResponse['response']['header']['resultCode'] !== '00') {
                throw new Exception("API 오류: " . ($kepcoApiResponse['response']['header']['resultMsg'] ?? '알 수 없는 오류'));
            }

            $items = $kepcoApiResponse['response']['body']['items']['item'] ?? [];

            if (empty($items)) {
                throw new Exception("수집된 데이터가 비어 있습니다.");
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            foreach ($items as $index => $item) {
                try {
                    // 데이터 정제 및 타입 변환
                    $chargeTp = (int)($item['chargeTp'] ?? 0);
                    $cpStat = (int)($item['cpStat'] ?? 0);
                    $cpTp = (string)($item['cpTp'] ?? '');

                    $result = $this->mergeChargingStation(
                        'KEPCO',                                           // offer_cd (고정값)
                        (string)($item['csId'] ?? ''),                    // csId
                        (string)($item['csNm'] ?? ''),                    // csNm
                        (string)($item['addr'] ?? ''),                    // addr
                        (float)($item['lat'] ?? 0),                       // lat
                        (float)($item['longi'] ?? 0),                     // lngi (longi로 수정)
                        (string)($item['cpId'] ?? ''),                    // cpId
                        $chargeTp,                                        // charegTp
                        $this->chargeTypeMap[$chargeTp] ?? null,          // charegTpNm
                        (string)($item['cpNm'] ?? ''),                    // cpNm
                        $cpStat,                                          // cpStat
                        $this->chargeStatusMap[$cpStat] ?? null,          // cpStatNm
                        (int)$cpTp,                                       // cpTp
                        $this->connectorTypeMap[$cpTp] ?? null,           // cpTpNm
                        (string)($item['statUpdateDatetime'] ?? '')       // statUpdatetime
                    );

                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Index {$index}: 데이터 저장 실패";
                    }

                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Index {$index}: " . $e->getMessage();
                }
            }

            echo "정보 수집 및 저장 완료. 성공: {$successCount}, 실패: {$errorCount}\n";
        } catch (Exception $e) {
            echo "정보 수집 중 오류 발생: " . $e->getMessage() . "\n";
            throw new Exception("정보 수집 중 오류 발생: " . $e->getMessage());
        }
    }
}

?>
