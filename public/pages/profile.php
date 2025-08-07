<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "프로필 관리";
include_once '../../config/database.php';
include_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$user_info = null;

// 사용자 정보 조회
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

// 프로필 업데이트 처리
if($_POST) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // 기본 정보 업데이트
        if(!empty($name) && !empty($email)) {
            // 이메일 중복 체크 (본인 제외)
            $email_check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $email_check->execute([$email, $_SESSION['user_id']]);

            if($email_check->rowCount() > 0) {
                $message = "이미 사용 중인 이메일입니다.";
            } else {
                $update_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);

                if($update_stmt->execute([$name, $email, $phone, $_SESSION['user_id']])) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['success_message'] = "프로필이 업데이트되었습니다.";
                } else {
                    $message = "프로필 업데이트에 실패했습니다.";
                }
            }
        }

        // 비밀번호 변경
        if(!empty($current_password) && !empty($new_password)) {
            if($new_password !== $confirm_password) {
                $message = "새 비밀번호가 일치하지 않습니다.";
            } else {
                // 현재 비밀번호 확인
                if(password_verify($current_password, $user_info['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_query = "UPDATE users SET password = ? WHERE id = ?";
                    $password_stmt = $db->prepare($password_query);

                    if($password_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                        $_SESSION['success_message'] = "비밀번호가 변경되었습니다.";
                    } else {
                        $message = "비밀번호 변경에 실패했습니다.";
                    }
                } else {
                    $message = "현재 비밀번호가 올바르지 않습니다.";
                }
            }
        }

        // 성공 시 페이지 새로고침
        if(!$message && isset($_SESSION['success_message'])) {
            header("Location: profile.php");
            exit();
        }

    } catch(Exception $e) {
        $message = "처리 중 오류가 발생했습니다.";
    }
}

// 사용자 통계 정보
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'completed' THEN total_cost ELSE 0 END) as total_spent,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_bookings
    FROM bookings WHERE user_id = ?";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<main class="profile-page">
    <div class="container">
        <div class="page-header">
            <h1>프로필 관리</h1>
            <p>개인정보와 계정 설정을 관리하세요</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="profile-content">
            <!-- 사용자 통계 -->
            <div class="profile-stats">
                <h2>나의 충전 현황</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $stats['total_bookings']; ?></span>
                        <span class="stat-label">총 예약 수</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['total_spent']); ?>원</span>
                        <span class="stat-label">총 사용 금액</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $stats['active_bookings']; ?></span>
                        <span class="stat-label">활성 예약</span>
                    </div>
                </div>
            </div>

            <div class="profile-forms">
                <!-- 기본 정보 수정 -->
                <div class="form-section">
                    <h2>기본 정보</h2>
                    <form method="post" class="profile-form">
                        <div class="form-group">
                            <label for="name">이름</label>
                            <input type="text" id="name" name="name"
                                   value="<?php echo htmlspecialchars($user_info['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">이메일</label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">전화번호</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>"
                                   placeholder="010-0000-0000">
                        </div>

                        <div class="form-group">
                            <label>가입일</label>
                            <input type="text" value="<?php echo date('Y년 m월 d일', strtotime($user_info['created_at'])); ?>"
                                   readonly class="readonly-field">
                        </div>

                        <button type="submit" class="btn btn-primary">기본 정보 수정</button>
                    </form>
                </div>

                <!-- 비밀번호 변경 -->
                <div class="form-section">
                    <h2>비밀번호 변경</h2>
                    <form method="post" class="profile-form" id="password-form">
                        <div class="form-group">
                            <label for="current_password">현재 비밀번호</label>
                            <input type="password" id="current_password" name="current_password"
                                   minlength="6" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">새 비밀번호</label>
                            <input type="password" id="new_password" name="new_password"
                                   minlength="6" required>
                            <small class="form-help">최소 6자 이상 입력해주세요</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">새 비밀번호 확인</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   minlength="6" required>
                        </div>

                        <button type="submit" class="btn btn-primary">비밀번호 변경</button>
                    </form>
                </div>

                <!-- 계정 설정 -->
                <div class="form-section">
                    <h2>계정 설정</h2>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>이메일 알림</h4>
                            <p>예약 확인, 충전 완료 등의 알림을 이메일로 받습니다</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked data-setting="email_notifications">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>SMS 알림</h4>
                            <p>충전 시작/완료 알림을 문자로 받습니다</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" data-setting="sms_notifications">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>마케팅 정보 수신</h4>
                            <p>새로운 충전소, 할인 혜택 등의 정보를 받습니다</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked data-setting="marketing_notifications">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>자동 로그인</h4>
                            <p>이 기기에서 자동으로 로그인합니다</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" data-setting="auto_login">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 개인정보 관리 -->
                <div class="form-section">
                    <h2>개인정보 관리</h2>
                    <div class="privacy-item">
                        <div class="privacy-info">
                            <h4>내 정보 다운로드</h4>
                            <p>개인정보보호법에 따라 내 정보를 다운로드받을 수 있습니다</p>
                        </div>
                        <button class="btn btn-outline" onclick="downloadUserData()">정보 다운로드</button>
                    </div>

                    <div class="privacy-item">
                        <div class="privacy-info">
                            <h4>데이터 사용 내역</h4>
                            <p>내 정보가 어떻게 사용되고 있는지 확인할 수 있습니다</p>
                        </div>
                        <button class="btn btn-outline" onclick="showDataUsage()">사용 내역 보기</button>
                    </div>
                </div>

                <!-- 계정 삭제 -->
                <div class="form-section danger-section">
                    <h2>계정 삭제</h2>
                    <p class="danger-text">
                        <strong>주의:</strong> 계정을 삭제하면 모든 데이터가 영구적으로 삭제됩니다.<br>
                        이 작업은 되돌릴 수 없으므로 신중하게 결정해주세요.
                    </p>
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">
                        계정 삭제
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 계정 삭제 확인 모달 -->
<div id="delete-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>계정 삭제 확인</h3>
            <span class="close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong>정말로 계정을 삭제하시겠습니까?</strong></p>
            <p>이 작업은 되돌릴 수 없으며, 다음 데이터가 모두 삭제됩니다:</p>
            <ul>
                <li>개인 정보 및 프로필</li>
                <li>모든 예약 내역</li>
                <li>결제 정보 및 영수증</li>
                <li>계정 설정 및 알림 설정</li>
                <li>사용 통계 및 이력</li>
            </ul>
            <p class="warning-text">계속하려면 비밀번호를 입력해주세요:</p>
            <form id="delete-form">
                <div class="form-group">
                    <input type="password" id="delete-password" placeholder="현재 비밀번호" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="confirm-delete" required>
                        <span class="checkmark"></span>
                        위 내용을 이해했으며, 계정 삭제에 동의합니다
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">취소</button>
                    <button type="submit" class="btn btn-danger">계정 삭제</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 데이터 사용 내역 모달 -->
<div id="data-usage-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>데이터 사용 내역</h3>
            <span class="close" onclick="closeDataUsageModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="data-usage-section">
                <h4>수집된 정보</h4>
                <ul>
                    <li>계정 정보: 이름, 이메일, 전화번호</li>
                    <li>충전 기록: 예약 내역, 사용 시간, 결제 정보</li>
                    <li>위치 정보: 충전소 검색 및 이용 위치</li>
                    <li>이용 패턴: 서비스 사용 통계</li>
                </ul>
            </div>

            <div class="data-usage-section">
                <h4>정보 사용 목적</h4>
                <ul>
                    <li>서비스 제공 및 개선</li>
                    <li>고객 지원 및 문의 응답</li>
                    <li>결제 처리 및 영수증 발급</li>
                    <li>법적 의무 이행</li>
                </ul>
            </div>

            <div class="data-usage-section">
                <h4>정보 보관 기간</h4>
                <ul>
                    <li>계정 정보: 탈퇴 후 1년</li>
                    <li>결제 정보: 법정 보관 기간 5년</li>
                    <li>이용 기록: 서비스 개선 목적 2년</li>
                </ul>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="closeDataUsageModal()">확인</button>
            </div>
        </div>
    </div>
</div>

<style>
  .profile-page {
    padding: 2rem 0;
  }

  .profile-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
  }

  .profile-stats {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: fit-content;
  }

  .profile-stats h2 {
    margin-bottom: 1.5rem;
    color: #2c3e50;
  }

  .stats-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .stat-card {
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.3s;
  }

  .stat-card:hover {
    transform: translateY(-2px);
  }

  .stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 0.5rem;
  }

  .stat-label {
    color: #666;
    font-size: 0.9rem;
  }

  .profile-forms {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .form-section {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }

  .form-section h2 {
    margin-bottom: 1.5rem;
    color: #2c3e50;
    border-bottom: 2px solid #eee;
    padding-bottom: 0.5rem;
  }

  .profile-form .form-group {
    margin-bottom: 1.5rem;
  }

  .profile-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
  }

  .profile-form input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s;
  }

  .profile-form input:focus {
    outline: none;
    border-color: #3498db;
  }

  .readonly-field {
    background-color: #f8f9fa !important;
    color: #666 !important;
    cursor: not-allowed;
  }

  .form-help {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #666;
  }

  /* 설정 아이템 */
  .setting-item,
  .privacy-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 0;
    border-bottom: 1px solid #eee;
  }

  .setting-item:last-child,
  .privacy-item:last-child {
    border-bottom: none;
  }

  .setting-info h4,
  .privacy-info h4 {
    margin-bottom: 0.5rem;
    color: #2c3e50;
  }

  .setting-info p,
  .privacy-info p {
    color: #666;
    font-size: 0.9rem;
  }

  /* 토글 스위치 */
  .switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
  }

  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 34px;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
  }

  input:checked + .slider {
    background-color: #3498db;
  }

  input:checked + .slider:before {
    transform: translateX(26px);
  }

  /* 체크박스 스타일 */
  .checkbox-container {
    display: block;
    position: relative;
    padding-left: 35px;
    margin-bottom: 12px;
    cursor: pointer;
    font-size: 0.9rem;
    user-select: none;
  }

  .checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
  }

  .checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #eee;
    border-radius: 3px;
  }

  .checkbox-container:hover input ~ .checkmark {
    background-color: #ccc;
  }

  .checkbox-container input:checked ~ .checkmark {
    background-color: #3498db;
  }

  .checkmark:after {
    content: "";
    position: absolute;
    display: none;
  }

  .checkbox-container input:checked ~ .checkmark:after {
    display: block;
  }

  .checkbox-container .checkmark:after {
    left: 7px;
    top: 3px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
  }

  /* 위험 구역 */
  .danger-section {
    border: 2px solid #e74c3c;
  }

  .danger-section h2 {
    color: #e74c3c;
  }

  .danger-text {
    color: #721c24;
    background: #f8d7da;
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
  }

  .btn-danger {
    background: #e74c3c;
    color: white;
  }

  .btn-danger:hover {
    background: #c0392b;
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
    color: #2c3e50;
  }

  .danger-section .modal-header h3 {
    color: #e74c3c;
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

  .modal-body ul {
    margin: 1rem 0;
    padding-left: 2rem;
  }

  .modal-body li {
    margin-bottom: 0.5rem;
    color: #666;
  }

  .warning-text {
    color: #e74c3c;
    font-weight: 600;
    margin-top: 1rem;
  }

  .modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
  }

  .modal-actions .btn {
    flex: 1;
  }

  .data-usage-section {
    margin-bottom: 2rem;
  }

  .data-usage-section h4 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 0.5rem;
  }

  /* 반응형 디자인 */
  @media (max-width: 768px) {
    .profile-content {
      grid-template-columns: 1fr;
    }

    .setting-item,
    .privacy-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .stats-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 480px) {
    .profile-page {
      padding: 1rem 0;
    }

    .form-section {
      padding: 1.5rem;
    }

    .modal-content {
      width: 95%;
      margin: 5% auto;
    }
  }
</style>

<script>
  // 비밀번호 확인 검증
  document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
      passwordForm.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
          e.preventDefault();
          alert('새 비밀번호가 일치하지 않습니다.');
          return false;
        }
      });
    }

    // 실시간 비밀번호 일치 확인
    const confirmPasswordField = document.getElementById('confirm_password');
    if (confirmPasswordField) {
      confirmPasswordField.addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;

        if (confirmPassword && newPassword !== confirmPassword) {
          this.style.borderColor = '#e74c3c';
        } else {
          this.style.borderColor = '#e9ecef';
        }
      });
    }

    // 토글 스위치 이벤트
    document.querySelectorAll('.switch input').forEach(toggle => {
      toggle.addEventListener('change', function() {
        const settingName = this.dataset.setting;
        saveSetting(settingName, this.checked);
      });
    });
  });

  // 계정 삭제 확인
  function confirmDeleteAccount() {
    document.getElementById('delete-modal').style.display = 'block';
  }

  function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
    document.getElementById('delete-password').value = '';
    document.getElementById('confirm-delete').checked = false;
  }

  // 계정 삭제 처리
  document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('delete-form');
    if (deleteForm) {
      deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const password = document.getElementById('delete-password').value;
        const confirmDelete = document.getElementById('confirm-delete').checked;

        if (!password) {
          alert('비밀번호를 입력해주세요.');
          return;
        }

        if (!confirmDelete) {
          alert('계정 삭제에 동의해주세요.');
          return;
        }

        if (!confirm('정말로 계정을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
          return;
        }

        // API 호출
        fetch('../api/delete-account.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            password: password
          })
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('계정이 삭제되었습니다. 그동안 이용해 주셔서 감사합니다.');
              window.location.href = '../index.php';
            } else {
              alert(data.message || '계정 삭제에 실패했습니다.');
            }
          })
          .catch(error => {
            console.error('계정 삭제 오류:', error);
            alert('계정 삭제 중 오류가 발생했습니다.');
          });
      });
    }
  });

  // 설정 저장
  function saveSetting(settingName, value) {
    fetch('../api/settings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },