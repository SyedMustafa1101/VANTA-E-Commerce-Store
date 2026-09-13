<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/admin/AdminRepository.php';

$repository=new AdminRepository(db());
if(count($repository->admins())>0){fwrite(STDERR,"An admin already exists. Use VANTA Admin → Admins to manage accounts.\n");exit(1);}
$options=getopt('',['name:','email:']);
$prompt=static function(string$label):string{fwrite(STDOUT,$label.': ');$value=fgets(STDIN);return trim(is_string($value)?$value:'');};
$name=trim((string)($options['name']??$prompt('Admin name')));$email=normalize_email((string)($options['email']??$prompt('Admin email')));
fwrite(STDOUT,"Password (minimum 12 characters; input is not stored): ");$password=fgets(STDIN);
fwrite(STDOUT,"Confirm password: ");$confirm=fgets(STDIN);$password=rtrim(is_string($password)?$password:'',"\r\n");$confirm=rtrim(is_string($confirm)?$confirm:'',"\r\n");
if(mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Enter a valid name and email.\n");exit(1);}if(strlen($password)<12){fwrite(STDERR,"Password must be at least 12 characters.\n");exit(1);}if(!hash_equals($password,$confirm)){fwrite(STDERR,"Passwords did not match.\n");exit(1);}
$id=$repository->createAdmin($name,$email,password_hash($password,PASSWORD_DEFAULT),'SUPER_ADMIN');$repository->audit($id,'admin.bootstrap','admin',$id,'Created initial super admin via the CLI setup workflow.');fwrite(STDOUT,"Initial SUPER_ADMIN created for {$email}. Delete terminal scrollback if it is shared.\n");
