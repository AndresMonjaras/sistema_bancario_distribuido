<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Closure;
use App\Services\Bank;
use App\Services\BankError;
class BankNode {
 public function handle(Request $r,Closure $next) {
  $key=$r->header('X-API-Key'); if(!$key) throw new BankError('UNAUTHORIZED','Se requiere X-API-Key.',401);
  $r->attributes->set('bank_node',app(Bank::class)->call('resolve_node',['key_hash'=>hash('sha256',$key)])); return $next($r);
 }
}
