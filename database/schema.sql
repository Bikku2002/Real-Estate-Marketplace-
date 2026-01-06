-- Create database if not exists
CREATE DATABASE IF NOT EXISTS final6 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE final6;

-- Users (buyers/sellers)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(40) NULL,
  role ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer',
  password_hash VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) NULL,
  kyc_status ENUM('pending','verified','rejected') DEFAULT 'pending',
  kyc_document_type ENUM('citizenship','passport','license') NULL,
  kyc_document_number VARCHAR(50) NULL,
  kyc_document_image VARCHAR(255) NULL,
  kyc_verified_at TIMESTAMP NULL,
  kyc_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (kyc_status)
);

-- Properties (land/house)
CREATE TABLE IF NOT EXISTS properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  type ENUM('land','house') NOT NULL,
  district VARCHAR(100) NOT NULL,
  municipality VARCHAR(120) NULL,
  ward VARCHAR(20) NULL,
  price BIGINT NOT NULL,
  area_sqft INT NULL,
  area_ana DECIMAL(10,2) NULL,
  bedrooms TINYINT NULL,
  bathrooms TINYINT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  seller_id INT NOT NULL,
  description TEXT NULL,
  cover_image VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (type),
  INDEX (district),
  INDEX (price),
  CONSTRAINT fk_properties_seller FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- Images
CREATE TABLE IF NOT EXISTS property_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  CONSTRAINT fk_property_images_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Offers (bargain flow)
CREATE TABLE IF NOT EXISTS offers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  buyer_id INT NOT NULL,
  offer_amount BIGINT NOT NULL,
  status ENUM('pending','accepted','rejected','countered') DEFAULT 'pending',
  message VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_offers_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_offers_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (property_id, created_at)
);

-- Simple activity feed for real-time
CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('new_property','new_offer','price_drop') NOT NULL,
  ref_id INT NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at)
);

-- Messages / contact
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(120) NULL,
  email VARCHAR(160) NULL,
  phone VARCHAR(40) NULL,
  subject VARCHAR(160) NULL,
  body TEXT NOT NULL,
  status ENUM('new','in_progress','resolved') DEFAULT 'new',
  admin_notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX(status), INDEX(created_at)
);

-- Seed demo data
INSERT INTO users (name, email, phone, role, password_hash) VALUES
('Seller One','seller1@example.com','9800000001','seller', '$2y$10$abcdefghijklmnopqrstuv'),
('Buyer One','buyer1@example.com','9800000002','buyer', '$2y$10$abcdefghijklmnopqrstuv');

-- Seed admin (password = password)
INSERT INTO users (name, email, phone, role, password_hash) VALUES
('Admin','admin@example.com',NULL,'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO properties (title, type, district, municipality, ward, price, area_sqft, area_ana, bedrooms, bathrooms, latitude, longitude, seller_id, description, cover_image)
VALUES
('Sunny Plot in Bhaktapur','land','Bhaktapur','Suryabinayak','5', 6500000, NULL, 5.5, NULL, NULL, 27.6710000, 85.4290000, 1, 'Ideal for a small family house with south-facing frontage.', 'assets/img/demo_land.jpg'),
('Modern House in Lalitpur','house','Lalitpur','Godawari','3', 18500000, 2200, NULL, 4, 3, 27.5915000, 85.3241000, 1, 'Contemporary 2.5-storey with garden and parking.', 'assets/img/demo_house.jpg');

INSERT INTO property_images (property_id, image_url, sort_order) VALUES
(1, 'assets/img/demo_land.jpg', 1),
(2, 'assets/img/demo_house.jpg', 1);

INSERT INTO activities (type, ref_id, meta) VALUES
('new_property', 1, JSON_OBJECT('title','Sunny Plot in Bhaktapur','price',6500000)),
('new_property', 2, JSON_OBJECT('title','Modern House in Lalitpur','price',18500000));


