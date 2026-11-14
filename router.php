<?php
declare(strict_types=1);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/enquiry.php'&&!getenv('DATABASE_URL')){require __DIR__.'/app/legacy-enquiry.php';return true;}
if($path==='/admin'){header('Location: /admin/',true,308);return true;}
if(!in_array($path,['/','/index.html','/products.html','/product.html'],true))return false;
$file=__DIR__.'/public/'.($path==='/'?'index.html':basename($path));
$html=file_get_contents($file);
if(getenv('DATABASE_URL')){
 require __DIR__.'/app/db.php';
 try{$rows=products(db());}
 catch(Throwable $e){error_log('Catalogue database unavailable');http_response_code(503);header('Content-Type: text/html; charset=utf-8');echo '<h1>Catalogue temporarily unavailable</h1><p>Please contact Qwanta directly at mraj7848@gmail.com.</p>';return true;}
 $payload=json_encode($rows,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
 $html=preg_replace_callback('/<script>/',fn($m)=>'<script>window.QWANTA_CATALOGUE='.$payload.';</script><script>', $html,1);
}
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-cache');echo $html;return true;
