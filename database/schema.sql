-- Sity Cloud TeamFlow database schema + seed data
-- طور بواسطة youssef r. عشان يبقى عندك نظام جاهز مع نقاط الأداء وتقارير المراجعة

-- لو عايز تستخدم اسم قاعدة بيانات مختلف عدل السطرين الجايين
CREATE DATABASE IF NOT EXISTS teamflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE teamflow;

DROP TABLE IF EXISTS task_reports;
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
  performance_points INT UNSIGNED DEFAULT 0,
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
  report_requirement ENUM('required','optional') DEFAULT 'required',
  start_date DATE,
  due_date DATE,
  completed_at TIMESTAMP NULL DEFAULT NULL,
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

CREATE TABLE task_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  submitted_by INT NOT NULL,
  report_text TEXT NOT NULL,
  attachment_path VARCHAR(255) DEFAULT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  review_status ENUM('pending','approved','needs_changes') DEFAULT 'pending',
  reviewed_by INT DEFAULT NULL,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  review_note TEXT,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الأدمن الأساسي (الباسورد: Admin@123)
INSERT INTO users (name, email, password, role, status, performance_points)
VALUES
  ('System Admin', 'admin@example.com', '$2y$12$4TEqN7uwdMqKZhHRsIO5g.U6efSyAPhNVE1x.Defh1GWyk2JlZL42', 'admin', 'active', 50);

-- عضو تجريبي يشيل نقاط ويستقبل مهام
INSERT INTO users (name, email, password, role, status, performance_points)
VALUES
  ('سريع التنفيذ', 'fast.member@example.com', '$2y$10$0jmK4rKArlTc95xJMu38lONd4VXx/seUa.Z7Aj/QWqdAZr1eD6N9K', 'member', 'active', 25);

-- بيانات تجريبية للمهام (مع خيار التقرير)
INSERT INTO tasks (title, description, assigned_to, status, priority, created_by, report_requirement, start_date, due_date)
VALUES
  ('تهيئة بيئة Sity Cloud', 'ارفع الخدمات التأسيسية وشارك تقرير نشر كامل.', 2, 'in_progress', 'high', 1, 'required', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
  ('مراجعة دليل العمل الداخلي', 'اقرأ التحديثات وشارك ملخص. التقرير اختياري هنا.', 2, 'pending', 'medium', 1, 'optional', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY));

INSERT INTO task_comments (task_id, user_id, comment)
VALUES
  (1, 1, 'متابعة يومية مع الفريق'),
  (1, 2, 'جارى الانتهاء من الخطوات الأولى.');

INSERT INTO task_reports (task_id, submitted_by, report_text, attachment_path, review_status)
VALUES
  (1, 2, 'تم إعداد البنية ورفع ملف التوثيق.', NULL, 'pending');
