<?php

declare(strict_types=1);

final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findAdminByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $statement->execute([normalize_email($email)]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findAdminById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id,name,email,role,active,last_login_at,created_at,updated_at FROM admins WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function failedLoginCount(string $email, string $ipHash): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM admin_login_attempts
             WHERE email = ? AND ip_hash = ? AND was_successful = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $statement->execute([normalize_email($email), $ipHash]);
        return (int) $statement->fetchColumn();
    }

    public function recordLoginAttempt(string $email, string $ipHash, bool $successful): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO admin_login_attempts (email,ip_hash,attempted_at,was_successful) VALUES (?,?,NOW(),?)'
        );
        $statement->execute([normalize_email($email), $ipHash, $successful ? 1 : 0]);
        $this->pdo->exec('DELETE FROM admin_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    }

    public function touchAdminLogin(int $adminId): void
    {
        $statement = $this->pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?');
        $statement->execute([$adminId]);
    }

    public function audit(?int $adminId, string $action, string $entityType, ?int $entityId, string $summary): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO admin_audit_logs (admin_id,action,entity_type,entity_id,summary) VALUES (?,?,?,?,?)'
        );
        $statement->execute([$adminId, $action, $entityType, $entityId, mb_substr($summary, 0, 500)]);
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function products(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1'];
        $params = [];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(p.name LIKE :search_name OR p.slug LIKE :search_slug OR EXISTS (
                SELECT 1 FROM product_variants pv_search WHERE pv_search.product_id = p.id AND pv_search.sku LIKE :search_sku
            ))';
            $params['search_name'] = '%' . $search . '%';
            $params['search_slug'] = '%' . $search . '%';
            $params['search_sku'] = '%' . $search . '%';
        }
        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['draft', 'active', 'archived'], true)) {
            $where[] = 'p.status = :status';
            $params['status'] = $status;
        }
        foreach (['category_id' => 'p.category_id', 'collection_id' => 'p.collection_id'] as $key => $column) {
            $value = filter_var($filters[$key] ?? null, FILTER_VALIDATE_INT);
            if ($value) {
                $where[] = $column . ' = :' . $key;
                $params[$key] = (int) $value;
            }
        }
        $sortMap = [
            'updated' => 'p.updated_at DESC', 'name' => 'p.name ASC', 'price' => 'p.price ASC',
            'stock' => 'stock_total ASC', 'newest' => 'p.created_at DESC',
        ];
        $order = $sortMap[(string) ($filters['sort'] ?? '')] ?? $sortMap['updated'];
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT p.*, cat.name AS category_name, c.name AS collection_name,
                       COALESCE(SUM(v.stock_quantity),0) AS stock_total,
                       SUM(CASE WHEN v.is_active = 1 THEN 1 ELSE 0 END) AS variant_count,
                       (SELECT path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC,pi.sort_order,pi.id LIMIT 1) AS image_path
                FROM products p
                JOIN categories cat ON cat.id = p.category_id
                JOIN collections c ON c.id = p.collection_id
                LEFT JOIN product_variants v ON v.product_id = p.id
                WHERE ' . $whereSql . '
                GROUP BY p.id,cat.name,c.name ORDER BY ' . $order;
        $countSql = 'SELECT COUNT(*) FROM products p WHERE ' . $whereSql;
        return $this->page($sql, $countSql, $params, $page, $perPage);
    }

    /** @return array<string,mixed>|null */
    public function product(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*,cat.name AS category_name,c.name AS collection_name
             FROM products p JOIN categories cat ON cat.id=p.category_id JOIN collections c ON c.id=p.collection_id
             WHERE p.id=? LIMIT 1'
        );
        $statement->execute([$id]);
        $product = $statement->fetch();
        if (!$product) {
            return null;
        }
        $statement = $this->pdo->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order,id');
        $statement->execute([$id]);
        $product['images'] = $statement->fetchAll();
        $statement = $this->pdo->prepare('SELECT * FROM product_variants WHERE product_id=? ORDER BY color_name,size,sku');
        $statement->execute([$id]);
        $product['variants'] = $statement->fetchAll();
        return $product;
    }

    public function slugExists(string $table, string $slug, ?int $ignoreId = null): bool
    {
        if (!in_array($table, ['products', 'categories', 'collections'], true)) {
            throw new InvalidArgumentException('Unsupported slug table.');
        }
        $sql = 'SELECT 1 FROM ' . $table . ' WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn() !== false;
    }

    /** @param array<string,mixed> $data */
    public function saveProduct(array $data, ?int $id): int
    {
        $params = [
            $data['category_id'], $data['collection_id'], $data['slug'], $data['name'],
            $data['short_description'], $data['description'], $data['materials'], $data['care'],
            $data['price'], $data['sale_price'], $data['label'], $data['popularity'], $data['keywords'],
            $data['status'], $data['is_featured'], $data['meta_title'], $data['meta_description'], $data['is_active'],
        ];
        if ($id === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO products (category_id,collection_id,slug,name,short_description,description,materials,care,
                    price,sale_price,label,popularity,keywords,status,is_featured,meta_title,meta_description,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $statement->execute($params);
            return (int) $this->pdo->lastInsertId();
        }
        $params[] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE products SET category_id=?,collection_id=?,slug=?,name=?,short_description=?,description=?,materials=?,care=?,
                price=?,sale_price=?,label=?,popularity=?,keywords=?,status=?,is_featured=?,meta_title=?,meta_description=?,is_active=? WHERE id=?'
        );
        $statement->execute($params);
        return $id;
    }

    public function archiveProduct(int $id): void
    {
        $statement = $this->pdo->prepare("UPDATE products SET status='archived',is_active=0 WHERE id=?");
        $statement->execute([$id]);
    }

    public function deleteProductIfSafe(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM order_items WHERE product_id=?)
                  + (SELECT COUNT(*) FROM reviews WHERE product_id=?)
                  + (SELECT COUNT(*) FROM wishlist_items WHERE product_id=?)
                  + (SELECT COUNT(*) FROM cart_items ci JOIN product_variants v ON v.id=ci.variant_id WHERE v.product_id=?)'
        );
        $statement->execute([$id, $id, $id, $id]);
        if ((int) $statement->fetchColumn() > 0) {
            return false;
        }
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id=?');
        $statement->execute([$id]);
        return $statement->rowCount() === 1;
    }

    /** @return array<int,array<string,mixed>> */
    public function categories(): array
    {
        return $this->pdo->query(
            'SELECT c.*,COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id
             GROUP BY c.id ORDER BY c.sort_order,c.name'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function collections(): array
    {
        return $this->pdo->query(
            'SELECT c.*,COUNT(p.id) AS product_count FROM collections c LEFT JOIN products p ON p.collection_id=c.id
             GROUP BY c.id ORDER BY c.sort_order,c.name'
        )->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function saveCategory(array $data, ?int $id): int
    {
        if ($id === null) {
            $statement = $this->pdo->prepare('INSERT INTO categories (name,slug,description,is_active,sort_order) VALUES (?,?,?,?,?)');
            $statement->execute([$data['name'],$data['slug'],$data['description'],$data['is_active'],$data['sort_order']]);
            return (int) $this->pdo->lastInsertId();
        }
        $statement = $this->pdo->prepare('UPDATE categories SET name=?,slug=?,description=?,is_active=?,sort_order=? WHERE id=?');
        $statement->execute([$data['name'],$data['slug'],$data['description'],$data['is_active'],$data['sort_order'],$id]);
        return $id;
    }

    /** @param array<string,mixed> $data */
    public function saveCollection(array $data, ?int $id): int
    {
        $values = [$data['name'],$data['slug'],$data['eyebrow'],$data['description'],$data['primary_image'],$data['secondary_image'],$data['is_active'],$data['is_featured'],$data['sort_order']];
        if ($id === null) {
            $statement = $this->pdo->prepare('INSERT INTO collections (name,slug,eyebrow,description,primary_image,secondary_image,is_active,is_featured,sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
            $statement->execute($values);
            return (int) $this->pdo->lastInsertId();
        }
        $values[] = $id;
        $statement = $this->pdo->prepare('UPDATE collections SET name=?,slug=?,eyebrow=?,description=?,primary_image=?,secondary_image=?,is_active=?,is_featured=?,sort_order=? WHERE id=?');
        $statement->execute($values);
        return $id;
    }

    public function deleteTaxonomyIfSafe(string $table, int $id): bool
    {
        if (!in_array($table, ['categories', 'collections'], true)) {
            throw new InvalidArgumentException('Unsupported taxonomy table.');
        }
        $foreign = $table === 'categories' ? 'category_id' : 'collection_id';
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE ' . $foreign . '=?');
        $statement->execute([$id]);
        if ((int) $statement->fetchColumn() > 0) {
            return false;
        }
        $statement = $this->pdo->prepare('DELETE FROM ' . $table . ' WHERE id=?');
        $statement->execute([$id]);
        return $statement->rowCount() === 1;
    }

    public function saveVariant(array $data, ?int $id): int
    {
        $values = [$data['product_id'],$data['color_slug'],$data['color_name'],$data['color_hex'],$data['size'],$data['sku'],$data['image_id'],$data['stock_quantity'],$data['is_active']];
        if ($id === null) {
            $statement = $this->pdo->prepare('INSERT INTO product_variants (product_id,color_slug,color_name,color_hex,size,sku,image_id,stock_quantity,is_active) VALUES (?,?,?,?,?,?,?,?,?)');
            $statement->execute($values);
            return (int) $this->pdo->lastInsertId();
        }
        $values[] = $id;
        $statement = $this->pdo->prepare('UPDATE product_variants SET product_id=?,color_slug=?,color_name=?,color_hex=?,size=?,sku=?,image_id=?,stock_quantity=?,is_active=? WHERE id=?');
        $statement->execute($values);
        return $id;
    }

    public function deleteVariantIfSafe(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT (SELECT COUNT(*) FROM order_items WHERE variant_id=?) + (SELECT COUNT(*) FROM cart_items WHERE variant_id=?)');
        $statement->execute([$id,$id]);
        if ((int) $statement->fetchColumn() > 0) {
            return false;
        }
        $statement = $this->pdo->prepare('DELETE FROM product_variants WHERE id=?');
        $statement->execute([$id]);
        return $statement->rowCount() === 1;
    }

    /** @return array<string,mixed>|null */
    public function image(int $id): ?array
    {
        $statement=$this->pdo->prepare('SELECT * FROM product_images WHERE id=? LIMIT 1');
        $statement->execute([$id]);$row=$statement->fetch();return$row?:null;
    }

    /** @param array<string,mixed> $data */
    public function addImage(array $data): int
    {
        $statement=$this->pdo->prepare('INSERT INTO product_images (product_id,color_slug,path,alt_text,role,is_primary,is_uploaded,mime_type,file_size,width,height,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $statement->execute([$data['product_id'],$data['color_slug'],$data['path'],$data['alt_text'],$data['role'],$data['is_primary'],$data['is_uploaded'],$data['mime_type'],$data['file_size'],$data['width'],$data['height'],$data['sort_order']]);
        return(int)$this->pdo->lastInsertId();
    }

    public function nextImageSort(int $productId,string $colorSlug): int
    {
        $statement=$this->pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id=? AND color_slug=?');
        $statement->execute([$productId,$colorSlug]);return(int)$statement->fetchColumn();
    }

    public function setPrimaryImage(int $productId,int $imageId): void
    {
        $statement=$this->pdo->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=?');$statement->execute([$productId]);
        $statement=$this->pdo->prepare('UPDATE product_images SET is_primary=1 WHERE id=? AND product_id=?');$statement->execute([$imageId,$productId]);
    }

    public function updateImageMeta(int $id,string $alt,string $role,string $colorSlug,int $sort): void
    {
        $statement=$this->pdo->prepare('UPDATE product_images SET alt_text=?,role=?,color_slug=?,sort_order=? WHERE id=?');
        $statement->execute([$alt,$role,$colorSlug,$sort,$id]);
    }

    public function deleteImage(int $id): void
    {
        $statement=$this->pdo->prepare('DELETE FROM product_images WHERE id=?');$statement->execute([$id]);
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function inventory(array $filters, int $page = 1, int $perPage = 30): array
    {
        $threshold = max(0, (int) setting('low_stock_threshold', '5'));
        $where = ['1=1'];
        $params = [];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(v.sku LIKE :search_sku OR p.name LIKE :search_product OR v.color_name LIKE :search_color OR v.size LIKE :search_size)';
            $params['search_sku'] = '%' . $search . '%';
            $params['search_product'] = '%' . $search . '%';
            $params['search_color'] = '%' . $search . '%';
            $params['search_size'] = '%' . $search . '%';
        }
        if (($filters['stock'] ?? '') === 'low') {
            $where[] = 'v.stock_quantity BETWEEN 1 AND :threshold';
            $params['threshold'] = $threshold;
        } elseif (($filters['stock'] ?? '') === 'out') {
            $where[] = 'v.stock_quantity = 0';
        }
        if (($filters['active'] ?? '') === 'active') $where[] = 'v.is_active=1';
        if (($filters['active'] ?? '') === 'inactive') $where[] = 'v.is_active=0';
        $sortMap = ['sku'=>'v.sku','product'=>'p.name','stock'=>'v.stock_quantity ASC','updated'=>'v.updated_at DESC'];
        $order = $sortMap[(string) ($filters['sort'] ?? '')] ?? $sortMap['stock'];
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT v.*,p.name AS product_name,p.status AS product_status,p.slug AS product_slug
                FROM product_variants v JOIN products p ON p.id=v.product_id WHERE ' . $whereSql . ' ORDER BY ' . $order;
        $countSql = 'SELECT COUNT(*) FROM product_variants v JOIN products p ON p.id=v.product_id WHERE ' . $whereSql;
        return $this->page($sql,$countSql,$params,$page,$perPage);
    }

    /** @return array<string,mixed>|null */
    public function lockVariant(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT v.*,p.name AS product_name FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function updateVariantStock(int $id, int $stock): void
    {
        $statement = $this->pdo->prepare('UPDATE product_variants SET stock_quantity=? WHERE id=?');
        $statement->execute([$stock,$id]);
    }

    public function recordInventoryAdjustment(int $variantId, int $adminId, int $change, string $reason, string $note, int $previous, int $new): void
    {
        $statement = $this->pdo->prepare('INSERT INTO inventory_adjustments (variant_id,admin_id,change_amount,reason,note,previous_stock,new_stock) VALUES (?,?,?,?,?,?,?)');
        $statement->execute([$variantId,$adminId,$change,$reason,$note === '' ? null : mb_substr($note,0,255),$previous,$new]);
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function orders(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(o.order_number LIKE :search_order OR o.customer_email LIKE :search_email OR CONCAT(o.customer_first_name,\' \',o.customer_last_name) LIKE :search_name)';
            $params['search_order'] = '%' . $search . '%';
            $params['search_email'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
        }
        if (in_array(($filters['status'] ?? ''), ['pending','processing','shipped','delivered','cancelled'], true)) {
            $where[] = 'o.order_status=:status'; $params['status']=$filters['status'];
        }
        if (in_array(($filters['payment'] ?? ''), ['pending','paid','failed','cod_pending'], true)) {
            $where[] = 'o.payment_status=:payment'; $params['payment']=$filters['payment'];
        }
        foreach (['from'=>'>=','to'=>'<='] as $key=>$operator) {
            $date=(string)($filters[$key]??'');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) {
                $where[]='DATE(o.placed_at) '.$operator.' :'.$key; $params[$key]=$date;
            }
        }
        $whereSql=implode(' AND ',$where);
        $sql='SELECT o.*,COUNT(oi.id) AS item_lines,COALESCE(SUM(oi.quantity),0) AS item_count
              FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id WHERE '.$whereSql.'
              GROUP BY o.id ORDER BY o.placed_at DESC';
        $countSql='SELECT COUNT(*) FROM orders o WHERE '.$whereSql;
        return $this->page($sql,$countSql,$params,$page,$perPage);
    }

    /** @return array<string,mixed>|null */
    public function order(int $id, bool $forUpdate = false): ?array
    {
        $statement=$this->pdo->prepare('SELECT o.*,(SELECT d.status FROM order_email_deliveries d WHERE d.order_id=o.id AND d.email_type=\'order_confirmation\' LIMIT 1) AS confirmation_email_status FROM orders o WHERE o.id=? LIMIT 1'.($forUpdate?' FOR UPDATE':''));
        $statement->execute([$id]);
        $order=$statement->fetch();
        if(!$order) return null;
        $statement=$this->pdo->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');
        $statement->execute([$id]); $order['items']=$statement->fetchAll();
        $statement=$this->pdo->prepare('SELECT h.*,a.name AS admin_name FROM order_status_history h LEFT JOIN admins a ON a.id=h.admin_id WHERE h.order_id=? ORDER BY h.created_at DESC,h.id DESC');
        $statement->execute([$id]); $order['history']=$statement->fetchAll();
        return $order;
    }

    public function updateOrderState(int $id, string $orderStatus, string $paymentStatus, ?string $restoredAt = null): void
    {
        $sql='UPDATE orders SET order_status=?,payment_status=?'; $params=[$orderStatus,$paymentStatus];
        if($restoredAt!==null){$sql.=',inventory_restored_at=?';$params[]=$restoredAt;}
        $sql.=' WHERE id=?';$params[]=$id;
        $statement=$this->pdo->prepare($sql);$statement->execute($params);
    }

    public function addOrderHistory(int $orderId,int $adminId,string $from,string $to,string $paymentFrom,string $paymentTo,string $note): void
    {
        $statement=$this->pdo->prepare('INSERT INTO order_status_history (order_id,admin_id,from_status,to_status,payment_from_status,payment_to_status,note) VALUES (?,?,?,?,?,?,?)');
        $statement->execute([$orderId,$adminId,$from,$to,$paymentFrom,$paymentTo,$note===''?null:mb_substr($note,0,255)]);
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function customers(array $filters,int $page=1,int $perPage=25): array
    {
        $where=['1=1'];$params=[];$search=trim((string)($filters['q']??''));
        if($search!==''){$where[]='(u.email LIKE :search_email OR CONCAT(u.first_name,\' \',u.last_name) LIKE :search_name)';$params['search_email']='%'.$search.'%';$params['search_name']='%'.$search.'%';}
        if(in_array(($filters['status']??''),['active','disabled'],true)){$where[]='u.status=:status';$params['status']=$filters['status'];}
        $whereSql=implode(' AND ',$where);
        $sql='SELECT u.id,u.first_name,u.last_name,u.email,u.status,u.last_login_at,u.created_at,
                    COUNT(o.id) AS orders_count,COALESCE(SUM(CASE WHEN o.order_status<>\'cancelled\' THEN o.total ELSE 0 END),0) AS total_spend,MAX(o.placed_at) AS last_order_at
              FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE '.$whereSql.' GROUP BY u.id ORDER BY u.created_at DESC';
        $countSql='SELECT COUNT(*) FROM users u WHERE '.$whereSql;
        return $this->page($sql,$countSql,$params,$page,$perPage);
    }

    /** @return array<string,mixed>|null */
    public function customer(int $id): ?array
    {
        $statement=$this->pdo->prepare('SELECT u.id,u.first_name,u.last_name,u.email,u.status,u.last_login_at,u.created_at,u.updated_at,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) orders_count,
            (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id=u.id AND o.order_status<>\'cancelled\') lifetime_spend,
            (SELECT COUNT(*) FROM wishlist_items wi JOIN wishlists w ON w.id=wi.wishlist_id WHERE w.user_id=u.id) wishlist_count,
            (SELECT COUNT(*) FROM reviews r WHERE r.user_id=u.id) review_count
            FROM users u WHERE u.id=? LIMIT 1');
        $statement->execute([$id]);$user=$statement->fetch();if(!$user)return null;
        $statement=$this->pdo->prepare('SELECT id,label,recipient_name,phone,address_line_1,address_line_2,city,province,postal_code,country,is_default FROM addresses WHERE user_id=? ORDER BY is_default DESC,id');
        $statement->execute([$id]);$user['addresses']=$statement->fetchAll();
        $statement=$this->pdo->prepare('SELECT id,order_number,placed_at,total,payment_status,order_status FROM orders WHERE user_id=? ORDER BY placed_at DESC LIMIT 50');
        $statement->execute([$id]);$user['orders']=$statement->fetchAll();return $user;
    }

    public function updateCustomerStatus(int $id,string $status): void
    {
        $statement=$this->pdo->prepare('UPDATE users SET status=? WHERE id=?');$statement->execute([$status,$id]);
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function coupons(array $filters,int $page=1,int $perPage=25): array
    {
        $where=['1=1'];$params=[];$search=trim((string)($filters['q']??''));
        if($search!==''){$where[]='c.code LIKE :search';$params['search']='%'.$search.'%';}
        $sql='SELECT c.*,COUNT(cu.id) AS usage_count FROM coupons c LEFT JOIN coupon_usage cu ON cu.coupon_id=c.id WHERE '.implode(' AND ',$where).' GROUP BY c.id ORDER BY c.created_at DESC';
        $countSql='SELECT COUNT(*) FROM coupons c WHERE '.implode(' AND ',$where);
        return $this->page($sql,$countSql,$params,$page,$perPage);
    }

    /** @return array<string,mixed>|null */
    public function coupon(int $id): ?array
    { $statement=$this->pdo->prepare('SELECT * FROM coupons WHERE id=?');$statement->execute([$id]);$row=$statement->fetch();return $row?:null; }

    public function saveCoupon(array $data,?int $id): int
    {
        $values=[$data['code'],$data['type'],$data['value'],$data['minimum_order'],$data['starts_at'],$data['expires_at'],$data['is_active'],$data['usage_limit'],$data['per_customer_limit']];
        if($id===null){$statement=$this->pdo->prepare('INSERT INTO coupons (code,type,value,minimum_order,starts_at,expires_at,is_active,usage_limit,per_customer_limit) VALUES (?,?,?,?,?,?,?,?,?)');$statement->execute($values);return(int)$this->pdo->lastInsertId();}
        $values[]=$id;$statement=$this->pdo->prepare('UPDATE coupons SET code=?,type=?,value=?,minimum_order=?,starts_at=?,expires_at=?,is_active=?,usage_limit=?,per_customer_limit=? WHERE id=?');$statement->execute($values);return$id;
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function reviews(array $filters,int $page=1,int $perPage=25): array
    {
        $where=['1=1'];$params=[];$search=trim((string)($filters['q']??''));
        if($search!==''){$where[]='(p.name LIKE :search_product OR u.email LIKE :search_email OR r.content LIKE :search_content)';$params['search_product']='%'.$search.'%';$params['search_email']='%'.$search.'%';$params['search_content']='%'.$search.'%';}
        if(in_array(($filters['status']??''),['pending','approved','rejected','hidden'],true)){$where[]='r.status=:status';$params['status']=$filters['status'];}
        $sql='SELECT r.*,p.name AS product_name,p.slug AS product_slug,CONCAT(u.first_name,\' \',u.last_name) customer_name,u.email,
                    EXISTS(SELECT 1 FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.user_id=r.user_id AND oi.product_id=r.product_id AND o.order_status<>\'cancelled\') AS verified_purchase
              FROM reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id WHERE '.implode(' AND ',$where).' ORDER BY r.created_at DESC';
        $countSql='SELECT COUNT(*) FROM reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id WHERE '.implode(' AND ',$where);
        return $this->page($sql,$countSql,$params,$page,$perPage);
    }

    public function updateReviewStatus(int $id,string $status): void
    { $statement=$this->pdo->prepare('UPDATE reviews SET status=? WHERE id=?');$statement->execute([$status,$id]); }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public function subscribers(array $filters,int $page=1,int $perPage=30): array
    {
        $where=['1=1'];$params=[];$search=trim((string)($filters['q']??''));
        if($search!==''){$where[]='email LIKE :search';$params['search']='%'.$search.'%';}
        if(in_array(($filters['status']??''),['subscribed','unsubscribed'],true)){$where[]='status=:status';$params['status']=$filters['status'];}
        return $this->page('SELECT * FROM newsletter_subscribers WHERE '.implode(' AND ',$where).' ORDER BY created_at DESC','SELECT COUNT(*) FROM newsletter_subscribers WHERE '.implode(' AND ',$where),$params,$page,$perPage);
    }

    public function updateSubscriberStatus(int $id,string $status): void
    { $statement=$this->pdo->prepare('UPDATE newsletter_subscribers SET status=? WHERE id=?');$statement->execute([$status,$id]); }

    /** @return array<string,string> */
    public function settings(): array
    {
        $rows=$this->pdo->query('SELECT setting_key,setting_value FROM settings ORDER BY setting_key')->fetchAll();$result=[];
        foreach($rows as$row)$result[(string)$row['setting_key']]=(string)$row['setting_value'];return$result;
    }

    public function saveSettings(array $settings): void
    {
        $statement=$this->pdo->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        foreach($settings as$key=>$value)$statement->execute([(string)$key,(string)$value]);
    }

    /** @return array<int,array<string,mixed>> */
    public function admins(): array
    { return $this->pdo->query('SELECT id,name,email,role,active,last_login_at,created_at,updated_at FROM admins ORDER BY role DESC,name')->fetchAll(); }

    public function countActiveSuperAdmins(): int
    { return (int)$this->pdo->query("SELECT COUNT(*) FROM admins WHERE role='SUPER_ADMIN' AND active=1")->fetchColumn(); }

    public function createAdmin(string $name,string $email,string $hash,string $role): int
    { $statement=$this->pdo->prepare('INSERT INTO admins (name,email,password_hash,role,active) VALUES (?,?,?,?,1)');$statement->execute([$name,normalize_email($email),$hash,$role]);return(int)$this->pdo->lastInsertId(); }

    public function updateAdmin(int $id,string $name,string $email,string $role,bool $active): void
    { $statement=$this->pdo->prepare('UPDATE admins SET name=?,email=?,role=?,active=? WHERE id=?');$statement->execute([$name,normalize_email($email),$role,$active?1:0,$id]); }

    public function updateAdminPassword(int $id,string $hash): void
    { $statement=$this->pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?');$statement->execute([$hash,$id]); }

    /** @return array<string,mixed> */
    public function dashboard(?string $since): array
    {
        $where=$since===null?'':' AND o.placed_at >= :since';$params=$since===null?[]:['since'=>$since];
        $statement=$this->pdo->prepare("SELECT
            COALESCE(SUM(CASE WHEN o.payment_status='paid' AND o.order_status<>'cancelled' THEN o.total ELSE 0 END),0) revenue,
            COUNT(CASE WHEN o.order_status<>'cancelled' THEN 1 END) orders,
            COALESCE(AVG(CASE WHEN o.order_status<>'cancelled' THEN o.total END),0) aov,
            SUM(CASE WHEN o.order_status IN ('pending','processing') THEN 1 ELSE 0 END) pending_orders
            FROM orders o WHERE 1=1".$where);$statement->execute($params);$kpis=$statement->fetch();
        $customerWhere=$since===null?'':' WHERE created_at >= :since';$statement=$this->pdo->prepare('SELECT COUNT(*) FROM users'.$customerWhere);$statement->execute($params);$kpis['customers']=(int)$statement->fetchColumn();
        $kpis['products']=(int)$this->pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
        $threshold=max(0,(int)setting('low_stock_threshold','5'));
        $statement=$this->pdo->prepare('SELECT COUNT(*) FROM product_variants WHERE is_active=1 AND stock_quantity<=?');$statement->execute([$threshold]);$kpis['low_stock']=(int)$statement->fetchColumn();
        $kpis['pending_reviews']=(int)$this->pdo->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn();
        $recent=$this->pdo->query('SELECT id,order_number,customer_first_name,customer_last_name,total,payment_status,order_status,placed_at FROM orders ORDER BY placed_at DESC LIMIT 8')->fetchAll();
        $statement=$this->pdo->prepare("SELECT p.id,p.name,SUM(oi.quantity) units,SUM(oi.line_total) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE o.order_status<>'cancelled'".$where.' GROUP BY p.id,p.name ORDER BY units DESC,revenue DESC LIMIT 6');$statement->execute($params);$best=$statement->fetchAll();
        $statement=$this->pdo->prepare('SELECT v.id,v.sku,v.color_name,v.size,v.stock_quantity,p.name product_name FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.is_active=1 AND v.stock_quantity<=? ORDER BY v.stock_quantity,p.name LIMIT 8');$statement->execute([$threshold]);$low=$statement->fetchAll();
        $statement=$this->pdo->prepare('SELECT o.order_status status,COUNT(*) count FROM orders o WHERE 1=1'.$where.' GROUP BY o.order_status ORDER BY count DESC');$statement->execute($params);$status=$statement->fetchAll();
        $statement=$this->pdo->prepare("SELECT DATE(o.placed_at) label,COALESCE(SUM(CASE WHEN o.payment_status='paid' AND o.order_status<>'cancelled' THEN o.total ELSE 0 END),0) value FROM orders o WHERE 1=1".$where.' GROUP BY DATE(o.placed_at) ORDER BY label');$statement->execute($params);$series=$statement->fetchAll();
        return ['kpis'=>$kpis,'recent_orders'=>$recent,'best_sellers'=>$best,'low_stock'=>$low,'status_summary'=>$status,'revenue_series'=>$series];
    }

    /** @return array<string,mixed> */
    public function analytics(?string $since): array
    {
        $data=$this->dashboard($since);$where=$since===null?'':' AND o.placed_at>=:since';$params=$since===null?[]:['since'=>$since];
        $statement=$this->pdo->prepare("SELECT cat.name label,SUM(oi.quantity) units,SUM(oi.line_total) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id JOIN categories cat ON cat.id=p.category_id WHERE o.order_status<>'cancelled'".$where.' GROUP BY cat.id,cat.name ORDER BY revenue DESC');$statement->execute($params);$data['categories']=$statement->fetchAll();
        $statement=$this->pdo->prepare("SELECT c.name label,SUM(oi.quantity) units,SUM(oi.line_total) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id JOIN collections c ON c.id=p.collection_id WHERE o.order_status<>'cancelled'".$where.' GROUP BY c.id,c.name ORDER BY revenue DESC');$statement->execute($params);$data['collections']=$statement->fetchAll();
        $statement=$this->pdo->prepare("SELECT DATE_FORMAT(o.placed_at,'%Y-%m') label,COALESCE(SUM(CASE WHEN o.payment_status='paid' AND o.order_status<>'cancelled' THEN o.total ELSE 0 END),0) value FROM orders o WHERE 1=1".$where.' GROUP BY DATE_FORMAT(o.placed_at,\'%Y-%m\') ORDER BY label');$statement->execute($params);$data['monthly']=$statement->fetchAll();
        return$data;
    }

    /** @return array<int,array<string,mixed>> */
    public function exportRows(string $type): array
    {
        return match($type){
            'orders'=>$this->pdo->query('SELECT order_number,customer_first_name,customer_last_name,customer_email,placed_at,total,payment_method,payment_status,order_status FROM orders ORDER BY placed_at DESC')->fetchAll(),
            'customers'=>$this->pdo->query('SELECT u.first_name,u.last_name,u.email,u.status,u.created_at,COUNT(o.id) orders_count,COALESCE(SUM(CASE WHEN o.order_status<>\'cancelled\' THEN o.total ELSE 0 END),0) total_spend FROM users u LEFT JOIN orders o ON o.user_id=u.id GROUP BY u.id ORDER BY u.created_at DESC')->fetchAll(),
            'newsletter'=>$this->pdo->query('SELECT email,status,created_at,updated_at FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll(),
            'inventory'=>$this->pdo->query('SELECT v.sku,p.name product,v.color_name color,v.size,v.stock_quantity stock,IF(v.is_active=1,\'active\',\'inactive\') status FROM product_variants v JOIN products p ON p.id=v.product_id ORDER BY p.name,v.sku')->fetchAll(),
            default=>throw new InvalidArgumentException('Unsupported export type.'),
        };
    }

    public function pdo(): PDO { return $this->pdo; }

    /** @param array<string,mixed> $params
     *  @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int}
     */
    private function page(string $sql,string $countSql,array $params,int $page,int $perPage): array
    {
        $page=max(1,$page);$perPage=max(1,min(100,$perPage));
        $count=$this->pdo->prepare($countSql);$count->execute($params);$total=(int)$count->fetchColumn();
        $pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
        $statement=$this->pdo->prepare($sql.' LIMIT '.$perPage.' OFFSET '.$offset);$statement->execute($params);
        return['items'=>$statement->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
}
