ALTER TABLE public.reports
  ADD COLUMN IF NOT EXISTS user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE;

ALTER TABLE public.schedules
  ADD COLUMN IF NOT EXISTS user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE;

ALTER TABLE public.ideas
  ADD COLUMN IF NOT EXISTS user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS reports_user_id_idx ON public.reports (user_id);
CREATE INDEX IF NOT EXISTS schedules_user_id_idx ON public.schedules (user_id);
CREATE INDEX IF NOT EXISTS ideas_user_id_idx ON public.ideas (user_id);
