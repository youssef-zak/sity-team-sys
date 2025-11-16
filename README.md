# TeamFlow – نظام إدارة فريق خفيف

واجهة بسيطة لكن مليانة شغل محترم لإدارة الفريق ومتابعة المهام. مبني بـ **PHP + MySQL + Bootstrap**، ومقسم لصفحتين رئيسيتين (login + dashboard) مع شوية أكشن فايلز بتنظم الدنيا.

## 🚀 المميزات
- تسجيل دخول آمن (password_hash/password_verify + Sessions).
- صلاحيات واضحة: مدير يقدر يعمل كل حاجة، عضو يشوف مهامه بس.
- إدارة فريق (إضافة، تعديل، حذف، تفعيل/تعطيل حساب).
- إدارة مهام كاملة مع أولويات، تواريخ تسليم، وتعليقات.
- لوحات إحصائيات سريعة توضح حالة الفريق وعدد المهام.
- بنية ملفات منظمة وسهلة التوسيع لأي مستقبل أكبر.

## 📂 هيكلة الملفات
```
project/
├── assets/
│   └── css/styles.css
├── config/
│   └── db.php
├── includes/
│   ├── auth.php
│   ├── footer.php
│   └── header.php
├── actions/
│   ├── add_comment.php
│   ├── add_task.php
│   ├── add_user.php
│   ├── delete_task.php
│   ├── delete_user.php
│   ├── login_action.php
│   ├── update_task_status.php
│   └── update_user.php
├── dashboard.php
├── login.php
└── logout.php
```

## 🗄️ قاعدة البيانات المقترحة
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','member') DEFAULT 'member',
  status ENUM('active','inactive') DEFAULT 'active'
);

CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  assigned_to INT NOT NULL,
  status ENUM('pending','in_progress','done') DEFAULT 'pending',
  priority ENUM('low','medium','high') DEFAULT 'medium',
  created_by INT NOT NULL,
  due_date DATE,
  created_at DATETIME,
  updated_at DATETIME,
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE task_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  comment TEXT NOT NULL,
  created_at DATETIME,
  FOREIGN KEY (task_id) REFERENCES tasks(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## ⚙️ الإعداد السريع
1. عدّل بيانات الاتصال في `config/db.php` أو استخدم متغيرات البيئة (DB_HOST, DB_NAME, DB_USER, DB_PASS).
2. نفّذ سكريبت إنشاء الجداول فوق.
3. أضف مستخدم مدير يدويًا (أو من خلال سكريبت) عشان تبدأ تسجل دخول.
4. افتح `login.php` من السيرفر المحلي واستمتع.

## 🧭 مسار الاستخدام
1. **login.php**: إدخال الإيميل والباسورد.
2. **dashboard.php**: Tabs للـ Overview، أعضاء الفريق، والمهام (حسب الصلاحية).
3. **Modals**: إضافة/تعديل أعضاء ومهام من غير ما تسيب الصفحة.
4. **actions/**: كل POST request له سكريبت منفصل بيعمل المطلوب ويرجع للدashboard.

لو عايز تكبر المشروع، تقدر بسهولة تضيف API، تنبيهات، أو حتى واجهة React فوق نفس الباك-إند.
