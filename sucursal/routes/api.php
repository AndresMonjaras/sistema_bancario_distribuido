<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BankController as C;
Route::prefix('v1')->group(function(){
 Route::get('/health',function(){try{DB::select('select 1');return response()->json(['data'=>['service'=>'banco-central','status'=>'ok','database'=>config('database.default')==='pgsql'?'Supabase PostgreSQL':'SQLite local','timestamp'=>now()->toISOString()]]);}catch(Throwable $e){return response()->json(['error'=>['code'=>'DATABASE_UNAVAILABLE','message'=>'Base de datos no disponible']],503);}});
 Route::middleware(['bank.node','throttle:60,1'])->group(function(){
  Route::get('/node',[C::class,'node']); Route::get('/accounts',[C::class,'accounts']); Route::get('/accounts/{number}/balance',[C::class,'balance']); Route::get('/transactions',[C::class,'transactions']);
  foreach(['accounts'=>'open_account','withdrawals'=>'withdrawal','deposits'=>'deposit','transfers'=>'transfer'] as $path=>$action)Route::post('/'.$path,fn(Illuminate\Http\Request $r)=>app(C::class)->money($r,$action));
 });
});
