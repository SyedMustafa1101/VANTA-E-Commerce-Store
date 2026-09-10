<?php

declare(strict_types=1);

/**
 * Phase 2 catalog fixture. Its shape mirrors the product, image, option, and
 * variant boundaries planned for Phase 3.
 *
 * @return array<int, array<string, mixed>>
 */
function catalog_products(): array
{
    static $products;

    if (is_array($products)) {
        return $products;
    }

    $essential = 'images/catalog/vanta-essential-look.jpg';
    $afterDark = 'images/catalog/vanta-after-dark-look.jpg';
    $accessories = 'images/catalog/vanta-accessories-still-life.jpg';
    $campaign = 'images/vanta-foundation-campaign.jpg';

    $products = [
        [
            'id' => 101,
            'slug' => 'vanta-oversized-essential-tee',
            'name' => 'VANTA Oversized Essential Tee',
            'category' => 'Tees',
            'collection' => 'ESSENTIALS',
            'price' => 6800,
            'sale_price' => null,
            'label' => 'NEW',
            'popularity' => 98,
            'description' => 'A deliberately oversized heavyweight tee with dropped shoulders and a clean, structured fall.',
            'keywords' => ['heavyweight', 'oversized', 'cotton', 'core', 'black tee'],
            'images' => [$essential, $afterDark, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#0A0A0A', 'images' => [$essential, $afterDark]],
                ['id' => 'bone', 'name' => 'Bone', 'hex' => '#E7E2D8', 'images' => [$campaign, $essential]],
            ],
            'stock' => [
                'black' => ['S' => 8, 'M' => 12, 'L' => 4, 'XL' => 0],
                'bone' => ['S' => 3, 'M' => 7, 'L' => 9, 'XL' => 2],
            ],
            'materials' => '320 GSM combed cotton jersey. Pre-shrunk and garment washed.',
            'care' => 'Cold wash inside out. Do not bleach. Hang dry. Cool iron on reverse.',
        ],
        [
            'id' => 102,
            'slug' => 'after-dark-hoodie',
            'name' => 'After Dark Hoodie',
            'category' => 'Hoodies',
            'collection' => 'AFTER DARK',
            'price' => 14500,
            'sale_price' => null,
            'label' => 'LIMITED',
            'popularity' => 96,
            'description' => 'Dense brushed fleece, a sculpted hood, and a cropped boxy body made for layered night uniforms.',
            'keywords' => ['hoodie', 'fleece', 'layer', 'night', 'black'],
            'images' => [$afterDark, $essential, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#0A0A0A', 'images' => [$afterDark, $essential]],
                ['id' => 'charcoal', 'name' => 'Charcoal', 'hex' => '#393939', 'images' => [$essential, $afterDark]],
            ],
            'stock' => [
                'black' => ['S' => 4, 'M' => 7, 'L' => 3, 'XL' => 1],
                'charcoal' => ['S' => 0, 'M' => 3, 'L' => 5, 'XL' => 2],
            ],
            'materials' => '480 GSM brushed cotton fleece with tonal rib trims.',
            'care' => 'Cold wash on gentle cycle. Reshape while damp. Dry flat.',
        ],
        [
            'id' => 103,
            'slug' => 'shadow-cargo-pant',
            'name' => 'Shadow Cargo Pant',
            'category' => 'Bottoms',
            'collection' => 'NEW DROP',
            'price' => 12800,
            'sale_price' => 10900,
            'label' => 'NEW',
            'popularity' => 91,
            'description' => 'Wide utility trousers with articulated knees, low-profile cargo pockets, and an adjustable hem.',
            'keywords' => ['cargo', 'trouser', 'utility', 'wide', 'technical'],
            'images' => [$essential, $afterDark, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#0A0A0A', 'images' => [$essential, $afterDark]],
                ['id' => 'graphite', 'name' => 'Graphite', 'hex' => '#555555', 'images' => [$afterDark, $essential]],
            ],
            'stock' => [
                'black' => ['S' => 5, 'M' => 8, 'L' => 6, 'XL' => 2],
                'graphite' => ['S' => 2, 'M' => 0, 'L' => 4, 'XL' => 1],
            ],
            'materials' => 'Cotton-nylon ripstop with matte metal hardware.',
            'care' => 'Cold wash. Close hardware before washing. Line dry.',
        ],
        [
            'id' => 104,
            'slug' => 'nocturne-bomber',
            'name' => 'Nocturne Bomber',
            'category' => 'Outerwear',
            'collection' => 'AFTER DARK',
            'price' => 24500,
            'sale_price' => null,
            'label' => 'LIMITED',
            'popularity' => 94,
            'description' => 'A cropped technical bomber with sculpted volume, tonal pocketing, and a muted gunmetal zip.',
            'keywords' => ['bomber', 'jacket', 'outerwear', 'technical', 'cropped'],
            'images' => [$afterDark, $essential, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#080808', 'images' => [$afterDark, $essential]],
            ],
            'stock' => ['black' => ['S' => 2, 'M' => 4, 'L' => 2, 'XL' => 0]],
            'materials' => 'Water-resistant matte nylon shell with recycled fill.',
            'care' => 'Professional clean recommended. Do not tumble dry.',
        ],
        [
            'id' => 105,
            'slug' => 'void-heavyweight-tee',
            'name' => 'Void Heavyweight Tee',
            'category' => 'Tees',
            'collection' => 'NEW DROP',
            'price' => 7200,
            'sale_price' => null,
            'label' => 'NEW',
            'popularity' => 87,
            'description' => 'Compact cotton jersey with a high rib neck and a long, angular sleeve line.',
            'keywords' => ['tee', 'heavyweight', 'cotton', 'void'],
            'images' => [$essential, $campaign, $afterDark],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#0B0B0B', 'images' => [$essential, $campaign]],
                ['id' => 'white', 'name' => 'White', 'hex' => '#F1EFE8', 'images' => [$campaign, $essential]],
            ],
            'stock' => [
                'black' => ['S' => 7, 'M' => 9, 'L' => 7, 'XL' => 3],
                'white' => ['S' => 2, 'M' => 6, 'L' => 1, 'XL' => 0],
            ],
            'materials' => '340 GSM compact cotton jersey.',
            'care' => 'Cold wash inside out. Hang dry.',
        ],
        [
            'id' => 106,
            'slug' => 'vanta-core-hoodie',
            'name' => 'VANTA Core Hoodie',
            'category' => 'Hoodies',
            'collection' => 'ESSENTIALS',
            'price' => 13200,
            'sale_price' => 11800,
            'label' => null,
            'popularity' => 90,
            'description' => 'An everyday heavyweight layer cut with dropped shoulders and a clean kangaroo pocket.',
            'keywords' => ['hoodie', 'core', 'essential', 'fleece'],
            'images' => [$afterDark, $essential, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#0A0A0A', 'images' => [$afterDark, $essential]],
                ['id' => 'charcoal', 'name' => 'Charcoal', 'hex' => '#373737', 'images' => [$essential, $afterDark]],
            ],
            'stock' => [
                'black' => ['S' => 9, 'M' => 12, 'L' => 8, 'XL' => 4],
                'charcoal' => ['S' => 4, 'M' => 5, 'L' => 2, 'XL' => 1],
            ],
            'materials' => '450 GSM brushed cotton fleece.',
            'care' => 'Cold wash. Dry flat. Do not iron trims.',
        ],
        [
            'id' => 107,
            'slug' => 'midnight-utility-cargo',
            'name' => 'Midnight Utility Cargo',
            'category' => 'Bottoms',
            'collection' => 'LIMITED',
            'price' => 15600,
            'sale_price' => null,
            'label' => 'LIMITED',
            'popularity' => 84,
            'description' => 'A limited wide-leg cargo with modular pockets and concealed ankle adjusters.',
            'keywords' => ['cargo', 'limited', 'utility', 'midnight'],
            'images' => [$essential, $afterDark, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#060606', 'images' => [$essential, $afterDark]],
            ],
            'stock' => ['black' => ['S' => 0, 'M' => 2, 'L' => 1, 'XL' => 0]],
            'materials' => 'Technical cotton-nylon canvas.',
            'care' => 'Spot clean or cold hand wash. Line dry.',
        ],
        [
            'id' => 108,
            'slug' => 'obsidian-jacket',
            'name' => 'Obsidian Jacket',
            'category' => 'Outerwear',
            'collection' => 'LIMITED',
            'price' => 28900,
            'sale_price' => null,
            'label' => 'SOLD OUT',
            'popularity' => 93,
            'description' => 'A rigid cropped shell with a high stand collar and concealed fastening.',
            'keywords' => ['jacket', 'shell', 'obsidian', 'outerwear'],
            'images' => [$afterDark, $campaign, $essential],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#050505', 'images' => [$afterDark, $campaign]],
            ],
            'stock' => ['black' => ['S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0]],
            'materials' => 'Bonded cotton shell with a smooth cupro lining.',
            'care' => 'Professional dry clean only.',
        ],
        [
            'id' => 109,
            'slug' => 'after-hours-cap',
            'name' => 'After Hours Cap',
            'category' => 'Accessories',
            'collection' => 'AFTER DARK',
            'price' => 4200,
            'sale_price' => null,
            'label' => 'NEW',
            'popularity' => 82,
            'description' => 'A structured six-panel cap in dense brushed cotton with tonal hardware.',
            'keywords' => ['cap', 'hat', 'accessory', 'after hours'],
            'images' => [$accessories, $afterDark, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#090909', 'images' => [$accessories, $afterDark]],
            ],
            'stock' => ['black' => ['OS' => 14]],
            'materials' => 'Brushed cotton twill with a metal adjuster.',
            'care' => 'Spot clean only.',
        ],
        [
            'id' => 110,
            'slug' => 'vanta-crossbody-bag',
            'name' => 'VANTA Crossbody Bag',
            'category' => 'Accessories',
            'collection' => 'ESSENTIALS',
            'price' => 8900,
            'sale_price' => null,
            'label' => null,
            'popularity' => 86,
            'description' => 'A compact crossbody with modular webbing, tonal zips, and a fully adjustable strap.',
            'keywords' => ['bag', 'crossbody', 'utility', 'accessory'],
            'images' => [$accessories, $essential, $campaign],
            'colors' => [
                ['id' => 'black', 'name' => 'Black', 'hex' => '#080808', 'images' => [$accessories, $essential]],
            ],
            'stock' => ['black' => ['OS' => 9]],
            'materials' => 'Recycled matte nylon with powder-coated hardware.',
            'care' => 'Wipe clean with a damp cloth.',
        ],
    ];

    return $products;
}

/** @return array<string, mixed>|null */
function catalog_product_by_slug(string $slug): ?array
{
    foreach (catalog_products() as $product) {
        if ($product['slug'] === $slug) {
            return $product;
        }
    }

    return null;
}

/** @return array<int, array<string, mixed>> */
function catalog_products_for_collection(string $collection): array
{
    $collection = strtoupper(trim($collection));

    return array_values(array_filter(
        catalog_products(),
        static fn (array $product): bool => $product['collection'] === $collection
    ));
}

function format_pkr(int $amount): string
{
    return 'PKR ' . number_format($amount);
}

/** @param array<string, mixed> $product */
function product_available(array $product): bool
{
    foreach ($product['stock'] as $sizes) {
        foreach ($sizes as $quantity) {
            if ($quantity > 0) {
                return true;
            }
        }
    }

    return false;
}

/** @param array<string, mixed> $product
 *  @return array<int, string>
 */
function product_sizes(array $product): array
{
    $sizes = [];
    foreach ($product['stock'] as $stockBySize) {
        $sizes = array_merge($sizes, array_keys($stockBySize));
    }

    return array_values(array_unique($sizes));
}

/** @return array<string, array<string, string>> */
function catalog_collections(): array
{
    return [
        'NEW DROP' => [
            'slug' => 'new-drop',
            'title' => 'NEW DROP',
            'eyebrow' => 'DROP 001 / JUST LANDED',
            'copy' => 'The first VANTA release: dense jersey, controlled volume, and utility built for the hours after dark.',
        ],
        'ESSENTIALS' => [
            'slug' => 'essentials',
            'title' => 'ESSENTIALS',
            'eyebrow' => 'CORE / PERMANENT',
            'copy' => 'The permanent uniform. Heavyweight foundations designed to repeat, layer, and live in.',
        ],
        'AFTER DARK' => [
            'slug' => 'after-dark',
            'title' => 'AFTER DARK',
            'eyebrow' => 'CAMPAIGN / 00:01',
            'copy' => 'Technical layers and deep silhouettes shaped by the city when the daylight disappears.',
        ],
        'LIMITED' => [
            'slug' => 'limited',
            'title' => 'LIMITED',
            'eyebrow' => 'NO RESTOCK / NUMBERED',
            'copy' => 'Small-run pieces that leave when they are gone. No repeats. No second release.',
        ],
    ];
}

/** @return array<string, string>|null */
function catalog_collection_by_slug(string $slug): ?array
{
    foreach (catalog_collections() as $collection) {
        if ($collection['slug'] === $slug) {
            return $collection;
        }
    }

    return null;
}
