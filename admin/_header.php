<?php

declare(strict_types=1);

$admin=require_admin();
$adminTitle=$adminTitle??'Dashboard';
$adminPage=$adminPage??'dashboard';
$adminSection=$adminSection??'Dashboard';
$flashes=pull_flashes();
$nav=[
    'Dashboard'=>[['dashboard','index.php','Dashboard']],
    'Store'=>[['products','products.php','Products'],['categories','categories.php','Categories'],['collections','collections.php','Collections'],['inventory','inventory.php','Inventory']],
    'Sales'=>[['orders','orders.php','Orders'],['customers','customers.php','Customers'],['coupons','coupons.php','Coupons']],
    'Content'=>[['reviews','reviews.php','Reviews'],['newsletter','newsletter.php','Newsletter']],
    'Reports'=>[['analytics','analytics.php','Analytics']],
    'System'=>[['settings','settings.php','Settings']],
];
if($admin['role']==='SUPER_ADMIN')$nav['System'][]=['admins','admins.php','Admins'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($adminTitle) ?> — VANTA Admin</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%230A0A0A'/%3E%3Cpath d='M11 13h12l9 28 9-28h12L38 53H26z' fill='%23B7FF2A'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>">
</head>
<body class="admin-body">
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar" data-sidebar aria-label="Admin navigation">
        <div class="admin-brand"><a href="<?= e(url('admin/index.php')) ?>">VANTA<span>.</span></a><small>ADMIN</small></div>
        <nav class="admin-nav">
            <?php foreach($nav as$group=>$items): ?>
                <section><h2><?= e($group) ?></h2>
                <?php foreach($items as[$key,$path,$label]): ?><a href="<?= e(url('admin/'.$path)) ?>" class="<?= $adminPage===$key?'is-active':'' ?>" <?= $adminPage===$key?'aria-current="page"':'' ?>><span class="nav-mark" aria-hidden="true"></span><?= e($label) ?></a><?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar__footer">
            <a href="<?= e(url('admin/profile.php')) ?>"><strong><?= e($admin['name']) ?></strong><span><?= e(format_status((string)$admin['role'])) ?></span></a>
            <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button type="submit">Log out</button></form>
        </div>
    </aside>
    <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>
    <div class="admin-workspace">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false"><span></span><span></span><span class="sr-only">Open navigation</span></button>
            <div><span><?= e($adminSection) ?></span><strong><?= e($adminTitle) ?></strong></div>
            <a class="store-link" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View store <span aria-hidden="true">↗</span></a>
        </header>
        <main id="admin-main" class="admin-main">
            <?php if($flashes): ?><div class="toast-stack" aria-live="polite"><?php foreach($flashes as$flash): ?><div class="admin-toast admin-toast--<?= e($flash['type']) ?>" data-toast><p><?= e($flash['message']) ?></p><button type="button" data-toast-close aria-label="Dismiss">×</button></div><?php endforeach; ?></div><?php endif; ?>
            <div class="page-heading"><div><p><?= e($adminSection) ?> / VANTA</p><h1><?= e($adminTitle) ?></h1></div><?php if(isset($adminActions))echo$adminActions; ?></div>
