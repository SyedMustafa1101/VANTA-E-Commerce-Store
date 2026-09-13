<?php

declare(strict_types=1);

final class AdminService
{
    private const ORDER_TRANSITIONS=[
        'pending'=>['processing','cancelled'],
        'processing'=>['shipped','cancelled'],
        'shipped'=>['delivered'],
        'delivered'=>[],
        'cancelled'=>[],
    ];
    private const PAYMENT_STATUSES=['pending','paid','failed','cod_pending'];
    private const STOCK_REASONS=['restock','correction','return','damage','manual_adjustment','order_cancellation'];

    public function __construct(private AdminRepository $repository)
    {
    }

    /** @return array<string,mixed> */
    public function login(string $email,string $password): array
    {
        $email=normalize_email($email);$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');$ipHash=hash('sha256','vanta-admin|'.$ip);
        if($this->repository->failedLoginCount($email,$ipHash)>=5)throw new DomainException('Unable to sign in. Wait a few minutes and try again.');
        $admin=$this->repository->findAdminByEmail($email);
        $dummy='$2y$10$wH6kqYBC6HkOvhHUMhVkJ.76vEEiDc05n3wSLx.Bu8gcg9A5aNoEe';
        $valid=password_verify($password,(string)($admin['password_hash']??$dummy));
        if(!$admin||!$valid||!(bool)$admin['active']){
            $this->repository->recordLoginAttempt($email,$ipHash,false);
            throw new DomainException('Invalid email or password.');
        }
        $this->repository->recordLoginAttempt($email,$ipHash,true);
        session_regenerate_id(true);$_SESSION['admin_id']=(int)$admin['id'];$_SESSION['admin_authenticated_at']=time();
        unset($_SESSION['csrf_token']);$this->repository->touchAdminLogin((int)$admin['id']);
        $this->repository->audit((int)$admin['id'],'admin.login','admin',(int)$admin['id'],'Admin signed in.');
        reset_current_admin_cache();return$admin;
    }

    public function logout(): void
    {
        $admin=current_admin();if($admin)$this->repository->audit((int)$admin['id'],'admin.logout','admin',(int)$admin['id'],'Admin signed out.');
        unset($_SESSION['admin_id'],$_SESSION['admin_authenticated_at']);session_regenerate_id(true);reset_current_admin_cache();
    }

    /** @param array<string,mixed> $input */
    public function saveProduct(array $input,?int $id): int
    {
        $name=trim((string)($input['name']??''));if(mb_strlen($name)<2)throw new DomainException('Enter a product name.');
        $category=(int)($input['category_id']??0);$collection=(int)($input['collection_id']??0);if($category<1||$collection<1)throw new DomainException('Choose a category and collection.');
        $price=(float)($input['price']??-1);$sale=trim((string)($input['sale_price']??''));$salePrice=$sale===''?null:(float)$sale;
        if($price<0||($salePrice!==null&&($salePrice<0||$salePrice>$price)))throw new DomainException('Enter valid product pricing.');
        $status=(string)($input['status']??'draft');if(!in_array($status,['draft','active','archived'],true))throw new DomainException('Choose a valid product status.');
        $slug=admin_slug((string)($input['slug']??$name));if($slug==='')throw new DomainException('Enter a valid product slug.');
        if($this->repository->slugExists('products',$slug,$id))throw new DomainException('That product slug is already in use.');
        $keywords=array_values(array_filter(array_map('trim',explode(',',(string)($input['keywords']??'')))));
        $data=['category_id'=>$category,'collection_id'=>$collection,'slug'=>$slug,'name'=>$name,
            'short_description'=>trim((string)($input['short_description']??'')),'description'=>trim((string)($input['description']??'')),
            'materials'=>trim((string)($input['materials']??'')),'care'=>trim((string)($input['care']??'')),'price'=>$price,
            'sale_price'=>$salePrice,'label'=>trim((string)($input['label']??''))?:null,'popularity'=>max(0,min(100,(int)($input['popularity']??0))),
            'keywords'=>json_encode($keywords,JSON_UNESCAPED_UNICODE),'status'=>$status,'is_featured'=>isset($input['is_featured'])?1:0,
            'meta_title'=>trim((string)($input['meta_title']??''))?:null,'meta_description'=>trim((string)($input['meta_description']??''))?:null,
            'is_active'=>$status==='active'?1:0];
        $saved=$this->repository->saveProduct($data,$id);$admin=require_admin();
        $this->repository->audit((int)$admin['id'],$id===null?'product.create':'product.update','product',$saved,($id===null?'Created ':'Updated ').$name.'.');return$saved;
    }

    public function archiveProduct(int $id): void
    { $this->repository->archiveProduct($id);$a=require_admin();$this->repository->audit((int)$a['id'],'product.archive','product',$id,'Archived product.'); }

    /** @param array<string,mixed> $input */
    public function saveTaxonomy(string $type,array $input,?int $id): int
    {
        if(!in_array($type,['category','collection'],true))throw new DomainException('Invalid record type.');
        $name=trim((string)($input['name']??''));if($name==='')throw new DomainException('Enter a name.');
        $table=$type==='category'?'categories':'collections';$slug=admin_slug((string)($input['slug']??$name));
        if($slug===''||$this->repository->slugExists($table,$slug,$id))throw new DomainException('Enter a unique slug.');
        $common=['name'=>$name,'slug'=>$slug,'description'=>trim((string)($input['description']??'')),'is_active'=>isset($input['is_active'])?1:0,'sort_order'=>max(0,(int)($input['sort_order']??0))];
        if($type==='category')$saved=$this->repository->saveCategory($common,$id);
        else{$saved=$this->repository->saveCollection($common+['eyebrow'=>trim((string)($input['eyebrow']??'')),'primary_image'=>trim((string)($input['primary_image']??'')),'secondary_image'=>trim((string)($input['secondary_image']??'')),'is_featured'=>isset($input['is_featured'])?1:0],$id);}
        $a=require_admin();$this->repository->audit((int)$a['id'],$type.'.'.($id===null?'create':'update'),$type,$saved,ucfirst($type).' saved: '.$name.'.');return$saved;
    }

    /** @param array<string,mixed> $input */
    public function saveVariant(array $input,?int $id): int
    {
        $product=(int)($input['product_id']??0);$colorName=trim((string)($input['color_name']??''));$colorSlug=admin_slug((string)($input['color_slug']??$colorName));$hex=strtoupper(trim((string)($input['color_hex']??'')));$size=strtoupper(trim((string)($input['size']??'')));$sku=strtoupper(trim((string)($input['sku']??'')));$stock=(int)($input['stock_quantity']??-1);
        if($product<1||$colorName===''||$colorSlug===''||$size===''||$sku==='')throw new DomainException('Complete every required variant field.');
        if(!preg_match('/^#[0-9A-F]{6}$/',$hex))throw new DomainException('Use a six-digit hexadecimal color.');
        if($stock<0)throw new DomainException('Stock cannot be negative.');
        $image=filter_var($input['image_id']??null,FILTER_VALIDATE_INT)?:null;
        $saved=$this->repository->saveVariant(['product_id'=>$product,'color_slug'=>$colorSlug,'color_name'=>$colorName,'color_hex'=>$hex,'size'=>$size,'sku'=>$sku,'image_id'=>$image,'stock_quantity'=>$stock,'is_active'=>isset($input['is_active'])?1:0],$id);
        $a=require_admin();$this->repository->audit((int)$a['id'],$id===null?'variant.create':'variant.update','variant',$saved,'Saved variant '.$sku.'.');return$saved;
    }

    public function adjustStock(int $variantId,int $change,string $reason,string $note=''): int
    {
        if($change===0)throw new DomainException('Enter a non-zero stock change.');if(!in_array($reason,self::STOCK_REASONS,true)||$reason==='order_cancellation')throw new DomainException('Choose a valid stock reason.');
        $admin=require_admin();$pdo=$this->repository->pdo();$pdo->beginTransaction();
        try{$variant=$this->repository->lockVariant($variantId);if(!$variant)throw new DomainException('Variant not found.');$previous=(int)$variant['stock_quantity'];$new=$previous+$change;if($new<0)throw new DomainException('This adjustment would make stock negative.');
            $this->repository->updateVariantStock($variantId,$new);$this->repository->recordInventoryAdjustment($variantId,(int)$admin['id'],$change,$reason,$note,$previous,$new);
            $this->repository->audit((int)$admin['id'],'inventory.adjust','variant',$variantId,'Stock '.$previous.' to '.$new.' for '.$variant['sku'].'.');$pdo->commit();return$new;
        }catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    }

    public function updateOrder(int $orderId,string $orderStatus,string $paymentStatus,string $note=''): void
    {
        if(!isset(self::ORDER_TRANSITIONS[$orderStatus])||!in_array($paymentStatus,self::PAYMENT_STATUSES,true))throw new DomainException('Choose valid order and payment statuses.');
        $admin=require_admin();$pdo=$this->repository->pdo();$pdo->beginTransaction();
        try{$order=$this->repository->order($orderId,true);if(!$order)throw new DomainException('Order not found.');$from=(string)$order['order_status'];$paymentFrom=(string)$order['payment_status'];
            if($orderStatus!==$from&&!in_array($orderStatus,self::ORDER_TRANSITIONS[$from],true))throw new DomainException('That order-status transition is not allowed.');
            $restored=null;
            if($orderStatus==='cancelled'&&$from!=='cancelled'&&$order['inventory_restored_at']===null){
                foreach($order['items']as$item){if(!$item['variant_id'])continue;$variant=$this->repository->lockVariant((int)$item['variant_id']);if(!$variant)continue;$previous=(int)$variant['stock_quantity'];$new=$previous+(int)$item['quantity'];$this->repository->updateVariantStock((int)$variant['id'],$new);$this->repository->recordInventoryAdjustment((int)$variant['id'],(int)$admin['id'],(int)$item['quantity'],'order_cancellation','Restored from '.$order['order_number'].'.',$previous,$new);}
                $restored=date('Y-m-d H:i:s');
            }
            $this->repository->updateOrderState($orderId,$orderStatus,$paymentStatus,$restored);$this->repository->addOrderHistory($orderId,(int)$admin['id'],$from,$orderStatus,$paymentFrom,$paymentStatus,$note);
            $this->repository->audit((int)$admin['id'],'order.status','order',$orderId,'Order '.$order['order_number'].' changed from '.$from.' to '.$orderStatus.'.');$pdo->commit();
        }catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    }

    /** @param array<string,mixed> $input */
    public function saveCoupon(array$input,?int$id): int
    {
        $code=strtoupper(trim((string)($input['code']??'')));$type=(string)($input['type']??'');$value=(float)($input['value']??0);$minimum=(float)($input['minimum_order']??0);
        if(!preg_match('/^[A-Z0-9_-]{3,60}$/',$code)||!in_array($type,['percentage','fixed'],true)||$value<=0||$minimum<0)throw new DomainException('Enter valid coupon details.');
        if($type==='percentage'&&$value>100)throw new DomainException('Percentage coupons cannot exceed 100%.');
        $start=trim((string)($input['starts_at']??''))?:null;$expiry=trim((string)($input['expires_at']??''))?:null;if($start&&$expiry&&strtotime($expiry)<=strtotime($start))throw new DomainException('Expiry must be after the start date.');
        $saved=$this->repository->saveCoupon(['code'=>$code,'type'=>$type,'value'=>$value,'minimum_order'=>$minimum,'starts_at'=>$start?str_replace('T',' ',$start).':00':null,'expires_at'=>$expiry?str_replace('T',' ',$expiry).':00':null,'is_active'=>isset($input['is_active'])?1:0,'usage_limit'=>($input['usage_limit']??'')===''?null:max(1,(int)$input['usage_limit']),'per_customer_limit'=>($input['per_customer_limit']??'')===''?null:max(1,(int)$input['per_customer_limit'])],$id);
        $a=require_admin();$this->repository->audit((int)$a['id'],$id===null?'coupon.create':'coupon.update','coupon',$saved,'Saved coupon '.$code.'.');return$saved;
    }

    /** @param array<string,mixed> $input */
    public function saveSettings(array$input): void
    {
        $allowed=['store_name','contact_email','currency','shipping_standard_pkr','free_shipping_threshold_pkr','low_stock_threshold','brand_tagline','support_email','default_country'];$values=[];
        foreach($allowed as$key)$values[$key]=trim((string)($input[$key]??''));
        if($values['store_name']===''||!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL)||!filter_var($values['support_email'],FILTER_VALIDATE_EMAIL))throw new DomainException('Enter a store name and valid contact emails.');
        if(!preg_match('/^[A-Z]{3}$/',strtoupper($values['currency'])))throw new DomainException('Currency must be a three-letter code.');$values['currency']=strtoupper($values['currency']);
        foreach(['shipping_standard_pkr','free_shipping_threshold_pkr','low_stock_threshold']as$key)if(!is_numeric($values[$key])||(float)$values[$key]<0)throw new DomainException('Shipping and stock values must be zero or greater.');
        $this->repository->saveSettings($values);$a=require_admin();$this->repository->audit((int)$a['id'],'settings.update','settings',null,'Updated safe store settings.');
    }

    public function uploadImages(int$productId,array$files,string$alt='',string$colorSlug=''): int
    {
        $admin=require_admin();$normalized=$this->normalizeFiles($files);if($normalized===[])throw new DomainException('Choose at least one image.');if(count($normalized)>10)throw new DomainException('Upload no more than 10 images at once.');
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$directory=dirname(__DIR__,2).'/uploads/products';if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('Upload directory is unavailable.');$count=0;
        foreach($normalized as$file){if($file['error']!==UPLOAD_ERR_OK)throw new DomainException('One image could not be uploaded.');if((int)$file['size']>8*1024*1024)throw new DomainException('Images must be 8 MB or smaller.');
            $extension=mb_strtolower((string)pathinfo((string)$file['name'],PATHINFO_EXTENSION),'UTF-8');$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$finfo->file((string)$file['tmp_name']);$size=@getimagesize((string)$file['tmp_name']);$validExtension=in_array($extension,['jpg','jpeg','png','webp'],true);$matchesMime=isset($allowed[$mime])&&($extension===$allowed[$mime]||($mime==='image/jpeg'&&$extension==='jpeg'));if(!$validExtension||!$matchesMime||!is_array($size)||$size[0]<1||$size[1]<1||$size[0]>12000||$size[1]>12000)throw new DomainException('Only valid JPG, PNG, or WebP images are accepted.');
            $filename=bin2hex(random_bytes(18)).'.'.$allowed[$mime];$target=$directory.'/'.$filename;if(!move_uploaded_file((string)$file['tmp_name'],$target))throw new RuntimeException('The image could not be stored.');
            try{$sort=$this->repository->nextImageSort($productId,admin_slug($colorSlug));$imageId=$this->repository->addImage(['product_id'=>$productId,'color_slug'=>admin_slug($colorSlug),'path'=>'uploads/products/'.$filename,'alt_text'=>mb_substr(trim($alt),0,255),'role'=>'gallery','is_primary'=>0,'is_uploaded'=>1,'mime_type'=>$mime,'file_size'=>(int)$file['size'],'width'=>(int)$size[0],'height'=>(int)$size[1],'sort_order'=>$sort]);$this->repository->audit((int)$admin['id'],'image.upload','product_image',$imageId,'Uploaded product image.');$count++;}
            catch(Throwable$e){if(is_file($target))unlink($target);throw$e;}
        }return$count;
    }

    public function updateImage(int$imageId,array$input): void
    {
        $image=$this->repository->image($imageId);if(!$image)throw new DomainException('Image not found.');$role=(string)($input['role']??'gallery');if(!in_array($role,['gallery','campaign','detail'],true))throw new DomainException('Choose a valid image role.');
        $this->repository->updateImageMeta($imageId,mb_substr(trim((string)($input['alt_text']??'')),0,255),$role,admin_slug((string)($input['color_slug']??'')),max(0,(int)($input['sort_order']??0)));
        if(isset($input['is_primary']))$this->repository->setPrimaryImage((int)$image['product_id'],$imageId);$a=require_admin();$this->repository->audit((int)$a['id'],'image.update','product_image',$imageId,'Updated product image metadata.');
    }

    public function deleteImage(int$imageId): void
    {
        $image=$this->repository->image($imageId);if(!$image)throw new DomainException('Image not found.');$this->repository->deleteImage($imageId);
        if((bool)$image['is_uploaded']&&str_starts_with((string)$image['path'],'uploads/products/')){$root=realpath(dirname(__DIR__,2).'/uploads/products');$file=realpath(dirname(__DIR__,2).'/'.(string)$image['path']);if($root&&$file&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)&&is_file($file))unlink($file);}
        $a=require_admin();$this->repository->audit((int)$a['id'],'image.delete','product_image',$imageId,'Removed product image.');
    }

    /** @return array<int,array{name:string,type:string,tmp_name:string,error:int,size:int}> */
    private function normalizeFiles(array$files): array
    {
        if(!isset($files['name']))return[];if(!is_array($files['name']))return[$files];$normalized=[];
        foreach($files['name']as$i=>$name)$normalized[]=['name'=>(string)$name,'type'=>(string)($files['type'][$i]??''),'tmp_name'=>(string)($files['tmp_name'][$i]??''),'error'=>(int)($files['error'][$i]??UPLOAD_ERR_NO_FILE),'size'=>(int)($files['size'][$i]??0)];return$normalized;
    }
}
