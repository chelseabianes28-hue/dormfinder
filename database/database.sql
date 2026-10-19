-- DormFinder database
-- Import this file in phpMyAdmin (Import tab) or run: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS dormfinder
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE dormfinder;

-- Users: students, owners, and admins share one table, separated by role
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','owner','admin') NOT NULL DEFAULT 'student',
  phone         VARCHAR(30) NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Listings: dorms and bedspaces posted by owners
CREATE TABLE IF NOT EXISTS listings (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id        INT UNSIGNED NOT NULL,
  title           VARCHAR(150) NOT NULL,
  description     TEXT NULL,
  type            ENUM('dormitory','bedspace','boarding_house') NOT NULL DEFAULT 'dormitory',
  price_per_month DECIMAL(10,2) NOT NULL,
  slots_total     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  slots_available SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  gender_policy   ENUM('any','male_only','female_only') NOT NULL DEFAULT 'any',
  address         VARCHAR(255) NOT NULL,
  city            VARCHAR(100) NOT NULL,
  latitude        DECIMAL(10,7) NULL,
  longitude       DECIMAL(10,7) NULL,
  rules           TEXT NULL,
  amenities       VARCHAR(255) NULL,
  status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_listings_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_listings_status (status),
  INDEX idx_listings_city (city),
  INDEX idx_listings_price (price_per_month)
) ENGINE=InnoDB;

-- Photos: many photos per listing
CREATE TABLE IF NOT EXISTS listing_photos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id  INT UNSIGNED NOT NULL,
  file_path   VARCHAR(255) NOT NULL,
  is_primary  TINYINT(1) NOT NULL DEFAULT 0,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_photos_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Inquiries: a student asks about or requests a listing
CREATE TABLE IF NOT EXISTS inquiries (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id   INT UNSIGNED NOT NULL,
  student_id   INT UNSIGNED NOT NULL,
  message      TEXT NOT NULL,
  move_in_date DATE NULL,
  status       ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inquiries_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  CONSTRAINT fk_inquiries_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Favorites: students save listings
CREATE TABLE IF NOT EXISTS favorites (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  listing_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  UNIQUE KEY uq_favorite (user_id, listing_id)
) ENGINE=InnoDB;

-- Notifications: in-system messages for users
CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  message    VARCHAR(255) NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
