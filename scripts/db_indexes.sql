-- Auto‑generated index script for JPMS database
-- This file was copied from the agent's brain directory for version control.
-- Run it with:
--   psql -h <db-host> -U <db-user> -d jpms -f /path/to/db_indexes.sql

-- Example indexes (adjusted for actual JPMS schema)
CREATE INDEX IF NOT EXISTS idx_manuscripts_status ON manuscripts (status);
CREATE INDEX IF NOT EXISTS idx_manuscripts_submitted_at ON manuscripts (submitted_at);
CREATE INDEX IF NOT EXISTS idx_journals_title ON journals (title);
CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles (user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON user_roles (role_id);

-- Add any additional indexes needed for frequent query columns.
