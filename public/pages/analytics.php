<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.href = '/';</script>";
    exit();
}

$page_title = "방문 통계";
include_once '../includes/header.php';
?>

    <main class="analytics-page">
        <div class="container">
            <div class="page-header">
                <h1>방문 통계</h1>
                <p>사이트 방문 현황을 분석하세요</p>
            </div>

            <!-- 통계 필터 -->
            <div class="stats-filters">
                <div class="filter-group">
                    <label for="stat-type">통계 유형:</label>
                    <select id="stat-type">
                        <option value="daily">일별</option>
                        <option value="hourly">시간별</option>
                        <option value="weekly">주간</option>
                        <option value="monthly">월간</option>
                        <option value="realtime">실시간</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="start-date">시작일:</label>
                    <input type="date" id="start-date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                </div>

                <div class="filter-group">
                    <label for="end-date">종료일:</label>
                    <input type="date" id="end-date" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <button class="btn btn-primary" onclick="loadStats()">통계 조회</button>
                <button class="btn btn-secondary" onclick="generateStats()">통계 생성</button>
            </div>

            <!-- 요약 카드 -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3 id="total-visits">0</h3>
                    <p>총 방문수</p>
                </div>
                <div class="summary-card">
                    <h3 id="unique-visitors">0</h3>
                    <p>순 방문자</p>
                </div>
                <div class="summary-card">
                    <h3 id="avg-duration">0</h3>
                    <p>평균 체류시간</p>
                </div>
                <div class="summary-card">
                    <h3 id="bounce-rate">0%</h3>
                    <p>이탈률</p>
                </div>
            </div>

            <!-- 차트 영역 -->
            <div class="chart-container">
                <canvas id="visits-chart"></canvas>
            </div>

            <!-- 상세 테이블 -->
            <div class="stats-table-container">
                <table id="stats-table" class="stats-table">
                    <thead>
                    <tr id="table-headers">
                        <!-- 동적으로 생성 -->
                    </tr>
                    </thead>
                    <tbody id="table-body">
                    <!-- 동적으로 생성 -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <style>
      .analytics-page {
        padding: 2rem 0;
      }

      .stats-filters {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
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

      .filter-group input,
      .filter-group select {
        padding: 8px 12px;
        border: 2px solid #e9ecef;
        border-radius: 5px;
      }

      .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
      }

      .summary-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
      }

      .summary-card h3 {
        font-size: 2rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
      }

      .summary-card p {
        color: #666;
      }

      .chart-container {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        height: 400px;
      }

      .stats-table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
      }

      .stats-table {
        width: 100%;
        border-collapse: collapse;
      }

      .stats-table th,
      .stats-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
      }

      .stats-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #2c3e50;
      }

      .stats-table tr:hover {
        background: #f8f9fa;
      }

      /* 반응형 */
      @media (max-width: 768px) {
        .stats-filters {
          grid-template-columns: 1fr;
        }

        .summary-cards {
          grid-template-columns: repeat(2, 1fr);
        }
      }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      let visitsChart;

      // 페이지 로드 시 초기 통계 로드
      document.addEventListener('DOMContentLoaded', function() {
        loadStats();
      });

      // 통계 데이터 로드
      function loadStats() {
        const type = document.getElementById('stat-type').value;
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;

        const url = `../api/visit-stats.php?type=${type}&start_date=${startDate}&end_date=${endDate}`;

        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateSummaryCards(data.summary);
              updateChart(data.data, type);
              updateTable(data.data, type);
            } else {
              alert(data.message || '통계 로드에 실패했습니다.');
            }
          })
          .catch(error => {
            console.error('통계 로드 오류:', error);
            alert('통계 로드 중 오류가 발생했습니다.');
          });
      }

      // 요약 카드 업데이트
      function updateSummaryCards(summary) {
        document.getElementById('total-visits').textContent = summary.total_visits.toLocaleString();
        document.getElementById('unique-visitors').textContent = summary.total_unique.toLocaleString();
        document.getElementById('avg-duration').textContent = Math.round(summary.avg_duration) + 's';
        // 이탈률은 별도 계산 필요
        document.getElementById('bounce-rate').textContent = '0%';
      }

      // 차트 업데이트
      function updateChart(data, type) {
        const ctx = document.getElementById('visits-chart').getContext('2d');

        if (visitsChart) {
          visitsChart.destroy();
        }

        let labels = [];
        let visitData = [];
        let uniqueData = [];

        data.forEach(item => {
          if (type === 'hourly') {
            labels.push(`${item.date} ${item.hour}:00`);
          } else if (type === 'weekly') {
            labels.push(`${item.week_start} ~ ${item.week_end}`);
          } else if (type === 'monthly') {
            labels.push(item.month);
          } else if (type === 'realtime') {
            labels.push(`${item.date} ${item.hour}:00`);
          } else {
            labels.push(item.date);
          }

          visitData.push(item.total_visits || item.visits || 0);
          uniqueData.push(item.unique_visitors || 0);
        });

        visitsChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: '총 방문수',
              data: visitData,
              borderColor: '#3498db',
              backgroundColor: 'rgba(52, 152, 219, 0.1)',
              tension: 0.4
            }, {
              label: '순 방문자',
              data: uniqueData,
              borderColor: '#e74c3c',
              backgroundColor: 'rgba(231, 76, 60, 0.1)',
              tension: 0.4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      }

      // 테이블 업데이트
      function updateTable(data, type) {
        const headers = document.getElementById('table-headers');
        const tbody = document.getElementById('table-body');

        // 헤더 생성
        let headerHtml = '';
        if (type === 'hourly') {
          headerHtml = '<th>날짜</th><th>시간</th><th>총 방문</th><th>순 방문자</th><th>평균 체류시간</th>';
        } else if (type === 'weekly') {
          headerHtml = '<th>주간</th><th>총 방문</th><th>순 방문자</th><th>평균 체류시간</th>';
        } else if (type === 'monthly') {
          headerHtml = '<th>월</th><th>총 방문</th><th>순 방문자</th><th>평균 체류시간</th>';
        } else if (type === 'realtime') {
          headerHtml = '<th>날짜</th><th>시간</th><th>방문수</th><th>순 방문자</th><th>순 IP</th>';
        } else {
          headerHtml = '<th>날짜</th><th>총 방문</th><th>순 방문자</th><th>평균 체류시간</th>';
        }
        headers.innerHTML = headerHtml;

        // 데이터 행 생성
        let bodyHtml = '';
        data.forEach(item => {
          if (type === 'hourly') {
            bodyHtml += `
                <tr>
                    <td>${item.date}</td>
                    <td>${item.hour}:00</td>
                    <td>${item.total_visits}</td>
                    <td>${item.unique_visitors}</td>
                    <td>${Math.round(item.avg_duration)}s</td>
                </tr>
            `;
          } else if (type === 'weekly') {
            bodyHtml += `
                <tr>
                    <td>${item.week_start} ~ ${item.week_end}</td>
                    <td>${item.total_visits}</td>
                    <td>${item.unique_visitors}</td>
                    <td>${Math.round(item.avg_duration)}s</td>
                </tr>
            `;
          } else if (type === 'monthly') {
            bodyHtml += `
                <tr>
                    <td>${item.month}</td>
                    <td>${item.total_visits}</td>
                    <td>${item.unique_visitors}</td>
                    <td>${Math.round(item.avg_duration)}s</td>
                </tr>
            `;
          } else if (type === 'realtime') {
            bodyHtml += `
                <tr>
                    <td>${item.date}</td>
                    <td>${item.hour}:00</td>
                    <td>${item.visits}</td>
                    <td>${item.unique_visitors}</td>
                    <td>${item.unique_ips}</td>
                </tr>
            `;
          } else {
            bodyHtml += `
                <tr>
                    <td>${item.date}</td>
                    <td>${item.total_visits}</td>
                    <td>${item.unique_visitors}</td>
                    <td>${Math.round(item.avg_duration)}s</td>
                </tr>
            `;
          }
        });
        tbody.innerHTML = bodyHtml;
      }

      // 통계 생성 (집계 실행)
      function generateStats() {
        if (!confirm('통계를 새로 생성하시겠습니까?')) return;

        fetch('../api/generate-stats.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'generate_all'
          })
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('통계 생성이 완료되었습니다.');
              loadStats();
            } else {
              alert(data.message || '통계 생성에 실패했습니다.');
            }
          })
          .catch(error => {
            console.error('통계 생성 오류:', error);
            alert('통계 생성 중 오류가 발생했습니다.');
          });
      }
    </script>

<?php include_once '../includes/footer.php'; ?>