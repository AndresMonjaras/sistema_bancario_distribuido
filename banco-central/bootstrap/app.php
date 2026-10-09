<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Services\BankError;
return Application::configure(basePath:dirname(__DIR__))
 ->withRouting(web:__DIR__.'/../routes/web.php',api:__DIR__.'/../routes/api.php',commands:__DIR__.'/../routes/console.php',health:'/up')
 ->withMiddleware(function(Middleware $m):void { $m->alias(['bank.admin'=>App\Http\Middleware\BankAdmin::class,'bank.node'=>App\Http\Middleware\BankNode::class]); })
 ->withExceptions(function(Exceptions $e):void {
  $e->shouldRenderJsonWhen(fn(Request $r,Throwable $t)=>$r->is('api/*')||$r->expectsJson());
  $e->render(fn(BankError $e,Request $r)=>response()->json(['error'=>['code'=>$e->errorCode,'message'=>$e->getMessage()]],$e->status));
  $e->render(fn(Illuminate\Validation\ValidationException $e,Request $r)=>response()->json(['error'=>['code'=>'VALIDATION_ERROR','message'=>'Revisa los datos.'],'errors'=>$e->errors()],422));
 })->create();
