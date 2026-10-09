CREATE TABLE IF NOT EXISTS public.atm_sessions (sid varchar NOT NULL PRIMARY KEY, sess json NOT NULL, expire timestamp(6) NOT NULL);
CREATE INDEX IF NOT EXISTS atm_sessions_expire_idx ON public.atm_sessions (expire);
CREATE TABLE IF NOT EXISTS public.node_configurations (config_id varchar(120) PRIMARY KEY, encrypted_payload text NOT NULL, updated_at timestamptz NOT NULL DEFAULT now());
ALTER TABLE public.atm_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.node_configurations ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.atm_sessions, public.node_configurations FROM anon, authenticated;
