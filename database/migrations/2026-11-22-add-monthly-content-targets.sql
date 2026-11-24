CREATE TABLE IF NOT EXISTS public.monthly_content_targets (
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  target_month VARCHAR(7) NOT NULL
    CHECK (target_month ~ '^[0-9]{4}-(0[1-9]|1[0-2])$'),
  target_count INTEGER NOT NULL CHECK (target_count > 0),
  PRIMARY KEY (user_id, target_month)
);
