-- Starter (vulnerable) application seed data.
-- WARNING: plaintext and weak hashes are used ON PURPOSE to demonstrate the
-- insecure baseline. Fictitious data only.
INSERT INTO users (matric_no, full_name, email, password_hash, role, phone) VALUES
 ('2019/1-0001', 'Admin User',        'admin@ftminna.edu.ng', 'admin', 'admin',   '08000000001'),
 ('2022/1-10111', 'Amina Yusuf',      'amina.yusuf@ftminna.edu.ng', 'Student@1234!', 'student', '08000000002'),
 ('2022/1-10112', 'Tunde Bakare',     'tunde.bakare@ftminna.edu.ng', 'Student@1234!', 'student', '08000000003'),
 ('2022/1-10113', 'Ngozi Okafor',     'ngozi.okafor@ftminna.edu.ng', 'Student@1234!', 'student', '08000000004')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO courses (code, title, credit_units, capacity, description) VALUES
 ('IFT 542', 'Information Security',           3, 60, 'Security assessment and hardening'),
 ('COS 101', 'Introduction to Computing',      3, 60, 'Foundations of computing'),
 ('COS 201', 'Data Structures',                3, 60, 'Linear and non-linear data structures'),
 ('MAT 111', 'Engineering Mathematics I',      3, 60, 'Calculus and algebra'),
 ('PHY 101', 'General Physics I',              2, 60, 'Mechanics and heat'),
 ('GST 105', 'Use of English',                 2, 60, 'Communication skills')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT IGNORE INTO enrolments (user_id, course_id, status) VALUES
 (2, 1, 'enrolled'),
 (3, 1, 'enrolled');
