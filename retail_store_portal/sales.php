<?php
require_once __DIR__.'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST')safe_action(function(){
 $items=line_items();
 $customer=($_POST['customer_id']??'')!==''?valid_id($_POST['customer_id']):null;
 $notes=optional_text($_POST['notes']??'',255);
 if($customer!==null&&!one('SELECT customer_id FROM customers WHERE customer_id=?',[$customer]))throw new RuntimeException('Customer not found.');
 $pdo=db();$pdo->beginTransaction();
 try{
    // Lock products to prevent two sales from deducting the same stock simultaneously.
    $locked=[];$ids=array_keys($items);sort($ids,SORT_NUMERIC);
    $find=$pdo->prepare('SELECT product_id,product_name,stock_quantity,selling_price FROM products WHERE product_id=? FOR UPDATE');
    foreach($ids as $id){$find->execute([$id]);$p=$find->fetch();if(!$p)throw new RuntimeException('A selected product no longer exists.');if((int)$p['stock_quantity']<$items[$id])throw new RuntimeException('Insufficient stock for '.$p['product_name'].' (available: '.$p['stock_quantity'].').');$locked[$id]=$p;}
    $stmt=$pdo->prepare('INSERT INTO sales(customer_id,notes) VALUES (?,?)');$stmt->execute([$customer,$notes]);$saleId=(int)$pdo->lastInsertId();
    $itemStmt=$pdo->prepare('INSERT INTO sale_items(sale_id,product_id,quantity,unit_price) VALUES (?,?,?,?)');
    $stockStmt=$pdo->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE product_id=?');
    foreach($locked as $id=>$p){$itemStmt->execute([$saleId,$id,$items[$id],$p['selling_price']]);$stockStmt->execute([$items[$id],$id]);}
    $pdo->commit();set_flash('Sale #'.$saleId.' recorded. Inventory updated automatically.');go('sales.php');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
},'sales.php');
$products=rows('SELECT product_id,product_name,sku,selling_price,stock_quantity FROM products ORDER BY product_name');
$customers=rows('SELECT customer_id,customer_name FROM customers ORDER BY customer_name');
$sales=rows('SELECT s.sale_id,s.sale_date,COALESCE(c.customer_name,\'Walk-in customer\') customer_name,SUM(si.quantity) units,SUM(si.quantity*si.unit_price) total, GROUP_CONCAT(CONCAT(p.product_name,\' ×\',si.quantity) ORDER BY p.product_name SEPARATOR \', \') items FROM sales s LEFT JOIN customers c ON s.customer_id=c.customer_id JOIN sale_items si ON s.sale_id=si.sale_id JOIN products p ON p.product_id=si.product_id GROUP BY s.sale_id,s.sale_date,c.customer_name ORDER BY s.sale_date DESC,s.sale_id DESC LIMIT 30');
render_header('Sales','sales.php');page_heading('TRANSACTIONS','Sales','Record customer purchases and automatically adjust stock.');
?>
<div class="two-column form-layout"><section class="panel"><div class="panel-head"><div><h3>New sale</h3><p>Add one or more products to a transaction</p></div></div><form method="post" class="form-body" data-lines="sale"><?=csrf_field()?><div class="field"><label>Customer</label><select name="customer_id"><option value="">Walk-in customer</option><?php foreach($customers as $c):?><option value="<?=$c['customer_id']?>"><?=e($c['customer_name'])?></option><?php endforeach;?></select><div class="help">Choose a customer or leave blank for a walk-in purchase.</div></div><div class="line-title">Products in this sale</div><div class="line-items" id="line-items"><div class="line-item"><div class="field"><label>Product</label><select name="product_id[]" class="product-choice" required><option value="">Select product</option><?php foreach($products as $p):?><option value="<?=$p['product_id']?>" data-price="<?=e($p['selling_price'])?>" data-stock="<?=e($p['stock_quantity'])?>"><?=e($p['product_name'])?> · <?=e($p['sku'])?> (<?=$p['stock_quantity']?> left)</option><?php endforeach;?></select></div><div class="field qty-field"><label>Qty</label><input name="quantity[]" type="number" min="1" value="1" required></div><button type="button" class="icon-button remove-line" aria-label="Remove item" title="Remove item"><?=icon('trash')?></button><small class="line-hint"></small></div></div><button class="btn text-button" type="button" id="add-line"><?=icon('plus')?> Add another item</button><div class="field"><label>Notes (optional)</label><textarea name="notes" maxlength="255" rows="2" placeholder="Notes about the transaction"></textarea></div><div class="sale-total"><div><span>Estimated total</span><small>Final price is verified on the server</small></div><strong id="sale-total">Rs. 0.00</strong></div><div class="form-actions"><button type="submit" class="btn primary"><?=icon('check')?> Complete sale</button></div></form></section>
<section class="panel"><div class="panel-head"><div><h3>Sales history</h3><p>Most recent 30 sales with customer and item details</p></div><a class="text-link" href="reports.php">Sales report <?=icon('arrow')?></a></div><div class="table-wrap"><table><thead><tr><th>Sale / Date</th><th>Customer / Items</th><th class="right">Total</th></tr></thead><tbody><?php foreach($sales as $s):?><tr><td><strong>#<?=str_pad($s['sale_id'],4,'0',STR_PAD_LEFT)?></strong><small class="table-sub"><?=e(date('d M Y, h:i A',strtotime($s['sale_date'])))?></small></td><td><?=e($s['customer_name'])?><small class="table-sub wrap-sub"><?=e($s['items'])?></small></td><td class="right amount"><?=cash($s['total'])?></td></tr><?php endforeach;if(!$sales):?><tr><td colspan="3" class="empty">No sales recorded.</td></tr><?php endif;?></tbody></table></div></section></div>
<?php render_footer(); ?>
