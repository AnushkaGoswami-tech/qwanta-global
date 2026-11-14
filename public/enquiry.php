<?php
declare(strict_types=1);require dirname(__DIR__).'/app/db.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function reply(int $code,array $data): never {http_response_code($code);echo json_encode($data);exit;}

function notifyOwner(string $ref,array $d): void {
 $key=config('RESEND_API_KEY');
 if(!$key){error_log('Qwanta email not configured');return;}
 $from=config('QWANTA_FROM_EMAIL')?:'Qwanta Enquiries <onboarding@resend.dev>';
 $to=config('QWANTA_NOTIFY_EMAIL')?:'mraj7848@gmail.com';
 $labels=['name'=>'Buyer','company'=>'Company','email'=>'Business email','product'=>'Product','quantity'=>'Quantity','destination'=>'Destination','requirements'=>'Additional requirements','incoterm'=>'Trade terms'];
 $lines=["New export enquiry — ".$ref,""];
 foreach($labels as $k=>$label)$lines[]=$label.': '.($d[$k]?:'Not specified');
 $lines[]="\nManage enquiry: https://qwanta-global.onrender.com/admin/?tab=enquiries";
 $payload=json_encode(['from'=>$from,'to'=>[$to],'subject'=>'New Qwanta buyer enquiry | '.$ref,'text'=>implode("\n",$lines),'reply_to'=>$d['email']],JSON_THROW_ON_ERROR);
 $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Authorization: Bearer ".$key."\r\nContent-Type: application/json\r\nIdempotency-Key: ".$ref."\r\n",'content'=>$payload,'timeout'=>8,'ignore_errors'=>true,'follow_location'=>0]]);
 $response=@file_get_contents('https://api.resend.com/emails',false,$ctx);
 $status=$http_response_header[0]??'';
 if($response===false||!preg_match('/^HTTP\/\S+ 2\d\d\b/',$status)){error_log('Qwanta notification failed: '.$ref.' '.($status?:'network error'));return;}
 error_log('Qwanta notification accepted: '.$ref);
}

if($_SERVER['REQUEST_METHOD']!=='POST')reply(405,['error'=>'Method not allowed']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>16000||!empty($_POST['website']))reply(400,['error'=>'Invalid submission']);
$d=[];foreach(['name'=>120,'company'=>160,'email'=>254,'product'=>100,'quantity'=>100,'destination'=>160,'requirements'=>2000,'incoterm'=>40] as $k=>$max){$v=$_POST[$k]??'';if(!is_string($v)||strlen($v)>$max)reply(400,['error'=>'Invalid field']);$d[$k]=trim($v);}
foreach(['name','company','email','product','quantity','destination'] as $k)if($d[$k]==='')reply(400,['error'=>'Required field missing']);
if(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)||($_POST['consent']??'')!=='on')reply(400,['error'=>'Email and consent required']);
try{$db=db();if($d['product']!=='Other product'){$q=$db->prepare('SELECT 1 FROM q_products WHERE published AND name=?');$q->execute([$d['product']]);if(!$q->fetchColumn())reply(400,['error'=>'Invalid product']);}
$now=time();$ip=iphash();$ref='QGS-'.strtoupper(bin2hex(random_bytes(6)));$db->beginTransaction();lock($db,784826);$q=$db->prepare('SELECT COUNT(*) FROM q_enquiries WHERE iphash=? AND created>?');$q->execute([$ip,$now-3600]);if((int)$q->fetchColumn()>=5){$db->rollBack();unlock($db,784826);reply(429,['error'=>'Too many enquiries']);}$q=$db->prepare('INSERT INTO q_enquiries(reference,created,iphash,payload) VALUES (?,?,?,?)');$q->execute([$ref,$now,$ip,json_encode($d,JSON_THROW_ON_ERROR)]);$db->commit();unlock($db,784826);try{notifyOwner($ref,$d);}catch(Throwable $e){error_log('Qwanta notification failed: '.$ref);}reply(201,['ok'=>true,'reference'=>$ref]);}catch(Throwable $e){error_log('Enquiry storage unavailable');reply(503,['error'=>'Enquiry storage unavailable. Please contact Qwanta directly.']);}
