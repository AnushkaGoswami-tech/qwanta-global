<?php
declare(strict_types=1);
/* Hostinger production database. Configuration is loaded from outside the web root. */
function config(string $key): string {
 static $local=null;
 if($local===null){
 $path=dirname(__DIR__,2).'/qwanta-config.php';
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
 $sql="SELECT id,name,category,description,specifications,origin,packaging,moq,lead_time,price,availability,published,featured,local,url,source,author,license,(image_data IS NOT NULL AND CHAR_LENGTH(image_data)>0) AS has_image,LEFT(SHA2(image_data,256),16) AS image_version FROM q_products".($all?'':' WHERE published=1')." ORDER BY id";
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

/* Homepage totals are independent of catalogue data. Missing table means a new installation. */
function impact(PDO $db): array {
 try{$r=$db->query('SELECT shipments,revenue_inr FROM q_impact WHERE id=1')->fetch();}
 catch(PDOException $e){if((string)$e->getCode()!=='42S02')throw $e;$r=false;}
 return ['shipments'=>(int)($r['shipments']??0),'revenue_inr'=>(int)($r['revenue_inr']??0)];
}
function saveImpact(PDO $db,int $shipments,int $revenue): void {
 $db->exec('CREATE TABLE IF NOT EXISTS q_impact (id TINYINT PRIMARY KEY, shipments BIGINT UNSIGNED NOT NULL DEFAULT 0, revenue_inr BIGINT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB');
 $db->prepare('INSERT INTO q_impact(id,shipments,revenue_inr) VALUES (1,?,?) ON DUPLICATE KEY UPDATE shipments=VALUES(shipments),revenue_inr=VALUES(revenue_inr)')->execute([$shipments,$revenue]);
}

/* Validate stored images before serving or accepting an upload. */
function checkedImage(string $bytes): array {
 if($bytes===''||strlen($bytes)>2097152)throw new RuntimeException('Upload a JPG, PNG or WebP image under 2 MB.');
 $info=@getimagesizefromstring($bytes);$mime=$info['mime']??'';
 if(!$info||!in_array($mime,['image/jpeg','image/png','image/webp'],true)||$info[0]*$info[1]>16000000)throw new RuntimeException('Invalid or unsupported product photograph.');
 if(function_exists('imagecreatefromstring')){$im=@imagecreatefromstring($bytes);if(!$im)throw new RuntimeException('The image file is damaged. Export it again as JPG or PNG.');imagedestroy($im);}
 return [$bytes,$mime];
}
function storedImage(string $data): array {
 $bytes=base64_decode($data,true);
 if($bytes===false)throw new RuntimeException('Invalid stored image.');
 return checkedImage($bytes);
}
