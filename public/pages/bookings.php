<?php
session_start();

echo "<script>alert('준비중입니다.'); window.location.href = '/';</script>";
exit();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "예약 관리";
include_once '../../config/database.php';
include_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 사용자의 예약 목록 조회
$bookings_query = "SELECT b.*, s.csNm as station_name, s.addr as address, 
                          s.cpNm as charger_name, s.charegTpNm, s.cpTpNm
                   FROM bookings b 
                   JOIN eg_charging_stations s ON b.station_offer_cd = s.offer_cd 
                      AND b.station_csId = s.csId AND b.station_cpId = s.cpId
                   WHERE b.user_id = ? 
                   ORDER BY b.created_at DESC";
$bookings_stmt = $db->prepare($bookings_query);
$bookings_stmt->execute([$_SESSION['user_id']]);
$bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

// 상태별 통계
$stats_query = "SELECT status, COUNT(*) as count 
                FROM bookings 
                WHERE user_id = ? 
                GROUP BY status";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = [];
while ($row = $stats_stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['status']] = $row['count'];
}
?>

  <main class="bookings-page">
    <div class="container">
      <div class="page-header">
        <h1>예약 관리</h1>
        <p>나의 충전소 예약 내역을 관리하세요</p>
      </div>

      <!-- 예약 통계 -->
      <div class="booking-stats">
        <div class="stat-item">
          <span class="stat-number"><?php echo $stats['active'] ?? 0; ?></span>
          <span class="stat-label">활성 예약</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo $stats['completed'] ?? 0; ?></span>
          <span class="stat-label">완료된 예약</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo $stats['cancelled'] ?? 0; ?></span>
          <span class="stat-label">취소된 예약</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo count($bookings); ?></span>
          <span class="stat-label">전체 예약</span>
        </div>
      </div>

      <!-- 필터 및 검색 -->
      <div class="booking-filters">
        <div class="filter-group">
          <label for="status-filter">상태별 필터:</label>
          <select id="status-filter">
            <option value="">전체</option>
            <option value="active">활성</option>
            <option value="completed">완료</option>
            <option value="cancelled">취소</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="date-filter">기간별 필터:</label>
          <select id="date-filter">
            <option value="">전체 기간</option>
            <option value="today">오늘</option>
            <option value="week">이번 주</option>
            <option value="month">이번 달</option>
            <option value="3months">최근 3개월</option>
          </select>
        </div>

        <div class="search-group">
          <input type="text" id="station-search" placeholder="충전소명 검색">
          <button type="button" class="btn btn-outline" onclick="searchBookings()">검색</button>
        </div>
      </div>

      <!-- 예약 목록 -->
      <div class="bookings-list">
          <?php if (count($bookings) > 0): ?>
              <?php foreach ($bookings as $booking): ?>
              <div class="booking-card" data-status="<?php echo $booking['status']; ?>"
                   data-date="<?php echo $booking['start_time']; ?>">
                <div class="booking-header">
                  <div class="booking-info">
                    <h3><?php echo htmlspecialchars($booking['station_name']); ?></h3>
                    <p class="booking-address"><?php echo htmlspecialchars($booking['address']); ?></p>
                    <p class="charger-info">
                      <strong>충전기:</strong> <?php echo htmlspecialchars($booking['charger_name']); ?>
                      (<?php echo htmlspecialchars($booking['charegTpNm']); ?>
                      - <?php echo htmlspecialchars($booking['cpTpNm']); ?>)
                    </p>
                  </div>
                  <div class="booking-status">
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php
                                    switch ($booking['status']) {
                                        case 'active':
                                            echo '활성';
                                            break;
                                        case 'completed':
                                            echo '완료';
                                            break;
                                        case 'cancelled':
                                            echo '취소';
                                            break;
                                    }
                                    ?>
                                </span>
                  </div>
                </div>

                <div class="booking-details">
                  <div class="detail-row">
                    <div class="detail-item">
                      <span class="detail-label">시작 시간</span>
                      <span
                        class="detail-value"><?php echo date('Y-m-d H:i', strtotime($booking['start_time'])); ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">종료 시간</span>
                      <span class="detail-value">
                                        <?php
                                        if ($booking['end_time']) {
                                            echo date('Y-m-d H:i', strtotime($booking['end_time']));
                                        } else {
                                            echo '미정';
                                        }
                                        ?>
                                    </span>
                    </div>
                  </div>

                  <div class="detail-row">
                    <div class="detail-item">
                      <span class="detail-label">예약 일시</span>
                      <span
                        class="detail-value"><?php echo date('Y-m-d H:i', strtotime($booking['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">총 비용</span>
                      <span
                        class="detail-value cost"><?php echo number_format($booking['total_cost']); ?>원</span>
                    </div>
                  </div>
                </div>

                <div class="booking-actions">
                    <?php if ($booking['status'] === 'active'): ?>
                        <?php
                        $now = new DateTime();
                        $start_time = new DateTime($booking['start_time']);
                        $can_cancel = $start_time > $now->modify('+1 hour'); // 시작 1시간 전까지 취소 가능
                        ?>

                        <?php if ($can_cancel): ?>
                        <button class="btn btn-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">예약
                          취소
                        </button>
                        <?php endif; ?>

                      <button class="btn btn-success" onclick="completeBooking(<?php echo $booking['id']; ?>)">
                        충전 완료
                      </button>
                      <button class="btn btn-outline" onclick="extendBooking(<?php echo $booking['id']; ?>)">시간
                        연장
                      </button>

                    <?php elseif ($booking['status'] === 'completed'): ?>
                      <button class="btn btn-outline" onclick="showReceipt(<?php echo $booking['id']; ?>)">영수증
                        보기
                      </button>
                      <a href="stations.php" class="btn btn-primary">재예약</a>

                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                      <a href="stations.php" class="btn btn-primary">새 예약</a>
                    <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-icon">📅</div>
              <h3>예약 내역이 없습니다</h3>
              <p>첫 번째 충전소 예약을 해보세요!</p>
              <a href="stations.php" class="btn btn-primary">충전소 찾기</a>
            </div>
          <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- 영수증 모달 -->
  <div id="receipt-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>충전 영수증</h3>
        <span class="close" onclick="closeReceiptModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div id="receipt-content">
          <!-- 영수증 내용이 여기에 표시됩니다 -->
        </div>
        <div class="modal-actions">
          <button class="btn btn-outline" onclick="printReceipt()">인쇄</button>
          <button class="btn btn-primary" onclick="downloadReceipt()">다운로드</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 시간 연장 모달 -->
  <div id="extend-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>시간 연장</h3>
        <span class="close" onclick="closeExtendModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="extend-form">
          <input type="hidden" id="extend-booking-id">

          <div class="form-group">
            <label for="extend-hours">연장 시간</label>
            <select id="extend-hours" name="extend_hours" required>
              <option value="1">1시간</option>
              <option value="2">2시간</option>
              <option value="3">3시간</option>
            </select>
          </div>

          <div class="form-group">
            <label>추가 비용</label>
            <div id="extend-cost" class="cost-display">0원</div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeExtendModal()">취소</button>
            <button type="submit" class="btn btn-primary">연장 확인</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <style>
    .bookings-page {
      padding: 2rem 0;
    }

    .booking-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin: 2rem 0;
    }

    .stat-item {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
      display: block;
      font-size: 2rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #666;
      font-size: 0.9rem;
    }

    .booking-filters {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 2rem;
      display: grid;
      grid-template-columns: 1fr 1fr 2fr;
      gap: 2rem;
      align-items: end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .filter-group label {
      font-weight: 600;
      color: #2c3e50;
    }

    .filter-group select,
    .search-group input {
      padding: 8px 12px;
      border: 2px solid #e9ecef;
      border-radius: 5px;
      font-size: 1rem;
    }

    .search-group {
      display: flex;
      gap: 0.5rem;
    }

    .search-group input {
      flex: 1;
    }

    .bookings-list {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .booking-card {
      background: white;
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s;
    }

    .booking-card:hover {
      transform: translateY(-2px);
    }

    .booking-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1.5rem;
    }

    .booking-info h3 {
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    .booking-address {
      color: #666;
      font-size: 0.9rem;
    }

    .booking-details {
      margin-bottom: 1.5rem;
    }

    .detail-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-bottom: 1rem;
    }

    .detail-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem;
      background: #f8f9fa;
      border-radius: 5px;
    }

    .detail-label {
      font-weight: 600;
      color: #555;
    }

    .detail-value {
      color: #2c3e50;
    }

    .detail-value.cost {
      font-weight: bold;
      color: #1976d2;
    }

    .booking-actions {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .booking-actions .btn {
      flex: 1;
      min-width: 120px;
    }

    .btn-danger {
      background: #e74c3c;
      color: white;
    }

    .btn-danger:hover {
      background: #c0392b;
    }

    .btn-success {
      background: #27ae60;
      color: white;
    }

    .btn-success:hover {
      background: #219a52;
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .empty-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    .empty-state p {
      color: #666;
      margin-bottom: 2rem;
    }

    /* 모달 스타일 */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 0;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid #eee;
    }

    .modal-header h3 {
      margin: 0;
    }

    .close {
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      color: #666;
    }

    .close:hover {
      color: #333;
    }

    .modal-body {
      padding: 1.5rem;
    }

    .modal-actions {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .modal-actions .btn {
      flex: 1;
    }

    /* 영수증 스타일 */
    #receipt-content {
      background: #f8f9fa;
      padding: 2rem;
      border-radius: 5px;
      font-family: 'Courier New', monospace;
      margin-bottom: 1rem;
    }

    .receipt-header {
      text-align: center;
      border-bottom: 2px solid #333;
      padding-bottom: 1rem;
      margin-bottom: 1rem;
    }

    .receipt-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      padding: 0.25rem 0;
    }

    .receipt-total {
      border-top: 2px solid #333;
      padding-top: 0.5rem;
      margin-top: 1rem;
      font-weight: bold;
      font-size: 1.1rem;
    }

    /* 반응형 디자인 */
    @media (max-width: 768px) {
      .booking-filters {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .booking-header {
        flex-direction: column;
        gap: 1rem;
      }

      .detail-row {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .booking-actions {
        flex-direction: column;
      }

      .booking-actions .btn {
        min-width: auto;
      }

      .booking-stats {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 480px) {
      .booking-stats {
        grid-template-columns: 1fr;
      }

      .modal-content {
        width: 95%;
        margin: 5% auto;
      }
    }

    /* 숨김 클래스 */
    .hidden {
      display: none !important;
    }
  </style>

  <script>
    // 예약 취소
    function cancelBooking(bookingId) {
      if (!confirm('정말 예약을 취소하시겠습니까?')) {
        return;
      }

      fetch('../api/bookings.php', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          booking_id: bookingId,
          action: 'cancel'
        })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showMessage('예약이 취소되었습니다.', 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showMessage(data.message || '취소에 실패했습니다.', 'error');
          }
        })
        .catch(error => {
          console.error('취소 오류:', error);
          showMessage('취소 중 오류가 발생했습니다.', 'error');
        });
    }

    // 충전 완료
    function completeBooking(bookingId) {
      if (!confirm('충전을 완료하시겠습니까?')) {
        return;
      }

      fetch('../api/bookings.php', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          booking_id: bookingId,
          action: 'complete'
        })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showMessage('충전이 완료되었습니다.', 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showMessage(data.message || '완료 처리에 실패했습니다.', 'error');
          }
        })
        .catch(error => {
          console.error('완료 처리 오류:', error);
          showMessage('완료 처리 중 오류가 발생했습니다.', 'error');
        });
    }

    // 시간 연장
    function extendBooking(bookingId) {
      document.getElementById('extend-booking-id').value = bookingId;
      document.getElementById('extend-modal').style.display = 'block';
      updateExtendCost();
    }

    function closeExtendModal() {
      document.getElementById('extend-modal').style.display = 'none';
    }

    function updateExtendCost() {
      const hours = parseInt(document.getElementById('extend-hours').value);
      // 기본 가격을 300원으로 가정 (실제로는 해당 충전소 가격을 가져와야 함)
      const cost = 300 * hours;
      document.getElementById('extend-cost').textContent = cost.toLocaleString() + '원';
    }

    // 영수증 보기
    function showReceipt(bookingId) {
      // 실제로는 API에서 영수증 데이터를 가져와야 함
      const receiptContent = `
        <div class="receipt-header">
            <h3>EGCharge 충전 영수증</h3>
            <p>예약번호: #${bookingId}</p>
        </div>
        <div class="receipt-item">
            <span>충전소명:</span>
            <span>강남역 충전소</span>
        </div>
        <div class="receipt-item">
            <span>주소:</span>
            <span>서울시 강남구 테헤란로 123</span>
        </div>
        <div class="receipt-item">
            <span>충전 시간:</span>
            <span>2시간</span>
        </div>
        <div class="receipt-item">
            <span>단가:</span>
            <span>300원/시간</span>
        </div>
        <div class="receipt-item receipt-total">
            <span>총 금액:</span>
            <span>600원</span>
        </div>
        <div style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: #666;">
            <p>발급일시: ${new Date().toLocaleString()}</p>
            <p>감사합니다!</p>
        </div>
    `;

      document.getElementById('receipt-content').innerHTML = receiptContent;
      document.getElementById('receipt-modal').style.display = 'block';
    }

    function closeReceiptModal() {
      document.getElementById('receipt-modal').style.display = 'none';
    }

    function printReceipt() {
      const content = document.getElementById('receipt-content').innerHTML;
      const printWindow = window.open('', '', 'height=600,width=800');
      printWindow.document.write(`
        <html>
            <head><title>충전 영수증</title></head>
            <body style="font-family: Arial, sans-serif; padding: 20px;">
                ${content}
            </body>
        </html>
    `);
      printWindow.document.close();
      printWindow.print();
    }

    function downloadReceipt() {
      // 실제로는 PDF 생성 라이브러리를 사용해야 함
      alert('영수증 다운로드 기능은 준비 중입니다.');
    }

    // 필터링 기능
    document.addEventListener('DOMContentLoaded', function () {
      const statusFilter = document.getElementById('status-filter');
      const dateFilter = document.getElementById('date-filter');
      const stationSearch = document.getElementById('station-search');
      const extendHours = document.getElementById('extend-hours');

      if (statusFilter) {
        statusFilter.addEventListener('change', filterBookings);
      }

      if (dateFilter) {
        dateFilter.addEventListener('change', filterBookings);
      }

      if (stationSearch) {
        stationSearch.addEventListener('input', filterBookings);
      }

      if (extendHours) {
        extendHours.addEventListener('change', updateExtendCost);
      }

      // 시간 연장 폼 제출
      const extendForm = document.getElementById('extend-form');
      if (extendForm) {
        extendForm.addEventListener('submit', function (e) {
          e.preventDefault();

          const bookingId = document.getElementById('extend-booking-id').value;
          const hours = document.getElementById('extend-hours').value;

          // 실제 API 호출
          fetch('../api/bookings.php', {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              booking_id: parseInt(bookingId),
              action: 'extend',
              extend_hours: parseInt(hours)
            })
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                showMessage('시간이 연장되었습니다.', 'success');
                closeExtendModal();
                setTimeout(() => location.reload(), 1500);
              } else {
                showMessage(data.message || '시간 연장에 실패했습니다.', 'error');
              }
            })
            .catch(error => {
              console.error('시간 연장 오류:', error);
              showMessage('시간 연장 중 오류가 발생했습니다.', 'error');
            });
        });
      }
    });

    function filterBookings() {
      const statusFilter = document.getElementById('status-filter').value;
      const dateFilter = document.getElementById('date-filter').value;
      const searchTerm = document.getElementById('station-search').value.toLowerCase();

      const bookingCards = document.querySelectorAll('.booking-card');

      bookingCards.forEach(card => {
        let show = true;

        // 상태 필터
        if (statusFilter && card.dataset.status !== statusFilter) {
          show = false;
        }

        // 날짜 필터
        if (dateFilter && !matchesDateFilter(card.dataset.date, dateFilter)) {
          show = false;
        }

        // 검색 필터
        if (searchTerm) {
          const stationName = card.querySelector('h3').textContent.toLowerCase();
          if (!stationName.includes(searchTerm)) {
            show = false;
          }
        }

        card.style.display = show ? 'block' : 'none';
      });
    }

    function matchesDateFilter(dateString, filter) {
      const bookingDate = new Date(dateString);
      const now = new Date();

      switch (filter) {
        case 'today':
          return bookingDate.toDateString() === now.toDateString();
        case 'week':
          const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
          return bookingDate >= weekAgo;
        case 'month':
          const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
          return bookingDate >= monthAgo;
        case '3months':
          const threeMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
          return bookingDate >= threeMonthsAgo;
        default:
          return true;
      }
    }

    function searchBookings() {
      filterBookings();
    }

    // 메시지 표시 함수
    function showMessage(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      toast.textContent = message;
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 5px;
        color: white;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s;
        ${type === 'success' ? 'background: #27ae60;' : 'background: #e74c3c;'}
    `;

      document.body.appendChild(toast);

      setTimeout(() => toast.style.opacity = '1', 100);

      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
  </script>

<?php include_once '../includes/footer.php'; ?>