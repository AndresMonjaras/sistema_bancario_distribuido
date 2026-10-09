<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Services\RemoteError;
return Application::configure(basePath:dirname(__DIR__))->withRouting(web:__DIR__.'/../routes/web.php',commands:__DIR__.'/../routes/console.php',health:'/up')
 ->withMiddleware(function(Middleware $m):void{$m->alias(['node.admin'=>App\Http\Middleware\NodeAdmin::class]);})
 ->withExceptions(function(Exceptions $e):void{
 $e->shouldRenderJsonWhen(fn(Request $r,Throwable $t)=>$r->is('api/*')||$r->expectsJson());
 $e->render(fn(RemoteError $e,Request $r)=>response()->json(['error'=>['code'=>$e->errorCode,'message'=>$e->getMessage()]],$e->status));
 $e->render(fn(Illuminate\Validation\ValidationException $e,Request $r)=>response()->json(['error'=>['code'=>'VALIDATION_ERROR','message'=>'Revisa los datos.'],'errors'=>$e->errors()],422));
 })->create();
