<?php

declare(strict_types=1);

function admin_repository(): AdminRepository
{
    static $repository;
    return $repository ??= new AdminRepository(db());
}

function admin_service(): AdminService
{
    static $service;
    return $service ??= new AdminService(admin_repository());
}

/** @return array<string,mixed>|null */
function current_admin(): ?array
{
    global $vantaCurrentAdmin,$vantaCurrentAdminLoaded;
    if($vantaCurrentAdminLoaded??false)return is_array($vantaCurrentAdmin)?$vantaCurrentAdmin:null;
    $vantaCurrentAdminLoaded=true;$id=filter_var($_SESSION['admin_id']??null,FILTER_VALIDATE_INT);
    if(!$id){$vantaCurrentAdmin=null;return null;}
    $vantaCurrentAdmin=admin_repository()->findAdminById((int)$id);
    if($vantaCurrentAdmin===null || !(bool)$vantaCurrentAdmin['active']){
        unset($_SESSION['admin_id']);$vantaCurrentAdmin=null;return null;
    }
    return$vantaCurrentAdmin;
}

function reset_current_admin_cache(): void
{
    global $vantaCurrentAdmin,$vantaCurrentAdminLoaded;$vantaCurrentAdmin=null;$vantaCurrentAdminLoaded=false;
}

/** @return array<string,mixed> */
function require_admin(?string $role=null): array
{
    $admin=current_admin();
    if($admin===null){
        $return=safe_return_path((string)($_SERVER['REQUEST_URI']??''),'admin/index.php');
        flash('error','Sign in to access VANTA Admin.');
        redirect('admin/login.php?return='.rawurlencode($return));
    }
    if($role!==null && $admin['role']!==$role){http_response_code(403);require __DIR__.'/../../admin/forbidden.php';exit;}
    return$admin;
}

function require_super_admin(): array { return require_admin('SUPER_ADMIN'); }

function admin_redirect(string $path='index.php'): never
{
    redirect('admin/'.ltrim($path,'/'));
}

function admin_post_guard(): void
{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Method not allowed.');}
    if(!verify_csrf(request_csrf_token()))throw new DomainException('Your session expired. Refresh and try again.');
}

function admin_slug(string $value): string
{
    $value=mb_strtolower(trim($value),'UTF-8');
    $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);
    if(is_string($ascii))$value=$ascii;
    $value=(string)preg_replace('/[^a-z0-9]+/','-',$value);
    return trim($value,'-');
}

function admin_money(float|int|string $value): string
{
    return e(setting('currency','PKR')).' '.number_format((float)$value,0);
}

/** @return array{key:string,label:string,since:?string} */
function admin_range(string $key): array
{
    $key=in_array($key,['today','7d','30d','month','all'],true)?$key:'30d';
    $label=['today'=>'Today','7d'=>'7 Days','30d'=>'30 Days','month'=>'This Month','all'=>'All Time'][$key];
    $since=match($key){
        'today'=>date('Y-m-d 00:00:00'),
        '7d'=>date('Y-m-d 00:00:00',strtotime('-6 days')),
        '30d'=>date('Y-m-d 00:00:00',strtotime('-29 days')),
        'month'=>date('Y-m-01 00:00:00'),
        default=>null,
    };
    return['key'=>$key,'label'=>$label,'since'=>$since];
}

function admin_status_class(string $status): string
{
    return 'status status--'.preg_replace('/[^a-z0-9_-]/','',mb_strtolower($status,'UTF-8'));
}

/** @param array<string,mixed> $overrides */
function admin_query_url(array $overrides=[]): string
{
    $query=array_merge($_GET,$overrides);
    foreach($query as$key=>$value)if($value===''||$value===null)unset($query[$key]);
    return'?'.http_build_query($query);
}

/** @param array{page:int,pages:int,total:int} $result */
function admin_pagination(array $result): string
{
    if($result['pages']<=1)return'';$html='<nav class="pagination" aria-label="Pagination"><span>'.number_format($result['total']).' records</span><div>';
    if($result['page']>1)$html.='<a class="button button--quiet" href="'.e(admin_query_url(['page'=>$result['page']-1])).'">Previous</a>';
    $html.='<span>Page '.$result['page'].' / '.$result['pages'].'</span>';
    if($result['page']<$result['pages'])$html.='<a class="button button--quiet" href="'.e(admin_query_url(['page'=>$result['page']+1])).'">Next</a>';
    return$html.'</div></nav>';
}

function admin_csv_cell(mixed $value): string
{
    $text=(string)$value;
    if(preg_match('/^[=+\-@\t\r]/',$text))$text="'".$text;
    return$text;
}

function admin_flash_exception(Throwable $exception): void
{
    $message=$exception instanceof DomainException?$exception->getMessage():'The request could not be completed.';
    if($exception instanceof PDOException && $exception->getCode()==='23000')$message='That value conflicts with an existing record.';
    flash('error',$message);
}
