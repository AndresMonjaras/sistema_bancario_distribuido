<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Closure;
use App\Services\BankError;
class BankAdmin {
 public function handle(Request $r,Closure $next) {
  $token=config('bank.admin_token');
  if(!$r->session()->get('bank_admin') && !($token && $r->bearerToken() && hash_equals($token,$r->bearerToken()))) throw new BankError('UNAUTHORIZED','Inicia sesión como administrador.',401);
  return $next($r);
 }
}
