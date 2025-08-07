CREATE DATABASE egcharge;

CREATE USER 'egcharge'@'%' IDENTIFIED BY '비밀번호';
CREATE USER 'egcharge'@'localhost' IDENTIFIED BY '비밀번호';
GRANT ALL PRIVILEGES ON egcharge.* TO 'egcharge'@'%';


USE egcharge;

CREATE DATABASE egcharge;
USE egcharge;


DROP TABLE IF EXISTS users;
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(100) NOT NULL,
                       email VARCHAR(100) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       phone VARCHAR(20),
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS charging_stations;
CREATE TABLE charging_stations (
                                   id INT AUTO_INCREMENT PRIMARY KEY,
                                   name VARCHAR(100) NOT NULL,
                                   address VARCHAR(255) NOT NULL,
                                   latitude DECIMAL(10, 8),
                                   longitude DECIMAL(11, 8),
                                   price DECIMAL(10, 2) DEFAULT 0.00,
                                   status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
                                   capacity INT DEFAULT 1,
                                   connector_type VARCHAR(50),
                                   charging_speed VARCHAR(50),
                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
drop table if exists bookings;
CREATE TABLE bookings (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          user_id INT NOT NULL,
                          station_id INT NOT NULL,
                          start_time DATETIME NOT NULL,
                          end_time DATETIME,
                          status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
                          total_cost DECIMAL(10, 2) DEFAULT 0.00,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                          FOREIGN KEY (station_id) REFERENCES charging_stations(id) ON DELETE CASCADE
);

-- 인덱스 추가
CREATE INDEX idx_bookings_user_id ON bookings(user_id);
CREATE INDEX idx_bookings_station_id ON bookings(station_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_stations_status ON charging_stations(status);

-- 샘플 데이터
INSERT INTO charging_stations (name, address, latitude, longitude, price, connector_type, charging_speed) VALUES
                                                                                                              ('강남역 충전소', '서울시 강남구 테헤란로 123', 37.498095, 127.027610, 300.00, 'Type2', '50kW'),
                                                                                                              ('홍대입구 충전소', '서울시 마포구 양화로 45', 37.556489, 126.923706, 250.00, 'CHAdeMO', '100kW'),
                                                                                                              ('잠실 충전소', '서울시 송파구 올림픽로 300', 37.513294, 127.100052, 280.00, 'CCS', '75kW'),
                                                                                                              ('인천공항 충전소', '인천시 중구 공항로 272', 37.449441, 126.450631, 350.00, 'Type2', '150kW'),
                                                                                                              ('부산역 충전소', '부산시 동구 중앙대로 206', 35.116777, 129.041610, 320.00, 'CHAdeMO', '120kW');