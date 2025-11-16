-- TeamFlow database schema + seed data
-- شغل الـ DB كله في ملف واحد عشان تقدر تنسخه على طول في MySQL

-- لو عايز تستخدم اسم قاعدة بيانات مختلف، عدّل السطرين الجايين
CREATE DATABASE IF NOT EXISTS teamflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE teamflow;

DROP TABLE IF EXISTS task_comments;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'member') DEFAULT 'member',
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  assigned_to INT NOT NULL,
  status ENUM('pending','in_progress','done') DEFAULT 'pending',
  priority ENUM('low','medium','high') DEFAULT 'medium',
  created_by INT NOT NULL,
  start_date DATE,
  due_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  comment TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- يوزر الأدمن الأساسي (الباسورد: Admin@123)
INSERT INTO users (name, email, password, role, status)
VALUES
  ('System Admin', 'admin@example.com', '$2y$12$4TEqN7uwdMqKZhHRsIO5g.U6efSyAPhNVE1x.Defh1GWyk2JlZL42', 'admin', 'active');

-- بيانات تجريبية للمهام (اختياري - احذفها لو مش محتاجها)
INSERT INTO tasks (title, description, assigned_to, status, priority, created_by, start_date, due_date)
VALUES
  ('إعداد حسابات الفريق', 'تأكد إن كل الأعضاء ليهم صلاحيات مناسبة.', 1, 'in_progress', 'high', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY)),
  ('مراجعة الباك لوج', 'راجع المهام القديمة وحدّث الأولويات.', 1, 'pending', 'medium', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY));

INSERT INTO task_comments (task_id, user_id, comment)
VALUES
  (1, 1, 'تم تجهيز معظم الحسابات، فاضل مراجعة الصلاحيات.');
