<?php
namespace App\Http\Controllers;
use App\Services\Bank;
use App\Services\BankError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class BankController extends Controller {
 public function __construct(private Bank $bank) {}
 private function data($data,int $status=200) { return response()->json(['data'=>$data],$status); }
 public function login(Request $r) {
  $p=$r->validate(['email'=>'required|email','password'=>'required|string']);
  if(config('bank.admin_supabase_id')) {
   $res=Http::timeout(15)->withHeaders(['apikey'=>config('bank.supabase_anon_key')])->post(config('bank.supabase_url').'/auth/v1/token?grant_type=password',$p);
   if(!$res->successful()||$res->json('user.id')!==config('bank.admin_supabase_id')) throw new BankError('INVALID_CREDENTIALS','Credenciales incorrectas.',401);
  } elseif(!hash_equals(config('bank.admin_email'),$p['email'])||!config('bank.admin_password')||!hash_equals(config('bank.admin_password'),$p['password'])) throw new BankError('INVALID_CREDENTIALS','Credenciales incorrectas.',401);
  $r->session()->regenerate(); $r->session()->put('bank_admin',true); return $this->data(['email'=>$p['email'],'csrf_token'=>csrf_token()]);
 }
 public function logout(Request $r) { $r->session()->invalidate(); $r->session()->regenerateToken(); return $this->data(['message'=>'Sesión cerrada']); }
 public function dashboard(Request $r){ return $this->data($this->bank->call('dashboard',$this->filters($r))); }
 public function nodes(){return $this->data($this->bank->call('nodes'));}
 public function createNode(Request $r){$p=$r->validate(['type'=>'required|in:branch,atm','name'=>'required|string|max:100','responsible'=>'required|string|max:100','initial_cash'=>'required']); return $this->data($this->bank->call('create_node',$p),201);}
 public function updateNode(Request $r,string $id){$p=$r->validate(['name'=>'sometimes|required|string|max:100','responsible'=>'sometimes|required|string|max:100','status'=>'sometimes|required|in:active,blocked']);return $this->data($this->bank->call('update_node',array_merge($p,['id'=>$id])));}
 public function rotateKey(string $id){return $this->data($this->bank->call('rotate_key',['id'=>$id]));}
 public function cash(Request $r,string $id){$p=$r->validate(['amount'=>'required']); return $this->data($this->bank->call('allocate_cash',array_merge($p,['id'=>$id])));}
 public function accounts(Request $r){$n=$r->attributes->get('bank_node'); if($n&&$n['type']!=='branch') throw new BankError('FORBIDDEN','Esta consulta requiere una sucursal.',403);return $this->data($this->bank->call('accounts',$n?['branch_id'=>$n['id']]:[]));}
 public function status(Request $r,string $number){$p=$r->validate(['status'=>'required|in:active,blocked']); return $this->data($this->bank->call('account_status',array_merge($p,['number'=>$number])));}
 public function node(Request $r){return $this->data($r->attributes->get('bank_node'));}
 public function balance(Request $r,string $number){return $this->data($this->bank->call('balance',['number'=>$number,'pin'=>$r->header('X-Account-Pin','')]));}
 private function filters(Request $r):array {$p=$r->validate(['node_id'=>'sometimes|uuid','account_number'=>'sometimes|digits:12','type'=>'sometimes|in:withdrawal,deposit,transfer','from'=>'sometimes|date_format:Y-m-d','to'=>'sometimes|date_format:Y-m-d']);if($n=$r->attributes->get('bank_node')) $p['node_id']=$n['id'];return $p;}
 public function transactions(Request $r){return $this->data($this->bank->call('transactions',$this->filters($r)));}
 public function reports(Request $r){$rows=$this->bank->call('transactions',$this->filters($r)); if($r->query('format')==='csv') return response()->streamDownload(function()use($rows){$f=fopen('php://output','w');fputcsv($f,['ID','Fecha','Nodo','Tipo','Origen','Destino','Monto MXN','Saldo posterior','SHA256']);foreach($rows as $t)fputcsv($f,[$t['id'],$t['created_at'],$t['node_name']??$t['node_id'],$t['type'],$t['source_account'],$t['destination_account'],$t['amount'],$t['balance_after'],$t['integrity_hash']]);fclose($f);},'operaciones.csv',['Content-Type'=>'text/csv']);$totals=['withdrawal'=>0,'deposit'=>0,'transfer'=>0];foreach($rows as $t)$totals[$t['type']]+=$t['amount_cents'];return $this->data(['count'=>count($rows),'totals_cents'=>$totals,'transactions'=>$rows]);}
 public function money(Request $r,string $action){
  $rules=$action==='open_account'?['holder_name'=>'required|string|max:120','initial_balance'=>'required','pin'=>'required|regex:/^\d{4}$/']:($action==='transfer'?['source_account'=>'required|digits:12','destination_account'=>'required|digits:12','amount'=>'required']:['account_number'=>'required|digits:12','amount'=>'required']);
  $p=$r->validate($rules);$key=$r->header('Idempotency-Key','');if(!preg_match('/^[A-Za-z0-9_-]{16,128}$/',$key))throw new BankError('IDEMPOTENCY_REQUIRED','Envía Idempotency-Key de 16 a 128 caracteres.',422);
  $p['idempotency_key']=$key;$p['node_id']=$r->attributes->get('bank_node')['id'];if($action!=='open_account')$p['pin']=$r->header('X-Account-Pin','');$result=$this->bank->call($action,$p);return $this->data($result,$result['replayed']?200:201);
 }
}
