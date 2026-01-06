-- Update database schema for role-based features
-- Add missing fields to properties table

ALTER TABLE properties 
ADD COLUMN IF NOT EXISTS is_negotiable BOOLEAN DEFAULT FALSE AFTER description,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'sold', 'inactive') DEFAULT 'active' AFTER is_negotiable;

-- Create favorites table for buyers
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, property_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Create property_queries table for buyer inquiries
CREATE TABLE IF NOT EXISTS property_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'replied', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_queries_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    CONSTRAINT fk_queries_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_queries_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_properties_seller_status ON properties(seller_id, status);
CREATE INDEX IF NOT EXISTS idx_properties_type_district ON properties(type, district);
CREATE INDEX IF NOT EXISTS idx_properties_price ON properties(price);
CREATE INDEX IF NOT EXISTS idx_offers_property_buyer ON offers(property_id, buyer_id);
CREATE INDEX IF NOT EXISTS idx_favorites_user ON favorites(user_id);
CREATE INDEX IF NOT EXISTS idx_queries_property_buyer ON property_queries(property_id, buyer_id);

-- Update existing properties to have default values
UPDATE properties SET is_negotiable = FALSE WHERE is_negotiable IS NULL;
UPDATE properties SET status = 'active' WHERE status IS NULL;

-- Insert sample data for testing
INSERT IGNORE INTO properties (title, type, district, municipality, ward, price, area_sqft, area_ana, bedrooms, bathrooms, description, cover_image, seller_id, is_negotiable, status) VALUES
('Modern Apartment in Kathmandu', 'house', 'Kathmandu', 'Kathmandu Metropolitan City', '1', 25000000, 1800, NULL, 3, 2, 'Beautiful 3 BHK apartment with modern amenities, parking, and security.', 'assets/img/placeholder.svg', 1, TRUE, 'active'),
('Commercial Land in Lalitpur', 'land', 'Lalitpur', 'Lalitpur Metropolitan City', '3', 45000000, NULL, 8.5, NULL, NULL, 'Prime commercial land near ring road, ideal for business development.', 'assets/img/placeholder.svg', 1, TRUE, 'active'),
('Family House in Bhaktapur', 'house', 'Bhaktapur', 'Bhaktapur Municipality', '5', 35000000, 2500, NULL, 4, 3, 'Spacious family house with garden, parking, and modern facilities.', 'assets/img/placeholder.svg', 1, FALSE, 'active');

-- Insert sample offers
INSERT IGNORE INTO offers (property_id, buyer_id, offer_amount, status, message) VALUES
(1, 2, 23000000, 'pending', 'Interested in this property. Is the price negotiable?'),
(2, 2, 42000000, 'pending', 'Great location! Would you consider a lower price?');

-- Insert sample favorites
INSERT IGNORE INTO favorites (user_id, property_id) VALUES
(2, 1),
(2, 3);

-- Insert sample queries
INSERT IGNORE INTO property_queries (property_id, buyer_id, seller_id, subject, message) VALUES
(1, 2, 1, 'Property Viewing Request', 'Hi, I would like to schedule a viewing of this property. When would be convenient?'),
(2, 2, 1, 'Land Development Questions', 'I have some questions about the land development potential. Can we discuss?');
