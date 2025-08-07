<?php
session_start();
include_once '../config/database.php';
include_once 'includes/header.php';

$message = '';

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT id, name, password FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            if($email == 'dudori74@naver.com'){
                $_SESSION['is_admin'] = true; // 관리자 계정 설정
            } else {
                $_SESSION['is_admin'] = false; // 일반 사용자 계정 설정
            }
            $_SESSION['success_message'] = ($_SESSION['is_admin']? '[관리자]':'')."로그인되었습니다.";
            header("Location: pages/dashboard.php");
            exit();
        } else {
            $message = "비밀번호가 올바르지 않습니다.";
        }
    } else {
        $message = "사용자를 찾을 수 없습니다.";
    }
}
?>

  <main class="auth-page">
    <div class="container">
      <div class="auth-form">
        <h2>로그인</h2>

          <?php if($message): ?>
            <div class="alert alert-error"><?php echo $message; ?></div>
          <?php endif; ?>

        <form method="post">
          <div class="form-group">
            <label for="email">이메일</label>
            <input type="email" id="email" name="email" required>
          </div>

          <div class="form-group">
            <label for="password">비밀번호</label>
            <input type="password" id="password" name="password" required>
          </div>

          <button type="submit" class="btn btn-primary btn-full">로그인</button>
        </form>

        <p class="auth-link">계정이 없으신가요? <a href="register.php">회원가입</a></p>
      </div>
    </div>
  </main>

<?php include_once 'includes/footer.php'; ?>