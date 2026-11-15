CREATE TABLE IF NOT EXISTS public.instagram_connections (
  user_id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  instagram_user_id TEXT NOT NULL,
  username TEXT NOT NULL,
  account_type TEXT,
  token_encrypted TEXT NOT NULL,
  token_issued_at TIMESTAMPTZ NOT NULL,
  token_expires_at TIMESTAMPTZ NOT NULL,
  profile JSONB NOT NULL DEFAULT '{}'::jsonb,
  insights JSONB NOT NULL DEFAULT '{}'::jsonb,
  last_synced_at TIMESTAMPTZ,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.instagram_oauth_states (
  state_hash CHAR(64) PRIMARY KEY,
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  expires_at TIMESTAMPTZ NOT NULL
);

CREATE INDEX IF NOT EXISTS instagram_oauth_states_expires_at_idx
  ON public.instagram_oauth_states (expires_at);
