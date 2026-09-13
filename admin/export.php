<?php

declare(strict_types=1);

require __DIR__.'/_init.php';require_admin();$type=(string)($_GET['type']??'');$names=['orders'=>'vanta-orders','customers'=>'vanta-customers','newsletter'=>'vanta-newsletter','inventory'=>'vanta-inventory'];if(!isset($names[$type])){http_response_code(400);exit('Invalid export.');}$rows=admin_repository()->exportRows($type);header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$names[$type].'-'.date('Y-m-d').'.csv"');header('X-Content-Type-Options: nosniff');$output=fopen('php://output','wb');fwrite($output,"\xEF\xBB\xBF");if($rows){fputcsv($output,array_keys($rows[0]));foreach($rows as$row)fputcsv($output,array_map('admin_csv_cell',$row));}fclose($output);exit;
