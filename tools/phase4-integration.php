<?php

declare(strict_types=1);

$sessionPath=__DIR__.'/qa-sessions';
if(!is_dir($sessionPath))mkdir($sessionPath,0700,true);
ini_set('session.save_path',$sessionPath);

require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/admin/AdminRepository.php';
require dirname(__DIR__).'/includes/admin/AdminService.php';
require dirname(__DIR__).'/includes/admin/helpers.php';

$pdo=db();$repository=admin_repository();$service=admin_service();$suffix=bin2hex(random_bytes(5));$email='phase4-'.$suffix.'@example.test';$checks=[];$ids=['admin'=>0,'user'=>0,'product'=>0,'variant'=>0,'order'=>0,'coupon'=>0,'review'=>0];$settingsBefore=$repository->settings();
$check=static function(bool$condition,string$label,mixed$actual=null)use(&$checks):void{$checks[]=['label'=>$label,'passed'=>$condition,'actual'=>$actual];if(!$condition)throw new RuntimeException($label.' failed.');};
$cleanup=static function()use($pdo,&$ids,$email,$settingsBefore):void{
    try{
        if($ids['order']){$statement=$pdo->prepare('DELETE FROM orders WHERE id=?');$statement->execute([$ids['order']]);}
        if($ids['review']){$statement=$pdo->prepare('DELETE FROM reviews WHERE id=?');$statement->execute([$ids['review']]);}
        if($ids['variant']){$statement=$pdo->prepare('DELETE FROM inventory_adjustments WHERE variant_id=?');$statement->execute([$ids['variant']]);$statement=$pdo->prepare('DELETE FROM product_variants WHERE id=?');$statement->execute([$ids['variant']]);}
        if($ids['product']){$statement=$pdo->prepare('DELETE FROM products WHERE id=?');$statement->execute([$ids['product']]);}
        if($ids['coupon']){$statement=$pdo->prepare('DELETE FROM coupons WHERE id=?');$statement->execute([$ids['coupon']]);}
        if($ids['user']){$statement=$pdo->prepare('DELETE FROM users WHERE id=?');$statement->execute([$ids['user']]);}
        if($ids['admin']){$statement=$pdo->prepare('DELETE FROM admin_audit_logs WHERE admin_id=?');$statement->execute([$ids['admin']]);$statement=$pdo->prepare('DELETE FROM inventory_adjustments WHERE admin_id=?');$statement->execute([$ids['admin']]);$statement=$pdo->prepare('DELETE FROM admins WHERE id=?');$statement->execute([$ids['admin']]);}
        $statement=$pdo->prepare('DELETE FROM admin_login_attempts WHERE email=?');$statement->execute([$email]);
        $statement=$pdo->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');foreach($settingsBefore as$key=>$value)$statement->execute([$key,$value]);
    }catch(Throwable$exception){fwrite(STDERR,'Cleanup warning: '.$exception->getMessage().PHP_EOL);}
};
register_shutdown_function($cleanup);

try{
    foreach(['admins','admin_login_attempts','inventory_adjustments','order_status_history','admin_audit_logs']as$table){$statement=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$statement->execute([$table]);$check((int)$statement->fetchColumn()===1,'schema table '.$table);}
    $password='QA!'.bin2hex(random_bytes(8)).'aA9';$ids['admin']=$repository->createAdmin('Phase 4 QA',$email,password_hash($password,PASSWORD_DEFAULT),'SUPER_ADMIN');
    $_SERVER['REMOTE_ADDR']='127.0.0.42';
    try{$service->login($email,'wrong-password');$check(false,'invalid admin login rejected');}catch(DomainException$exception){$check($exception->getMessage()==='Invalid email or password.','invalid login uses generic error');}
    for($attempt=0;$attempt<4;$attempt++){try{$service->login($email,'wrong-password');}catch(DomainException){}}
    try{$service->login($email,$password);$check(false,'admin login throttle activates');}catch(DomainException$exception){$check($exception->getMessage()==='Unable to sign in. Wait a few minutes and try again.','admin login throttle activates');}
    $statement=$pdo->prepare('DELETE FROM admin_login_attempts WHERE email=?');$statement->execute([$email]);
    $loggedIn=$service->login($email,$password);$check((int)$loggedIn['id']===$ids['admin']&&($_SESSION['admin_id']??0)===$ids['admin'],'valid dedicated admin login');
    $check(current_user_id()===null,'admin login does not create customer access');

    $category=(int)$pdo->query('SELECT id FROM categories WHERE is_active=1 ORDER BY id LIMIT 1')->fetchColumn();$collection=(int)$pdo->query('SELECT id FROM collections WHERE is_active=1 ORDER BY id LIMIT 1')->fetchColumn();
    $productInput=['category_id'=>$category,'collection_id'=>$collection,'name'=>'Phase 4 QA Product '.$suffix,'slug'=>'phase-4-qa-'.$suffix,'short_description'=>'Admin integration product.','description'=>'Admin integration product.','materials'=>'QA fabric.','care'=>'QA only.','price'=>'1000','sale_price'=>'900','label'=>'QA','popularity'=>'1','keywords'=>'qa, admin','status'=>'active','is_featured'=>'1'];
    $ids['product']=$service->saveProduct($productInput,null);$product=$repository->product($ids['product']);$check($product&&$product['status']==='active'&&(float)$product['sale_price']===900.0,'product create with pricing and status');
    $check($repository->products(['q'=>$suffix],1,5)['total']===1,'product search uses prepared parameters');
    $productInput['name']='Phase 4 QA Product Updated '.$suffix;$service->saveProduct($productInput,$ids['product']);$check($repository->product($ids['product'])['name']===$productInput['name'],'product edit');
    $variantInput=['product_id'=>$ids['product'],'color_name'=>'Black','color_slug'=>'black','color_hex'=>'#0A0A0A','size'=>'M','sku'=>'QA-'.$suffix.'-M','stock_quantity'=>'8','is_active'=>'1'];
    $ids['variant']=$service->saveVariant($variantInput,null);$check((int)$repository->lockVariant($ids['variant'])['stock_quantity']===8,'variant create');
    $check($repository->inventory(['q'=>$variantInput['sku']],1,5)['total']===1,'inventory search uses prepared parameters');
    try{$service->saveVariant($variantInput,null);$check(false,'duplicate SKU rejected');}catch(PDOException$exception){$check($exception->getCode()==='23000','duplicate SKU rejected');}
    $newStock=$service->adjustStock($ids['variant'],3,'restock','Integration restock.');$check($newStock===11,'stock adjustment');
    $statement=$pdo->prepare('SELECT previous_stock,new_stock,reason FROM inventory_adjustments WHERE variant_id=? ORDER BY id DESC LIMIT 1');$statement->execute([$ids['variant']]);$adjust=$statement->fetch();$check($adjust&&(int)$adjust['previous_stock']===8&&(int)$adjust['new_stock']===11&&$adjust['reason']==='restock','inventory adjustment audit');

    $statement=$pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,status) VALUES (?,?,?,?,\'active\')');$statement->execute(['Phase','Four','customer-'.$suffix.'@example.test',password_hash('Customer-QA-123!',PASSWORD_DEFAULT)]);$ids['user']=(int)$pdo->lastInsertId();
    $check($repository->customers(['q'=>'customer-'.$suffix],1,5)['total']===1,'customer search uses prepared parameters');
    $orders=new OrderRepository($pdo);$ids['order']=$orders->create(['user_id'=>$ids['user'],'coupon_id'=>null,'order_number'=>'QA-'.$suffix,'customer_first_name'=>'Phase','customer_last_name'=>'Four','customer_email'=>'customer-'.$suffix.'@example.test','customer_phone'=>'+920000000000','shipping_label'=>'QA','shipping_recipient'=>'Phase Four','shipping_address_line_1'=>'QA Street','shipping_address_line_2'=>null,'shipping_city'=>'Karachi','shipping_province'=>'Sindh','shipping_postal_code'=>'75000','shipping_country'=>'Pakistan','shipping_method'=>'Standard Pakistan Delivery','payment_method'=>'demo_card','payment_status'=>'paid','order_status'=>'processing','subtotal'=>1800,'discount_total'=>0,'shipping_total'=>0,'total'=>1800,'coupon_code'=>null]);
    $orders->addItem($ids['order'],['product_id'=>$ids['product'],'variant_id'=>$ids['variant'],'name'=>$productInput['name'],'variant_description'=>'Black / M','sku'=>$variantInput['sku'],'image_path'=>'','unit_price'=>900,'quantity'=>2,'line_total'=>1800]);$repository->updateVariantStock($ids['variant'],9);
    $check($repository->orders(['q'=>'QA-'.$suffix],1,5)['total']===1,'order search uses prepared parameters');
    $service->updateOrder($ids['order'],'cancelled','paid','Integration cancellation.');$afterFirst=(int)$repository->lockVariant($ids['variant'])['stock_quantity'];$service->updateOrder($ids['order'],'cancelled','paid','Idempotency retry.');$afterSecond=(int)$repository->lockVariant($ids['variant'])['stock_quantity'];$check($afterFirst===11&&$afterSecond===11,'cancelled order restores stock exactly once');
    $statement=$pdo->prepare("SELECT COUNT(*) FROM inventory_adjustments WHERE variant_id=? AND reason='order_cancellation'");$statement->execute([$ids['variant']]);$check((int)$statement->fetchColumn()===1,'cancellation adjustment logged once');
    $statement=$pdo->prepare('SELECT COUNT(*) FROM order_status_history WHERE order_id=?');$statement->execute([$ids['order']]);$check((int)$statement->fetchColumn()>=1,'order status history persists');
    try{$service->updateOrder($ids['order'],'shipped','paid','Invalid transition.');$check(false,'invalid order transition rejected');}catch(DomainException){$check(true,'invalid order transition rejected');}

    $repository->updateCustomerStatus($ids['user'],'disabled');$check($repository->customer($ids['user'])['status']==='disabled','customer disable preserves profile');$repository->updateCustomerStatus($ids['user'],'active');
    try{$service->saveCoupon(['code'=>'BAD','type'=>'percentage','value'=>'101','minimum_order'=>'0'],null);$check(false,'invalid coupon rejected');}catch(DomainException){$check(true,'invalid coupon rejected');}
    $ids['coupon']=$service->saveCoupon(['code'=>'QA'.$suffix,'type'=>'percentage','value'=>'10','minimum_order'=>'500','is_active'=>'1','usage_limit'=>'2','per_customer_limit'=>'1'],null);$check((int)$repository->coupon($ids['coupon'])['usage_limit']===2,'coupon create and limits');

    $statement=$pdo->prepare("INSERT INTO reviews (user_id,product_id,rating,content,status) VALUES (?,?,5,?,'pending')");$statement->execute([$ids['user'],$ids['product'],'Phase 4 moderation QA.']);$ids['review']=(int)$pdo->lastInsertId();$repository->updateReviewStatus($ids['review'],'approved');$approved=(new ReviewRepository($pdo))->approvedForProduct($ids['product']);$check(count($approved)===1,'approved review becomes public');$repository->updateReviewStatus($ids['review'],'hidden');$check((new ReviewRepository($pdo))->approvedForProduct($ids['product'])===[],'hidden review leaves public storefront');
    $check($repository->reviews(['q'=>'Phase 4 moderation QA'],1,5)['total']===1,'review search uses prepared parameters');
    $check($repository->deleteProductIfSafe($ids['product'])===false,'referenced product cannot be hard deleted');

    $service->saveSettings(['store_name'=>'VANTA','contact_email'=>'qa@example.test','currency'=>'PKR','shipping_standard_pkr'=>'325','free_shipping_threshold_pkr'=>'16000','low_stock_threshold'=>'6','brand_tagline'=>'Built for after dark.','support_email'=>'support@example.test','default_country'=>'Pakistan']);$statement=$pdo->prepare("SELECT setting_value FROM settings WHERE setting_key='shipping_standard_pkr'");$statement->execute();$check($statement->fetchColumn()==='325','store setting persistence');
    $direct=(float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid' AND order_status<>'cancelled'")->fetchColumn();$reported=(float)$repository->analytics(null)['kpis']['revenue'];$check(abs($direct-$reported)<0.01,'analytics revenue matches direct aggregation');
    $injection=$repository->products(['sort'=>'p.id; DROP TABLE products'],1,5);$check(is_array($injection['items']),'unsafe sort input falls back to allowlist');
    $check(admin_csv_cell('=HYPERLINK("https://example.test")')[0]==="'",'CSV formula injection is neutralized');
    $_SERVER['REQUEST_METHOD']='POST';$_POST=['csrf_token'=>'invalid'];try{admin_post_guard();$check(false,'CSRF rejection');}catch(DomainException){$check(true,'CSRF rejection');}
    $service->archiveProduct($ids['product']);$check((new ProductRepository($pdo))->findBySlug($productInput['slug'])===null,'archived product leaves storefront');
    $statement=$pdo->prepare('SELECT COUNT(*) FROM admin_audit_logs WHERE admin_id=?');$statement->execute([$ids['admin']]);$check((int)$statement->fetchColumn()>0,'sensitive admin actions create audit entries');
    $service->logout();$check(current_admin()===null,'admin logout clears admin session');
    echo json_encode(['passed'=>true,'checkCount'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable$exception){fwrite(STDERR,$exception::class.': '.$exception->getMessage().PHP_EOL);echo json_encode(['passed'=>false,'checkCount'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;exit(1);}
