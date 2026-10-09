import pg from 'pg';import fs from 'node:fs';
const c=JSON.parse(fs.readFileSync(new URL('../.runtime/connections.json',import.meta.url)));
for(const name of ['central_db','nodes_db']){const cfg=c[name];const db=new pg.Client({host:cfg.host,user:cfg.user,password:cfg.password,database:'postgres',port:5432,ssl:{rejectUnauthorized:false},connectionTimeoutMillis:10000});await db.connect();
if(name==='nodes_db'){
 await db.query(`CREATE TABLE IF NOT EXISTS public.node_runtime (config_id varchar(120) PRIMARY KEY,node_id uuid,cash_cents bigint NOT NULL DEFAULT 0 CHECK(cash_cents>=0),updated_at timestamptz NOT NULL DEFAULT now()); ALTER TABLE public.node_events ENABLE ROW LEVEL SECURITY; ALTER TABLE public.node_runtime ENABLE ROW LEVEL SECURITY; REVOKE ALL ON public.node_events, public.node_runtime FROM anon, authenticated;`);
}else{
 await db.query(`DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_publication_tables WHERE pubname='supabase_realtime' AND tablename='transactions' AND schemaname='public') THEN ALTER PUBLICATION supabase_realtime ADD TABLE public.transactions; END IF; IF NOT EXISTS (SELECT 1 FROM pg_publication_tables WHERE pubname='supabase_realtime' AND tablename='users_accounts' AND schemaname='public') THEN ALTER PUBLICATION supabase_realtime ADD TABLE public.users_accounts; END IF; END $$;`);
}
console.log(name,(await db.query("select tablename,rowsecurity from pg_tables where schemaname='public' and tablename in ('nodes','users_accounts','transactions','cash_allocations','node_events','node_runtime') order by tablename")).rows);await db.end();}
