<?php
// indexer.php: tokenize pages and build terms/postings (works with sqlite or mysql based on config.php)
if (!file_exists(__DIR__ . '/config.php')) { echo "Please configure config.php\n"; exit(1); }
$cfg = include __DIR__ . '/config.php';
if ($cfg['mode'] === 'sqlite') {
    $pdo = new PDO('sqlite:' . $cfg['sqlite_path']);
} else {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;',$cfg['db_host'],$cfg['db_port'],$cfg['db_name']);
    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass']);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function tokenize($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $parts = preg_split('/[^\p{L}\p{N}]+/u', $text);
    $tokens = array_values(array_filter($parts, fn($t) => mb_strlen($t) > 0));
    return $tokens;
}

// clear previous index
$pdo->exec('DELETE FROM terms');
$pdo->exec('DELETE FROM postings');

$pages = $pdo->query('SELECT id, content FROM pages')->fetchAll(PDO::FETCH_ASSOC);
$termIns = $pdo->prepare('INSERT INTO terms (term, df) VALUES (?, 0)');
$termSel = $pdo->prepare('SELECT id FROM terms WHERE term = ?');
$postIns = $pdo->prepare('INSERT INTO postings (term_id, page_id, tf, positions) VALUES (?, ?, ?, ?)');

foreach ($pages as $p) {
    $tokens = tokenize($p['content']);
    $positions = [];
    $tf = [];
    foreach ($tokens as $i => $t) {
        $positions[$t][] = $i;
        $tf[$t] = ($tf[$t] ?? 0) + 1;
    }
    foreach ($tf as $term => $count) {
        try { $termIns->execute([$term]); } catch (Exception $e) {}
        $term_id = $pdo->query('SELECT id FROM terms WHERE term = ' . $pdo->quote($term))->fetchColumn();
        $pos_str = implode(',', $positions[$term]);
        $postIns->execute([$term_id, $p['id'], $count, $pos_str]);
    }
}
// update df
$pdo->exec('UPDATE terms SET df = (SELECT COUNT(*) FROM postings p WHERE p.term_id = terms.id)');
$pdo->exec("UPDATE meta SET v = (SELECT COUNT(*) FROM pages) WHERE k='doc_count'");
echo "Indexing done.\n";
