<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
class CentralClient {
 public function settings():array {
  $file=storage_path('app/private/node-config.json');
  if(is_file($file))return json_decode(Crypt::decryptString(file_get_contents($file)),true);
  return ['central_url'=>config('node.central_url'),'api_key'=>config('node.api_key')];
 }
 public function save(array $data):array {
  $node=$this->send('GET','/node',[],[],$data);
  if($node['type']!=='branch')throw new RemoteError('WRONG_NODE_TYPE','La API key debe corresponder a una sucursal.',422);
  file_put_contents(storage_path('app/private/node-config.json'),Crypt::encryptString(json_encode($data)));chmod(storage_path('app/private/node-config.json'),0600);return $node;
 }
 public function send(string $method,string $path,array $data=[],array $headers=[],?array $settings=null):mixed {
  $s=$settings??$this->settings();if(empty($s['api_key']))throw new RemoteError('NODE_NOT_CONFIGURED','Configura la conexión con el banco central.',422);
  try{$r=Http::connectTimeout(8)->timeout(20)->withHeaders(array_merge(['Accept'=>'application/json','X-API-Key'=>$s['api_key']],$headers))->send($method,rtrim($s['central_url'],'/').'/api/v1'.$path,$method==='GET'?['query'=>$data]:['json'=>$data]);}
  catch(\Throwable $e){throw new RemoteError('CENTRAL_UNAVAILABLE','No fue posible comunicarnos con el banco central.',503);}
  if(!$r->successful())throw new RemoteError($r->json('error.code','CENTRAL_ERROR'),$r->json('error.message','El banco central rechazó la solicitud.'),$r->status());
  return $r->json('data');
 }
 public function record(array $result):void {
  try{DB::table('node_events')->insertOrIgnore(['id'=>$result['transaction_id'],'config_id'=>config('node.config_id'),'type'=>$result['type'],'account_number'=>$result['account']['account_number'],'amount_cents'=>round((float)$result['amount']*100),'central_transaction_id'=>$result['transaction_id'],'created_at'=>$result['timestamp']]);}
  catch(\Throwable $e){file_put_contents(storage_path('logs/pending-audit.jsonl'),json_encode($result)."\n",FILE_APPEND|LOCK_EX);}
 }
}
