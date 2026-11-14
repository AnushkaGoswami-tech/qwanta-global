<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require dirname(__DIR__).'/app/db.php';
// Run on a trusted machine with PHP PDO PostgreSQL + MySQL. Supply SOURCE_DATABASE_URL privately.
// Use an empty destination database initialized with mysql-schema.sql. Pause writes during final migration.
$source=getenv('SOURCE_DATABASE_URL');if(!$source)throw new RuntimeException('SOURCE_DATABASE_URL required');
$p=parse_url($source);if(!$p||!isset($p['host'],$p['user'],$p['path']))throw new RuntimeException('Invalid source configuration');
$pg=new PDO('pgsql:host='.$p['host'].';port='.($p['port']??5432).';dbname='.ltrim($p['path'],'/').';sslmode=require',rawurldecode($p['user']),rawurldecode($p['pass']??''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$my=db();
$tables=[
'q_categories'=>['name'],
'q_products'=>['id','name','category','description','specifications','origin','packaging','moq','lead_time','price','availability','published','featured','local','url','source','author','license','image_data','image_mime'],
'q_enquiries'=>['reference','created','iphash','payload','status']
];
$my->exec("SET SESSION sql_mode=CONCAT_WS(',',@@sql_mode,'NO_AUTO_VALUE_ON_ZERO')");
$pg->beginTransaction();$pg->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
$my->beginTransaction();
try{
 foreach($tables as $table=>$cols){
 if((int)$my->query("SELECT COUNT(*) FROM $table")->fetchColumn()!==0)throw new RuntimeException('Destination tables must be empty; no records overwritten.');
 $list=implode(',',$cols);
 $select=$table==='q_products'?str_replace(['published,','featured,'],['published::int AS published,','featured::int AS featured,'],$list):$list;
 $q=$pg->query("SELECT $select FROM $table");
 $insert=$my->prepare("INSERT INTO $table ($list) VALUES (".implode(',',array_fill(0,count($cols),'?')).")");
 $count=0;while($row=$q->fetch()){$insert->execute(array_map(fn($c)=>$row[$c],$cols));$count++;}
 if($count!==(int)$my->query("SELECT COUNT(*) FROM $table")->fetchColumn())throw new RuntimeException('Count mismatch');
 echo "$table: $count rows verified\n";
 }
 $my->commit();$pg->commit();echo "Migration completed. Verify product images and enquiries before switching traffic.\n";
}catch(Throwable $e){if($my->inTransaction())$my->rollBack();if($pg->inTransaction())$pg->rollBack();fwrite(STDERR,"Migration failed; check private local logs and database settings.\n");exit(1);}
