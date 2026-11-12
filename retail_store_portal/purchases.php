<?php
require_once __DIR__.'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST')safe_action(function(){
 $mode=$_POST['mode']??'create';$pdo=db();
 if($mode==='receive'){
  $id=valid_id($_POST['id']??null);$pdo->beginTransaction();
  try{
    $stmt=$pdo->prepare('SELECT status FROM purchase_orders WHERE purchase_order_id=? FOR UPDATE');$stmt->execute([$id]);$order=$stmt->fetch();
    if(!$order)throw new RuntimeException('Purchase order not found.');
    if($order['status']!=='Pending')throw new RuntimeException('This purchase order has already been received.');
    $items=rows('SELECT product_id,quantity FROM purchase_order_items WHERE purchase_order_id=?',[$id]);
    $increase=$pdo->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE product_id=?');
    foreach($items as $item)$increase->execute([$item['quantity'],$item['product_id']]);
    $stmt=$pdo->prepare("UPDATE purchase_orders SET status='Received',received_at=NOW() WHERE purchase_order_id=?");$stmt->execute([$id]);
    $pdo->commit();set_flash('Purchase order #'.$id.' received. Stock increased.');go('purchases.php');
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 }
 $supplier=valid_id($_POST['supplier_id']??null);$items=line_items();$costs=$_POST['unit_cost']??[];
 if(!is_array($costs)||count($costs)!==count($_POST['product_id']??[]))throw new RuntimeException('Missing item costs.');
 $costByProduct=[];
 foreach(($_POST['product_id']??[]) as $idx=>$pid){if((string)$pid==='')continue;$id=valid_id($pid);$costByProduct[$id]=nonneg_money($costs[$idx]??null);}
 $notes=optional_text($_POST['notes']??'',255);
 if(!one('SELECT supplier_id FROM suppliers WHERE supplier_id=?',[$supplier]))throw new RuntimeException('Supplier not found.');
 $pdo->beginTransaction();
 try{
    $stmt=$pdo->prepare('INSERT INTO purchase_orders(supplier_id,notes) VALUES (?,?)');$stmt->execute([$supplier,$notes]);$orderId=(int)$pdo->lastInsertId();
    $itemStmt=$pdo->prepare('INSERT INTO purchase_order_items(purchase_order_id,product_id,quantity,unit_cost) VALUES (?,?,?,?)');
    $link=$pdo->prepare('INSERT INTO product_suppliers(product_id,supplier_id,supply_price) VALUES (?,?,?) ON DUPLICATE KEY UPDATE supply_price=VALUES(supply_price)');
    $exists=$pdo->prepare('SELECT product_id FROM products WHERE product_id=?');
    foreach($items as $pid=>$qty){$exists->execute([$pid]);if(!$exists->fetch())throw new RuntimeException('Selected product not found.');$itemStmt->execute([$orderId,$pid,$qty,$costByProduct[$pid]]);$link->execute([$pid,$supplier,$costByProduct[$pid]]);}
    $pdo->commit();set_flash('Purchase order #'.$orderId.' created. Mark it received when delivery arrives.');go('purchases.php');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
},'purchases.php');
$suppliers=rows('SELECT supplier_id,supplier_name FROM suppliers ORDER BY supplier_name');
$products=rows('SELECT product_id,product_name,sku FROM products ORDER BY product_name');
$orders=rows('SELECT po.purchase_order_id,po.order_date,po.status,po.received_at,po.notes,s.supplier_name,COUNT(poi.product_id) product_count,SUM(poi.quantity*poi.unit_cost) total, GROUP_CONCAT(CONCAT(p.product_name,\' ×\',poi.quantity) ORDER BY p.product_name SEPARATOR \', \') items FROM purchase_orders po JOIN suppliers s ON s.supplier_id=po.supplier_id JOIN purchase_order_items poi ON poi.purchase_order_id=po.purchase_order_id JOIN products p ON p.product_id=poi.product_id GROUP BY po.purchase_order_id,po.order_date,po.status,po.received_at,po.notes,s.supplier_name ORDER BY po.order_date DESC,po.purchase_order_id DESC LIMIT 30');
render_header('Purchase orders','purchases.php');page_heading('PROCUREMENT','Purchase orders','Order products from suppliers and receive deliveries into stock.');
?>
<div class="two-column form-layout"><section class="panel"><div class="panel-head"><div><h3>Create purchase order</h3><p>Orders begin as pending; stock updates on receipt.</p></div></div><form method="post" class="form-body" data-lines="purchase"><?=csrf_field()?><input name="mode" type="hidden" value="create"><div class="field"><label>Supplier <span>*</span></label><select name="supplier_id" required><option value="">Choose a supplier</option><?php foreach($suppliers as $s):?><option value="<?=$s['supplier_id']?>"><?=e($s['supplier_name'])?></option><?php endforeach;?></select></div><div class="line-title">Items to order</div><div class="line-items" id="line-items"><div class="line-item purchase-item"><div class="field"><label>Product</label><select name="product_id[]" required class="product-choice"><option value="">Select product</option><?php foreach($products as $p):?><option value="<?=$p['product_id']?>"><?=e($p['product_name'])?> · <?=e($p['sku'])?></option><?php endforeach;?></select></div><div class="field qty-field"><label>Qty</label><input name="quantity[]" type="number" min="1" value="1" required></div><div class="field cost-field"><label>Unit cost</label><input name="unit_cost[]" type="number" min="0" step="0.01" value="0.00" required></div><button type="button" class="icon-button remove-line" title="Remove item"><?=icon('trash')?></button></div></div><button class="btn text-button" id="add-line" type="button"><?=icon('plus')?> Add another item</button><div class="field"><label>Notes</label><textarea rows="2" maxlength="255" name="notes" placeholder="Notes about this order"></textarea></div><div class="form-actions"><button type="submit" class="btn primary"><?=icon('check')?> Create order</button></div></form></section>
<section class="panel"><div class="panel-head"><div><h3>Purchase order history</h3><p>Receive pending orders to increase inventory</p></div></div><div class="table-wrap"><table><thead><tr><th>Order / Supplier</th><th>Items</th><th class="right">Value</th><th>Status / Action</th></tr></thead><tbody><?php foreach($orders as $o):?><tr><td><strong>PO-<?=str_pad($o['purchase_order_id'],4,'0',STR_PAD_LEFT)?></strong><small class="table-sub"><?=e($o['supplier_name'])?></small><small class="table-sub"><?=e(date('d M Y',strtotime($o['order_date'])))?></small></td><td><span class="wrap-sub"><?=e($o['items'])?></span></td><td class="right amount"><?=cash($o['total'])?></td><td><?=status_badge($o['status'])?><?php if($o['status']==='Pending'):?><form method="post" class="receive-form" onsubmit="return confirm('Confirm delivery? This will add ordered quantities to stock.')"><?=csrf_field()?><input type="hidden" name="mode" value="receive"><input type="hidden" name="id" value="<?=$o['purchase_order_id']?>"><button class="receive-btn" type="submit">Mark received <?=icon('arrow')?></button></form><?php endif;?></td></tr><?php endforeach;if(!$orders):?><tr><td colspan="4" class="empty">No purchase orders yet.</td></tr><?php endif;?></tbody></table></div></section></div>
<?php render_footer(); ?>
