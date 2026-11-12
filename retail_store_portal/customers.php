<?php
require_once __DIR__.'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST')safe_action(function(){
 $name=required_text($_POST['customer_name']??'',150);$email=optional_text($_POST['email']??'',150);
 if($email!==null&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
 $phone=optional_text($_POST['phone']??'',30);$address=optional_text($_POST['address']??'',255);
 if(!empty($_POST['id'])){
  $stmt=db()->prepare('UPDATE customers SET customer_name=?,email=?,phone=?,address=? WHERE customer_id=?');$stmt->execute([$name,$email,$phone,$address,valid_id($_POST['id'])]);set_flash('Customer updated.');
 }else{
  $stmt=db()->prepare('INSERT INTO customers(customer_name,email,phone,address) VALUES (?,?,?,?)');$stmt->execute([$name,$email,$phone,$address]);set_flash('Customer added.');
 }go('customers.php');
},'customers.php');
$edit=isset($_GET['edit'])?one('SELECT * FROM customers WHERE customer_id=?',[valid_id($_GET['edit'])]):false;
$customers=rows('SELECT c.*,COUNT(DISTINCT s.sale_id) sale_count FROM customers c LEFT JOIN sales s ON c.customer_id=s.customer_id GROUP BY c.customer_id ORDER BY c.customer_name');
render_header('Customers','customers.php');page_heading('RELATIONSHIPS','Customers','Keep customer information connected to every sale.');
?>
<div class="two-column form-layout"><section class="panel"><div class="panel-head"><div><h3><?=$edit?'Edit customer':'Add customer'?></h3><p>Capture basic customer contact details</p></div></div><form class="form-body" method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=e($edit['customer_id']??'')?>"><div class="field"><label>Customer name <span>*</span></label><input required name="customer_name" maxlength="150" value="<?=e($edit['customer_name']??'')?>" placeholder="Full name"></div><div class="field"><label>Email address</label><input type="email" name="email" maxlength="150" value="<?=e($edit['email']??'')?>" placeholder="name@example.com"></div><div class="field"><label>Phone number</label><input name="phone" maxlength="30" value="<?=e($edit['phone']??'')?>" placeholder="07XXXXXXXX"></div><div class="field"><label>Address</label><textarea name="address" rows="2" maxlength="255"><?=e($edit['address']??'')?></textarea></div><div class="form-actions"><button type="submit" class="btn primary"><?=icon('check')?><?=$edit?'Save changes':'Add customer'?></button><?php if($edit):?><a href="customers.php" class="btn ghost">Cancel</a><?php endif;?></div></form></section>
<section class="panel"><div class="panel-head"><div><h3>Customer directory</h3><p><?=count($customers)?> registered customers</p></div></div><div class="table-wrap"><table><thead><tr><th>Customer</th><th>Phone</th><th>Sales</th><th></th></tr></thead><tbody><?php foreach($customers as $c):?><tr><td><strong><?=e($c['customer_name'])?></strong><small class="table-sub"><?=e($c['email']??'')?></small></td><td><?=e($c['phone']??'—')?></td><td><?=e($c['sale_count'])?></td><td><a class="icon-button" title="Edit" href="customers.php?edit=<?=$c['customer_id']?>"><?=icon('edit')?></a></td></tr><?php endforeach;if(!$customers):?><tr><td colspan="4" class="empty">No customers yet.</td></tr><?php endif;?></tbody></table></div></section></div>
<?php render_footer(); ?>
