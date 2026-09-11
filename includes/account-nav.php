<?php

declare(strict_types=1);

$accountSection = $accountSection ?? 'overview';
?>
<nav class="account-nav" aria-label="Customer account">
    <?php foreach ([
        'overview' => ['Overview', 'account/index.php'],
        'profile' => ['Profile', 'account/profile.php'],
        'addresses' => ['Addresses', 'account/addresses.php'],
        'orders' => ['Orders', 'account/orders.php'],
        'wishlist' => ['Wishlist', 'account/wishlist.php'],
    ] as $key => [$label, $path]): ?>
        <a class="<?= $accountSection === $key ? 'is-current' : '' ?>" href="<?= e(url($path)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <form method="post" action="<?= e(url('logout.php')) ?>">
        <?= csrf_field() ?>
        <button type="submit">Sign out</button>
    </form>
</nav>

