<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Services\RemoteError;
class NodeAdmin {public function handle(Request $r,Closure $next){if(!$r->session()->get('node_admin'))throw new RemoteError('UNAUTHORIZED','Inicia sesión como ejecutivo.',401);return $next($r);}}
