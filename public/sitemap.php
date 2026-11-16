<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/db.php';
try{
 $rows=products(db());
 $urls=['https://qwantaglobal.com/','https://qwantaglobal.com/products.html','https://qwantaglobal.com/privacy.html'];
 foreach($rows as $r){$urls[]='https://qwantaglobal.com/product.html?id='.(int)$r['id'];}
 header('Content-Type: application/xml; charset=utf-8');
 header('Cache-Control: no-cache');
 echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
 foreach($urls as $url){echo '<url><loc>'.htmlspecialchars($url,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>'."\n";}
 echo '</urlset>';
}catch(Throwable $e){error_log('Qwanta sitemap unavailable');http_response_code(503);header('Retry-After: 300');header('Content-Type: text/plain; charset=utf-8');echo 'Sitemap temporarily unavailable';}
