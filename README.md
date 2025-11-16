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
لو عايز تشتغل بسرعة، فيه ملف جاهز (`database/schema.sql`) بيعمل الآتي:

```sql
CREATE DATABASE IF NOT EXISTS teamflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE teamflow;

-- حذف الجداول في حالة إنها موجودة
DROP TABLE IF EXISTS task_comments;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

-- إنشاء الجداول الثلاثة (users, tasks, task_comments)
CREATE TABLE users (...);
CREATE TABLE tasks (...);
CREATE TABLE task_comments (...);

-- إضافة يوزر أدمن جاهز بالباسورد المشفر
INSERT INTO users (name, email, password, role, status)
VALUES ('System Admin', 'admin@example.com', '$2y$12$4TEqN7uwdMqKZhHRsIO5g.U6efSyAPhNVE1x.Defh1GWyk2JlZL42', 'admin', 'active');
```

تقدر تفتح الملف وتشوف كل الـ SQL كاملة، وكمان فيه بيانات تجريبية للمهام والتعليقات لو حابب تبدأ بحاجة جاهزة.

## ⚙️ الإعداد السريع
1. عدّل بيانات الاتصال في `config/db.php` أو استخدم متغيرات البيئة (DB_HOST, DB_NAME, DB_USER, DB_PASS).
2. نفّذ سكريبت إنشاء الجداول فوق.
3. لو محتاج تعتمد بيانات مختلفة عن الأدمن الجاهز (`admin@example.com` / `Admin@123`)، عدّل أو أضف مستخدمين جدد.
4. افتح `login.php` من السيرفر المحلي واستمتع.

## 🧭 مسار الاستخدام
1. **login.php**: إدخال الإيميل والباسورد.
2. **dashboard.php**: Tabs للـ Overview، أعضاء الفريق، والمهام (حسب الصلاحية).
3. **Modals**: إضافة/تعديل أعضاء ومهام من غير ما تسيب الصفحة.
4. **actions/**: كل POST request له سكريبت منفصل بيعمل المطلوب ويرجع للدashboard.

لو عايز تكبر المشروع، تقدر بسهولة تضيف API، تنبيهات، أو حتى واجهة React فوق نفس الباك-إند.
