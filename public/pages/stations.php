<?php
session_start();
$page_title = "충전소 찾기";
include_once '../includes/header.php';

// 검색 파라미터
$search = $_GET['search'] ?? '';
$charge_type = $_GET['charge_type'] ?? '';
$connector_type = $_GET['connector_type'] ?? '';
$status = $_GET['status'] ?? '';

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
      <!-- 하나의 통합된 폼 -->
      <form method="GET" class="search-form" id="search-filter-form">
        <!-- 검색 입력 -->
        <div class="search-input-group">
          <input type="text" name="search" id="station-search"
                 placeholder="충전소명 또는 주소로 검색"
                 value="<?= htmlspecialchars($search) ?>" class="search-input">
          <button type="submit" class="btn btn-primary">검색</button>
        </div>

        <!-- 필터들 -->
        <div class="filters">
          <select name="charge_type" id="charge-type-filter">
            <option value="">모든 충전방식</option>
            <option value="1" <?= $charge_type == '1' ? 'selected' : '' ?>>완속</option>
            <option value="2" <?= $charge_type == '2' ? 'selected' : '' ?>>급속</option>
          </select>

          <select name="connector_type" id="connector-filter">
            <option value="">모든 커넥터</option>
            <option value="1" <?= $connector_type == '1' ? 'selected' : '' ?>>B타입(5핀)</option>
            <option value="2" <?= $connector_type == '2' ? 'selected' : '' ?>>C타입(5핀)</option>
            <option value="3" <?= $connector_type == '3' ? 'selected' : '' ?>>BC타입(5핀)</option>
            <option value="4" <?= $connector_type == '4' ? 'selected' : '' ?>>BC타입(7핀)</option>
            <option value="5" <?= $connector_type == '5' ? 'selected' : '' ?>>DC차데모</option>
            <option value="6" <?= $connector_type == '6' ? 'selected' : '' ?>>AC3상</option>
            <option value="7" <?= $connector_type == '7' ? 'selected' : '' ?>>DC콤보</option>
            <option value="8" <?= $connector_type == '8' ? 'selected' : '' ?>>DC차데모+DC콤보</option>
            <!-- 나머지 옵션들... -->
          </select>

          <select name="status" id="status-filter">
            <option value="">모든 상태</option>
            <option value="1" <?= $status == '1' ? 'selected' : '' ?>>충전가능</option>
            <option value="2" <?= $status == '2' ? 'selected' : '' ?>>충전중</option>
            <option value="3" <?= $status == '3' ? 'selected' : '' ?>>고장/점검</option>
          </select>

          <button type="submit" class="btn btn-secondary" style="display: none">필터 적용</button>
        </div>
      </form>
    </div>

    <div id="loading" class="loading-message" style="display: none;">
      <p>충전소 정보를 불러오는 중...</p>
    </div>

    <div id="stations-grid" class="stations-grid">
      <!-- JavaScript로 동적 로딩 -->
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

      <div class="reviews-section">
        <h4>💬 이용자 한줄평</h4>
        <div id="reviews-list" class="reviews-list"></div>
        <button id="load-more-reviews" class="load-more-btn" style="display: none;">더보기</button>
      </div>

        <?php if (isset($_SESSION['user_id'])): ?>
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
          <input type="hidden" id="review-station-id" name="station_id">
          <input type="hidden" id="review-content" name="content" maxlength="100">
          <div class="char-count" style="display: none">0/100</div>
          <div class="login-required">
            <p>한줄평 작성을 위해 로그인이 필요합니다.</p>
            <a href="../login.php" class="btn btn-primary">로그인</a>
          </div>
        <?php endif; ?>
    </div>
  </div>
</div>

<?php
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
$jsPath = __DIR__ . '/../assets/css/stations.js';
$jsFileName = basename($jsPath);
$jsVersion = file_exists($jsPath) ? filemtime($jsPath) : time();

$cssPath = __DIR__ . '/../assets/css/stations.css';
$cssFileName = basename($cssPath);
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/<?=$cssFileName?>?v=<?=$cssVersion?>">

<script>
  window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

  // 페이지 로드시 초기 검색 파라미터로 데이터 로드
  const initialParams = {
    search: '<?= htmlspecialchars($search) ?>',
    charge_type: '<?= htmlspecialchars($charge_type) ?>',
    connector_type: '<?= htmlspecialchars($connector_type) ?>',
    status: '<?= htmlspecialchars($status) ?>'
  };
  // URL 파라미터도 함께 업데이트
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.toString()) {
    // URL에 파라미터가 있으면 해당 파라미터로 초기화
    const params = {};
    for (const [key, value] of urlParams) {
      if (value) params[key] = value;
    }
    window.initialParams = params;
  } else {
    window.initialParams = initialParams;
  }
</script>
<script src="<?php echo $base_path; ?>assets/js/<?=$jsFileName?>?v=<?=$jsVersion?>"></script>

<?php include_once '../includes/footer.php'; ?>
