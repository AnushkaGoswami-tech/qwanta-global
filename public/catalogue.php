<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/db.php';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$name=basename($path??'');
if($name===''||$name==='/')$name='index.html';
if(!in_array($name,['index.html','products.html','product.html'],true)){http_response_code(404);exit('Not found');}
try{$rows=products(db());$html=file_get_contents(__DIR__.'/'.$name);
if($name==='product.html'){
 $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);$item=null;
 if($id!==false&&$id!==null&&$id>=0){foreach($rows as $r){if((int)$r['id']===$id){$item=$r;break;}}}
 if(!$item){http_response_code(404);header('X-Robots-Tag: noindex');}
 else{
  $title=$item['name'].' | Qwanta Global Sourcing';
  $desc=trim(preg_replace('/\s+/u',' ',strip_tags($item['description'])))?:($item['name'].' sourcing enquiries from Qwanta Global Sourcing, India.');
  $url='https://qwantaglobal.com/product.html?id='.$id;
  $tags='<link rel="canonical" href="'.esc($url).'"><meta property="og:url" content="'.esc($url).'">';
  $html=preg_replace_callback('/<title>[^<]*<\/title>/',fn()=>'<title>'.esc($title).'</title>',$html,1);
  $html=preg_replace_callback('/<meta name="description" content="[^"]*">/',fn()=>'<meta name="description" content="'.esc($desc).'">',$html,1);
  $html=preg_replace_callback('/<meta property="og:title" content="[^"]*">/',fn()=>'<meta property="og:title" content="'.esc($title).'">',$html,1);
  $html=preg_replace_callback('/<meta property="og:description" content="[^"]*">/',fn()=>'<meta property="og:description" content="'.esc($desc).'">',$html,1);
  $schema=['@context'=>'https://schema.org','@type'=>'Product','name'=>$item['name'],'description'=>$desc,'category'=>$item['category'],'url'=>$url];
  if(!empty($item['has_image']))$schema['image']='https://qwantaglobal.com/image.php?id='.$id;
  elseif(preg_match('~^https://upload\.wikimedia\.org/~i',(string)$item['url']))$schema['image']=$item['url'];
  $tags.='<script type="application/ld+json">'.json_encode($schema,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR).'</script>';
  $html=str_replace('<!--QWANTA_PRODUCT_SEO-->',$tags,$html);
  $initial='<h1>'.esc($item['name']).'</h1><p>'.esc($desc).'</p><p>'.esc($item['specifications']).'</p>';
  $html=str_replace('<section id="product-detail" class="wrap catalog-hero"></section>','<section id="product-detail" class="wrap catalog-hero">'.$initial.'</section>',$html);
 }
}
if($name==='products.html'){
 $links='<noscript><ul>';foreach($rows as $r){$links.='<li><a href="/product.html?id='.(int)$r['id'].'">'.esc($r['name']).'</a></li>';}$links.='</ul></noscript>';
 $html=str_replace('<div class="products" id="cards"></div>','<div class="products" id="cards"></div>'.$links,$html);
}
$payload=json_encode($rows,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
$html=preg_replace_callback('/<script>/',fn($m)=>'<script>window.QWANTA_CATALOGUE='.$payload.';</script><script>',$html,1);
if($name==='index.html'){try{$totals=impact(db());$html=str_replace(['data-impact="shipments">0','data-impact="revenue">$0'],['data-impact="shipments">'.number_format($totals['shipments']),'data-impact="revenue">$'.number_format($totals['revenue_inr'])],$html);}catch(Throwable $e){error_log('Qwanta impact unavailable');}}
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
