<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Check exact table names so a failed or partial SQL import never causes a dashboard fatal error.
            $expected = ['categories','customers','products','product_suppliers','purchase_orders','purchase_order_items','sales','sale_items','suppliers'];
            $found = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $missing = array_diff($expected, $found);
            if ($missing) {
                http_response_code(503);
                exit('<!doctype html><html><meta charset="utf-8"><title>Database setup required</title><body style="background:#f5f7fb;font:16px system-ui;color:#1f2937"><section style="background:white;max-width:700px;margin:10vh auto;padding:36px;border-radius:16px;box-shadow:0 8px 30px #0001"><h1>Database setup incomplete</h1><p>Connected to <b>'.htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8').'</b>, but these tables are missing: <code>'.htmlspecialchars(implode(', ', $missing), ENT_QUOTES, 'UTF-8').'</code>.</p><ol><li>Start Apache and MySQL in XAMPP.</li><li>Open phpMyAdmin, click <b>Import</b> and choose the <b>database.sql</b> supplied with this project.</li><li>After importing, reload this page.</li></ol><p><a href="diagnostics.php">Open database diagnostics</a></p></section></body></html>');
            }
        } catch (PDOException $e) {
            http_response_code(503);
            exit('<div style="font:16px Arial;max-width:700px;margin:12vh auto;padding:35px;border:1px solid #ddd;border-radius:15px"><h2>Database connection failed</h2><p>Start MySQL in XAMPP, import <code>database.sql</code> and check <code>config.php</code>.</p><pre>'.htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8').'</pre></div>');
        }
    }
    return $pdo;
}
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function cash($value): string { return 'Rs. '.number_format((float)$value, 2); }
function url(string $page, array $params=[]): string { return $page.($params ? '?'.http_build_query($params) : ''); }
function go(string $page): never { header('Location: '.$page); exit; }
function set_flash(string $message, string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f; }
function csrf_token(): string { if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {http_response_code(405);exit('Method not allowed');}
    if (!hash_equals(csrf_token(),(string)($_POST['csrf']??''))) {http_response_code(403);exit('Invalid security token. Refresh and retry.');}
}
function valid_id($v): int { $id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); if ($id===false) throw new RuntimeException('Choose a valid record.'); return (int)$id; }
function nonneg_money($v): float { if (!is_numeric($v) || !is_finite((float)$v) || (float)$v < 0 || (float)$v > 9999999999.99) throw new RuntimeException('Enter a valid non-negative amount.');return round((float)$v,2); }
function nonneg_int($v): int { $n=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100000000]]);if($n===false)throw new RuntimeException('Enter a valid non-negative whole number.');return(int)$n; }
function positive_int($v): int { $n=nonneg_int($v);if($n<1)throw new RuntimeException('Quantity must be greater than zero.');return $n; }
function required_text($v,int $max=150): string { $s=trim((string)$v); if ($s==='' || mb_strlen($s)>$max)throw new RuntimeException('A required field is empty or too long (maximum '.$max.' characters).');return $s; }
function optional_text($v,int $max=255): ?string { $s=trim((string)($v??''));if (mb_strlen($s)>$max)throw new RuntimeException('Text exceeds the maximum length of '.$max.'.');return $s===''?null:$s; }
function safe_action(callable $fn,string $back): void {
    try { require_post(); $fn(); }
    catch(Throwable $e) { set_flash($e instanceof PDOException ? 'Database operation failed. Check linked records or duplicate values.' : $e->getMessage(),'error'); go($back); }
}
function rows(string $sql, array $params=[]): array { $stmt=db()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(); }
function one(string $sql, array $params=[]): array|false { $stmt=db()->prepare($sql);$stmt->execute($params);return $stmt->fetch(); }
function scalar(string $sql,array $params=[]): mixed { $stmt=db()->prepare($sql);$stmt->execute($params);return $stmt->fetchColumn(); }
function line_items(): array {
    $ids=$_POST['product_id']??[];$qtys=$_POST['quantity']??[];
    if (!is_array($ids)||!is_array($qtys)||count($ids)!==count($qtys)||count($ids)<1||count($ids)>50)throw new RuntimeException('Add between 1 and 50 valid items.');
    $items=[];
    foreach($ids as $i=>$id){
        if ((string)$id==='' && (string)($qtys[$i]??'')==='')continue;
        $pid=valid_id($id);$qty=positive_int($qtys[$i]??null);
        if(isset($items[$pid]))throw new RuntimeException('Each product may appear only once per order.');
        $items[$pid]=$qty;
    }
    if(!$items)throw new RuntimeException('Add at least one product.');
    return $items;
}
function icon(string $name): string {
    $paths=[
      'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
      'box'=>'<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
      'truck'=>'<path d="M10 17h4V5H2v12h3"/><path d="M14 9h4l4 4v4h-3"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/>',
      'cart'=>'<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.8 13.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6L23 6H6"/>',
      'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
      'chart'=>'<path d="M3 3v18h18M7 16v-5M12 16V7M17 16v-9"/>',
      'plus'=>'<path d="M12 5v14M5 12h14"/>',
      'search'=>'<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
      'alert'=>'<path d="M10.3 3.9 2 18.2A2 2 0 0 0 3.7 21h16.6a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
      'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
      'check'=>'<path d="m5 12 4 4L19 6"/>',
      'layers'=>'<path d="m12 2-9 5 9 5 9-5-9-5ZM3 12l9 5 9-5M3 17l9 5 9-5"/>',
      'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
      'printer'=>'<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
      'edit'=>'<path d="M12 20h9M16 4a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
      'trash'=>'<path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6M10 10v6m4-6v6"/>',
    ];
    return '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['grid']).'</svg>';
}
function render_header(string $title,string $section): void {
    $nav=['index.php'=>['grid','Dashboard'],'products.php'=>['box','Products'],'suppliers.php'=>['truck','Suppliers'],'customers.php'=>['users','Customers'],'purchases.php'=>['layers','Purchase orders'],'sales.php'=>['cart','Sales'],'reports.php'=>['chart','Reports']];
    $flash=take_flash();
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · <?=APP_NAME?></title><link rel="stylesheet" href="assets/style.css"></head>
    <body><div class="shell"><aside class="sidebar" id="sidebar"><div class="brand"><div class="brand-mark">S<span>•</span></div><div><strong>stockpilot</strong><small>RETAIL OPERATIONS</small></div></div><div class="nav-heading">WORKSPACE</div><nav class="nav"><?php foreach($nav as $path=>[$glyph,$label]): ?><a class="nav-link <?=$section===$path?'active':''?>" href="<?=e($path)?>"><?=icon($glyph)?><span><?=e($label)?></span></a><?php endforeach; ?></nav><div class="sidebar-bottom"><div class="avatar">RS</div><div><strong>Retail Store</strong><small>Administrator</small></div><span class="online"></span></div></aside><div class="main"><header class="topbar"><button type="button" class="icon-button mobile-menu" onclick="document.getElementById('sidebar').classList.toggle('show')" aria-label="Open menu"><?=icon('menu')?></button><div class="breadcrumb">Workspace <span>/</span> <strong><?=e($title)?></strong></div><div class="top-right"><span class="live-dot"></span> Online <span class="top-date"><?=date('d M Y')?></span></div></header><main class="content"><?php if($flash):?><div class="notice <?=e($flash['type'])?>"><?=icon($flash['type']==='error'?'alert':'check')?><?=e($flash['message'])?></div><?php endif;?>
    <?php
}
function render_footer(): void { echo '</main><footer class="footer">StockPilot • Retail Store Management System <span>PHP + MySQL</span></footer></div></div><script src="assets/app.js"></script></body></html>'; }
function page_heading(string $eyebrow,string $title,string $description,string $right=''): void {echo '<div class="page-heading"><div><div class="eyebrow">'.e($eyebrow).'</div><h1>'.e($title).'</h1><p>'.e($description).'</p></div><div class="heading-actions">'.$right.'</div></div>';}
function status_badge(string $s): string {return '<span class="pill '.($s==='Received'?'positive':'pending').'">'.e($s).'</span>';}
