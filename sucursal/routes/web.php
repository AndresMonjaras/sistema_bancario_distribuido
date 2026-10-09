<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController as C;
Route::get('/',fn()=>view('bank'));
Route::prefix('api/v1')->group(function(){
 Route::get('/health',fn()=>response()->json(['data'=>['service'=>'sucursal','status'=>'ok','local_database'=>config('database.default')==='pgsql'?'Supabase PostgreSQL':'SQLite local']]));
 Route::get('/csrf',fn()=>response()->json(['data'=>['csrf_token'=>csrf_token()]]));
 Route::post('/auth/login',[C::class,'login'])->middleware('throttle:10,1');Route::post('/auth/logout',[C::class,'logout']);
 Route::middleware('node.admin')->group(function(){Route::get('/config',[C::class,'config']);Route::put('/config',[C::class,'saveConfig']);Route::get('/dashboard',[C::class,'dashboard']);Route::get('/accounts',[C::class,'accounts']);Route::post('/accounts',[C::class,'open']);Route::get('/transactions',[C::class,'transactions']);Route::get('/reports',[C::class,'reports']);});
});
