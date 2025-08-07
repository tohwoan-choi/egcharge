<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once '../../config/database.php';
$page_title = "대시보드";
include_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 통계 데이터 가져오기
$total_stations = $db->query("SELECT COUNT(*) FROM charging_stations")->fetchColumn();
$user_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE user_id = " . $_SESSION['user_id'])->fetchColumn();
$active_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE user_id = " . $_SESSION['user_id'] . " AND status = 'active'")->fetchColumn();

// 최근 예약 내역
$recent_bookings = $db->prepare("
    SELECT b.*, s.name as station_name, s.address 
    FROM bookings b 
    JOIN charging_stations s ON b.station_id = s.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$recent_bookings->execute([$_SESSION['user_id']]);
?>

  <main class="dashboard">
    <div class="container">
      <div class="page-header">
        <h1>대시보드</h1>
        <p>안녕하세요, <?php echo htmlspecialchars($_SESSION['user_name']); ?>님!</p>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🔌</div>
          <h3><?php echo $total_stations; ?></h3>
          <p>총 충전소</p>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📋</div>
          <h3><?php echo $user_bookings; ?></h3>
          <p>내 예약</p>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⚡</div>
          <h3><?php echo $active_bookings; ?></h3>
          <p>활성 예약</p>
        </div>
      </div>

      <div class="dashboard-content">
        <div class="quick-actions">
          <h2>빠른 실행</h2>
          <div class="action-buttons">
            <a href="stations.php" class="btn btn-primary">충전소 찾기</a>
            <a href="bookings.php" class="btn btn-secondary">예약 관리</a>
            <a href="profile.php" class="btn btn-outline">프로필 관리</a>
          </div>
        </div>

        <div class="recent-bookings">
          <h2>최근 예약 내역</h2>
            <?php if($recent_bookings->rowCount() > 0): ?>
              <div class="booking-list">
                  <?php while($booking = $recent_bookings->fetch()): ?>
                    <div class="booking-item">
                      <div class="booking-info">
                        <h4><?php echo htmlspecialchars($booking['station_name']); ?></h4>
                        <p><?php echo htmlspecialchars($booking['address']); ?></p>
                        <span class="booking-date"><?php echo date('Y-m-d H:i', strtotime($booking['start_time'])); ?></span>
                      </div>
                      <div class="booking-status">
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php
                                        switch($booking['status']) {
                                            case 'active': echo '활성'; break;
                                            case 'completed': echo '완료'; break;
                                            case 'cancelled': echo '취소'; break;
                                        }
                                        ?>
                                    </span>
                      </div>
                    </div>
                  <?php endwhile; ?>
              </div>
              <a href="bookings.php" class="btn btn-link">모든 예약 보기</a>
            <?php else: ?>
              <p class="empty-message">예약 내역이 없습니다.</p>
              <a href="stations.php" class="btn btn-primary">첫 예약하기</a>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

<?php include_once '../includes/footer.php'; ?>