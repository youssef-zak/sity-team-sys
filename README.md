# Sity Cloud TeamFlow – نظام إدارة فريق بختم youssef r.

واجهة بسيطة لكن مليانة شغل محترم لإدارة الفريق ومتابعة المهام باسم شركتك **Sity Cloud** ومن تطوير **youssef r.**. مبني بـ **PHP + MySQL + Bootstrap**، ومقسم لصفحتين رئيسيتين (login + dashboard) مع أكشن فايلز واضحة.

## 🚀 المميزات
- تسجيل دخول آمن (password_hash/password_verify + Sessions).
- صلاحيات واضحة: مدير يقدر يعمل كل حاجة، عضو يشوف مهامه بس.
- إدارة فريق (إضافة، تعديل، حذف، تفعيل/تعطيل حساب) مع متابعة **رصيد نقاط الأداء** لكل عضو.
- إدارة مهام كاملة مع أولويات، تواريخ تسليم، وتعليقات، ووضع تقرير (إلزامي/اختياري) لكل مهمة.
- لوحات إحصائيات + لوحة شرف Top 5 توضح أداء الفريق، وعدّاد بالتقارير اللي مستنية مراجعة المدير.
- نظام تقرير تسليم متكامل: العضو ما يقدرش يعلن إنه خلّص مهمة إلّا لما يرفع ملخص وملفات (لو المهمة إلزامية).
- مراجعة من صاحب المهمة: المدير اللي أنشأ التاسك يوافق على التقرير أو يطلب تعديلات ويكتب ملاحظات واضحة.
- جمع نقاط تلقائيًا لو العضو سلّم قبل الديدلاين؛ النقاط دي بتظهر على الـ Dashboard عشان تتابع نجوم Sity Cloud.
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
│   ├── review_report.php
│   ├── update_task_status.php
│   └── update_user.php
├── storage/
│   └── reports/.gitkeep
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

-- إنشاء الجداول (users, tasks، task_comments، task_reports)
CREATE TABLE users (
  ...
  performance_points INT UNSIGNED DEFAULT 0
);
CREATE TABLE tasks (
  ...
  report_requirement ENUM('required','optional') DEFAULT 'required',
  completed_at TIMESTAMP NULL
);
CREATE TABLE task_reports (...);

-- إضافة يوزر أدمن جاهز بالباسورد المشفر
INSERT INTO users (name, email, password, role, status)
VALUES ('System Admin', 'admin@example.com', '$2y$12$4TEqN7uwdMqKZhHRsIO5g.U6efSyAPhNVE1x.Defh1GWyk2JlZL42', 'admin', 'active');
```

تقدر تفتح الملف وتشوف كل الـ SQL كاملة، وكمان فيه بيانات تجريبية للمهام، التعليقات، وتقارير جاهزة توضح شكل المرفقات والنقاط.

### نقاط الأداء والتقارير
- كل عضو ليه عمود `performance_points`؛ بيزيد تلقائيًا لما يسلم قبل الديدلاين.
- كل مهمة ليها `report_requirement` يحدد إذا كان التقرير إلزامي أو اختياري.
- جدول `task_reports` بيخزن ملخص التسليم، رابط المرفق، حالة المراجعة، وملاحظات المدير اللي أنشأ المهمة.
- المرفقات بتتخزن داخل `storage/reports` (أقصى حجم 5MB، وصيغ مثل PDF/Office/صور/ZIP).

## ⚙️ الإعداد السريع
1. عدّل بيانات الاتصال في `config/db.php` أو استخدم متغيرات البيئة (DB_HOST, DB_NAME, DB_USER, DB_PASS).
2. نفّذ سكريبت إنشاء الجداول فوق.
3. لو محتاج تعتمد بيانات مختلفة عن الأدمن الجاهز (`admin@example.com` / `Admin@123`)، عدّل أو أضف مستخدمين جدد.
4. تأكد إن مجلد `storage/reports` قابل للكتابة عشان رفع الملفات.
5. افتح `login.php` من السيرفر المحلي واستمتع.

## 🧭 مسار الاستخدام
1. **login.php**: إدخال الإيميل والباسورد.
2. **dashboard.php**: Tabs للـ Overview، أعضاء الفريق، والمهام، مع:
   - لوحة شرف Top 5.
   - عدّاد التقارير اللي محتاجة مراجعة.
   - شريط نقاطك أنت كعضو.
3. **Modals**: إضافة/تعديل أعضاء ومهام + رفع تقرير ومرفقات لكل مهمة.
4. **actions/**: كل POST request له سكريبت منفصل (بما فيهم `review_report.php` لمراجعة التقارير).

لو عايز تكبر المشروع، تقدر بسهولة تضيف تنبيهات فورية عند اعتماد تقرير، نظام مكافآت أكبر، أو تربطه بتطبيق موبايل.
