<?php
require_once __DIR__.'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST')safe_action(function(){
 $name=required_text($_POST['supplier_name']??'',150);
 $person=optional_text($_POST['contact_person']??'',100);
 $email=optional_text($_POST['email']??'',150);
 if($email!==null&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
 $phone=optional_text($_POST['phone']??'',30);$address=optional_text($_POST['address']??'',255);
 if(!empty($_POST['id'])){
  $stmt=db()->prepare('UPDATE suppliers SET supplier_name=?,contact_person=?,email=?,phone=?,address=? WHERE supplier_id=?');$stmt->execute([$name,$person,$email,$phone,$address,valid_id($_POST['id'])]);set_flash('Supplier updated.');
 }else{
  $stmt=db()->prepare('INSERT INTO suppliers(supplier_name,contact_person,email,phone,address) VALUES (?,?,?,?,?)');$stmt->execute([$name,$person,$email,$phone,$address]);set_flash('Supplier added.');
 }go('suppliers.php');
},'suppliers.php');
$edit=isset($_GET['edit'])?one('SELECT * FROM suppliers WHERE supplier_id=?',[valid_id($_GET['edit'])]):false;
$suppliers=rows('SELECT s.*,COUNT(DISTINCT po.purchase_order_id) order_count FROM suppliers s LEFT JOIN purchase_orders po ON s.supplier_id=po.supplier_id GROUP BY s.supplier_id ORDER BY s.supplier_name');
render_header('Suppliers','suppliers.php');page_heading('PARTNERS','Suppliers','Maintain the vendors who source your store inventory.');
?>
<div class="two-column form-layout"><section class="panel"><div class="panel-head"><div><h3><?=$edit?'Edit supplier':'Add supplier'?></h3><p>Supplier details and contact information</p></div></div><form class="form-body" method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=e($edit['supplier_id']??'')?>"><div class="field"><label>Supplier name <span>*</span></label><input name="supplier_name" required maxlength="150" value="<?=e($edit['supplier_name']??'')?>" placeholder="Company name"></div><div class="field"><label>Contact person</label><input name="contact_person" maxlength="100" value="<?=e($edit['contact_person']??'')?>" placeholder="Full name"></div><div class="field"><label>Email address</label><input name="email" type="email" maxlength="150" value="<?=e($edit['email']??'')?>" placeholder="contact@example.com"></div><div class="field"><label>Phone</label><input name="phone" maxlength="30" value="<?=e($edit['phone']??'')?>" placeholder="07XXXXXXXX"></div><div class="field"><label>Address</label><textarea name="address" rows="2" maxlength="255" placeholder="Street, city"><?=e($edit['address']??'')?></textarea></div><div class="form-actions"><button class="btn primary" type="submit"><?=icon('check')?><?=$edit?'Save changes':'Add supplier'?></button><?php if($edit):?><a href="suppliers.php" class="btn ghost">Cancel</a><?php endif;?></div></form></section>
<section class="panel"><div class="panel-head"><div><h3>Supplier directory</h3><p><?=count($suppliers)?> vendors registered</p></div></div><div class="table-wrap"><table><thead><tr><th>Supplier</th><th>Contact</th><th>Orders</th><th></th></tr></thead><tbody><?php foreach($suppliers as $s):?><tr><td><strong><?=e($s['supplier_name'])?></strong><small class="table-sub"><?=e($s['contact_person']??'No contact person')?></small></td><td><?=e($s['phone']??'—')?><small class="table-sub"><?=e($s['email']??'')?></small></td><td><?=e($s['order_count'])?></td><td><a class="icon-button" title="Edit" href="suppliers.php?edit=<?=$s['supplier_id']?>"><?=icon('edit')?></a></td></tr><?php endforeach;if(!$suppliers):?><tr><td colspan="4" class="empty">No suppliers yet.</td></tr><?php endif;?></tbody></table></div></section></div>
<?php render_footer(); ?>
