<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\CentralClient;
use App\Services\RemoteError;
class BranchController extends Controller {
 public function __construct(private CentralClient $central){}
 private function data($data,int $code=200){return response()->json(['data'=>$data],$code);}
 public function login(Request $r){$p=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!hash_equals(config('node.admin_email'),$p['email'])||!config('node.admin_password')||!hash_equals(config('node.admin_password'),$p['password']))throw new RemoteError('INVALID_CREDENTIALS','Credenciales incorrectas.',401);$r->session()->regenerate();$r->session()->put('node_admin',true);return $this->data(['email'=>$p['email'],'csrf_token'=>csrf_token()]);}
 public function logout(Request $r){$r->session()->invalidate();$r->session()->regenerateToken();return $this->data(['message'=>'Sesión cerrada']);}
 public function config(){ $s=$this->central->settings();return $this->data(['central_url'=>$s['central_url'],'configured'=>!empty($s['api_key'])]); }
 public function saveConfig(Request $r){$p=$r->validate(['central_url'=>'required|url|max:300','api_key'=>'required|string|max:200']);return $this->data($this->central->save($p));}
 public function dashboard(){return $this->data(['node'=>$this->central->send('GET','/node'),'accounts'=>$this->central->send('GET','/accounts'),'transactions'=>$this->central->send('GET','/transactions')]);}
 public function accounts(){return $this->data($this->central->send('GET','/accounts'));}
 public function open(Request $r){$p=$r->validate(['holder_name'=>'required|string|max:120','initial_balance'=>'required','pin'=>'required|regex:/^\d{4}$/']);$key=$r->header('Idempotency-Key');if(!$key)throw new RemoteError('IDEMPOTENCY_REQUIRED','Envía Idempotency-Key.',422);$result=$this->central->send('POST','/accounts',$p,['Idempotency-Key'=>$key]);$this->central->record($result);return $this->data($result,$result['replayed']?200:201);}
 public function transactions(Request $r){$p=$r->validate(['account_number'=>'sometimes|digits:12','type'=>'sometimes|in:deposit,withdrawal,transfer','from'=>'sometimes|date_format:Y-m-d','to'=>'sometimes|date_format:Y-m-d']);return $this->data($this->central->send('GET','/transactions',$p));}
 public function reports(Request $r){$p=$r->validate(['account_number'=>'sometimes|digits:12','from'=>'sometimes|date_format:Y-m-d','to'=>'sometimes|date_format:Y-m-d']);$rows=$this->central->send('GET','/transactions',$p);if($r->query('format')==='csv')return response()->streamDownload(function()use($rows){$f=fopen('php://output','w');fputcsv($f,['ID','Fecha','Tipo','Cuenta','Monto','Saldo posterior']);foreach($rows as $t)fputcsv($f,[$t['id'],$t['created_at'],$t['type'],$t['destination_account']??$t['source_account'],$t['amount'],$t['balance_after']]);fclose($f);},'sucursal-operaciones.csv',['Content-Type'=>'text/csv']);return $this->data(['count'=>count($rows),'transactions'=>$rows]);}
}
