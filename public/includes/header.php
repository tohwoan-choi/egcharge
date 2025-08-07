<?php
// 세션이 시작되지 않았다면 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 현재 페이지 확인을 위한 경로 설정
$current_page = basename($_SERVER['PHP_SELF']);
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
?>
  <!DOCTYPE html>
  <html lang="ko">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Electric Vehicle Charging Station Management System">
    <meta property="og:type" content="website">
    <meta property="og:title" content="EGCharge - 전기차 충전소 관리">
    <meta property="og:description" content="Electric Vehicle Charging Station Management System">
    <meta property="og:image" content="https://egcharge.com/assets/img/apple-touch-icon.png">
    <meta property="og:url" content="https://egcharge.com">
    <title><?php echo isset($page_title) ? $page_title . ' - EGCharge' : 'EGCharge - 전기차 충전소 관리'; ?></title>
    <meta name="description" content="전기차 충전소 예약 및 관리 시스템">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png">
  </head>
<body>
  <header>
    <nav class="container">
      <div class="logo">
        <a href="<?php echo $base_path; ?>index.php">
          <span class="logo-icon">⚡</span>
          EGCharge
        </a>
      </div>

      <ul class="nav-links">
          <?php if(isset($_SESSION['user_id'])): ?>
            <!-- 로그인된 사용자 메뉴 -->
            <li><a href="<?php echo $base_path; ?>pages/dashboard.php"
                   class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                대시보드
              </a></li>

            <li><a href="<?php echo $base_path; ?>pages/stations.php"
                   class="<?php echo ($current_page == 'stations.php') ? 'active' : ''; ?>">
                충전소 찾기
              </a></li>

            <li><a href="<?php echo $base_path; ?>pages/bookings.php"
                   class="<?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
                예약 관리
              </a></li>

            <li class="user-menu">
              <a href="#" class="user-toggle">
                <span class="user-icon">👤</span>
                  <?php echo htmlspecialchars($_SESSION['user_name']); ?>
              </a>
              <ul class="dropdown-menu">
                <li><a href="<?php echo $base_path; ?>pages/profile.php">프로필</a></li>
                <li><hr></li>
                <li><a href="<?php echo $base_path; ?>logout.php">로그아웃</a></li>
              </ul>
            </li>

          <?php else: ?>
            <!-- 비로그인 사용자 메뉴 -->
            <li><a href="<?php echo $base_path; ?>index.php"
                   class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                홈
              </a></li>

            <li><a href="<?php echo $base_path; ?>pages/stations.php"
                   class="<?php echo ($current_page == 'stations.php') ? 'active' : ''; ?>">
                충전소 찾기
              </a></li>

            <li><a href="<?php echo $base_path; ?>login.php" class="btn btn-outline">
                로그인
              </a></li>

            <li><a href="<?php echo $base_path; ?>register.php" class="btn btn-primary">
                회원가입
              </a></li>
          <?php endif; ?>
      </ul>

      <!-- 모바일 메뉴 토글 -->
      <div class="mobile-menu-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </nav>

    <!-- 모바일 메뉴 -->
    <div class="mobile-menu">
      <ul>
          <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="<?php echo $base_path; ?>pages/dashboard.php">대시보드</a></li>
            <li><a href="<?php echo $base_path; ?>pages/stations.php">충전소 찾기</a></li>
            <li><a href="<?php echo $base_path; ?>pages/bookings.php">예약 관리</a></li>
            <li><a href="<?php echo $base_path; ?>pages/profile.php">프로필</a></li>
            <li><a href="<?php echo $base_path; ?>logout.php">로그아웃</a></li>
          <?php else: ?>
            <li><a href="<?php echo $base_path; ?>index.php">홈</a></li>
            <li><a href="<?php echo $base_path; ?>pages/stations.php">충전소 찾기</a></li>
            <li><a href="<?php echo $base_path; ?>login.php">로그인</a></li>
            <li><a href="<?php echo $base_path; ?>register.php">회원가입</a></li>
          <?php endif; ?>
      </ul>
    </div>
  </header>

  <!-- 알림 메시지 표시 -->
<?php if(isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success">
      <?php
      echo htmlspecialchars($_SESSION['success_message']);
      unset($_SESSION['success_message']);
      ?>
  </div>
<?php endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
  <div class="alert alert-error">
      <?php
      echo htmlspecialchars($_SESSION['error_message']);
      unset($_SESSION['error_message']);
      ?>
  </div>
<?php endif; ?>