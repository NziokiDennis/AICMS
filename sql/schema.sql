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


USE counseling_system;

-- Insert test users (passwords are all 'password123')
INSERT INTO users (role, name, email, password_hash, phone) VALUES
('ADMIN', 'System Administrator', 'admin@counseling.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-0001'),
('STUDENT', 'John Smith', 'john.smith@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-1001'),
('STUDENT', 'Emily Johnson', 'emily.johnson@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-1002'),
('STUDENT', 'Michael Davis', 'michael.davis@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-1003'),
('COUNSELOR', 'Dr. Sarah Wilson', 'dr.wilson@counseling.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-2001'),
('COUNSELOR', 'Dr. Robert Chen', 'dr.chen@counseling.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-2002'),
('COUNSELOR', 'Dr. Maria Garcia', 'dr.garcia@counseling.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1-555-2003');

-- Insert counselor profiles
INSERT INTO counselor_profiles (user_id, specialty, meeting_mode, bio, location) VALUES
(5, 'Anxiety & Depression', 'VIDEO', 'Licensed clinical psychologist specializing in anxiety disorders and depression treatment for university students.', 'Psychology Building, Room 201'),
(6, 'Academic Stress', 'IN_PERSON', 'Educational counselor with 10+ years experience helping students manage academic pressure and study habits.', 'Student Services Center, Room 105'),
(7, 'Relationships & Social Issues', 'PHONE', 'Marriage and family therapist focusing on relationship counseling and social anxiety in young adults.', 'Counseling Center, Room 302');

-- Insert availability slots (next week)
INSERT INTO availability_slots (counselor_id, start_at, end_at, status) VALUES
-- Dr. Sarah Wilson (ID: 5)
(5, '2024-09-02 09:00:00', '2024-09-02 10:00:00', 'OPEN'),
(5, '2024-09-02 10:00:00', '2024-09-02 11:00:00', 'OPEN'),
(5, '2024-09-02 14:00:00', '2024-09-02 15:00:00', 'OPEN'),
(5, '2024-09-03 09:00:00', '2024-09-03 10:00:00', 'OPEN'),
(5, '2024-09-03 11:00:00', '2024-09-03 12:00:00', 'OPEN'),

-- Dr. Robert Chen (ID: 6)
(6, '2024-09-02 10:00:00', '2024-09-02 11:00:00', 'OPEN'),
(6, '2024-09-02 11:00:00', '2024-09-02 12:00:00', 'OPEN'),
(6, '2024-09-02 13:00:00', '2024-09-02 14:00:00', 'OPEN'),
(6, '2024-09-04 09:00:00', '2024-09-04 10:00:00', 'OPEN'),
(6, '2024-09-04 15:00:00', '2024-09-04 16:00:00', 'OPEN'),

-- Dr. Maria Garcia (ID: 7)
(7, '2024-09-02 13:00:00', '2024-09-02 14:00:00', 'OPEN'),
(7, '2024-09-02 15:00:00', '2024-09-02 16:00:00', 'OPEN'),
(7, '2024-09-03 10:00:00', '2024-09-03 11:00:00', 'OPEN'),
(7, '2024-09-05 14:00:00', '2024-09-05 15:00:00', 'OPEN'),
(7, '2024-09-05 16:00:00', '2024-09-05 17:00:00', 'OPEN');

-- Insert sample appointments
INSERT INTO appointments (student_id, counselor_id, start_time, end_time, status, message) VALUES
(2, 5, '2024-09-02 09:00:00', '2024-09-02 10:00:00', 'APPROVED', 'Experiencing anxiety about upcoming exams'),
(3, 6, '2024-09-02 10:00:00', '2024-09-02 11:00:00', 'PENDING', 'Need help with time management and study strategies'),
(4, 7, '2024-09-02 13:00:00', '2024-09-02 14:00:00', 'APPROVED', 'Having relationship issues with roommates');

-- Insert sample sessions
INSERT INTO sessions (appointment_id, status) VALUES
(1, 'SCHEDULED'),
(3, 'SCHEDULED');

-- Insert sample notes (for testing)
INSERT INTO notes (session_id, counselor_id, content, visibility) VALUES
(1, 5, 'Initial consultation completed. Student shows signs of mild anxiety. Recommended breathing exercises and scheduled follow-up.', 'PUBLISHED'),
(2, 7, 'First session focused on communication skills. Student is receptive to feedback.', 'PRIVATE');

-- Insert sample feedback
INSERT INTO feedback (student_id, counselor_id, session_id, rating, comment) VALUES
(2, 5, 1, 5, 'Dr. Wilson was very understanding and provided excellent coping strategies. Highly recommend!');

-- =========================================================
-- 0) Choose counselors and the target date window
-- =========================================================
SET @from := '2025-08-25 00:00:00';
SET @to   := '2025-09-07 23:59:59';

-- Your counselor IDs (as per your mapping)
SET @c_sarah := 5;  -- Dr. Sarah Wilson
SET @c_chen  := 6;  -- Dr. Robert Chen
SET @c_maria := 7;  -- Dr. Maria Garcia

-- =========================================================
-- 1) FREE UP any existing slots in the window by setting OPEN
--    (if they already exist but are HOLD/BOOKED/BLOCKED etc.)
-- =========================================================
UPDATE availability_slots
SET status = 'OPEN'
WHERE counselor_id IN (@c_sarah, @c_chen, @c_maria)
  AND start_at BETWEEN @from AND @to;

-- =========================================================
-- 2) ADD new OPEN slots for the next 2 weeks (INSERT IGNORE)
--    If a slot already exists (unique key), it will be ignored.
--    We'll normalize them to OPEN in step 3.
-- =========================================================
INSERT IGNORE INTO availability_slots (counselor_id, start_at, end_at, status) VALUES
-- ========== Week 1 (Aug 25–31, 2025) ==========
-- Dr. Sarah (Mon, Wed, Fri)
(@c_sarah, '2025-08-25 09:00:00', '2025-08-25 10:00:00', 'OPEN'),
(@c_sarah, '2025-08-25 10:00:00', '2025-08-25 11:00:00', 'OPEN'),
(@c_sarah, '2025-08-27 14:00:00', '2025-08-27 15:00:00', 'OPEN'),
(@c_sarah, '2025-08-29 09:00:00', '2025-08-29 10:00:00', 'OPEN'),

-- Dr. Chen (Tue, Thu)
(@c_chen,  '2025-08-26 10:00:00', '2025-08-26 11:00:00', 'OPEN'),
(@c_chen,  '2025-08-26 11:00:00', '2025-08-26 12:00:00', 'OPEN'),
(@c_chen,  '2025-08-28 13:00:00', '2025-08-28 14:00:00', 'OPEN'),
(@c_chen,  '2025-08-28 15:00:00', '2025-08-28 16:00:00', 'OPEN'),

-- Dr. Maria (Mon, Thu)
(@c_maria, '2025-08-25 15:00:00', '2025-08-25 16:00:00', 'OPEN'),
(@c_maria, '2025-08-28 09:00:00', '2025-08-28 10:00:00', 'OPEN'),
(@c_maria, '2025-08-28 14:00:00', '2025-08-28 15:00:00', 'OPEN'),

-- ========== Week 2 (Sep 1–7, 2025) ==========
-- Dr. Sarah (Mon, Tue)
(@c_sarah, '2025-09-01 09:00:00', '2025-09-01 10:00:00', 'OPEN'),
(@c_sarah, '2025-09-01 10:00:00', '2025-09-01 11:00:00', 'OPEN'),
(@c_sarah, '2025-09-02 14:00:00', '2025-09-02 15:00:00', 'OPEN'),

-- Dr. Chen (Wed, Fri)
(@c_chen,  '2025-09-03 10:00:00', '2025-09-03 11:00:00', 'OPEN'),
(@c_chen,  '2025-09-03 11:00:00', '2025-09-03 12:00:00', 'OPEN'),
(@c_chen,  '2025-09-05 13:00:00', '2025-09-05 14:00:00', 'OPEN'),

-- Dr. Maria (Tue, Thu)
(@c_maria, '2025-09-02 10:00:00', '2025-09-02 11:00:00', 'OPEN'),
(@c_maria, '2025-09-04 16:00:00', '2025-09-04 17:00:00', 'OPEN');

-- =========================================================
-- 3) Normalize: force all these rows to OPEN within the window
--    (covers cases where INSERT IGNORE skipped existing rows)
-- =========================================================
UPDATE availability_slots
SET status = 'OPEN'
WHERE counselor_id IN (@c_sarah, @c_chen, @c_maria)
  AND start_at BETWEEN @from AND @to;
  
  -- Update existing sample appointments to 2025
UPDATE appointments
SET start_time = CASE id
    WHEN 1 THEN '2025-09-02 09:00:00'
    WHEN 2 THEN '2025-09-02 10:00:00'
    WHEN 3 THEN '2025-09-02 13:00:00'
END,
end_time = CASE id
    WHEN 1 THEN '2025-09-02 10:00:00'
    WHEN 2 THEN '2025-09-02 11:00:00'
    WHEN 3 THEN '2025-09-02 14:00:00'
END
WHERE id IN (1, 2, 3);