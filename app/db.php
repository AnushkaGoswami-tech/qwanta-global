<?php
declare(strict_types=1);
/* Hostinger production database. Configuration is loaded from outside the web root. */
function config(string $key): string {
 static $local=null;
 if($local===null){
 $path=dirname(__DIR__).'/qwanta-config.php';
 $local=is_file($path)?require $path:[];
 if(!is_array($local))throw new RuntimeException('Invalid configuration');
 }
 $v=getenv($key);return $v!==false?$v:(string)($local[$key]??'');
}
function dbConfigured(): bool {return config('MYSQL_DATABASE')!==''&&config('MYSQL_USER')!==''&&config('MYSQL_PASSWORD')!=='';}
function db(): PDO {
 static $db;if($db)return $db;
 if(!dbConfigured())throw new RuntimeException('Database not configured');
 $db=new PDO('mysql:host='.(config('MYSQL_HOST')?:'localhost').';port='.(config('MYSQL_PORT')?:'3306').';dbname='.config('MYSQL_DATABASE').';charset=utf8mb4',config('MYSQL_USER'),config('MYSQL_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 return $db;
}
function products(PDO $db,bool $all=false): array {
 $sql="SELECT id,name,category,description,specifications,origin,packaging,moq,lead_time,price,availability,published,featured,local,url,source,author,license,(image_data IS NOT NULL) AS has_image FROM q_products".($all?'':' WHERE published=1')." ORDER BY id";
 return $db->query($sql)->fetchAll();
}
function esc(string|int|null $v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function iphash(): string {
 $secret=config('QWANTA_RATE_SECRET');if(strlen($secret)<32)throw new RuntimeException('Rate limit secret not configured');
 return hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',$secret);
}
/* MySQL named locks are connection-scoped, unlike PostgreSQL transactional locks. */
function lock(PDO $db,int $id): void {
 $q=$db->prepare('SELECT GET_LOCK(?,8)');$q->execute(['qwanta-'.$id]);if((int)$q->fetchColumn()!==1)throw new RuntimeException('Database busy. Please retry.');
}
function unlock(PDO $db,int $id): void {
 $q=$db->prepare('SELECT RELEASE_LOCK(?)');$q->execute(['qwanta-'.$id]);
}
