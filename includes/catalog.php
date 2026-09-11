<?php

declare(strict_types=1);

function product_repository(): ProductRepository
{
    static $repository;
    return $repository ??= new ProductRepository(db());
}

/** @return array<int, array<string, mixed>> */
function catalog_products(): array
{
    return product_repository()->all();
}

/** @return array<string, mixed>|null */
function catalog_product_by_slug(string $slug): ?array
{
    return product_repository()->findBySlug($slug);
}

/** @return array<int, array<string, mixed>> */
function catalog_products_for_collection(string $collection): array
{
    return product_repository()->forCollection(strtoupper(trim($collection)));
}

/** @return array<string, array<string, mixed>> */
function catalog_collections(): array
{
    return product_repository()->collections();
}

/** @return array<string, mixed>|null */
function catalog_collection_by_slug(string $slug): ?array
{
    return product_repository()->collectionBySlug($slug);
}

function format_pkr(int|float $amount): string
{
    return 'PKR ' . number_format((float) $amount, 0);
}

/** @param array<string, mixed> $product */
function product_available(array $product): bool
{
    foreach ($product['stock'] ?? [] as $sizes) {
        foreach ($sizes as $quantity) {
            if ((int) $quantity > 0) {
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
    foreach ($product['stock'] ?? [] as $stockBySize) {
        $sizes = array_merge($sizes, array_keys($stockBySize));
    }
    return array_values(array_unique($sizes));
}
