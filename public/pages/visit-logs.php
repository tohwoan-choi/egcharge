<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$page_title = "방문로그";
include_once '../includes/header.php';
?>

  <main class="visit-logs-page">
    <div class="container">
      <div class="page-header">
        <h1>방문로그</h1>
        <p>실시간 사이트 방문 기록을 확인하세요</p>
      </div>

      <!-- 검색 및 필터 -->
      <div class="logs-filters">
        <div class="filter-row">
          <div class="filter-group">
            <label for="search-keyword">검색:</label>
            <input type="text" id="search-keyword" placeholder="IP, 페이지, 브라우저 검색">
          </div>

          <div class="filter-group">
            <label for="date-from">시작일:</label>
            <input type="date" id="date-from" value="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="filter-group">
            <label for="date-to">종료일:</label>
            <input type="date" id="date-to" value="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="filter-group">
            <label for="device-filter">기기:</label>
            <select id="device-filter">
              <option value="">전체</option>
              <option value="desktop">데스크톱</option>
              <option value="mobile">모바일</option>
              <option value="tablet">태블릿</option>
            </select>
          </div>
        </div>

        <div class="filter-row">
          <div class="filter-group">
            <label for="user-filter">사용자:</label>
            <select id="user-filter">
              <option value="">전체 (로그인/비로그인)</option>
              <option value="logged_in">로그인 사용자만</option>
              <option value="guest">비로그인 사용자만</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="browser-filter">브라우저:</label>
            <select id="browser-filter">
              <option value="">전체</option>
              <option value="Chrome">Chrome</option>
              <option value="Firefox">Firefox</option>
              <option value="Safari">Safari</option>
              <option value="Edge">Edge</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="page-filter">페이지:</label>
            <select id="page-filter">
              <option value="">전체 페이지</option>
              <option value="/index.php">홈페이지</option>
              <option value="/pages/dashboard.php">대시보드</option>
              <option value="/pages/stations.php">충전소</option>
              <option value="/pages/bookings.php">예약관리</option>
              <option value="/login.php">로그인</option>
              <option value="/register.php">회원가입</option>
            </select>
          </div>

          <div class="filter-actions">
            <button class="btn btn-primary" onclick="loadVisitLogs()">검색</button>
            <button class="btn btn-secondary" onclick="resetFilters()">초기화</button>
            <button class="btn btn-outline" onclick="exportLogs()">내보내기</button>
          </div>
        </div>
      </div>

      <!-- 실시간 통계 요약 -->
      <div class="logs-summary">
        <div class="summary-item">
          <span class="summary-number" id="total-logs">0</span>
          <span class="summary-label">총 방문</span>
        </div>
        <div class="summary-item">
          <span class="summary-number" id="unique-visitors">0</span>
          <span class="summary-label">순 방문자</span>
        </div>
        <div class="summary-item">
          <span class="summary-number" id="online-users">0</span>
          <span class="summary-label">현재 접속자</span>
        </div>
        <div class="summary-item">
          <span class="summary-number" id="avg-duration">0s</span>
          <span class="summary-label">평균 체류시간</span>
        </div>
      </div>

      <!-- 방문로그 테이블 -->
      <div class="logs-table-container">
        <div class="table-header">
          <h3>방문 기록</h3>
          <div class="table-controls">
            <label for="per-page">표시 수:</label>
            <select id="per-page" onchange="loadVisitLogs()">
              <option value="25">25개</option>
              <option value="50" selected>50개</option>
              <option value="100">100개</option>
              <option value="200">200개</option>
            </select>

            <button class="btn btn-small" onclick="toggleAutoRefresh()">
              <span id="auto-refresh-status">자동새로고침 OFF</span>
            </button>
          </div>
        </div>

        <div class="table-wrapper">
          <table id="logs-table" class="logs-table">
            <thead>
            <tr>
              <th onclick="sortTable('created_at')" class="sortable">
                시간 <span class="sort-icon">↕</span>
              </th>
              <th onclick="sortTable('user_name')" class="sortable">
                사용자 <span class="sort-icon">↕</span>
              </th>
              <th onclick="sortTable('ip_address')" class="sortable">
                IP 주소 <span class="sort-icon">↕</span>
              </th>
              <th onclick="sortTable('page_title')" class="sortable">
                페이지 <span class="sort-icon">↕</span>
              </th>
              <th onclick="sortTable('device_type')" class="sortable">
                기기 <span class="sort-icon">↕</span>
              </th>
              <th onclick="sortTable('browser')" class="sortable">
                브라우저 <span class="sort-icon">↕</span>
              </th>
              <th onclick="sortTable('visit_duration')" class="sortable">
                체류시간 <span class="sort-icon">↕</span>
              </th>
              <th>세부정보</th>
            </tr>
            </thead>
            <tbody id="logs-tbody">
            <!-- 동적으로 로드 -->
            </tbody>
          </table>
        </div>

        <!-- 페이지네이션 -->
        <div class="pagination-container">
          <div class="pagination-info">
            <span id="pagination-info"></span>
          </div>
          <div class="pagination-controls">
            <button id="prev-page" class="btn btn-small" onclick="changePage(-1)" disabled>이전</button>
            <span id="page-numbers"></span>
            <button id="next-page" class="btn btn-small" onclick="changePage(1)">다음</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- 상세 정보 모달 -->
  <div id="log-detail-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>방문 상세 정보</h3>
        <span class="close" onclick="closeDetailModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div id="log-detail-content">
          <!-- 동적으로 로드 -->
        </div>
      </div>
    </div>
  </div>

  <style>
    .visit-logs-page {
      padding: 2rem 0;
    }

    .logs-filters {
      background: white;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }

    .filter-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      align-items: end;
      margin-bottom: 1rem;
    }

    .filter-row:last-child {
      margin-bottom: 0;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .filter-group label {
      font-weight: 600;
      color: #2c3e50;
      font-size: 0.9rem;
    }

    .filter-group input,
    .filter-group select {
      padding: 8px 12px;
      border: 2px solid #e9ecef;
      border-radius: 5px;
      font-size: 0.9rem;
    }

    .filter-actions {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .logs-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .summary-item {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }

    .summary-number {
      display: block;
      font-size: 2rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }

    .summary-label {
      color: #666;
      font-size: 0.9rem;
    }

    .logs-table-container {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid #eee;
    }

    .table-header h3 {
      margin: 0;
      color: #2c3e50;
    }

    .table-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 0.9rem;
    }

    .table-controls label {
      color: #666;
    }

    .table-controls select {
      padding: 4px 8px;
      border: 1px solid #ddd;
      border-radius: 3px;
    }

    .table-wrapper {
      overflow-x: auto;
    }

    .logs-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
    }

    .logs-table th,
    .logs-table td {
      padding: 1rem 0.75rem;
      text-align: left;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
    }

    .logs-table th {
      background: #f8f9fa;
      font-weight: 600;
      color: #2c3e50;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .logs-table tr:hover {
      background: #f8f9fa;
    }

    .sortable {
      cursor: pointer;
      user-select: none;
    }

    .sortable:hover {
      background: #e9ecef !important;
    }

    .sort-icon {
      font-size: 0.8rem;
      color: #666;
    }

    .user-info {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }

    .user-name {
      font-weight: 600;
      color: #2c3e50;
    }

    .user-email {
      font-size: 0.8rem;
      color: #666;
    }

    .guest-user {
      color: #999;
      font-style: italic;
    }

    .page-info {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }

    .page-title {
      font-weight: 500;
      color: #2c3e50;
    }

    .page-url {
      font-size: 0.8rem;
      color: #666;
      font-family: monospace;
    }

    .device-info {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.2rem;
    }

    .device-icon {
      font-size: 1.2rem;
    }

    .device-type {
      font-size: 0.8rem;
      color: #666;
    }

    .browser-info {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }

    .browser-name {
      font-weight: 500;
    }

    .os-name {
      font-size: 0.8rem;
      color: #666;
    }

    .duration-display {
      font-family: monospace;
      font-weight: 600;
      text-align: right;
    }

    .duration-short {
      color: #e74c3c;
    }

    .duration-medium {
      color: #f39c12;
    }

    .duration-long {
      color: #27ae60;
    }

    .detail-btn {
      background: #3498db;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 3px;
      cursor: pointer;
      font-size: 0.8rem;
    }

    .detail-btn:hover {
      background: #2980b9;
    }

    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-top: 1px solid #eee;
    }

    .pagination-info {
      color: #666;
      font-size: 0.9rem;
    }

    .pagination-controls {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .page-number {
      padding: 0.5rem 0.75rem;
      border: 1px solid #ddd;
      background: white;
      color: #2c3e50;
      text-decoration: none;
      border-radius: 3px;
      font-size: 0.9rem;
      cursor: pointer;
    }

    .page-number:hover {
      background: #f8f9fa;
    }

    .page-number.active {
      background: #3498db;
      color: white;
      border-color: #3498db;
    }

    .btn-success {
      background: #27ae60;
      color: white;
    }

    .btn-success:hover {
      background: #219a52;
    }

    .btn-small {
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
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
      margin: 5% auto;
      padding: 0;
      border-radius: 10px;
      width: 90%;
      max-width: 800px;
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

    .detail-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .detail-section {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 5px;
    }

    .detail-section h4 {
      margin: 0 0 1rem 0;
      color: #2c3e50;
      font-size: 1rem;
    }

    .detail-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .detail-item:last-child {
      margin-bottom: 0;
    }

    .detail-label {
      font-weight: 600;
      color: #555;
    }

    .detail-value {
      color: #2c3e50;
      text-align: right;
      word-break: break-word;
    }

    /* 반응형 */
    @media (max-width: 768px) {
      .filter-row {
        grid-template-columns: 1fr;
      }

      .logs-summary {
        grid-template-columns: repeat(2, 1fr);
      }

      .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
      }

      .pagination-container {
        flex-direction: column;
        gap: 1rem;
      }

      .logs-table th,
      .logs-table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
      }
    }

    @media (max-width: 480px) {
      .logs-summary {
        grid-template-columns: 1fr;
      }

      .modal-content {
        width: 95%;
        margin: 2% auto;
      }
    }
  </style>

  <script>
    let currentPage = 1;
    let currentSort = { column: 'created_at', direction: 'desc' };
    let autoRefreshInterval = null;
    let isAutoRefreshEnabled = false;

    // 페이지 로드 시 초기 데이터 로드
    document.addEventListener('DOMContentLoaded', function() {
      loadVisitLogs();
      loadSummaryStats();

      // 필터 이벤트 리스너
      document.getElementById('search-keyword').addEventListener('input', debounce(loadVisitLogs, 500));
      document.getElementById('date-from').addEventListener('change', loadVisitLogs);
      document.getElementById('date-to').addEventListener('change', loadVisitLogs);
      document.getElementById('device-filter').addEventListener('change', loadVisitLogs);
      document.getElementById('user-filter').addEventListener('change', loadVisitLogs);
      document.getElementById('browser-filter').addEventListener('change', loadVisitLogs);
      document.getElementById('page-filter').addEventListener('change', loadVisitLogs);
    });

    // 방문로그 데이터 로드
    function loadVisitLogs() {
      const params = new URLSearchParams({
        page: currentPage,
        per_page: document.getElementById('per-page').value,
        sort_column: currentSort.column,
        sort_direction: currentSort.direction,
        search: document.getElementById('search-keyword').value,
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
        device: document.getElementById('device-filter').value,
        user_type: document.getElementById('user-filter').value,
        browser: document.getElementById('browser-filter').value,
        page_url: document.getElementById('page-filter').value
      });

      fetch(`../api/visit-logs.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayLogs(data.logs);
            updatePagination(data.pagination);
          } else {
            console.error('로그 로드 실패:', data.message);
          }
        })
        .catch(error => {
          console.error('로그 로드 오류:', error);
        });
    }

    // 요약 통계 로드
    function loadSummaryStats() {
      const dateFrom = document.getElementById('date-from').value;
      const dateTo = document.getElementById('date-to').value;

      fetch(`../api/visit-logs.php?action=summary&date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('total-logs').textContent = data.summary.total_logs.toLocaleString();
            document.getElementById('unique-visitors').textContent = data.summary.unique_visitors.toLocaleString();
            document.getElementById('online-users').textContent = data.summary.online_users.toLocaleString();
            document.getElementById('avg-duration').textContent = Math.round(data.summary.avg_duration) + 's';
          }
        })
        .catch(error => {
          console.error('통계 로드 오류:', error);
        });
    }

    // 로그 테이블 표시
    function displayLogs(logs) {
      const tbody = document.getElementById('logs-tbody');

      if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #666; padding: 2rem;">검색 결과가 없습니다.</td></tr>';
        return;
      }

      let html = '';
      logs.forEach(log => {
        const deviceIcon = getDeviceIcon(log.device_type);
        const durationClass = getDurationClass(log.visit_duration);
        const userName = log.user_name || '비로그인 사용자';
        const userClass = log.user_name ? 'user-name' : 'guest-user';

        html += `
            <tr>
                <td>
                    <div style="font-size: 0.9rem;">${formatDateTime(log.created_at)}</div>
                </td>
                <td>
                    <div class="user-info">
                        <span class="${userClass}">${userName}</span>
                        ${log.user_email ? `<span class="user-email">${log.user_email}</span>` : ''}
                    </div>
                </td>
                <td>
                    <span style="font-family: monospace; font-size: 0.9rem;">${log.ip_address}</span>
                </td>
                <td>
                    <div class="page-info">
                        <span class="page-title">${log.page_title || '제목 없음'}</span>
                        <span class="page-url">${log.page_url}</span>
                    </div>
                </td>
                <td>
                    <div class="device-info">
                        <span class="device-icon">${deviceIcon}</span>
                        <span class="device-type">${log.device_type || 'Unknown'}</span>
                    </div>
                </td>
                <td>
                    <div class="browser-info">
                        <span class="browser-name">${log.browser || 'Unknown'}</span>
                        <span class="os-name">${log.os || 'Unknown'}</span>
                    </div>
                </td>
                <td>
                    <span class="duration-display ${durationClass}">
                        ${formatDuration(log.visit_duration)}
                    </span>
                </td>
                <td>
                    <button class="detail-btn" onclick="showLogDetail(${log.id})">
                        상세보기
                    </button>
                </td>
            </tr>
        `;
      });

      tbody.innerHTML = html;
    }

    // 페이지네이션 업데이트 (수정)
    function updatePagination(pagination) {
      const info = document.getElementById('pagination-info');
      const prevBtn = document.getElementById('prev-page');
      const nextBtn = document.getElementById('next-page');
      const pageNumbers = document.getElementById('page-numbers');

      // 정보 텍스트
      const start = (pagination.current_page - 1) * pagination.per_page + 1;
      const end = Math.min(start + pagination.per_page - 1, pagination.total);
      info.textContent = `${start.toLocaleString()}-${end.toLocaleString()} / ${pagination.total.toLocaleString()}`;

      // 이전/다음 버튼 상태 업데이트
      prevBtn.disabled = pagination.current_page <= 1;
      nextBtn.disabled = pagination.current_page >= pagination.total_pages;

      // 비활성화된 버튼 스타일 적용
      if (prevBtn.disabled) {
        prevBtn.style.opacity = '0.5';
        prevBtn.style.cursor = 'not-allowed';
      } else {
        prevBtn.style.opacity = '1';
        prevBtn.style.cursor = 'pointer';
      }

      if (nextBtn.disabled) {
        nextBtn.style.opacity = '0.5';
        nextBtn.style.cursor = 'not-allowed';
      } else {
        nextBtn.style.opacity = '1';
        nextBtn.style.cursor = 'pointer';
      }

      // 페이지 번호 생성
      let pagesHtml = '';
      const startPage = Math.max(1, pagination.current_page - 2);
      const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

      if (startPage > 1) {
        pagesHtml += `<span class="page-number" onclick="goToPage(1)">1</span>`;
        if (startPage > 2) pagesHtml += `<span style="padding: 0.5rem;">...</span>`;
      }

      for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === pagination.current_page ? ' active' : '';
        pagesHtml += `<span class="page-number${activeClass}" onclick="goToPage(${i})">${i}</span>`;
      }

      if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) pagesHtml += `<span style="padding: 0.5rem;">...</span>`;
        pagesHtml += `<span class="page-number" onclick="goToPage(${pagination.total_pages})">${pagination.total_pages}</span>`;
      }

      pageNumbers.innerHTML = pagesHtml;
    }

    // 정렬 처리
    function sortTable(column) {
      if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
      } else {
        currentSort.column = column;
        currentSort.direction = 'desc';
      }

      currentPage = 1;
      loadVisitLogs();

      // 정렬 아이콘 업데이트
      document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.textContent = '↕';
      });

      const currentHeader = document.querySelector(`th[onclick="sortTable('${column}')"] .sort-icon`);
      if (currentHeader) {
        currentHeader.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
      }
    }

    // 페이지 이동 (수정)
    function changePage(direction) {
      const newPage = currentPage + direction;
      if (newPage > 0) {
        goToPage(newPage);
      }
    }

    function goToPage(page) {
      if (page < 1) return;
      currentPage = page;
      loadVisitLogs();
    }

    // 로그 상세정보 표시 (수정)
    function showLogDetail(logId) {
      // 로딩 상태 표시
      const modal = document.getElementById('log-detail-modal');
      const content = document.getElementById('log-detail-content');

      content.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="loading-spinner"></div><p>로딩 중...</p></div>';
      modal.style.display = 'block';

      fetch(`../api/visit-logs.php?action=detail&id=${logId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayLogDetail(data.log);
          } else {
            content.innerHTML = '<div style="text-align: center; padding: 2rem; color: #e74c3c;"><p>상세 정보를 불러올 수 없습니다.</p></div>';
          }
        })
        .catch(error => {
          console.error('상세정보 로드 오류:', error);
          content.innerHTML = '<div style="text-align: center; padding: 2rem; color: #e74c3c;"><p>로드 중 오류가 발생했습니다.</p></div>';
        });
    }

    // 상세정보 모달 내용 표시 (수정)
    function displayLogDetail(log) {
      const content = document.getElementById('log-detail-content');

      content.innerHTML = `
        <div class="detail-grid">
            <div class="detail-section">
                <h4>기본 정보</h4>
                <div class="detail-item">
                    <span class="detail-label">방문 시간:</span>
                    <span class="detail-value">${formatDateTime(log.created_at)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">사용자:</span>
                    <span class="detail-value">${log.user_name || '비로그인 사용자'}</span>
                </div>
                ${log.user_email ? `
                <div class="detail-item">
                    <span class="detail-label">이메일:</span>
                    <span class="detail-value">${log.user_email}</span>
                </div>
                ` : ''}
                <div class="detail-item">
                    <span class="detail-label">IP 주소:</span>
                    <span class="detail-value">${log.ip_address}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">세션 ID:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.8rem; word-break: break-all;">${log.session_id}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">체류 시간:</span>
                    <span class="detail-value">${formatDuration(log.visit_duration)}</span>
                </div>
            </div>

            <div class="detail-section">
                <h4>페이지 정보</h4>
                <div class="detail-item">
                    <span class="detail-label">페이지 제목:</span>
                    <span class="detail-value">${log.page_title || '제목 없음'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">페이지 URL:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.8rem; word-break: break-all;">${log.page_url}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">리퍼러:</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.8rem; word-break: break-all;">${log.referer || '직접 접속'}</span>
                </div>
            </div>

            <div class="detail-section">
                <h4>기기 및 브라우저</h4>
                <div class="detail-item">
                    <span class="detail-label">기기 유형:</span>
                    <span class="detail-value">${getDeviceIcon(log.device_type)} ${log.device_type || 'Unknown'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">브라우저:</span>
                    <span class="detail-value">${log.browser || 'Unknown'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">운영체제:</span>
                    <span class="detail-value">${log.os || 'Unknown'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">위치:</span>
                    <span class="detail-value">${log.country || 'Unknown'} ${log.city ? ', ' + log.city : ''}</span>
                </div>
            </div>

            <div class="detail-section" style="grid-column: 1 / -1;">
                <h4>User Agent</h4>
                <div style="background: #f8f9fa; padding: 0.75rem; border-radius: 3px; font-family: monospace; font-size: 0.8rem; word-break: break-all; line-height: 1.4;">
                    ${log.user_agent || 'Unknown'}
                </div>
            </div>
        </div>
    `;
    }

    // 모달 닫기 (수정)
    function closeDetailModal() {
      const modal = document.getElementById('log-detail-modal');
      modal.style.display = 'none';

      // 내용 초기화
      document.getElementById('log-detail-content').innerHTML = '';
    }

    // 필터 초기화
    function resetFilters() {
      document.getElementById('search-keyword').value = '';
      document.getElementById('date-from').value = new Date().toISOString().split('T')[0];
      document.getElementById('date-to').value = new Date().toISOString().split('T')[0];
      document.getElementById('device-filter').value = '';
      document.getElementById('user-filter').value = '';
      document.getElementById('browser-filter').value = '';
      document.getElementById('page-filter').value = '';
      document.getElementById('per-page').value = '50';

      currentPage = 1;
      currentSort = { column: 'created_at', direction: 'desc' };

      // 정렬 아이콘 초기화
      document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.textContent = '↕';
      });

      loadVisitLogs();
      loadSummaryStats();
    }

    // 자동 새로고침 토글
    function toggleAutoRefresh() {
      const statusElement = document.getElementById('auto-refresh-status');

      if (isAutoRefreshEnabled) {
        clearInterval(autoRefreshInterval);
        isAutoRefreshEnabled = false;
        statusElement.textContent = '자동새로고침 OFF';
        statusElement.parentElement.classList.remove('btn-success');
        statusElement.parentElement.classList.add('btn-secondary');
      } else {
        autoRefreshInterval = setInterval(() => {
          loadVisitLogs();
          loadSummaryStats();
        }, 30000); // 30초마다 새로고침

        isAutoRefreshEnabled = true;
        statusElement.textContent = '자동새로고침 ON';
        statusElement.parentElement.classList.remove('btn-secondary');
        statusElement.parentElement.classList.add('btn-success');
      }
    }

    // 로그 내보내기
    function exportLogs() {
      const params = new URLSearchParams({
        action: 'export',
        search: document.getElementById('search-keyword').value,
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
        device: document.getElementById('device-filter').value,
        user_type: document.getElementById('user-filter').value,
        browser: document.getElementById('browser-filter').value,
        page_url: document.getElementById('page-filter').value
      });

      window.open(`../api/visit-logs.php?${params.toString()}`, '_blank');
    }

    // 유틸리티 함수들
    function getDeviceIcon(deviceType) {
      switch(deviceType) {
        case 'mobile': return '📱';
        case 'tablet': return '📱';
        case 'desktop': return '🖥️';
        default: return '❓';
      }
    }

    function getDurationClass(duration) {
      if (duration < 10) return 'duration-short';
      if (duration < 60) return 'duration-medium';
      return 'duration-long';
    }

    function formatDateTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString('ko-KR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    }

    function formatDuration(seconds) {
      if (seconds < 60) return `${seconds}초`;
      if (seconds < 3600) return `${Math.floor(seconds / 60)}분 ${seconds % 60}초`;
      return `${Math.floor(seconds / 3600)}시간 ${Math.floor((seconds % 3600) / 60)}분`;
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

    // 모달 외부 클릭 시 닫기
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('log-detail-modal');
      if (event.target === modal) {
        closeDetailModal();
      }
    });

    // 키보드 이벤트 (ESC로 모달 닫기)
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeDetailModal();
      }
    });
  </script>

<?php include_once '../includes/footer.php'; ?>