<?php
session_start();
include_once '../config/database.php';
include_once 'includes/header.php';

$message = '';

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password) {
        $message = "비밀번호가 일치하지 않습니다.";
    } else {
        // 이메일 중복 체크
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $email);
        $check_stmt->execute();

        if($check_stmt->rowCount() > 0) {
            $message = "이미 사용 중인 이메일입니다.";
        } else {
            // 사용자 등록
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $name);
            $insert_stmt->bindParam(2, $email);
            $insert_stmt->bindParam(3, $hashed_password);

            if($insert_stmt->execute()) {
                $_SESSION['success_message'] = "회원가입이 완료되었습니다. 로그인해주세요.";
                header("Location: login.php");
                exit();
            } else {
                $message = "회원가입 중 오류가 발생했습니다.";
            }
        }
    }
}
?>

    <main class="auth-page">
        <div class="container">
            <div class="auth-form">
                <h2>회원가입</h2>

                <?php if($message): ?>
                    <div class="alert alert-error"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="name">이름</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">이메일</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">비밀번호</label>
                        <input type="password" id="password" name="password" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">비밀번호 확인</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">회원가입</button>
                </form>

                <p class="auth-link">이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
            </div>
        </div>
    </main>

<?php include_once 'includes/footer.php'; ?>