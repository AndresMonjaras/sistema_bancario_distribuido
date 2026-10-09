<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BankController as C;
Route::get('/',fn()=>view('bank'));
Route::prefix('api/v1')->group(function(){
 Route::get('/csrf',fn()=>response()->json(['data'=>['csrf_token'=>csrf_token()]]));
 Route::post('/auth/login',[C::class,'login'])->middleware('throttle:10,1');Route::post('/auth/logout',[C::class,'logout']);
 Route::middleware('bank.admin')->prefix('admin')->group(function(){
 Route::get('/dashboard',[C::class,'dashboard']); Route::get('/nodes',[C::class,'nodes']); Route::post('/nodes',[C::class,'createNode']); Route::patch('/nodes/{id}',[C::class,'updateNode']); Route::post('/nodes/{id}/cash',[C::class,'cash']);Route::post('/nodes/{id}/rotate-key',[C::class,'rotateKey']); Route::get('/accounts',[C::class,'accounts']);Route::patch('/accounts/{number}/status',[C::class,'status']);Route::get('/transactions',[C::class,'transactions']);Route::get('/reports',[C::class,'reports']);
 });
});
