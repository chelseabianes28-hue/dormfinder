-- Run this once on an existing DormFinder database to add profile photos.
-- phpMyAdmin: open the dormfinder database, click the SQL tab, paste this, and click Go.
USE dormfinder;
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER phone;
