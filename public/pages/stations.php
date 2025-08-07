<?php
session_start();
$page_title = "충전소 찾기";
include_once '../includes/header.php';
?>

    <main class="stations-page">
        <div class="container">
            <div class="page-header">
                <h1>충전소 찾기</h1>
                <p>원하는 충전소를 검색하고 예약하세요</p>
            </div>

            <div class="search-section">
                <div class="search-form">
                    <input type="text" id="station-search" placeholder="충전소명 또는 주소로 검색" class="search-input">
                    <button type="button" class="btn btn-primary" onclick="searchStations()">검색</button>
                </div>

                <div class="filters">
                    <select id="connector-filter">
                        <option value="">모든 커넥터</option>
                        <option value="Type2">Type2</option>
                        <option value="CHAdeMO">CHAdeMO</option>
                        <option value="CCS">CCS</option>
                    </select>

                    <select id="price-filter">
                        <option value="">가격대</option>
                        <option value="0-200">200원 이하</option>
                        <option value="200-300">200-300원</option>
                        <option value="300-400">300-400원</option>
                        <option value="400+">400원 이상</option>
                    </select>
                </div>
            </div>

            <div id="search-loading" class="loading-indicator" style="display: none;">
                <div class="loading-spinner"></div>
                <p>검색 중...</p>
            </div>

            <div id="search-results" class="stations-grid">
                <!-- 기본 충전소 목록 표시 -->
                <div class="station-card">
                    <div class="station-info">
                        <h3>강남역 충전소</h3>
                        <p class="station-address">서울시 강남구 테헤란로 123</p>
                        <div class="station-details">
                            <span class="station-price">300원/시간</span>
                            <span class="connector-type">Type2</span>
                            <span class="charging-speed">50kW</span>
                        </div>
                        <div class="station-status">
                            <span class="status-badge status-active">이용가능</span>
                        </div>
                    </div>
                    <div class="station-actions">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-primary" onclick="showBookingModal(1)">예약하기</button>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-primary">로그인 후 예약</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="station-card">
                    <div class="station-info">
                        <h3>홍대입구 충전소</h3>
                        <p class="station-address">서울시 마포구 양화로 45</p>
                        <div class="station-details">
                            <span class="station-price">250원/시간</span>
                            <span class="connector-type">CHAdeMO</span>
                            <span class="charging-speed">100kW</span>
                        </div>
                        <div class="station-status">
                            <span class="status-badge status-active">이용가능</span>
                        </div>
                    </div>
                    <div class="station-actions">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-primary" onclick="showBookingModal(2)">예약하기</button>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-primary">로그인 후 예약</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="station-card">
                    <div class="station-info">
                        <h3>잠실 충전소</h3>
                        <p class="station-address">서울시 송파구 올림픽로 300</p>
                        <div class="station-details">
                            <span class="station-price">280원/시간</span>
                            <span class="connector-type">CCS</span>
                            <span class="charging-speed">75kW</span>
                        </div>
                        <div class="station-status">
                            <span class="status-badge status-active">이용가능</span>
                        </div>
                    </div>
                    <div class="station-actions">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-primary" onclick="showBookingModal(3)">예약하기</button>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-primary">로그인 후 예약</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 예약 모달 -->
<?php if(isset($_SESSION['user_id'])): ?>
    <div id="booking-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>충전소 예약</h3>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="booking-form">
                    <input type="hidden" id="selected-station-id" name="station_id">

                    <div class="form-group">
                        <label>선택한 충전소</label>
                        <div id="selected-station-info" class="station-summary"></div>
                    </div>

                    <div class="form-group">
                        <label for="start-time">시작 시간</label>
                        <input type="datetime-local" id="start-time" name="start_time" required>
                    </div>

                    <div class="form-group">
                        <label for="duration">충전 시간</label>
                        <select id="duration" name="duration_hours" required>
                            <option value="1">1시간</option>
                            <option value="2">2시간</option>
                            <option value="3">3시간</option>
                            <option value="4">4시간</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>예상 비용</label>
                        <div id="estimated-cost" class="cost-display">0원</div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">취소</button>
                        <button type="submit" class="btn btn-primary">예약 확인</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

    <style>
      .stations-page {
        padding: 2rem 0;
      }

      .search-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
      }

      .search-form {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
      }

      .search-input {
        flex: 1;
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 5px;
        font-size: 1rem;
      }

      .filters {
        display: flex;
        gap: 1rem;
      }

      .filters select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
      }

      .stations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
      }

      .station-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s;
      }

      .station-card:hover {
        transform: translateY(-3px);
      }

      .station-info h3 {
        margin-bottom: 0.5rem;
        color: #2c3e50;
      }

      .station-address {
        color: #666;
        margin-bottom: 1rem;
      }

      .station-details {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
      }

      .station-details span {
        background: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 3px;
        font-size: 0.9rem;
      }

      .station-price {
        background: #e3f2fd !important;
        color: #1976d2;
        font-weight: bold;
      }

      .connector-type {
        background: #f3e5f5 !important;
        color: #7b1fa2;
      }

      .charging-speed {
        background: #e8f5e8 !important;
        color: #388e3c;
      }

      .station-actions {
        text-align: center;
      }

      .loading-indicator {
        text-align: center;
        padding: 2rem;
      }

      .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
      }

      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
        background-color: rgba(0,0,0,0.5);
      }

      .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 0;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
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
      }

      .close:hover {
        color: #999;
      }

      .modal-body {
        padding: 1.5rem;
      }

      .station-summary {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
      }

      .cost-display {
        font-size: 1.2rem;
        font-weight: bold;
        color: #2c3e50;
        background: #f8f9fa;
        padding: 0.75rem;
        border-radius: 5px;
        text-align: center;
      }

      .modal-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
      }

      .modal-actions .btn {
        flex: 1;
      }
    </style>

    <script>
      let currentStationId = 0;
      let currentStationPrice = 0;

      function showBookingModal(stationId) {
        // 충전소 정보 가져오기
        const stations = {
          1: { name: '강남역 충전소', address: '서울시 강남구 테헤란로 123', price: 300 },
          2: { name: '홍대입구 충전소', address: '서울시 마포구 양화로 45', price: 250 },
          3: { name: '잠실 충전소', address: '서울시 송파구 올림픽로 300', price: 280 }
        };

        const station = stations[stationId];
        if (!station) return;

        currentStationId = stationId;
        currentStationPrice = station.price;

        document.getElementById('selected-station-id').value = stationId;
        document.getElementById('selected-station-info').innerHTML = `
        <strong>${station.name}</strong><br>
        ${station.address}<br>
        <span style="color: #1976d2; font-weight: bold;">${station.price}원/시간</span>
    `;

        // 기본 시작 시간 설정 (현재 시간 + 1시간)
        const now = new Date();
        now.setHours(now.getHours() + 1);
        document.getElementById('start-time').value = now.toISOString().slice(0, 16);

        updateEstimatedCost();

        document.getElementById('booking-modal').style.display = 'block';
      }

      function closeBookingModal() {
        document.getElementById('booking-modal').style.display = 'none';
      }

      function updateEstimatedCost() {
        const duration = parseInt(document.getElementById('duration').value);
        const cost = currentStationPrice * duration;
        document.getElementById('estimated-cost').textContent = cost.toLocaleString() + '원';
      }

      // 예약 폼 제출
      document.addEventListener('DOMContentLoaded', function() {
        const bookingForm = document.getElementById('booking-form');
        if (bookingForm) {
          bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const bookingData = {
              station_id: parseInt(formData.get('station_id')),
              start_time: formData.get('start_time'),
              duration_hours: parseInt(formData.get('duration_hours'))
            };

            fetch('../api/bookings.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(bookingData)
            })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  alert('예약이 완료되었습니다!');
                  closeBookingModal();
                  window.location.href = 'bookings.php';
                } else {
                  alert(data.message || '예약에 실패했습니다.');
                }
              })
              .catch(error => {
                console.error('예약 오류:', error);
                alert('예약 중 오류가 발생했습니다.');
              });
          });
        }

        // 시간 변경 시 비용 업데이트
        const durationSelect = document.getElementById('duration');
        if (durationSelect) {
          durationSelect.addEventListener('change', updateEstimatedCost);
        }
      });
    </script>

<?php include_once '../includes/footer.php'; ?>