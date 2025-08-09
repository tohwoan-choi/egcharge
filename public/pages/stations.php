<?php
session_start();
$page_title = "충전소 찾기";
include_once '../includes/header.php';
include_once '../includes/stations_helper.php';

$database = new Database();
$db = $database->getConnection();

// API에서 충전소 데이터 가져오기
$search = $_GET['search'] ?? '';
$charge_type = $_GET['charge_type'] ?? '';
$connector_type = $_GET['connector_type'] ?? '';
$status = $_GET['status'] ?? '';

// API 호출 파라미터 구성
$api_params = [];
if ($search) $api_params['search'] = $search;
if ($charge_type) $api_params['charge_type'] = $charge_type;
if ($connector_type) $api_params['connector_type'] = $connector_type;
if ($status) $api_params['status'] = $status;



$stations = [];
$stationData = fetchStationsData($api_params);
if ($stationData['success']) {
    $stations = $stationData['stations'];
} else {
    $stations = [];
}

// 충전소별로 그룹화
$stationGroups = [];
foreach ($stations as $station) {
    $key = $station['csId'];
    if (!isset($stationGroups[$key])) {
        $stationGroups[$key] = [
            'csNm' => $station['csNm'],
            'addr' => $station['addr'],
            'lat' => $station['lat'],
            'lngi' => $station['lngi'],
            'statUpdatetime' => $station['statUpdatetime'],
            'chargers' => []
        ];
    }
    $stationGroups[$key]['chargers'][] = $station;
}

// 각 충전소의 좋아요/싫어요/한줄평 데이터 가져오기
function getStationReviews($csId) {
    global $db;
    try {
        // 좋아요/싫어요 카운트
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN reaction_type = 'like' THEN 1 ELSE 0 END) as likes,
                SUM(CASE WHEN reaction_type = 'dislike' THEN 1 ELSE 0 END) as dislikes
            FROM station_reactions 
            WHERE station_id = ?
        ");
        $stmt->execute([$csId]);
        $reactions = $stmt->fetch(PDO::FETCH_ASSOC);

        // 한줄평 카운트
        $stmt = $db->prepare("SELECT COUNT(*) as reviews_count FROM station_reviews WHERE station_id = ?");
        $stmt->execute([$csId]);
        $reviews = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'likes' => intval($reactions['likes'] ?? 0),
            'dislikes' => intval($reactions['dislikes'] ?? 0),
            'reviews_count' => intval($reviews['reviews_count'] ?? 0)
        ];
    } catch (Exception $e) {
        return [
            'likes' => 0,
            'dislikes' => 0,
            'reviews_count' => 0
        ];
    }
}


function getStatusClass($status) {
    switch(intval($status)) {
        case 1: return 'status-available';
        case 2: return 'status-charging';
        case 3: return 'status-broken';
        case 4:
        case 5: return 'status-offline';
        default: return '';
    }
}
?>

  <main class="stations-page">
    <div class="container">
      <div class="page-header">
        <h1>충전소 찾기</h1>
        <p>원하는 충전소를 검색하고 정보를 확인하세요</p>
      </div>

      <div class="search-section">
        <form method="GET" class="search-form">
          <input type="text" name="search" id="station-search"
                 placeholder="충전소명 또는 주소로 검색"
                 value="<?= htmlspecialchars($search) ?>" class="search-input">
          <button type="submit" class="btn btn-primary">검색</button>
        </form>

        <div class="filters">
          <form method="GET" id="filter-form">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

            <select name="charge_type" id="charge-type-filter" onchange="document.getElementById('filter-form').submit()">
              <option value="">모든 충전방식</option>
              <option value="1" <?= $charge_type == '1' ? 'selected' : '' ?>>완속</option>
              <option value="2" <?= $charge_type == '2' ? 'selected' : '' ?>>급속</option>
            </select>

            <select name="connector_type" id="connector-filter" onchange="document.getElementById('filter-form').submit()">
              <option value="">모든 커넥터</option>
              <option value="1" <?= $connector_type == '1' ? 'selected' : '' ?>>B타입(5핀)</option>
              <option value="2" <?= $connector_type == '2' ? 'selected' : '' ?>>C타입(5핀)</option>
              <option value="3" <?= $connector_type == '3' ? 'selected' : '' ?>>BC타입(5핀)</option>
              <option value="4" <?= $connector_type == '4' ? 'selected' : '' ?>>BC타입(7핀)</option>
              <option value="5" <?= $connector_type == '5' ? 'selected' : '' ?>>DC차데모</option>
              <option value="6" <?= $connector_type == '6' ? 'selected' : '' ?>>AC3상</option>
              <option value="7" <?= $connector_type == '7' ? 'selected' : '' ?>>DC콤보</option>
              <option value="8" <?= $connector_type == '8' ? 'selected' : '' ?>>DC차데모+DC콤보</option>
            </select>

            <select name="status" id="status-filter" onchange="document.getElementById('filter-form').submit()">
              <option value="">모든 상태</option>
              <option value="1" <?= $status == '1' ? 'selected' : '' ?>>충전가능</option>
              <option value="2" <?= $status == '2' ? 'selected' : '' ?>>충전중</option>
              <option value="3" <?= $status == '3' ? 'selected' : '' ?>>고장/점검</option>
            </select>
          </form>
        </div>
      </div>

      <div class="stations-grid">
          <?php if (empty($stationGroups)): ?>
            <p class="empty-message">검색 결과가 없습니다.</p>
          <?php else: ?>
              <?php foreach ($stationGroups as $csId => $stationGroup): ?>
                  <?php
                  $availableChargers = array_filter($stationGroup['chargers'], function($c) { return $c['cpStat'] == 1; });
                  $chargingChargers = array_filter($stationGroup['chargers'], function($c) { return $c['cpStat'] == 2; });
                  $brokenChargers = array_filter($stationGroup['chargers'], function($c) { return $c['cpStat'] == 3; });
                  $reviews = getStationReviews($csId);
                  ?>

              <div class="station-card">
                <div class="station-header">
                  <div class="station-info">
                    <h3><?= htmlspecialchars($stationGroup['csNm']) ?></h3>
                    <p class="station-address"><?= htmlspecialchars($stationGroup['addr']) ?></p>
                  </div>
                  <div class="station-summary">
                    <small>
                      충전가능: <?= count($availableChargers) ?> |
                      충전중: <?= count($chargingChargers) ?> |
                      고장: <?= count($brokenChargers) ?>
                    </small>
                  </div>
                </div>

                <div class="station-reactions">
                  <div class="reaction-buttons">
                    <button class="reaction-btn like-btn" onclick="toggleLike('<?= $csId ?>')" data-station="<?= $csId ?>">
                      👍 <span class="like-count"><?= $reviews['likes'] ?></span>
                    </button>
                    <button class="reaction-btn dislike-btn" onclick="toggleDislike('<?= $csId ?>')" data-station="<?= $csId ?>">
                      👎 <span class="dislike-count"><?= $reviews['dislikes'] ?></span>
                    </button>
                    <button class="reaction-btn review-btn" onclick="showReviewModal('<?= $csId ?>', '<?= htmlspecialchars($stationGroup['csNm']) ?>')">
                      💬 <span class="review-count"><?= $reviews['reviews_count'] ?></span>
                    </button>
                  </div>
                  <p class="update-time">업데이트: <?= htmlspecialchars($stationGroup['statUpdatetime']) ?></p>
                </div>

                <div class="chargers-list">
                    <?php foreach ($stationGroup['chargers'] as $charger): ?>
                      <div class="charger-details">
                        <h4><?= htmlspecialchars($charger['cpNm']) ?></h4>
                        <div class="detail-tags">
                          <span><?= htmlspecialchars($charger['charegTpNm']) ?></span>
                          <span><?= htmlspecialchars($charger['cpTpNm']) ?></span>
                          <span class="<?= getStatusClass($charger['cpStat']) ?>">
                      <?= htmlspecialchars($charger['cpStatNm']) ?>
                    </span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                </div>
              </div>
              <?php endforeach; ?>
          <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- 한줄평 모달 -->
  <div id="review-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>한줄평</h3>
        <span class="close" onclick="closeReviewModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div id="selected-station-name" class="station-name-display"></div>

        <!-- 기존 한줄평 목록 -->
        <div class="reviews-section">
          <h4>💬 이용자 한줄평</h4>
          <div id="reviews-list" class="reviews-list"></div>
          <button id="load-more-reviews" class="load-more-btn" style="display: none;">더보기</button>
        </div>

          <?php if (isset($_SESSION['user_id'])): ?>
            <!-- 한줄평 작성 폼 -->
            <div class="write-review-section">
              <h4>한줄평 작성</h4>
              <form id="review-form">
                <input type="hidden" id="review-station-id" name="station_id">
                <div class="form-group">
                  <label for="review-content">한줄평을 작성해주세요</label>
                  <textarea id="review-content" name="content" maxlength="100"
                            placeholder="충전소 이용 경험을 짧게 공유해주세요 (최대 100자)" required></textarea>
                  <div class="char-count">0/100</div>
                </div>
                <div class="modal-actions">
                  <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">취소</button>
                  <button type="submit" class="btn btn-primary">작성 완료</button>
                </div>
              </form>
            </div>
          <?php else: ?>
            <div class="login-required">
              <p>한줄평 작성을 위해 로그인이 필요합니다.</p>
              <a href="../login.php" class="btn btn-primary">로그인</a>
            </div>
          <?php endif; ?>
      </div>
    </div>
  </div>

  <link rel="stylesheet" href="../assets/css/stations.css">
  <script>
    // 로그인 상태를 JavaScript에서 사용할 수 있도록 설정
    window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
  </script>
  <script src="../assets/js/stations.js"></script>

<?php include_once '../includes/footer.php'; ?>