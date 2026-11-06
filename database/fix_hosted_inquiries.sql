-- Run this ONLY on the hosted database (InfinityFree phpMyAdmin), not on your local XAMPP database.
-- It removes two unused columns so the hosted inquiries table matches the project.
ALTER TABLE inquiries DROP COLUMN visit_date, DROP COLUMN visit_time;
