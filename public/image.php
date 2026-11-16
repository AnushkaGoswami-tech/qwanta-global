<?php
declare(strict_types=1);
ini_set('display_errors','0');
require dirname(__DIR__).'/app/db.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
try{
 $raw=$_GET['id']??null;
 if(!is_string($raw)||!preg_match('/^[0-9]{1,10}$/D',$raw)|| (float)$raw>2147483647){http_response_code(404);exit;}
 $id=(int)$raw;
 $q=db()->prepare('SELECT image_data,image_mime FROM q_products WHERE id=? AND published=1');
 $q->execute([$id]);$r=$q->fetch();
 if(!$r||empty($r['image_data'])){http_response_code(404);exit;}
 [$bytes,$mime]=storedImage((string)$r['image_data']);
 $etag='"'.hash('sha256',$bytes).'"';
 header('Content-Type: '.$mime);header('Cache-Control: public, max-age=0, must-revalidate');header('ETag: '.$etag);
 if(($_SERVER['HTTP_IF_NONE_MATCH']??'')===$etag){http_response_code(304);exit;}
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo $bytes;
}catch(Throwable $e){error_log('Qwanta image delivery failed: '.get_class($e).' id='.(isset($id)?$id:'invalid'));http_response_code(503);header('Cache-Control: no-store');}
