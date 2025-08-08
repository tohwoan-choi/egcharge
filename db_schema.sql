CREATE
DATABASE egcharge;

CREATE
USER 'egcharge'@'%' IDENTIFIED BY '비밀번호';
CREATE
USER 'egcharge'@'localhost' IDENTIFIED BY '비밀번호';
GRANT ALL PRIVILEGES ON egcharge.* TO
'egcharge'@'%';


USE egcharge;

CREATE DATABASE egcharge;


DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS charging_stations;
DROP TABLE IF EXISTS eg_charging_stations;
-- EGCharge 데이터베이스 새로 생성 스크립트

-- 1. 기존 테이블 삭제 (주의: 데이터 백업 후 실행)
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS charging_stations;
DROP TABLE IF EXISTS eg_charging_stations;

-- 2. 사용자 테이블 (기존 유지 또는 새로 생성)
CREATE TABLE IF NOT EXISTS users (
                                     id INT AUTO_INCREMENT PRIMARY KEY,
                                     name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- 3. 새로운 충전소 테이블 생성 (KEPCO 표준)
CREATE TABLE eg_charging_stations (
                                      id             INT AUTO_INCREMENT UNIQUE KEY,
                                      offer_cd       VARCHAR(50)  NOT NULL COMMENT '충전소 코드 KEPCO 제공',
                                      csId           VARCHAR(50)  NOT NULL COMMENT '충전소 ID KEPCO 제공',
                                      csNm           VARCHAR(100) NOT NULL COMMENT '충전소 이름 KEPCO 제공',
                                      addr           VARCHAR(255) NOT NULL COMMENT '충전소 주소 KEPCO 제공',
                                      lat            DECIMAL(10, 8) COMMENT '위도 KEPCO 제공',
                                      lngi           DECIMAL(11, 8) COMMENT '경도 KEPCO 제공',
                                      cpId           VARCHAR(50)  NOT NULL COMMENT '충전기 ID KEPCO 제공',
                                      charegTp       TINYINT COMMENT '충전기 타입 코드 KEPCO 제공 1:완속 2:급속',
                                      charegTpNm     VARCHAR(50) COMMENT '충전기 타입명',
                                      cpNm           VARCHAR(100) NOT NULL COMMENT '충전기 이름 KEPCO 제공',
                                      cpStat         TINYINT COMMENT '충전기 상태 코드 KEPCO 제공 1:충전가능 2:충전중 3:고장/점검 4:통신장애 5:통신미연결',
                                      cpStatNm       VARCHAR(50) COMMENT '충전기 상태명',
                                      cpTp           TINYINT COMMENT '충전기 커넥터 타입 코드 KEPCO 제공',
                                      cpTpNm         VARCHAR(50) COMMENT '충전기 커넥터 타입명',
                                      statUpdatetime VARCHAR(50) COMMENT '충전기 상태 갱신 시각 KEPCO 제공',
                                      created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                      updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                      PRIMARY KEY (offer_cd, csId, cpId),
                                      INDEX idx_id (id),
                                      INDEX idx_csNm (csNm),
                                      INDEX idx_addr (addr),
                                      INDEX idx_cpStat (cpStat),
                                      INDEX idx_charegTp (charegTp),
                                      INDEX idx_location (lat, lngi)
);

-- 4. 새로운 예약 테이블 생성
CREATE TABLE bookings (
                          id                INT AUTO_INCREMENT PRIMARY KEY,
                          user_id           INT NOT NULL,
                          station_offer_cd  VARCHAR(50) NOT NULL COMMENT '충전소 코드',
                          station_csId      VARCHAR(50) NOT NULL COMMENT '충전소 ID',
                          station_cpId      VARCHAR(50) NOT NULL COMMENT '충전기 ID',
                          start_time        DATETIME NOT NULL COMMENT '예약 시작 시간',
                          end_time          DATETIME COMMENT '예약 종료 시간',
                          status            ENUM('active', 'completed', 'cancelled') DEFAULT 'active' COMMENT '예약 상태',
                          total_cost        DECIMAL(10, 2) DEFAULT 0.00 COMMENT '총 비용',
                          created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                          FOREIGN KEY (station_offer_cd, station_csId, station_cpId)
                              REFERENCES eg_charging_stations(offer_cd, csId, cpId) ON DELETE CASCADE,

                          INDEX idx_user_id (user_id),
                          INDEX idx_station (station_offer_cd, station_csId, station_cpId),
                          INDEX idx_status (status),
                          INDEX idx_start_time (start_time)
);

-- 5. 샘플 데이터 삽입
INSERT INTO eg_charging_stations (
    offer_cd, csId, csNm, addr, lat, lngi, cpId,
    charegTp, charegTpNm, cpNm, cpStat, cpStatNm,
    cpTp, cpTpNm, statUpdatetime
) VALUES
-- 강남역 충전소
('KEPCO001', 'CS001', '강남역 충전소', '서울시 강남구 테헤란로 123', 37.498095, 127.027610, 'CP001',
 2, '급속', '강남역 급속충전기 1호', 1, '충전가능', 7, 'DC콤보', '2025-01-08 10:30:00'),

('KEPCO001', 'CS001', '강남역 충전소', '서울시 강남구 테헤란로 123', 37.498095, 127.027610, 'CP002',
 2, '급속', '강남역 급속충전기 2호', 1, '충전가능', 5, 'DC차데모', '2025-01-08 10:30:00'),

('KEPCO001', 'CS001', '강남역 충전소', '서울시 강남구 테헤란로 123', 37.498095, 127.027610, 'CP003',
 1, '완속', '강남역 완속충전기 1호', 1, '충전가능', 1, 'B타입(5핀)', '2025-01-08 10:30:00'),

-- 홍대입구 충전소
('KEPCO002', 'CS002', '홍대입구 충전소', '서울시 마포구 양화로 45', 37.556489, 126.923706, 'CP004',
 1, '완속', '홍대입구 완속충전기 1호', 1, '충전가능', 1, 'B타입(5핀)', '2025-01-08 10:25:00'),

('KEPCO002', 'CS002', '홍대입구 충전소', '서울시 마포구 양화로 45', 37.556489, 126.923706, 'CP005',
 2, '급속', '홍대입구 급속충전기 1호', 2, '충전중', 7, 'DC콤보', '2025-01-08 10:25:00'),

-- 잠실 충전소
('KEPCO003', 'CS003', '잠실 충전소', '서울시 송파구 올림픽로 300', 37.513294, 127.100052, 'CP006',
 2, '급속', '잠실 급속충전기 1호', 1, '충전가능', 8, 'DC차데모+DC콤보', '2025-01-08 10:20:00'),

('KEPCO003', 'CS003', '잠실 충전소', '서울시 송파구 올림픽로 300', 37.513294, 127.100052, 'CP007',
 1, '완속', '잠실 완속충전기 1호', 3, '고장/점검', 2, 'C타입(5핀)', '2025-01-08 10:20:00'),

('KEPCO003', 'CS003', '잠실 충전소', '서울시 송파구 올림픽로 300', 37.513294, 127.100052, 'CP008',
 2, '급속', '잠실 급속충전기 2호', 1, '충전가능', 7, 'DC콤보', '2025-01-08 10:20:00'),

-- 인천공항 충전소
('KEPCO004', 'CS004', '인천공항 충전소', '인천시 중구 공항로 272', 37.449441, 126.450631, 'CP009',
 2, '급속', '인천공항 급속충전기 1호', 1, '충전가능', 7, 'DC콤보', '2025-01-08 10:15:00'),

('KEPCO004', 'CS004', '인천공항 충전소', '인천시 중구 공항로 272', 37.449441, 126.450631, 'CP010',
 2, '급속', '인천공항 급속충전기 2호', 1, '충전가능', 5, 'DC차데모', '2025-01-08 10:15:00'),

('KEPCO004', 'CS004', '인천공항 충전소', '인천시 중구 공항로 272', 37.449441, 126.450631, 'CP011',
 1, '완속', '인천공항 완속충전기 1호', 1, '충전가능', 6, 'AC3상', '2025-01-08 10:15:00'),

-- 부산역 충전소
('KEPCO005', 'CS005', '부산역 충전소', '부산시 동구 중앙대로 206', 35.116777, 129.041610, 'CP012',
 2, '급속', '부산역 급속충전기 1호', 4, '통신장애', 7, 'DC콤보', '2025-01-08 09:45:00'),

('KEPCO005', 'CS005', '부산역 충전소', '부산시 동구 중앙대로 206', 35.116777, 129.041610, 'CP013',
 1, '완속', '부산역 완속충전기 1호', 1, '충전가능', 6, 'AC3상', '2025-01-08 09:45:00'),

-- 대구 동성로 충전소
('KEPCO006', 'CS006', '대구 동성로 충전소', '대구시 중구 동성로2길 지하 1층', 35.869083, 128.593056, 'CP014',
 2, '급속', '동성로 급속충전기 1호', 1, '충전가능', 7, 'DC콤보', '2025-01-08 09:30:00'),

('KEPCO006', 'CS006', '대구 동성로 충전소', '대구시 중구 동성로2길 지하 1층', 35.869083, 128.593056, 'CP015',
 2, '급속', '동성로 급속충전기 2호', 1, '충전가능', 8, 'DC차데모+DC콤보', '2025-01-08 09:30:00'),

-- 광주 충장로 충전소
('KEPCO007', 'CS007', '광주 충장로 충전소', '광주시 동구 충장로 1번지', 35.149948, 126.918388, 'CP016',
 1, '완속', '충장로 완속충전기 1호', 1, '충전가능', 1, 'B타입(5핀)', '2025-01-08 09:15:00'),

('KEPCO007', 'CS007', '광주 충장로 충전소', '광주시 동구 충장로 1번지', 35.149948, 126.918388, 'CP017',
 2, '급속', '충장로 급속충전기 1호', 1, '충전가능', 5, 'DC차데모', '2025-01-08 09:15:00'),

-- 제주 신제주 충전소
('KEPCO008', 'CS008', '제주 신제주 충전소', '제주시 연동 신제주로터리', 33.486958, 126.488611, 'CP018',
 2, '급속', '신제주 급속충전기 1호', 1, '충전가능', 7, 'DC콤보', '2025-01-08 09:00:00'),

('KEPCO008', 'CS008', '제주 신제주 충전소', '제주시 연동 신제주로터리', 33.486958, 126.488611, 'CP019',
 1, '완속', '신제주 완속충전기 1호', 5, '통신미연결', 2, 'C타입(5핀)', '2025-01-08 09:00:00'),

-- 울산 태화강 충전소
('KEPCO009', 'CS009', '울산 태화강 충전소', '울산시 중구 태화강국가정원길 154', 35.538472, 129.337778, 'CP020',
 2, '급속', '태화강 급속충전기 1호', 1, '충전가능', 8, 'DC차데모+DC콤보', '2025-01-08 08:45:00'),

('KEPCO009', 'CS009', '울산 태화강 충전소', '울산시 중구 태화강국가정원길 154', 35.538472, 129.337778, 'CP021',
 1, '완속', '태화강 완속충전기 1호', 1, '충전가능', 3, 'BC타입(5핀)', '2025-01-08 08:45:00');

-- 6. 샘플 사용자 데이터 (테스트용)
INSERT INTO users (name, email, password, phone) VALUES
                                                     ('김철수', 'test@egcharge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '010-1234-5678'),
                                                     ('이영희', 'user2@egcharge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '010-9876-5432');

-- 7. 샘플 예약 데이터 (테스트용)
INSERT INTO bookings (user_id, station_offer_cd, station_csId, station_cpId, start_time, end_time, status, total_cost) VALUES
                                                                                                                           (1, 'KEPCO001', 'CS001', 'CP001', '2025-01-08 14:00:00', '2025-01-08 16:00:00', 'completed', 1000.00),
                                                                                                                           (1, 'KEPCO002', 'CS002', 'CP005', '2025-01-09 10:00:00', '2025-01-09 12:00:00', 'active', 1000.00),
                                                                                                                           (2, 'KEPCO003', 'CS003', 'CP006', '2025-01-08 18:00:00', '2025-01-08 19:00:00', 'completed', 500.00);

-- 8. 데이터 확인 쿼리
SELECT
    csNm as '충전소명',
    COUNT(*) as '충전기수',
    SUM(CASE WHEN cpStat = 1 THEN 1 ELSE 0 END) as '사용가능',
    SUM(CASE WHEN cpStat = 2 THEN 1 ELSE 0 END) as '충전중',
    SUM(CASE WHEN cpStat = 3 THEN 1 ELSE 0 END) as '고장점검'
FROM eg_charging_stations
GROUP BY csNm, addr
ORDER BY csNm;

-- 9. 예약 통계 확인
SELECT
    u.name as '사용자명',
    COUNT(b.id) as '총예약수',
    SUM(CASE WHEN b.status = 'active' THEN 1 ELSE 0 END) as '활성예약',
    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as '완료예약',
    SUM(b.total_cost) as '총사용금액'
FROM users u
         LEFT JOIN bookings b ON u.id = b.user_id
GROUP BY u.id, u.name;

-- 10. 테이블 정보 확인
SHOW CREATE TABLE eg_charging_stations;
SHOW CREATE TABLE bookings;



-- 방문로그 테이블 생성
CREATE TABLE visit_logs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NULL,
                            session_id VARCHAR(255) NOT NULL,
                            ip_address VARCHAR(45) NOT NULL,
                            user_agent TEXT,
                            page_url VARCHAR(500),
                            page_title VARCHAR(255),
                            referer VARCHAR(500),
                            device_type ENUM('desktop', 'mobile', 'tablet'),
                            browser VARCHAR(100),
                            os VARCHAR(100),
                            country VARCHAR(100),
                            city VARCHAR(100),
                            visit_duration INT DEFAULT 0,
                            visit_date DATE,
                            visit_hour TINYINT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                            INDEX idx_user_id (user_id),
                            INDEX idx_session_id (session_id),
                            INDEX idx_ip_address (ip_address),
                            INDEX idx_created_at (created_at),
                            INDEX idx_visit_date (visit_date),
                            INDEX idx_visit_hour (visit_hour),
                            INDEX idx_page_url (page_url(100)),

                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 방문 통계 요약 테이블 (성능 최적화용)
DROP TABLE IF EXISTS visit_stats;

CREATE TABLE visit_stats (
                             id INT AUTO_INCREMENT PRIMARY KEY,
                             stat_date DATE NOT NULL COMMENT '통계 날짜',
                             stat_hour TINYINT COMMENT '시간 (0-23, 시간별 통계에서만 사용)',
                             stat_type ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL COMMENT '통계 유형',
                             total_visits INT DEFAULT 0 COMMENT '총 방문수',
                             unique_visitors INT DEFAULT 0 COMMENT '순 방문자수',
                             page_views INT DEFAULT 0 COMMENT '페이지뷰',
                             avg_duration DECIMAL(10,2) DEFAULT 0 COMMENT '평균 체류시간',
                             bounce_rate DECIMAL(5,2) DEFAULT 0 COMMENT '이탈률',
                             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                             updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- 하나의 UNIQUE KEY로 통합 (stat_hour가 NULL 허용)
                             UNIQUE KEY unique_stat (stat_type, stat_date, stat_hour),

                             INDEX idx_stat_type (stat_type),
                             INDEX idx_stat_date (stat_date),
                             INDEX idx_stat_type_date (stat_type, stat_date)
);

CREATE TABLE page_stats (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            page_url VARCHAR(500) NOT NULL,
                            page_title VARCHAR(255),
                            stat_date DATE NOT NULL,
                            total_visits INT DEFAULT 0,
                            unique_visitors INT DEFAULT 0,
                            avg_duration DECIMAL(10,2) DEFAULT 0,
                            bounce_rate DECIMAL(5,2) DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                            UNIQUE KEY unique_page_stat (page_url(100), stat_date),
                            INDEX idx_stat_date (stat_date),
                            INDEX idx_total_visits (total_visits)
);


drop table egcharge_agent_job;
CREATE TABLE IF NOT EXISTS egcharge_agent_job (
                                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                                 agent_id VARCHAR(200) NOT NULL,
    agent_ip VARCHAR(100) NOT NULL,
    job VARCHAR(200),
    job_execute_time DATETIME,
    job_status VARCHAR(100),
    job_message VARCHAR(200)
    );
