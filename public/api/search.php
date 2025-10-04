<?php
header('Content-Type: application/json; charset=utf-8');
if (!file_exists(__DIR__ . '/../../config.php')) { echo json_encode([]); exit; }
$cfg = include __DIR__ . '/../../config.php';

if ($cfg['mode'] === 'sqlite') {
    $pdo = new PDO('sqlite:' . $cfg['sqlite_path']);
} else {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;',$cfg['db_host'],$cfg['db_port'],$cfg['db_name']);
    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass']);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$q = $_GET['q'] ?? '';
if (!$q) { echo json_encode([]); exit; }

function tokenize($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $parts = preg_split('/[^\p{L}\p{N}]+/u', $text);
    $tokens = array_values(array_filter($parts, fn($t) => mb_strlen($t) > 0));
    return $tokens;
}

$tokens = tokenize($q);
$N = (int)$pdo->query("SELECT v FROM meta WHERE k='doc_count'")->fetchColumn();
if ($N <= 0) $N = (int)$pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$avgdl = (float)$pdo->query('SELECT AVG(length) FROM pages')->fetchColumn();
if (!$avgdl) $avgdl = 1000;
$k1=1.5; $b=0.75;

$scores = [];
foreach ($tokens as $t) {
    $termRow = $pdo->prepare('SELECT id, df FROM terms WHERE term = ?');
    $termRow->execute([$t]);
    $term = $termRow->fetch(PDO::FETCH_ASSOC);
    if (!$term) continue;
    $term_id = $term['id']; $df = max(1, $term['df']);
    $idf = log( ( $N - $df + 0.5 ) / ( $df + 0.5 ) + 1 );
    $postings = $pdo->prepare('SELECT p.page_id, p.tf, pg.length, pg.title, pg.url, pg.content
                               FROM postings p JOIN pages pg ON p.page_id = pg.id
                               WHERE p.term_id = ?');
    $postings->execute([$term_id]);
    while ($r = $postings->fetch(PDO::FETCH_ASSOC)) {
        $tf = (int)$r['tf']; $dl = (int)$r['length'];
        $score = $idf * ( ($tf * ($k1 + 1)) / ($tf + $k1 * (1 - $b + $b * $dl/$avgdl)) );
        if (!isset($scores[$r['page_id']])) $scores[$r['page_id']] = ['score'=>0,'meta'=>$r];
        $scores[$r['page_id']]['score'] += $score;
    }
}

uasort($scores, function($a,$b){ return $b['score'] <=> $a['score']; });
$top = array_slice($scores, 0, 20, true);
$out = [];
foreach ($top as $item) {
    $meta = $item['meta'];
    $content = $meta['content'] ?? '';
    $snippet = mb_substr(trim(preg_replace('/\s+/u',' ', $content)), 0, 300);
    foreach ($tokens as $tk) {
        $snippet = preg_replace('/(' . preg_quote($tk, '/') . ')/iu', '<b>$1</b>', $snippet);
    }
    $out[] = ['url'=>$meta['url'],'title'=>$meta['title'],'snippet'=>$snippet,'score'=>$item['score']];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
