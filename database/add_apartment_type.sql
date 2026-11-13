-- Run this once on an older DormFinder/StayC database so owners can post apartments.
-- phpMyAdmin: open the database, click the SQL tab, paste this, and click Go.
ALTER TABLE listings
  MODIFY type ENUM('dormitory','bedspace','boarding_house','apartment') NOT NULL DEFAULT 'dormitory';
