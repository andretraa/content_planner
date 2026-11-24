ALTER TABLE public.monthly_content_targets
  ADD COLUMN IF NOT EXISTS platform VARCHAR(255) NOT NULL DEFAULT 'all'
    CHECK (platform IN ('all', 'instagram', 'tiktok', 'threads', 'youtube'));

ALTER TABLE public.monthly_content_targets
  DROP CONSTRAINT IF EXISTS monthly_content_targets_pkey;

ALTER TABLE public.monthly_content_targets
  ADD CONSTRAINT monthly_content_targets_pkey
  PRIMARY KEY (user_id, target_month, platform);
