<?php
// api/reviews.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $station_id = $input['station_id'] ?? '';
    $content = trim($input['content'] ?? '');

    if (empty($station_id) || empty($content)) {
        echo json_encode(['success' => false, 'message' => '충전소 ID와 내용이 필요합니다.']);
        exit;
    }

    if (mb_strlen($content) > 100) {
        echo json_encode(['success' => false, 'message' => '한줄평은 100자 이내로 작성해주세요.']);
        exit;
    }

    try {

        // 중복 작성 방지 (10분에 한 번만)
        $stmt = $db->prepare("SELECT id FROM station_reviews WHERE user_id = ? AND station_id = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt->execute([$user_id, $station_id]);

        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '10분에 한 번만 한줄평을 작성할 수 있습니다.']);
            exit;
        }

        // 한줄평 저장
        $stmt = $db->prepare("INSERT INTO station_reviews (user_id, station_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $station_id, $content]);

        echo json_encode(['success' => true, 'message' => '한줄평이 등록되었습니다.']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
    }

} elseif ($method === 'GET') {
    // 특정 충전소의 한줄평 목록 조회
    $station_id = $_GET['station_id'] ?? '';
    $page = intval($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    if (empty($station_id)) {
        echo json_encode(['success' => false, 'message' => '충전소 ID가 필요합니다.']);
        exit;
    }

    try {

        // 한줄평 목록 조회 (본인 글 표시를 위해 user_id 확인)
        $stmt = $db->prepare("
            SELECT sr.id, sr.content, sr.created_at, u.name,
                   CASE WHEN sr.user_id = ? THEN 1 ELSE 0 END as is_my_review
            FROM station_reviews sr 
            JOIN users u ON sr.user_id = u.id 
            WHERE sr.station_id = ? 
            ORDER BY sr.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $station_id, $limit, $offset]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 전체 개수 조회
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM station_reviews WHERE station_id = ?");
        $stmt->execute([$station_id]);
        $total = $stmt->fetch()['total'];

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'total' => $total,
            'page' => $page,
            'has_more' => ($page * $limit) < $total
        ]);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.check']);
    }

} elseif ($method === 'DELETE') {
    // 한줄평 삭제
    $input = json_decode(file_get_contents('php://input'), true);
    $review_id = $input['review_id'] ?? '';

    if (empty($review_id)) {
        echo json_encode(['success' => false, 'message' => '한줄평 ID가 필요합니다.']);
        exit;
    }

    try {

        // 본인이 작성한 한줄평인지 확인
        $stmt = $db->prepare("SELECT user_id FROM station_reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();

        if (!$review) {
            echo json_encode(['success' => false, 'message' => '한줄평을 찾을 수 없습니다.']);
            exit;
        }

        if ($review['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => '본인이 작성한 한줄평만 삭제할 수 있습니다.']);
            exit;
        }

        // 한줄평 삭제
        $stmt = $db->prepare("DELETE FROM station_reviews WHERE id = ? AND user_id = ?");
        $stmt->execute([$review_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => '한줄평이 삭제되었습니다.']);
        } else {
            echo json_encode(['success' => false, 'message' => '한줄평 삭제에 실패했습니다.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '지원하지 않는 요청 방식입니다.']);
}
?>