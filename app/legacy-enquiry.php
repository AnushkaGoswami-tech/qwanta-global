<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reply(int $code,array $data): never { http_response_code($code); echo json_encode($data); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405,['error'=>'Method not allowed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>16000) reply(413,['error'=>'Request too large']);
if (!empty($_POST['website'])) reply(400,['error'=>'Invalid submission']);
$fields=['name'=>120,'company'=>160,'email'=>254,'product'=>100,'quantity'=>100,'destination'=>160,'requirements'=>2000,'incoterm'=>40];$d=[];
foreach($fields as $k=>$max){$v=$_POST[$k]??'';if(!is_string($v)||strlen($v)>$max)reply(400,['error'=>'Invalid field']);$d[$k]=trim($v);}
foreach(['name','company','email','product','quantity','destination'] as $k) if($d[$k]==='')reply(400,['error'=>'Required field missing']);
if(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)||($_POST['consent']??'')!=='on')reply(400,['error'=>'Email and consent required']);
if(!in_array($d['product'],['Raw green bananas','Yellow bananas','Banana powder','Rice','Onions','Spices','Pulses','Peanuts','Dehydrated vegetables','Other product'],true))reply(400,['error'=>'Invalid product']);
try {
$dir=getenv('QWANTA_PRIVATE_DIR') ?: dirname(__DIR__).'/private';
if(!is_dir($dir)||!is_writable($dir))throw new RuntimeException('Storage not configured');
$db=new PDO('sqlite:'.$dir.'/enquiries.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA busy_timeout=5000; CREATE TABLE IF NOT EXISTS enquiries (reference TEXT PRIMARY KEY, created INTEGER NOT NULL, iphash TEXT NOT NULL, payload TEXT NOT NULL); CREATE INDEX IF NOT EXISTS recent_ip ON enquiries(iphash,created)');
$secret=getenv('QWANTA_RATE_SECRET');if(!$secret)throw new RuntimeException('Rate secret missing');
$ip=hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',$secret);$now=time();$ref='QGS-'.strtoupper(bin2hex(random_bytes(6)));
$db->exec('BEGIN IMMEDIATE');$q=$db->prepare('SELECT COUNT(*) FROM enquiries WHERE iphash=? AND created>?');$q->execute([$ip,$now-3600]);if((int)$q->fetchColumn()>=5){$db->exec('ROLLBACK');reply(429,['error'=>'Too many enquiries']);}
$q=$db->prepare('INSERT INTO enquiries VALUES (?,?,?,?)');$q->execute([$ref,$now,$ip,json_encode($d,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$db->exec('COMMIT');
$recipient=getenv('QWANTA_NOTIFY_EMAIL');$sender=getenv('QWANTA_FROM_EMAIL');if($recipient&&$sender&&filter_var($recipient,FILTER_VALIDATE_EMAIL)&&filter_var($sender,FILTER_VALIDATE_EMAIL)){ $sent=mail($recipient,'Qwanta RFQ '.$ref,implode("\n",array_map(fn($k,$v)=>$k.': '.$v,array_keys($d),$d)),['From'=>$sender,'Reply-To'=>$d['email'],'Content-Type'=>'text/plain; charset=UTF-8']);if(!$sent)error_log('Qwanta notification failed: '.$ref); }
reply(201,['ok'=>true,'reference'=>$ref]);
}catch(Throwable $e){error_log('Qwanta RFQ error: '.$e->getMessage());reply(500,['error'=>'Enquiry storage unavailable']);}
