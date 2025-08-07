# EGCharg - 전기차 충전소 관리 시스템

## 프로젝트 구조
```
egcharg/
├── config/
│   └── database.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── includes/
│   ├── header.php
│   └── footer.php
├── pages/
│   ├── dashboard.php
│   ├── stations.php
│   ├── bookings.php
│   └── users.php
├── api/
│   ├── stations.php
│   ├── bookings.php
│   └── users.php
├── index.php
├── login.php
├── register.php
└── README.md
```

## 1. 데이터베이스 설정 (config/database.php)
```php
<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'egcharg';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "연결 오류: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
```

## 2. 메인 페이지 (index.php)
```php
<?php
session_start();
include_once 'includes/header.php';
?>

<main class="hero">
    <div class="container">
        <h1>EGCharg</h1>
        <p>전기차 충전소 예약 및 관리 시스템</p>

        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="pages/dashboard.php" class="btn btn-primary">대시보드</a>
        <?php else: ?>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-primary">로그인</a>
                <a href="register.php" class="btn btn-secondary">회원가입</a>
            </div>
        <?php endif; ?>

        <div class="features">
            <div class="feature-card">
                <h3>충전소 찾기</h3>
                <p>주변 충전소를 쉽게 찾아보세요</p>
            </div>
            <div class="feature-card">
                <h3>실시간 예약</h3>
                <p>실시간으로 충전소를 예약할 수 있습니다</p>
            </div>
            <div class="feature-card">
                <h3>사용량 관리</h3>
                <p>충전 이력과 비용을 관리하세요</p>
            </div>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
```

## 3. 로그인 페이지 (login.php)
```php
<?php
session_start();
include_once 'config/database.php';
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
            header("Location: pages/dashboard.php");
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

                <button type="submit" class="btn btn-primary">로그인</button>
            </form>

            <p><a href="register.php">회원가입</a></p>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
```

## 4. 대시보드 (pages/dashboard.php)
```php
<?php
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
```

## 5. 스타일시트 (assets/css/style.css)
```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header */
header {
    background: #2c3e50;
    color: white;
    padding: 1rem 0;
}

nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
}

.nav-links {
    display: flex;
    list-style: none;
    gap: 2rem;
}

.nav-links a {
    color: white;
    text-decoration: none;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

/* Hero Section */
.hero {
    background: linear-gradient(135deg, #3498db, #2c3e50);
    color: white;
    padding: 100px 0;
    text-align: center;
}

.hero h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.auth-buttons {
    margin: 2rem 0;
    gap: 1rem;
    display: flex;
    justify-content: center;
}

/* Features */
.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 4rem;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    color: #333;
}

/* Dashboard */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.stat-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Forms */
.auth-form {
    max-width: 400px;
    margin: 50px auto;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.alert {
    padding: 10px;
    margin-bottom: 1rem;
    border-radius: 5px;
}

.alert-error {
    background: #e74c3c;
    color: white;
}

/* Footer */
footer {
    background: #2c3e50;
    color: white;
    text-align: center;
    padding: 2rem 0;
    margin-top: 4rem;
}
```

## 6. JavaScript (assets/js/main.js)
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // 충전소 검색 기능
    const searchInput = document.getElementById('station-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchStations(this.value);
        });
    }

    // 예약 상태 업데이트
    const bookingCards = document.querySelectorAll('.booking-card');
    bookingCards.forEach(card => {
        card.addEventListener('click', function() {
            showBookingDetails(this.dataset.bookingId);
        });
    });
});

function searchStations(query) {
    if (query.length < 2) return;

    fetch(`/api/stations.php?search=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('검색 오류:', error);
        });
}

function displaySearchResults(stations) {
    const resultsContainer = document.getElementById('search-results');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '';

    stations.forEach(station => {
        const stationElement = createStationElement(station);
        resultsContainer.appendChild(stationElement);
    });
}

function createStationElement(station) {
    const div = document.createElement('div');
    div.className = 'station-card';
    div.innerHTML = `
<h3>${station.name}</h3>
<p>${station.address}</p>
<p>가격: ${station.price}원/시간</p>
<button class="btn btn-primary" onclick="bookStation(${station.id})">예약하기</button>
`;
return div;
}

function bookStation(stationId) {
if (!confirm('이 충전소를 예약하시겠습니까?')) return;

fetch('/api/bookings.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        station_id: stationId,
        action: 'create'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('예약이 완료되었습니다.');
        location.reload();
    } else {
        alert('예약에 실패했습니다: ' + data.message);
    }
})
.catch(error => {
    console.error('예약 오류:', error);
    alert('예약 중 오류가 발생했습니다.');
});
}
```

## 7. 데이터베이스 스키마
```sql
CREATE DATABASE egcharg;
USE egcharg;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE charging_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    price DECIMAL(10, 2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    station_id INT,
    start_time DATETIME,
    end_time DATETIME,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    total_cost DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (station_id) REFERENCES charging_stations(id)
);
```

## 설치 및 실행 방법
1. XAMPP 또는 WAMP 설치
2. 프로젝트를 htdocs 폴더에 복사
3. MySQL에서 데이터베이스 생성 및 스키마 실행
4. `config/database.php`에서 데이터베이스 설정 수정
5. 브라우저에서 `http://localhost/egcharg` 접속

## 주요 기능
- 사용자 회원가입/로그인
- 충전소 검색 및 조회
- 실시간 예약 시스템
- 사용자 대시보드
- 예약 관리

이 프로젝트는 전기차 충전소 관리의 기본 기능을 제공하며, 필요에 따라 기능을 확장할 수 있습니다.