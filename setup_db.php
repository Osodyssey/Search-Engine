<?php
// setup_db.php
// Creates schema and seeds sample data for sqlite or mysql, then runs indexer.
// Usage: php setup_db.php

if (!file_exists(__DIR__ . '/config.php')) {
    echo "Please copy config.php.example to config.php and edit it.\n";
    exit(1);
}
$cfg = include __DIR__ . '/config.php';

if ($cfg['mode'] === 'sqlite') {
    $dbfile = $cfg['sqlite_path'];
    $dsn = 'sqlite:' . $dbfile;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Using SQLite DB: $dbfile\n";
    // create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        url TEXT,
        title TEXT,
        content TEXT,
        length INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");
    $pdo->exec("CREATE TABLE IF NOT EXISTS terms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        term TEXT UNIQUE,
        df INTEGER DEFAULT 0
    );");
    $pdo->exec("CREATE TABLE IF NOT EXISTS postings (
        term_id INTEGER,
        page_id INTEGER,
        tf INTEGER,
        positions TEXT,
        PRIMARY KEY(term_id, page_id)
    );");
    $pdo->exec("CREATE TABLE IF NOT EXISTS meta (k TEXT PRIMARY KEY, v TEXT);");
    $pdo->exec("INSERT OR IGNORE INTO meta (k,v) VALUES ('doc_count','0');");
} else {
    // mysql
    $dsn0 = sprintf('mysql:host=%s;port=%d;charset=utf8mb4;',$cfg['db_host'],$cfg['db_port']);
    try {
        $pdo0 = new PDO($dsn0, $cfg['db_user'], $cfg['db_pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    } catch (Exception $e) {
        echo "Cannot connect MySQL: " . $e->getMessage() . "\n"; exit(1);
    }
    // create db if not exists
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `" . $cfg['db_name'] . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;',$cfg['db_host'],$cfg['db_port'],$cfg['db_name']);
    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    echo "Using MySQL DB: " . $cfg['db_name'] . "\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      url TEXT,
      title TEXT,
      content LONGTEXT,
      length INT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS terms (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      term VARCHAR(255) UNIQUE,
      df INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS postings (
      term_id BIGINT,
      page_id INT,
      tf INT,
      positions TEXT,
      PRIMARY KEY(term_id, page_id),
      INDEX(page_id),
      INDEX(term_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS meta (
      k VARCHAR(100) PRIMARY KEY,
      v VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $stmt = $pdo->prepare("INSERT IGNORE INTO meta (k,v) VALUES ('doc_count','0')"); $stmt->execute();
}

// seed sample pages if empty
$count = (int)$pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
if ($count === 0) {
    $sample = [
        ['https://example.com/php','مقدمه‌ای بر PHP','PHP زبان برنامه‌نویسی سمت سرور است. این صفحه نمونه دربارهٔ PHP است.'],
        ['https://example.com/crawler','خزشگر وب (Crawler)','خزشگرها صفحات وب را پیدا و ذخیره می‌کنند. این مثال توضیح می‌دهد چگونه crawler کار می‌کند.'],
        ['https://example.com/search','الگوریتم‌های جستجو','جستجوگرها از ایندکس معکوس، BM25 و PageRank استفاده می‌کنند. این متن نمونه برای تست جستجو است.'],
        ['https://example.com/mysql','کار با MySQL','MySQL یک دیتابیس رابطه‌ای است و برای پروژه‌های وب بسیار استفاده می‌شود.'],
        ['https://example.com/tailwind','طراحی با Tailwind','Tailwind CSS چارچوبی برای استایل‌دهی سریع صفحات است.']
    ];
    $ins = $pdo->prepare('INSERT INTO pages (url, title, content, length) VALUES (?, ?, ?, ?)');
    foreach ($sample as $s) {
        $len = mb_strlen($s[2], 'UTF-8');
        $ins->execute([$s[0], $s[1], $s[2], $len]);
    }
    $pdo->exec("UPDATE meta SET v = (SELECT COUNT(*) FROM pages) WHERE k='doc_count'");
    echo "Seeded sample pages.\n";
} else {
    echo "Pages exist: {$count}\n";
}

// run indexer
echo "Running indexer...\n";
passthru('php ' . __DIR__ . '/indexer.php', $rv);
echo "Done.\n";
