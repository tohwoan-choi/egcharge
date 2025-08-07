CREATE DATABASE egcharge;

CREATE USER 'egcharge'@'%' IDENTIFIED BY '비밀번호';
CREATE USER 'egcharge'@'localhost' IDENTIFIED BY '비밀번호';
GRANT ALL PRIVILEGES ON egcharge.* TO 'egcharge'@'%';


USE egcharge;

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