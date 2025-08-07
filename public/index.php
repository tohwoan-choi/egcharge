<?php
session_start();
include_once 'includes/header.php';
?>

    <main class="hero">
        <div class="container">
            <h1>EGCharg</h1>
            <p>전기차 충전소 예약 및 관리 시스템</p>

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="pages/dashboard.php" class="btn btn-primary">대시보드</a>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-primary">로그인</a>
                    <a href="register.php" class="btn btn-secondary">회원가입</a>
                </div>
            <?php endif; ?>

            <div class="features">
                <div class="feature-card">
                    <h3>충전소 찾기</h3>
                    <p>주변 충전소를 쉽게 찾아보세요</p>
                </div>
                <div class="feature-card">
                    <h3>실시간 예약</h3>
                    <p>실시간으로 충전소를 예약할 수 있습니다</p>
                </div>
                <div class="feature-card">
                    <h3>사용량 관리</h3>
                    <p>충전 이력과 비용을 관리하세요</p>
                </div>
            </div>
        </div>
    </main>

<?php include_once 'includes/footer.php'; ?>