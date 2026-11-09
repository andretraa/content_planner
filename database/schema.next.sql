CREATE TABLE IF NOT EXISTS public.schedules (
  id BIGSERIAL PRIMARY KEY,
  platform VARCHAR(255) NOT NULL,
  tag VARCHAR(255),
  title VARCHAR(255) NOT NULL,
  date VARCHAR(255) NOT NULL,
  time VARCHAR(255) NOT NULL,
  status VARCHAR(255) NOT NULL,
  description TEXT,
  idea_id BIGINT
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
  scheduled_note VARCHAR(255)
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
  idea_id BIGINT
);
