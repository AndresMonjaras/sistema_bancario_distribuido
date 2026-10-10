CREATE TABLE IF NOT EXISTS public.atm_sessions (sid varchar NOT NULL PRIMARY KEY, sess json NOT NULL, expire timestamp(6) NOT NULL);
CREATE INDEX IF NOT EXISTS atm_sessions_expire_idx ON public.atm_sessions (expire);
CREATE TABLE IF NOT EXISTS public.node_configurations (config_id varchar(120) PRIMARY KEY, encrypted_payload text NOT NULL, updated_at timestamptz NOT NULL DEFAULT now());
CREATE TABLE IF NOT EXISTS public.node_events (id uuid PRIMARY KEY, config_id varchar(120) NOT NULL, type varchar(40) NOT NULL, account_number varchar(20) NOT NULL, amount_cents bigint NOT NULL, central_transaction_id uuid NOT NULL UNIQUE, created_at timestamptz NOT NULL);
CREATE INDEX IF NOT EXISTS node_events_config_date_idx ON public.node_events (config_id, created_at DESC);
CREATE TABLE IF NOT EXISTS public.node_runtime (config_id varchar(120) PRIMARY KEY, node_id uuid, cash_cents bigint NOT NULL DEFAULT 0 CHECK (cash_cents >= 0), updated_at timestamptz NOT NULL DEFAULT now());
ALTER TABLE public.atm_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.node_configurations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.node_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.node_runtime ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.atm_sessions, public.node_configurations, public.node_events, public.node_runtime FROM anon, authenticated;
DO $$
DECLARE table_name text;
BEGIN
  FOREACH table_name IN ARRAY ARRAY['users','password_reset_tokens','sessions','cache','cache_locks','jobs','job_batches','failed_jobs','migrations'] LOOP
    IF to_regclass('public.' || table_name) IS NOT NULL THEN
      EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', table_name);
      EXECUTE format('REVOKE ALL ON public.%I FROM anon, authenticated', table_name);
    END IF;
  END LOOP;
END $$;
