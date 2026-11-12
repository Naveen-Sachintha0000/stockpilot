<?php
require_once __DIR__.'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST')safe_action(function(){
    $mode=$_POST['mode']??'save';
    if($mode==='delete'){
        $id=valid_id($_POST['id']??null);
        $stmt=db()->prepare('DELETE FROM products WHERE product_id=?');$stmt->execute([$id]);
        set_flash('Product deleted.');go('products.php');
    }
    $sku=required_text($_POST['sku']??'',40);
    $name=required_text($_POST['product_name']??'',150);
    $category=valid_id($_POST['category_id']??null);
    $price=nonneg_money($_POST['selling_price']??null);
    $reorder=nonneg_int($_POST['reorder_level']??null);
    if(!one('SELECT category_id FROM categories WHERE category_id=?',[$category]))throw new RuntimeException('Select an existing category.');
    if(!empty($_POST['id'])){
        $id=valid_id($_POST['id']);
        $stmt=db()->prepare('UPDATE products SET sku=?,product_name=?,category_id=?,selling_price=?,reorder_level=? WHERE product_id=?');
        $stmt->execute([$sku,$name,$category,$price,$reorder,$id]);
        set_flash('Product updated.');
    }else{
        $stock=nonneg_int($_POST['stock_quantity']??null);
        $stmt=db()->prepare('INSERT INTO products(sku,product_name,category_id,selling_price,stock_quantity,reorder_level) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$sku,$name,$category,$price,$stock,$reorder]);
        set_flash('New product added to inventory.');
    }
    go('products.php');
},'products.php');
$edit=isset($_GET['edit'])?one('SELECT * FROM products WHERE product_id=?',[valid_id($_GET['edit'])]):false;
$cats=rows('SELECT * FROM categories ORDER BY category_name');
$search=trim((string)($_GET['q']??''));
$products=rows('SELECT p.*,c.category_name FROM products p JOIN categories c ON c.category_id=p.category_id WHERE (?="" OR p.product_name LIKE ? OR p.sku LIKE ?) ORDER BY p.product_id DESC',[$search,'%'.$search.'%','%'.$search.'%']);
render_header('Products','products.php');page_heading('INVENTORY','Products','Organize your catalog and monitor inventory levels.','<a class="btn secondary" href="purchases.php">'.icon('truck').' Restock via purchase order</a>');
?>
<div class="two-column form-layout"><section class="panel"><div class="panel-head"><div><h3><?=$edit?'Edit product':'Add new product'?></h3><p><?=$edit?'Update product details below':'Fill in details to create a new inventory item'?></p></div></div><form class="form-body" method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=e($edit['product_id']??'')?>"><div class="field"><label>SKU <span>*</span></label><input name="sku" required maxlength="40" placeholder="e.g. ELEC-004" value="<?=e($edit['sku']??'')?>"></div><div class="field"><label>Product name <span>*</span></label><input name="product_name" required maxlength="150" placeholder="e.g. Wireless Keyboard" value="<?=e($edit['product_name']??'')?>"></div><div class="field"><label>Category <span>*</span></label><select name="category_id" required><option value="">Select category</option><?php foreach($cats as $c):?><option value="<?=$c['category_id']?>" <?=($edit['category_id']??'')==$c['category_id']?'selected':''?>><?=e($c['category_name'])?></option><?php endforeach;?></select></div><div class="form-grid"><div class="field"><label>Selling price (LKR) <span>*</span></label><input name="selling_price" type="number" min="0" max="9999999999" step="0.01" required value="<?=e($edit['selling_price']??'')?>" placeholder="0.00"></div><div class="field"><label>Reorder level <span>*</span></label><input name="reorder_level" type="number" min="0" required value="<?=e($edit['reorder_level']??'5')?>"></div></div><?php if(!$edit):?><div class="field"><label>Initial stock <span>*</span></label><input name="stock_quantity" type="number" min="0" required value="0"><div class="help">After creation, stock changes through sales and received purchase orders.</div></div><?php else:?><div class="inline-note">Current stock: <strong><?=e($edit['stock_quantity'])?> units</strong>. Stock is adjusted using sales and received orders.</div><?php endif;?><div class="form-actions"><button class="btn primary" type="submit"><?=icon('check')?><?=$edit?'Save changes':'Add product'?></button><?php if($edit):?><a href="products.php" class="btn ghost">Cancel</a><?php endif;?></div></form></section>
<section class="panel"><div class="panel-head"><div><h3>Product catalog</h3><p><?=count($products)?> matching items</p></div><form class="search-box" method="get"><?=icon('search')?><input name="q" value="<?=e($search)?>" placeholder="Search products" aria-label="Search products"></form></div><div class="table-wrap"><table><thead><tr><th>Product / SKU</th><th>Category</th><th class="right">Price</th><th class="right">Stock</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($products as $p):?><tr><td><strong><?=e($p['product_name'])?></strong><small class="table-sub"><?=e($p['sku'])?></small></td><td><?=e($p['category_name'])?></td><td class="right amount"><?=cash($p['selling_price'])?></td><td class="right"><?=e($p['stock_quantity'])?></td><td><span class="pill <?=$p['stock_quantity']<=$p['reorder_level']?'danger':'positive'?>"><?=$p['stock_quantity']<=$p['reorder_level']?'Low stock':'Healthy'?></span></td><td><a href="products.php?edit=<?=$p['product_id']?>" class="icon-button" title="Edit product"><?=icon('edit')?></a></td></tr><?php endforeach;if(!$products):?><tr><td class="empty" colspan="6">No products found.</td></tr><?php endif;?></tbody></table></div></section></div>
<?php render_footer(); ?>
