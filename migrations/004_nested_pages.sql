ALTER TABLE pages
    ADD COLUMN parent_id INT UNSIGNED DEFAULT NULL AFTER slug,
    ADD COLUMN nav_order INT NOT NULL DEFAULT 0 AFTER status;

CREATE INDEX idx_pages_parent_status_order ON pages (parent_id, status, nav_order, title);
