<?php
// Local deployment diagnostics: remove this file before publishing the site publicly.
declare(strict_types=1);
require __DIR__.'/config.php';
$required=['categories','customers','products','product_suppliers','purchase_orders','purchase_order_items','sales','sale_items','suppliers'];
$ok=false;$message='';$missing=[];$counts=[];
try {
 $pdo=new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $existing=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
 $missing=array_values(array_diff($required,$existing));
 if(!$missing){
  foreach($required as $t){$counts[$t]=(int)$pdo->query('SELECT COUNT(*) FROM `'.$t.'`')->fetchColumn();}
  $ok=true;
  $message='All nine database tables were found and successfully queried.';
 } else $message='Some required tables are missing.';
} catch(Throwable $e) {$message='Connection or schema issue: '.$e->getMessage();}
function h($s){return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StockPilot setup check</title><style>body{font:16px system-ui;background:#f3f5fa;color:#172238;margin:0;padding:50px 20px}.card{max-width:720px;margin:auto;background:white;padding:32px;border-radius:18px;box-shadow:0 12px 40px #17223817}h1{margin-top:0}.state{padding:15px;border-radius:9px;background:<?= $ok?'#e4f7eb':'#fff1df' ?>}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:10px;border-bottom:1px solid #eee}a{color:#4054c8}code{background:#f1f1f4;padding:2px 5px;border-radius:4px}</style></head><body><div class="card"><h1>Database setup check</h1><p>Database: <code><?=h(DB_NAME)?></code> • Host: <code><?=h(DB_HOST)?></code></p><div class="state"><?= $ok?'✔':'⚠' ?> <?=h($message)?></div><?php if($missing): ?><p><strong>Missing tables:</strong> <?=h(implode(', ', $missing))?></p><?php endif; ?><?php if($ok): ?><h2>Tables & row counts</h2><table><thead><tr><th>Table</th><th>Rows</th></tr></thead><tbody><?php foreach($counts as $t=>$n): ?><tr><td><?=h($t)?></td><td><?=h($n)?></td></tr><?php endforeach ?></tbody></table><p><a href="index.php">Open StockPilot dashboard →</a></p><?php else: ?><h2>Fix</h2><p>Import <code>database.sql</code> through phpMyAdmin, then refresh this page. If you see “Access denied,” edit credentials in <code>config.php</code>.</p><?php endif; ?><p><small>Do not host diagnostics.php on a public server.</small></p></div></body></html>
