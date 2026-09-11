<?php

declare(strict_types=1);
?>
<div class="order-detail">
    <div class="order-detail__status">
        <div><span>Placed</span><strong><?= e(date('d M Y, H:i', strtotime((string) $order['placed_at']))) ?></strong></div>
        <div><span>Order status</span><strong><?= e(format_status((string) $order['order_status'])) ?></strong></div>
        <div><span>Payment</span><strong><?= e(format_status((string) $order['payment_status'])) ?></strong></div>
    </div>
    <div class="order-detail__body">
        <div class="order-items">
            <?php foreach ($order['items'] as $item): ?>
                <article>
                    <?php if ($item['image_path']): ?><img src="<?= e(asset((string) $item['image_path'])) ?>" alt="" width="220" height="275"><?php endif; ?>
                    <div><p><?= e($item['sku']) ?></p><h3><?= e($item['product_name']) ?></h3><span><?= e($item['variant_description']) ?> / Qty <?= (int) $item['quantity'] ?></span></div>
                    <strong><?= e(format_pkr((float) $item['line_total'])) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
        <aside class="order-summary">
            <h2>Order summary</h2>
            <dl>
                <div><dt>Subtotal</dt><dd><?= e(format_pkr((float) $order['subtotal'])) ?></dd></div>
                <div><dt>Discount<?= $order['coupon_code'] ? ' / ' . e($order['coupon_code']) : '' ?></dt><dd>−<?= e(format_pkr((float) $order['discount_total'])) ?></dd></div>
                <div><dt>Shipping</dt><dd><?= (float) $order['shipping_total'] > 0 ? e(format_pkr((float) $order['shipping_total'])) : 'Free' ?></dd></div>
                <div class="order-summary__total"><dt>Total</dt><dd><?= e(format_pkr((float) $order['total'])) ?></dd></div>
            </dl>
            <div class="order-address"><p>Ship to</p><strong><?= e($order['shipping_recipient']) ?></strong><address><?= e($order['shipping_address_line_1']) ?><br><?php if ($order['shipping_address_line_2']): ?><?= e($order['shipping_address_line_2']) ?><br><?php endif; ?><?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?><br><?= e($order['shipping_country']) ?><br><?= e($order['customer_phone']) ?></address></div>
            <div class="order-address"><p>Payment method</p><strong><?= e(format_status((string) $order['payment_method'])) ?></strong></div>
        </aside>
    </div>
</div>

