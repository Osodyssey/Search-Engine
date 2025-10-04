<?php
// indexer.php — build inverted index using MySQL
if (!file_exists(__DIR__ . '/config.php')) { echo "Please configure config.php\n"; exit(1); }
$cfg = include __DIR__ . '/config.php';
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;',$cfg['db_host'],$cfg['db_port'],$cfg['db_name']);
$pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);

function tokenize($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $parts = preg_split('/[^\p{L}\p{N}]+/u', $text);
    $tokens = array_values(array_filter($parts, fn($t) => mb_strlen($t) > 0));
    return $tokens;
}

// clear previous index (optional) — comment out in production
$pdo->exec('TRUNCATE TABLE terms');
$pdo->exec('TRUNCATE TABLE postings');

$pages = $pdo->query('SELECT id, content FROM pages')->fetchAll(PDO::FETCH_ASSOC);
$termInsert = $pdo->prepare('INSERT INTO terms (term, df) VALUES (?, 0)');
$termSelect = $pdo->prepare('SELECT id FROM terms WHERE term = ?');
$postingInsert = $pdo->prepare('INSERT INTO postings (term_id, page_id, tf, positions) VALUES (?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE tf=VALUES(tf), positions=VALUES(positions)');
foreach ($pages as $p) {
    $tokens = tokenize($p['content']);
    $positions = [];
    $tf_map = [];
    foreach ($tokens as $i => $t) {
        $positions[$t][] = $i;
        if (!isset($tf_map[$t])) $tf_map[$t] = 0;
        $tf_map[$t]++;
    }
    foreach ($tf_map as $term => $tf) {
        try {
            $termInsert->execute([$term]);
        } catch (Exception $e) {
            // ignore duplicate key
        }
        $term_id = $termSelect->execute([$term]) ? $pdo->query('SELECT id FROM terms WHERE term = ' . $pdo->quote($term))->fetchColumn() : $pdo->lastInsertId();
        $pos_str = implode(',', $positions[$term]);
        $postingInsert->execute([$term_id, $p['id'], $tf, $pos_str]);
    }
}
// update df
$pdo->exec('UPDATE terms SET df = (SELECT COUNT(*) FROM postings p WHERE p.term_id = terms.id)');
// update doc_count
$pdo->exec("UPDATE meta SET v = (SELECT COUNT(*) FROM pages) WHERE k='doc_count'");
echo "Indexing complete.\n";
