<?php

use config\Database;

session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once '../config/database.php';
include_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 통계 데이터 가져오기
$total_stations = $db->query("SELECT COUNT(*) FROM charging_stations")->fetchColumn();
$user_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();
$active_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE user_id = " . $_SESSION['user_id'] . " AND status = 'active'")->fetchColumn();
?>

    <main class="dashboard">
        <div class="container">
            <h1>대시보드</h1>
            <p>안녕하세요, <?php echo $_SESSION['user_name']; ?>님!</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_stations; ?></h3>
                    <p>총 충전소</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $user_bookings; ?></h3>
                    <p>내 예약</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $active_bookings; ?></h3>
                    <p>활성 예약</p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="stations.php" class="btn btn-primary">충전소 찾기</a>
                <a href="bookings.php" class="btn btn-secondary">예약 관리</a>
            </div>
        </div>
    </main>

<?php include_once '../includes/footer.php'; ?>