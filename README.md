# موتور جستجوی (Search Engine) — PHP + JS + Tailwind (MySQL)

این نسخه برای اجرا روی هاست و سرور طراحی شده و از **MySQL / MariaDB** به عنوان دیتابیس استفاده می‌کند.
شامل:
- setup_db.php: ایجاد جداول و دادهٔ نمونه
- indexer.php: ایندکس‌سازی صفحات (توکنایز + terms + postings)
- crawler.php: خزشگر ساده (اختیاری) که صفحات را ذخیره می‌کند
- public/: رابط کاربری (HTML/Tailwind) و API جستجو (BM25)
- config.php.example: تنظیمات اتصال به دیتابیس
- scripts/run_server.sh: اجرای سرور برای تست محلی (اختیاری)

## پیش‌نیازها
- PHP 8.x با PDO MySQL فعال (`pdo_mysql`)
- MySQL یا MariaDB
- وب‌سرور (Apache/Nginx) یا PHP built-in برای تست

## نصب و راه‌اندازی (روی هاست)
1. آپلود پرونده‌ها به سرور (مثلاً در `/var/www/search`).
2. یک دیتابیس و کاربر بساز:
   ```sql
   CREATE DATABASE search_engine CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   CREATE USER 'searchuser'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON search_engine.* TO 'searchuser'@'localhost';
   FLUSH PRIVILEGES;
   ```
3. فایل `config.php.example` را کپی کن به `config.php` و متغیرها را تنظیم کن:
   ```php
   <?php
   return [
     'db_host'=>'localhost',
     'db_name'=>'search_engine',
     'db_user'=>'searchuser',
     'db_pass'=>'your_password',
     'db_port'=>3306
   ];
   ```
4. از خط فرمان یا از طریق مرورگر، script راه‌اندازی را اجرا کن تا جداول و دادهٔ نمونه ایجاد شود:
   ```bash
   php setup_db.php
   ```
   یا با مرورگر: `https://yourhost/setup_db.php`

5. (اختیاری) اگر می‌خواهی صفحات جدید اضافه کنی، `crawler.php` را اجرا کن:
   ```bash
   php crawler.php seed_urls.txt
   ```
   سپس برای ایندکس جدید:
   ```bash
   php indexer.php
   ```

6. API و UI:
   - اگر در دایرکتوری public قرار داده باشی و وب‌سرور تنظیم شده باشد، صفحهٔ اصلی را باز کن: `https://yourhost/`
   - برای تست محلی می‌توانی:
     ```bash
     cd public
     php -S 127.0.0.1:8000
     ```
     سپس مرورگر را باز کن و به `http://127.0.0.1:8000` برو.

## نکات فنی
- این پروژه یک پیاده‌سازی آموزشی است؛ برای استفادهٔ production باید:
  - از OpenSearch/Elasticsearch یا موتور ایندکس حرفه‌ای استفاده کنی.
  - پردازش زبان فارسی شامل stemming و stopwords را بهبود دهی.
  - بهینه‌سازی‌های عملکردی (indexes on DB, sharding, caching) انجام شود.

## محتویات فایل‌ها
- `setup_db.php` — ایجاد جداول و دادهٔ نمونه و اجرای ایندکس اولیه.
- `indexer.php` — توکنایز و ساخت `terms` و `postings`.
- `public/api/search.php` — API جستجو (BM25).
- `public/index.php` — UI با Tailwind.
