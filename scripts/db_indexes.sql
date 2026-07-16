-- Auto‑generated index script for JPMS database
-- This file was copied from the agent's brain directory for version control.
-- Run it with:
--   psql -h <db-host> -U <db-user> -d jpms -f /path/to/db_indexes.sql

-- Example indexes (adjust column names to match your schema):
CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders (created_at);
CREATE INDEX IF NOT EXISTS idx_products_category_id ON products (category_id);

-- Add any additional indexes needed for frequent query columns.
