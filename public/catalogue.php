<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/db.php';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$name=basename($path??'');
if($name===''||$name==='/')$name='index.html';
if(!in_array($name,['index.html','products.html','product.html'],true)){http_response_code(404);exit('Not found');}
try{$rows=products(db());$html=file_get_contents(__DIR__.'/'.$name);$payload=json_encode($rows,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
$html=preg_replace_callback('/<script>/',fn($m)=>'<script>window.QWANTA_CATALOGUE='.$payload.';</script><script>',$html,1);
if($name==='index.html'){try{$totals=impact(db());$html=str_replace(['data-impact="shipments">0','data-impact="revenue">₹0'],['data-impact="shipments">'.number_format($totals['shipments']),'data-impact="revenue">₹'.number_format($totals['revenue_inr'])],$html);}catch(Throwable $e){error_log('Qwanta impact unavailable');}}
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-cache');echo $html;
}catch(Throwable $e){$configPath=dirname(__DIR__,2).'/qwanta-config.php';
$reason=match(true){
 !is_file($configPath)=>'CONFIG_MISSING',
 !is_readable($configPath)=>'CONFIG_UNREADABLE',
 $e instanceof ParseError=>'CONFIG_SYNTAX',
 !extension_loaded('pdo_mysql')=>'PDO_MYSQL_MISSING',
 $e instanceof PDOException=>'DATABASE_SQLSTATE_'.preg_replace('/[^A-Z0-9]/i','',(string)$e->getCode()).'_DRIVER_'.(int)($e->errorInfo[1]??0),
 default=>'APPLICATION_'.get_class($e)
};
error_log('Qwanta catalogue diagnostic: '.$reason);http_response_code(503);echo '<h1>Temporarily unavailable</h1><p>Please contact abhishek@qwantaglobal.com.</p>';}
