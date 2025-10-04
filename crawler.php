<?php
// crawler.php — simple fetcher to add pages to DB from seed list
if (!file_exists(__DIR__ . '/config.php')) { echo "Please configure config.php\n"; exit(1); }
$cfg = include __DIR__ . '/config.php';
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;',$cfg['db_host'],$cfg['db_port'],$cfg['db_name']);
$pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$seedFile = $argv[1] ?? 'seed_urls.txt';
if (!file_exists($seedFile)) { echo "Seed file not found: $seedFile\n"; exit(1); }
$seeds = file($seedFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

function fetch_url($url) {
    $opts = ['http'=>['user_agent'=>'MiniBot/1.0','timeout'=>10]];
    $ctx = stream_context_create($opts);
    $html = @file_get_contents($url, false, $ctx);
    return $html ?: '';
}
function extract_text($html) {
    $html = preg_replace('#<script.*?</script>#is', '', $html);
    $html = preg_replace('#<style.*?</style>#is', '', $html);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $title = '';
    $titles = $dom->getElementsByTagName('title');
    if ($titles->length) $title = $titles->item(0)->textContent;
    $body = $dom->getElementsByTagName('body')->item(0);
    $text = $body ? $dom->saveHTML($body) : $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return [trim($title), trim($text)];
}
$ins = $pdo->prepare('INSERT INTO pages (url, title, content, length) VALUES (?, ?, ?, ?)');
foreach ($seeds as $url) {
    echo "Fetching: $url\n";
    $html = fetch_url($url);
    if (!$html) { echo "Failed: $url\n"; continue; }
    list($title,$text) = extract_text($html);
    $len = mb_strlen($text,'UTF-8');
    $ins->execute([$url, $title, $text, $len]);
    echo "Saved: $url (len={$len})\n";
}
// update meta
$pdo->exec("UPDATE meta SET v = (SELECT COUNT(*) FROM pages) WHERE k='doc_count'");
echo "Crawler finished.\n";
