<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class Bank {
 public function call(string $action, array $p=[]): mixed {
  if(config('bank.driver')==='supabase') {
   $r=Http::timeout(20)->withHeaders(['apikey'=>config('bank.supabase_key'),'Authorization'=>'Bearer '.config('bank.supabase_key')])->post(rtrim(config('bank.supabase_url'),'/').'/rest/v1/rpc/bank_call',['p_action'=>$action,'p_payload'=>$p]);
   if(!$r->successful()) { $msg=$r->json('message','Persistencia central no disponible'); throw new BankError('DATABASE_ERROR',$msg,503); }
   $data=$r->json(); if(isset($data['error'])) throw new BankError($data['error']['code'],$data['error']['message'],$data['error']['status']??409); return $data;
  }
  return $this->local($action,$p);
 }
 public static function cents(mixed $v, bool $zero=false): int {
  if(!preg_match('/^\d{1,10}(\.\d{1,2})?$/',(string)$v)) throw new BankError('INVALID_AMOUNT','El monto debe tener máximo dos decimales.',422);
  $parts=explode('.',(string)$v); $c=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
  if($c<($zero?0:1)) throw new BankError('INVALID_AMOUNT','El monto debe ser positivo.',422); return $c;
 }
 private function nodeView($n): array { $a=(array)$n; unset($a['api_key_hash']); $a['cash']=number_format($a['cash_cents']/100,2,'.',''); return $a; }
 private function accountView($a): array { $v=(array)$a; unset($v['pin_hash']); $v['balance']=number_format($v['balance_cents']/100,2,'.',''); return $v; }
 private function txView($t): array { $v=(array)$t; unset($v['request_hash'],$v['response_json']); $v['amount']=number_format($v['amount_cents']/100,2,'.',''); $v['balance_after']=number_format($v['balance_after_cents']/100,2,'.',''); $v['node_name']=DB::table('nodes')->where('id',$v['node_id'])->value('name'); return $v; }
 private function findAccount(string $number, bool $lock=false) {
  $q=DB::table('users_accounts')->where('account_number',$number); if($lock) $q->lockForUpdate(); $a=$q->first();
  if(!$a) throw new BankError('ACCOUNT_NOT_FOUND','Cuenta no encontrada.',404); return $a;
 }
 private function customer($a,string $pin): void {
  if($a->status!=='active') throw new BankError('ACCOUNT_BLOCKED','La cuenta está bloqueada.',403);
  if(!password_verify($pin,$a->pin_hash)) throw new BankError('INVALID_PIN','PIN incorrecto.',401);
 }
 private function local(string $action,array $p): mixed {
  switch($action) {
   case 'resolve_node':
    $n=DB::table('nodes')->where('api_key_hash',$p['key_hash'])->first();
    if(!$n) throw new BankError('INVALID_API_KEY','API key inválida.',401);
    if($n->status!=='active') throw new BankError('NODE_BLOCKED','Nodo bloqueado.',403); return $this->nodeView($n);
   case 'nodes': return DB::table('nodes')->orderBy('created_at')->get()->map(fn($n)=>$this->nodeView($n))->all();
   case 'create_node':
    $key='bank_'.bin2hex(random_bytes(32)); $n=['id'=>(string)Str::uuid(),'type'=>$p['type'],'name'=>$p['name'],'responsible'=>$p['responsible'],'api_key_hash'=>hash('sha256',$key),'cash_cents'=>self::cents($p['initial_cash']??'0',true),'status'=>'active','created_at'=>now()->toISOString(),'updated_at'=>now()->toISOString()];
    DB::transaction(function()use($n){DB::table('nodes')->insert($n); if($n['cash_cents']>0) DB::table('cash_allocations')->insert(['id'=>(string)Str::uuid(),'node_id'=>$n['id'],'amount_cents'=>$n['cash_cents'],'created_at'=>now()->toISOString()]);});
    return array_merge($this->nodeView($n),['api_key'=>$key]);
   case 'update_node':
    if(!DB::table('nodes')->where('id',$p['id'])->exists()) throw new BankError('NODE_NOT_FOUND','Nodo no encontrado.',404);
    $update=array_intersect_key($p,array_flip(['name','responsible','status'])); $update['updated_at']=now()->toISOString(); DB::table('nodes')->where('id',$p['id'])->update($update); return $this->nodeView(DB::table('nodes')->where('id',$p['id'])->first());
   case 'rotate_key':
    $key='bank_'.bin2hex(random_bytes(32)); if(!DB::table('nodes')->where('id',$p['id'])->update(['api_key_hash'=>hash('sha256',$key),'updated_at'=>now()->toISOString()])) throw new BankError('NODE_NOT_FOUND','Nodo no encontrado.',404); return ['id'=>$p['id'],'api_key'=>$key];
   case 'allocate_cash': return DB::transaction(function()use($p){$n=DB::table('nodes')->where('id',$p['id'])->lockForUpdate()->first(); if(!$n) throw new BankError('NODE_NOT_FOUND','Nodo no encontrado.',404); $c=self::cents($p['amount']); DB::table('nodes')->where('id',$p['id'])->increment('cash_cents',$c); DB::table('cash_allocations')->insert(['id'=>(string)Str::uuid(),'node_id'=>$p['id'],'amount_cents'=>$c,'created_at'=>now()->toISOString()]);return $this->nodeView(DB::table('nodes')->where('id',$p['id'])->first());},3);
   case 'accounts':
    $q=DB::table('users_accounts'); if(isset($p['branch_id'])) $q->where('branch_id',$p['branch_id']); return $q->orderByDesc('created_at')->get()->map(fn($a)=>$this->accountView($a))->all();
   case 'account_status': $a=$this->findAccount($p['number']); DB::table('users_accounts')->where('id',$a->id)->update(['status'=>$p['status'],'updated_at'=>now()->toISOString()]); return $this->accountView($this->findAccount($p['number']));
   case 'balance': $a=$this->findAccount($p['number']); $this->customer($a,$p['pin']); return $this->accountView($a);
   case 'transactions':
    $q=DB::table('transactions'); if(!empty($p['node_id'])) $q->where('node_id',$p['node_id']);
    if(!empty($p['account_number'])) $q->where(fn($q)=>$q->where('source_account',$p['account_number'])->orWhere('destination_account',$p['account_number']));
    foreach(['type','from','to'] as $f) if(!empty($p[$f])) { if($f==='type') $q->where('type',$p[$f]); else $q->where('created_at',$f==='from'?'>=':'<=',$p[$f].($f==='to'?'T23:59:59Z':'')); }
    return $q->orderByDesc('created_at')->limit(500)->get()->map(fn($t)=>$this->txView($t))->all();
   case 'dashboard':
    $tx=$this->local('transactions',$p); $nodes=$this->local('nodes',[]); $accounts=$this->local('accounts',isset($p['node_id'])?['branch_id'=>$p['node_id']]:[]);
    return ['nodes'=>$nodes,'accounts'=>$accounts,'transactions'=>$tx,'total_balance'=>number_format(array_sum(array_column($accounts,'balance_cents'))/100,2,'.',''),'total_cash'=>number_format(array_sum(array_column($nodes,'cash_cents'))/100,2,'.',''),'transaction_count'=>count($tx)];
   case 'open_account': case 'withdrawal': case 'deposit': case 'transfer': return $this->money($action,$p);
   default: throw new BankError('UNKNOWN_ACTION','Acción no encontrada.',404);
  }
 }
 private function money(string $action,array $p): array {
  return DB::transaction(function()use($action,$p){
   $node=DB::table('nodes')->where('id',$p['node_id'])->lockForUpdate()->first();
   if(!$node||$node->status!=='active') throw new BankError('NODE_BLOCKED','Nodo no disponible.',403);
   if($action==='open_account'&&$node->type!=='branch') throw new BankError('FORBIDDEN','Sólo la sucursal abre cuentas.',403);
   if($action==='withdrawal'&&$node->type!=='atm') throw new BankError('FORBIDDEN','Sólo el ATM procesa retiros.',403);
   $c=self::cents($action==='open_account'?$p['initial_balance']:$p['amount'],$action==='open_account');
   $body=$p; unset($body['idempotency_key']); ksort($body); $hash=hash('sha256',$action.json_encode($body));
   $old=DB::table('transactions')->where('node_id',$node->id)->where('idempotency_key',$p['idempotency_key'])->first();
   if($old){if($old->request_hash!==$hash) throw new BankError('IDEMPOTENCY_CONFLICT','La clave ya se utilizó con otros datos.',409); return array_merge(json_decode($old->response_json,true),['replayed'=>true]);}
   $src=null; $dst=null;
   if($action==='open_account') {
    $number='10'.str_pad((string)random_int(0,9999999999),10,'0',STR_PAD_LEFT);
    $a=['id'=>(string)Str::uuid(),'account_number'=>$number,'holder_name'=>$p['holder_name'],'balance_cents'=>$c,'pin_hash'=>password_hash($p['pin'],PASSWORD_BCRYPT),'status'=>'active','branch_id'=>$node->id,'created_at'=>now()->toISOString(),'updated_at'=>now()->toISOString()]; DB::table('users_accounts')->insert($a);
    $dst=$number; $balance=$c; $cash=$node->cash_cents+$c; $type='deposit'; $account=$this->accountView($a);
   } else {
    $number=$action==='transfer'?$p['source_account']:$p['account_number'];
    if($action==='transfer') {
     if($number===$p['destination_account']) throw new BankError('SAME_ACCOUNT','Origen y destino deben ser distintos.',422);
     $numbers=[$number,$p['destination_account']]; sort($numbers); $locked=[]; foreach($numbers as $n) $locked[$n]=$this->findAccount($n,true); $a=$locked[$number]; $target=$locked[$p['destination_account']];
    } else $a=$this->findAccount($number,true);
    $this->customer($a,$p['pin']);
    if(in_array($action,['withdrawal','transfer'])&&$a->balance_cents<$c) throw new BankError('INSUFFICIENT_FUNDS','Saldo insuficiente.',409);
    if($action==='withdrawal'&&$node->cash_cents<$c) throw new BankError('INSUFFICIENT_CASH','El cajero no tiene efectivo suficiente.',409);
    $balance=$a->balance_cents+($action==='deposit'?$c:-$c); $cash=$node->cash_cents+($action==='deposit'?$c:($action==='withdrawal'?-$c:0));
    DB::table('users_accounts')->where('id',$a->id)->update(['balance_cents'=>$balance,'updated_at'=>now()->toISOString()]);
    $src=$action==='deposit'?null:$number; $dst=$action==='deposit'?$number:($action==='transfer'?$p['destination_account']:null); $type=$action;
    if($action==='transfer') {if($target->status!=='active') throw new BankError('ACCOUNT_BLOCKED','Cuenta destino bloqueada.',403); DB::table('users_accounts')->where('id',$target->id)->increment('balance_cents',$c);}
    $account=$this->accountView($this->findAccount($number));
   }
   DB::table('nodes')->where('id',$node->id)->update(['cash_cents'=>$cash,'updated_at'=>now()->toISOString()]);
   $id=(string)Str::uuid(); $time=now()->toISOString(); $immutable=['id'=>$id,'node_id'=>$node->id,'source_account'=>$src,'destination_account'=>$dst,'amount_cents'=>$c,'type'=>$type,'balance_after_cents'=>$balance,'idempotency_key'=>$p['idempotency_key'],'created_at'=>$time];
   $integrity=hash('sha256',json_encode($immutable,JSON_UNESCAPED_SLASHES));
   $result=['transaction_id'=>$id,'account'=>$account,'amount'=>number_format($c/100,2,'.',''),'balance'=>number_format($balance/100,2,'.',''),'node_cash'=>number_format($cash/100,2,'.',''),'type'=>$type,'timestamp'=>$time,'integrity_hash'=>$integrity,'replayed'=>false];
   DB::table('transactions')->insert(array_merge($immutable,['request_hash'=>$hash,'response_json'=>json_encode($result),'integrity_hash'=>$integrity])); return $result;
  },3);
 }
}
