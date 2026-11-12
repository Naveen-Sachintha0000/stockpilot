<?php
require_once __DIR__.'/bootstrap.php';
$stats=one("SELECT (SELECT COUNT(*) FROM products) products,(SELECT COUNT(*) FROM suppliers) suppliers,(SELECT COUNT(*) FROM sales) sales,(SELECT COALESCE(SUM(si.quantity*si.unit_price),0) FROM sale_items si) revenue,(SELECT COUNT(*) FROM products WHERE stock_quantity<=reorder_level) low_stock,(SELECT COUNT(*) FROM purchase_orders WHERE status='Pending') pending_orders");
$recent=rows('SELECT s.sale_id,s.sale_date,COALESCE(c.customer_name,\'Walk-in customer\') customer_name, SUM(si.quantity*si.unit_price) total,SUM(si.quantity) units FROM sales s LEFT JOIN customers c ON c.customer_id=s.customer_id JOIN sale_items si ON si.sale_id=s.sale_id GROUP BY s.sale_id,s.sale_date,c.customer_name ORDER BY s.sale_date DESC,s.sale_id DESC LIMIT 6');
$low=rows('SELECT product_name,sku,stock_quantity,reorder_level FROM products WHERE stock_quantity<=reorder_level ORDER BY stock_quantity ASC LIMIT 5');
$top=rows('SELECT p.product_name,COALESCE(SUM(si.quantity),0) units FROM products p LEFT JOIN sale_items si ON p.product_id=si.product_id GROUP BY p.product_id,p.product_name ORDER BY units DESC,p.product_name LIMIT 5');
$max=max(1,...array_map(fn($x)=>(int)$x['units'],$top));
render_header('Dashboard','index.php');
page_heading('OVERVIEW','Dashboard','An overview of sales, inventory and operations.','<a class="btn primary" href="sales.php">'.icon('plus').' New sale</a>');
?>
<div class="hero-banner"><div><span class="hero-badge">STORE PERFORMANCE</span><h2>Your store, at a glance.</h2><p>Manage your products, track every transaction, and keep stock in balance — all from one place.</p><a href="reports.php" class="hero-link">Explore reports <?=icon('arrow')?></a></div><div class="hero-art"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="hero-cube">S<span>•</span></div></div></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-top"><span>Total revenue</span><span class="stat-symbol purple"><?=icon('chart')?></span></div><strong><?=cash($stats['revenue'])?></strong><small>From all recorded sales</small></div>
<div class="stat-card"><div class="stat-top"><span>Active products</span><span class="stat-symbol blue"><?=icon('box')?></span></div><strong><?=number_format($stats['products'])?></strong><small>Products in your catalog</small></div>
<div class="stat-card"><div class="stat-top"><span>Total sales</span><span class="stat-symbol green"><?=icon('cart')?></span></div><strong><?=number_format($stats['sales'])?></strong><small>Completed transactions</small></div>
<div class="stat-card"><div class="stat-top"><span>Low stock items</span><span class="stat-symbol orange"><?=icon('alert')?></span></div><strong><?=number_format($stats['low_stock'])?></strong><small><?=$stats['pending_orders']?> purchase orders pending</small></div>
</div>

<div class="two-column wide-left"><section class="panel"><div class="panel-head"><div><h3>Inventory Alerts</h3><p>Items at or below their reorder threshold</p></div><a href="reports.php?tab=stock" class="text-link">Low stock report <?=icon('arrow')?></a></div><div class="table-wrap"><table><thead><tr><th>Product</th><th>SKU</th><th class="right">On hand</th><th class="right">Reorder at</th><th>Status</th></tr></thead><tbody><?php foreach($low as $r):?><tr><td class="strong-cell"><?=e($r['product_name'])?></td><td class="muted-cell"><?=e($r['sku'])?></td><td class="right"><?=e($r['stock_quantity'])?></td><td class="right"><?=e($r['reorder_level'])?></td><td><span class="pill danger">Low stock</span></td></tr><?php endforeach;if(!$low):?><tr><td colspan="5" class="empty">All products are above reorder levels.</td></tr><?php endif;?></tbody></table></div></section>

<section class="panel"><div class="panel-head"><div><h3>Best sellers</h3><p>Most popular products by units</p></div></div><div class="bar-list"><?php foreach($top as $t):?><div class="bar-item"><div class="bar-label"><span><?=e($t['product_name'])?></span><strong><?=e($t['units'])?> sold</strong></div><div class="bar-track"><div class="bar-fill" style="width:<?=round($t['units']/$max*100)?>%"></div></div></div><?php endforeach;?></div></section></div>
<?php render_footer(); ?>
