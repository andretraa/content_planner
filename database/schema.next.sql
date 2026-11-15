CREATE TABLE IF NOT EXISTS public.schedules (
  id BIGSERIAL PRIMARY KEY,
  platform VARCHAR(255) NOT NULL,
  tag VARCHAR(255),
  title VARCHAR(255) NOT NULL,
  date VARCHAR(255) NOT NULL,
  time VARCHAR(255) NOT NULL,
  status VARCHAR(255) NOT NULL,
  description TEXT,
  idea_id BIGINT,
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS public.ideas (
  id BIGSERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  platform VARCHAR(255) NOT NULL,
  category VARCHAR(255) NOT NULL DEFAULT 'General',
  "column" VARCHAR(255) NOT NULL,
  badge VARCHAR(255),
  time_ago VARCHAR(255),
  scheduled_note VARCHAR(255),
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS public.reports (
  id BIGSERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  platform VARCHAR(255) NOT NULL,
  tag VARCHAR(255),
  followers VARCHAR(255) NOT NULL DEFAULT '-',
  tayangan VARCHAR(255) NOT NULL DEFAULT '-',
  jangkauan VARCHAR(255) NOT NULL DEFAULT '-',
  engagement VARCHAR(255) NOT NULL DEFAULT '-%',
  date VARCHAR(255),
  period VARCHAR(255),
  schedule_id BIGINT,
  idea_id BIGINT,
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS schedules_user_id_idx ON public.schedules (user_id);
CREATE INDEX IF NOT EXISTS ideas_user_id_idx ON public.ideas (user_id);
CREATE INDEX IF NOT EXISTS reports_user_id_idx ON public.reports (user_id);

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
