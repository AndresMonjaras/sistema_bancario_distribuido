<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('nodes', function(Blueprint $t) {
   $t->uuid('id')->primary(); $t->string('type'); $t->string('name'); $t->string('responsible');
   $t->string('api_key_hash',64)->unique(); $t->bigInteger('cash_cents')->default(0); $t->string('status')->default('active'); $t->timestamps();
  });
  Schema::create('users_accounts',function(Blueprint $t) {
   $t->uuid('id')->primary(); $t->string('account_number',20)->unique(); $t->string('holder_name');
   $t->bigInteger('balance_cents')->default(0); $t->string('status')->default('active'); $t->string('pin_hash');
   $t->foreignUuid('branch_id')->constrained('nodes'); $t->timestamps();
  });
  Schema::create('transactions',function(Blueprint $t) {
   $t->uuid('id')->primary(); $t->foreignUuid('node_id')->constrained('nodes');
   $t->string('source_account',20)->nullable(); $t->string('destination_account',20)->nullable();
   $t->bigInteger('amount_cents'); $t->string('type'); $t->bigInteger('balance_after_cents');
   $t->string('idempotency_key',128); $t->string('request_hash',64); $t->text('response_json'); $t->string('integrity_hash',64);
   $t->timestamp('created_at'); $t->unique(['node_id','idempotency_key']); $t->index(['node_id','created_at']);
  });
  Schema::create('cash_allocations',function(Blueprint $t) { $t->uuid('id')->primary(); $t->foreignUuid('node_id')->constrained('nodes'); $t->bigInteger('amount_cents'); $t->timestamp('created_at'); });
  if(DB::getDriverName()==='pgsql') {
   DB::statement('ALTER TABLE users_accounts ADD CONSTRAINT accounts_nonnegative CHECK (balance_cents >= 0)');
   DB::statement('ALTER TABLE nodes ADD CONSTRAINT nodes_nonnegative CHECK (cash_cents >= 0)');
   DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.reject_transaction_change() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Transactions are immutable'; END; $$
SQL);
   DB::statement('CREATE TRIGGER transactions_immutable BEFORE UPDATE OR DELETE ON transactions FOR EACH ROW EXECUTE FUNCTION public.reject_transaction_change()');
   foreach(['nodes','users_accounts','transactions','cash_allocations'] as $table) {
    DB::statement('ALTER TABLE public.'.$table.' ENABLE ROW LEVEL SECURITY');
    DB::statement('REVOKE ALL ON public.'.$table.' FROM anon, authenticated');
   }
  }
  if(DB::getDriverName()==='sqlite') {
   DB::statement("CREATE TRIGGER transactions_no_update BEFORE UPDATE ON transactions BEGIN SELECT RAISE(ABORT,'Transactions are immutable'); END");
   DB::statement("CREATE TRIGGER transactions_no_delete BEFORE DELETE ON transactions BEGIN SELECT RAISE(ABORT,'Transactions are immutable'); END");
   DB::statement("CREATE TRIGGER accounts_nonnegative_insert BEFORE INSERT ON users_accounts WHEN NEW.balance_cents < 0 BEGIN SELECT RAISE(ABORT,'Negative balance'); END");
   DB::statement("CREATE TRIGGER accounts_nonnegative_update BEFORE UPDATE ON users_accounts WHEN NEW.balance_cents < 0 BEGIN SELECT RAISE(ABORT,'Negative balance'); END");
   DB::statement("CREATE TRIGGER nodes_nonnegative_update BEFORE UPDATE ON nodes WHEN NEW.cash_cents < 0 BEGIN SELECT RAISE(ABORT,'Negative cash'); END");
  }
 }
 public function down(): void { Schema::dropIfExists('cash_allocations'); Schema::dropIfExists('transactions'); Schema::dropIfExists('users_accounts'); Schema::dropIfExists('nodes'); }
};
