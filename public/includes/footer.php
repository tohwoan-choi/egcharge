<?php
// 현재 페이지 확인을 위한 경로 설정
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

$jsPath = __DIR__ . '/../assets/css/main.js';
$jsFileName = basename($jsPath);
$jsVersion = file_exists($jsPath) ? filemtime($jsPath) : time();


?>

<footer>
    <div class="container">
        <div class="footer-content">
            <!-- 회사 정보 -->
            <div class="footer-section company-info">
                <div class="footer-logo">
                    <span class="logo-icon">⚡</span>
                    <h3>EGCharge</h3>
                </div>
                <p>전기차 충전소 예약 및 관리 시스템</p>
                <p>더 나은 전기차 충전 경험을 제공합니다.</p>
            </div>

            <!-- 빠른 링크 -->
            <div class="footer-section quick-links">
                <h4>빠른 링크</h4>
                <ul>
                    <li><a href="<?php echo $base_path; ?>index.php">홈</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/stations.php">충전소 찾기</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo $base_path; ?>pages/dashboard.php">대시보드</a></li>
                        <li><a href="<?php echo $base_path; ?>pages/bookings.php">예약 관리</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_path; ?>login.php">로그인</a></li>
                        <li><a href="<?php echo $base_path; ?>register.php">회원가입</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 고객 지원 -->
            <div class="footer-section support">
                <h4>고객 지원</h4>
                <ul>
                    <li><a href="<?php echo $base_path; ?>pages/help.php">도움말</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/help.php#faq-section">자주 묻는 질문</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/help.php#contact-section">문의하기</a></li>
                </ul>
            </div>

            <!-- 연락처 정보 -->
            <div class="footer-section contact">
                <h4>연락처</h4>
                <div class="contact-info">
                    <p><span class="contact-icon">📞</span> by email</p>
                    <p><span class="contact-icon">✉️</span> woridori@gmail.com</p>
                    <p><span class="contact-icon">🏢</span> 서울시 양천구 오목로35길</p>
                    <p><span class="contact-icon">🕒</span> 평일 09:00-18:00</p>
                </div>

                <!-- 소셜 미디어 링크 -->
                <div class="social-links">
                    <a href="#" class="social-link" title="페이스북">📘</a>
                    <a href="#" class="social-link" title="인스타그램">📷</a>
                    <a href="#" class="social-link" title="유튜브">📺</a>
                </div>
            </div>
        </div>

        <!-- 하단 정보 -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> EGCharge. All rights reserved.</p>
                    <p>전기차 충전소 관리 시스템</p>
                </div>

                <div class="footer-stats">
                    <?php
                    // 간단한 통계 정보 표시
                    if(isset($_SESSION['user_id'])) {
                        try {
                            include_once $base_path . '../config/database.php';
                            $database = new Database();
                            $db = $database->getConnection();

                            $total_stations = $db->query("SELECT COUNT(*) FROM charging_stations WHERE status = 'active'")->fetchColumn();
                            $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

                            echo "<span>충전소 {$total_stations}개 | 회원 {$total_users}명</span>";
                        } catch(Exception $e) {
                            // 에러 시 무시
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 맨 위로 버튼 -->
    <button id="back-to-top" class="back-to-top" title="맨 위로">
        <span>⬆️</span>
    </button>
</footer>

<!-- JavaScript 파일 로드 -->
<script src="<?php echo $base_path; ?>assets/js/<?=$jsFileName?>?v=<?=$jsVersion?>"></script>

<script>
  // 맨 위로 버튼 기능
  document.addEventListener('DOMContentLoaded', function() {
    const backToTopBtn = document.getElementById('back-to-top');

    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('visible');
      } else {
        backToTopBtn.classList.remove('visible');
      }
    });

    backToTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // 모바일 메뉴 토글
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (mobileToggle && mobileMenu) {
      mobileToggle.addEventListener('click', function() {
        mobileMenu.classList.toggle('active');
        mobileToggle.classList.toggle('active');
      });
    }

    // 사용자 드롭다운 메뉴
    const userToggle = document.querySelector('.user-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (userToggle && dropdownMenu) {
      userToggle.addEventListener('click', function(e) {
        e.preventDefault();
        dropdownMenu.classList.toggle('active');
      });

      // 외부 클릭 시 드롭다운 닫기
      document.addEventListener('click', function(e) {
        if (!userToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
          dropdownMenu.classList.remove('active');
        }
      });
    }
  });
</script>
</body>
</html>