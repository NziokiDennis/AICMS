CREATE DATABASE counseling_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE counseling_system;

-- Users
CREATE TABLE users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('STUDENT','COUNSELOR','ADMIN') NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Counselor Profiles
CREATE TABLE counselor_profiles (
  user_id BIGINT PRIMARY KEY,
  specialty VARCHAR(150),
  meeting_mode ENUM('IN_PERSON','VIDEO','PHONE'),
  bio TEXT,
  location VARCHAR(150),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Availability Slots
CREATE TABLE availability_slots (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  counselor_id BIGINT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  status ENUM('OPEN','BLOCKED') DEFAULT 'OPEN',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (counselor_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE(counselor_id, start_at, end_at)
) ENGINE=InnoDB;

-- Appointments
CREATE TABLE appointments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT NOT NULL,
  counselor_id BIGINT NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  status ENUM('PENDING','APPROVED','DECLINED','CANCELLED','COMPLETED') DEFAULT 'PENDING',
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (counselor_id) REFERENCES users(id),
  INDEX(counselor_id, start_time),
  INDEX(student_id, start_time)
) ENGINE=InnoDB;

-- Sessions
CREATE TABLE sessions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  appointment_id BIGINT UNIQUE NOT NULL,
  status ENUM('SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED') DEFAULT 'SCHEDULED',
  started_at DATETIME NULL,
  ended_at DATETIME NULL,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notes
CREATE TABLE notes (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  session_id BIGINT NOT NULL,
  counselor_id BIGINT NOT NULL,
  content TEXT NOT NULL,
  visibility ENUM('PRIVATE','PUBLISHED') DEFAULT 'PRIVATE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sessions(id),
  FOREIGN KEY (counselor_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Feedback
CREATE TABLE feedback (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT NOT NULL,
  counselor_id BIGINT NOT NULL,
  session_id BIGINT NOT NULL,
  rating INT CHECK(rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (counselor_id) REFERENCES users(id),
  FOREIGN KEY (session_id) REFERENCES sessions(id),
  UNIQUE(student_id, counselor_id, session_id)
) ENGINE=InnoDB;
