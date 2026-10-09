<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {if(!Schema::hasTable('node_events'))Schema::create('node_events',function(Blueprint $t){$t->uuid('id')->primary();$t->string('config_id');$t->string('type');$t->string('account_number');$t->bigInteger('amount_cents');$t->uuid('central_transaction_id')->unique();$t->timestamp('created_at');}); if(\Illuminate\Support\Facades\DB::getDriverName()==='pgsql'){\Illuminate\Support\Facades\DB::statement('ALTER TABLE node_events ENABLE ROW LEVEL SECURITY');\Illuminate\Support\Facades\DB::statement('REVOKE ALL ON node_events FROM anon, authenticated');}}
 public function down():void {Schema::dropIfExists('node_events');}
};
