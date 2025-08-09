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
          <button type="button" class="btn btn-primary" onclick="searchStationBtn()">검색</button>
        </div>

        <div class="filters">
          <select id="charge-type-filter">
            <option value="">모든 충전방식</option>
            <option value="1">완속</option>
            <option value="2">급속</option>
          </select>

          <select id="connector-filter">
            <option value="">모든 커넥터</option>
            <option value="1">B타입(5핀)</option>
            <option value="2">C타입(5핀)</option>
            <option value="3">BC타입(5핀)</option>
            <option value="4">BC타입(7핀)</option>
            <option value="5">DC차데모</option>
            <option value="6">AC3상</option>
            <option value="7">DC콤보</option>
            <option value="8">DC차데모+DC콤보</option>
          </select>

          <select id="status-filter">
            <option value="">모든 상태</option>
            <option value="1">충전가능</option>
            <option value="2">충전중</option>
            <option value="3">고장/점검</option>
          </select>
        </div>
      </div>

      <div id="search-loading" class="loading-indicator" style="display: none;">
        <div class="loading-spinner"></div>
        <p>검색 중...</p>
      </div>

      <div id="search-results" class="stations-grid">
        <!-- 동적으로 로드될 충전소 목록 -->
      </div>
    </div>
  </main>

  <!-- 예약 모달 -->
<?php if(isset($_SESSION['user_id'])): ?>
  <div id="booking-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>충전기 예약</h3>
        <span class="close" onclick="closeBookingModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form id="booking-form">
          <input type="hidden" id="selected-offer-cd" name="offer_cd">
          <input type="hidden" id="selected-cs-id" name="cs_id">
          <input type="hidden" id="selected-cp-id" name="cp_id">

          <div class="form-group">
            <label>선택한 충전기</label>
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
            <div id="estimated-cost" class="cost-display">계산 중...</div>
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
    /* 기존 스타일 유지 + 추가 스타일 */
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
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    .station-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .station-info h3 {
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    .station-address {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    .update-time {
      margin: 0;
      padding: 2px 0;
      font-size: 0.75em;
      color: #999;
      opacity: 0.7;
      text-align: right;
    }
    .charger-details {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .charger-details h4 {
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    .detail-tags {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-bottom: 0.5rem;
    }

    .detail-tags span {
      background: #e3f2fd;
      color: #1976d2;
      padding: 0.25rem 0.5rem;
      border-radius: 3px;
      font-size: 0.8rem;
    }

    .status-available {
      background: #e8f5e8 !important;
      color: #388e3c !important;
    }

    .status-charging {
      background: #fff3e0 !important;
      color: #f57c00 !important;
    }

    .status-broken {
      background: #ffebee !important;
      color: #d32f2f !important;
    }

    .status-offline {
      background: #f3e5f5 !important;
      color: #7b1fa2 !important;
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

    /* 수정된 코드 */
    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 0;
      border-radius: 10px;
      width: 90%;
      max-width: 500px;
      max-height: 90vh;
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

    /* 반응형 */
    @media (max-width: 768px) {
      .search-form {
        flex-direction: column;
      }

      .filters {
        grid-template-columns: 1fr;
      }

      .stations-grid {
        grid-template-columns: 1fr;
      }

      .station-header {
        flex-direction: column;
        gap: 0.5rem;
      }

      .detail-tags {
        flex-direction: column;
      }

      .modal-content {
        margin: 2% auto;
        width: 95%;
        max-height: 95vh;
      }

      .modal-body {
        padding: 1rem;
      }
    }
  </style>

  <script>
    // 페이지 로드시 충전소 목록 가져오기
    document.addEventListener('DOMContentLoaded', function() {
      loadStations();

      // 필터 이벤트 리스너
      document.getElementById('station-search').addEventListener('input', debounce(filterStations, 300));
      document.getElementById('charge-type-filter').addEventListener('change', filterStations);
      document.getElementById('connector-filter').addEventListener('change', filterStations);
      document.getElementById('status-filter').addEventListener('change', filterStations);

      // 예약 폼 이벤트
      const bookingForm = document.getElementById('booking-form');
      if (bookingForm) {
        bookingForm.addEventListener('submit', handleBooking);
        document.getElementById('duration').addEventListener('change', updateEstimatedCost);
      }
    });

    // 충전소 목록 로드
    function loadStations(filters = {}) {
      const loadingElement = document.getElementById('search-loading');
      const resultsContainer = document.getElementById('search-results');

      loadingElement.style.display = 'block';

      // API 호출
      let url = '../api/stations.php';
      const params = new URLSearchParams();

      if (filters.search) params.append('search', filters.search);
      if (filters.charge_type) params.append('charge_type', filters.charge_type);
      if (filters.connector_type) params.append('connector_type', filters.connector_type);
      if (filters.status) params.append('status', filters.status);

      if (params.toString()) {
        url += '?' + params.toString();
      }

      fetch(url)
        .then(response => response.json())
        .then(data => {
          loadingElement.style.display = 'none';

          if (data.success) {
            displayStations(data.stations);
          } else {
            resultsContainer.innerHTML = '<p class="error-message">충전소 정보를 불러올 수 없습니다.</p>';
          }
        })
        .catch(error => {
          loadingElement.style.display = 'none';
          console.error('충전소 로드 오류:', error);
          resultsContainer.innerHTML = '<p class="error-message">네트워크 오류가 발생했습니다.</p>';
        });
    }

    // 충전소 표시
    function displayStations(stations) {
      const resultsContainer = document.getElementById('search-results');

      if (stations.length === 0) {
        resultsContainer.innerHTML = '<p class="empty-message">검색 결과가 없습니다.</p>';
        return;
      }

      // 충전소별로 그룹화
      const stationGroups = {};
      stations.forEach(station => {
        const key = station.csId;
        if (!stationGroups[key]) {
          stationGroups[key] = {
            csNm: station.csNm,
            addr: station.addr,
            lat: station.lat,
            lngi: station.lngi,
            statupdatetime: station.statUpdatetime,
            chargers: []
          };
        }
        stationGroups[key].chargers.push(station);
      });

      let html = '';
      Object.values(stationGroups).forEach(stationGroup => {
        html += createStationCard(stationGroup);
      });

      resultsContainer.innerHTML = html;
    }

    // 충전소 카드 생성
    function createStationCard(stationGroup) {
      const availableChargers = stationGroup.chargers.filter(c => c.cpStat == 1);
      const chargingChargers = stationGroup.chargers.filter(c => c.cpStat == 2);
      const brokenChargers = stationGroup.chargers.filter(c => c.cpStat == 3);

      let chargersHtml = '';
      stationGroup.chargers.forEach(charger => {
        const statusClass = getStatusClass(charger.cpStat);
        const isAvailable = charger.cpStat == 1;

        chargersHtml += `
            <div class="charger-details">
                <h4>${escapeHtml(charger.cpNm)}</h4>
                <div class="detail-tags">
                    <span>${charger.charegTpNm}</span>
                    <span>${charger.cpTpNm}</span>
                    <span class="${statusClass}">${charger.cpStatNm}</span>
                </div>
                ${isAvailable && <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?> ?
                  `<button class="btn btn-primary btn-sm" onclick="showBookingModal('${charger.offer_cd}', '${charger.csId}', '${charger.cpId}')">예약하기</button>` :
                  isAvailable ?
                  `<a href="../login.php" class="btn btn-primary btn-sm">로그인 후 예약</a>` :
                  `<button class="btn btn-secondary btn-sm" disabled>예약불가</button>`
        }
            </div>
        `;
      });

      return `
        <div class="station-card">
            <div class="station-header">
                <div class="station-info">
                    <h3>${escapeHtml(stationGroup.csNm)}</h3>
                    <p class="station-address">${escapeHtml(stationGroup.addr)}</p>
                </div>
                <div class="station-summary">
                    <small>
                        충전가능: ${availableChargers.length} |
                        충전중: ${chargingChargers.length} |
                        고장: ${brokenChargers.length}
                    </small>
                </div>
            </div>
            <div>
              <p class="update-time">update:${escapeHtml(stationGroup.statupdatetime)}</p>
            </div>
            <div class="chargers-list">
                ${chargersHtml}
            </div>
        </div>
    `;
    }

    // 상태별 CSS 클래스
    function getStatusClass(status) {
      switch(parseInt(status)) {
        case 1: return 'status-available';
        case 2: return 'status-charging';
        case 3: return 'status-broken';
        case 4:
        case 5: return 'status-offline';
        default: return '';
      }
    }

    // 필터링
    function filterStations() {
      const filters = {
        search: document.getElementById('station-search').value.trim(),
        charge_type: document.getElementById('charge-type-filter').value,
        connector_type: document.getElementById('connector-filter').value,
        status: document.getElementById('status-filter').value
      };

      loadStations(filters);
    }

    // 예약 모달 표시
    function showBookingModal(offerCd, csId, cpId) {
      document.getElementById('selected-offer-cd').value = offerCd;
      document.getElementById('selected-cs-id').value = csId;
      document.getElementById('selected-cp-id').value = cpId;

      // 충전기 정보 표시
      fetch(`../api/stations.php?offer_cd=${offerCd}&cs_id=${csId}&cp_id=${cpId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.station) {
            const station = data.station;
            document.getElementById('selected-station-info').innerHTML = `
                    <strong>${escapeHtml(station.csNm)}</strong><br>
                    ${escapeHtml(station.addr)}<br>
                    <strong>충전기:</strong> ${escapeHtml(station.cpNm)}<br>
                    <strong>타입:</strong> ${station.charegTpNm} (${station.cpTpNm})
                `;
          }
        });

      // 기본 시작 시간 설정
      const now = new Date();
      now.setHours(now.getHours() + 1);
      document.getElementById('start-time').value = now.toISOString().slice(0, 16);

      updateEstimatedCost();
      document.getElementById('booking-modal').style.display = 'block';
    }

    function closeBookingModal() {
      document.getElementById('booking-modal').style.display = 'none';
    }

    // 예상 비용 계산
    function updateEstimatedCost() {
      const duration = parseInt(document.getElementById('duration').value);
      // 기본 요금 (실제로는 충전기 타입별로 다르게 적용)
      const baseRate = 300; // 원/시간
      const cost = baseRate * duration;
      document.getElementById('estimated-cost').textContent = cost.toLocaleString() + '원';
    }

    // 예약 처리
    function handleBooking(e) {
      e.preventDefault();

      const formData = new FormData(e.target);
      const bookingData = {
        offer_cd: formData.get('offer_cd'),
        cs_id: formData.get('cs_id'),
        cp_id: formData.get('cp_id'),
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
    }

    // 유틸리티 함수
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }
  </script>

<?php include_once '../includes/footer.php'; ?>