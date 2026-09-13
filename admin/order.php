<?php

declare(strict_types=1);

require __DIR__.'/_init.php';
require_admin();
$id=(int)($_GET['id']??$_POST['id']??0);

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    try{
        admin_post_guard();
        admin_service()->updateOrder($id,(string)($_POST['order_status']??''),(string)($_POST['payment_status']??''),(string)($_POST['note']??''));
        flash('success','Order updated.');
    }catch(Throwable$exception){admin_flash_exception($exception);}
    admin_redirect('order.php?id='.$id);
}

$order=admin_repository()->order($id);
if(!$order){http_response_code(404);exit('Order not found.');}
$adminTitle=(string)$order['order_number'];
$adminPage='orders';
$adminSection='Sales / Orders';
$adminActions='<div class="page-actions"><a class="button button--quiet" href="'.e(url('admin/orders.php')).'">Back to orders</a></div>';
require __DIR__.'/_header.php';
?>
<div class="detail-grid">
    <div>
        <section class="panel">
            <header class="panel__header"><h2>Order items</h2><span><?= count($order['items']) ?> lines</span></header>
            <div class="table-wrap"><table class="data-table"><thead><tr><th>Item</th><th>Variant</th><th>SKU</th><th class="numeric">Qty</th><th class="numeric">Unit</th><th class="numeric">Line total</th></tr></thead><tbody>
            <?php foreach($order['items']as$item): ?><tr>
                <td><div style="display:flex;align-items:center;gap:.7rem"><?php if($item['image_path']): ?><img class="thumb" src="<?= e(media_url((string)$item['image_path'])) ?>" alt=""><?php endif; ?><strong><?= e((string)$item['product_name']) ?></strong></div></td>
                <td><?= e((string)$item['variant_description']) ?></td><td><?= e((string)$item['sku']) ?></td><td class="numeric"><?= (int)$item['quantity'] ?></td><td class="numeric"><?= admin_money($item['unit_price']) ?></td><td class="numeric"><strong><?= admin_money($item['line_total']) ?></strong></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
            <div class="panel__body"><dl class="definition-list">
                <dt>Subtotal</dt><dd><?= admin_money($order['subtotal']) ?></dd><dt>Discount</dt><dd>− <?= admin_money($order['discount_total']) ?><?= $order['coupon_code']?' · '.e((string)$order['coupon_code']):'' ?></dd><dt>Shipping</dt><dd><?= admin_money($order['shipping_total']) ?></dd><dt>Total</dt><dd><strong><?= admin_money($order['total']) ?></strong></dd>
            </dl></div>
        </section>
        <section class="panel" style="margin-top:1rem">
            <header class="panel__header"><h2>Customer and delivery</h2><?php if($order['user_id']): ?><a href="<?= e(url('admin/customer.php?id='.(int)$order['user_id'])) ?>">Customer profile</a><?php endif; ?></header>
            <div class="panel__body"><div class="field-row">
                <dl class="definition-list"><dt>Customer</dt><dd><?= e($order['customer_first_name'].' '.$order['customer_last_name']) ?></dd><dt>Email</dt><dd><a href="mailto:<?= e((string)$order['customer_email']) ?>"><?= e((string)$order['customer_email']) ?></a></dd><dt>Phone</dt><dd><?= e((string)$order['customer_phone']) ?></dd><dt>Placed</dt><dd><?= e((string)$order['placed_at']) ?></dd></dl>
                <dl class="definition-list"><dt>Recipient</dt><dd><?= e((string)$order['shipping_recipient']) ?></dd><dt>Address</dt><dd><?= e((string)$order['shipping_address_line_1']) ?><?= $order['shipping_address_line_2']?'<br>'.e((string)$order['shipping_address_line_2']):'' ?><br><?= e($order['shipping_city'].', '.$order['shipping_province'].' '.$order['shipping_postal_code']) ?><br><?= e((string)$order['shipping_country']) ?></dd><dt>Method</dt><dd><?= e((string)$order['shipping_method']) ?></dd></dl>
            </div></div>
        </section>
    </div>
    <aside>
        <form class="form-section form-stack" method="post" <?= $order['order_status']==='cancelled'?'':'data-confirm="Apply this order status change? Cancelling restores stock exactly once."' ?>>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>"><h2>Order controls</h2>
            <div><span class="<?= e(admin_status_class((string)$order['order_status'])) ?>"><?= e(format_status((string)$order['order_status'])) ?></span> <span class="<?= e(admin_status_class((string)$order['payment_status'])) ?>"><?= e(format_status((string)$order['payment_status'])) ?></span></div>
            <dl class="definition-list"><dt>Payment method</dt><dd><?= e(format_status((string)$order['payment_method'])) ?></dd><dt>Email delivery</dt><dd><span class="<?= e(admin_status_class((string)($order['confirmation_email_status']??'pending'))) ?>"><?= e(format_status((string)($order['confirmation_email_status']??'Not queued'))) ?></span></dd></dl>
            <label class="field"><span>Order status</span><select name="order_status"><?php foreach(['pending','processing','shipped','delivered','cancelled']as$status): ?><option value="<?= $status ?>" <?= $order['order_status']===$status?'selected':'' ?>><?= e(format_status($status)) ?></option><?php endforeach; ?></select></label>
            <label class="field"><span>Payment status</span><select name="payment_status"><?php foreach(['pending','paid','failed','cod_pending']as$status): ?><option value="<?= $status ?>" <?= $order['payment_status']===$status?'selected':'' ?>><?= e(format_status($status)) ?></option><?php endforeach; ?></select></label>
            <label class="field"><span>Internal note</span><input name="note" maxlength="255"></label>
            <?php if($order['inventory_restored_at']): ?><p class="status status--active">Stock restored <?= e((string)$order['inventory_restored_at']) ?></p><?php endif; ?>
            <button class="button button--primary" type="submit" <?= in_array($order['order_status'],['delivered','cancelled'],true)?'disabled':'' ?>>Update order</button>
        </form>
        <section class="form-section" style="margin-top:1rem"><h2>Activity</h2><div class="timeline">
            <?php foreach($order['history']as$event): ?><article><p><strong><?= e(format_status((string)$event['from_status'])) ?> → <?= e(format_status((string)$event['to_status'])) ?></strong></p><small><?= e((string)($event['admin_name']??'System')) ?> · <?= e((string)$event['created_at']) ?></small><?php if($event['note']): ?><p><?= e((string)$event['note']) ?></p><?php endif; ?></article><?php endforeach; ?>
            <?php if(!$order['history']): ?><p>No admin activity yet.</p><?php endif; ?><article><p><strong>Order placed</strong></p><small><?= e((string)$order['placed_at']) ?></small></article>
        </div></section>
    </aside>
</div>
<?php require __DIR__.'/_footer.php'; ?>
