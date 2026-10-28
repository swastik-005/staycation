-- Hotel Management System Database Schema
-- Staycation Hotel & Resort Management Software (INR Currency & Indian Phone Numbers)

CREATE DATABASE IF NOT EXISTS `hotel_management_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_management_db`;

-- Drop tables if resetting
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `room_services`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Admin & Staff)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'manager', 'receptionist') DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Admin (Password: admin123)
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$10$eE0m1Ww11sIeP8xVnC.77.w6gB73.P9yY87qOQYVq9YxLq8Fw1ZtC', 'Staycation General Manager', 'manager@staycation.hotel', 'admin');

-- 2. Rooms Table (Nightly rates in INR ₹)
CREATE TABLE `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(20) NOT NULL UNIQUE,
    `title` VARCHAR(120) NOT NULL,
    `type` VARCHAR(60) NOT NULL,
    `price_per_night` DECIMAL(10,2) NOT NULL,
    `capacity` INT NOT NULL DEFAULT 2,
    `size_sqft` INT NOT NULL,
    `bed_type` VARCHAR(60) NOT NULL,
    `view_type` VARCHAR(60) NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `amenities` TEXT NOT NULL,
    `status` ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    `featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Rooms with luxury photography & operational specs in INR
INSERT INTO `rooms` (`room_number`, `title`, `type`, `price_per_night`, `capacity`, `size_sqft`, `bed_type`, `view_type`, `image_url`, `description`, `amenities`, `status`, `featured`) VALUES
('101', 'Staycation Grand Royal Sanctuary', 'Presidential Suite', 24500.00, 4, 2150, 'Handcrafted Emperor King', 'Panoramic Ocean & Cliff Vista', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80', 'The epitome of architectural grace. Featuring private horizon infinity plunge pool, full butler pantry, bespoke travertine stone bath, and double-height ceiling overlooking the azure bay.', 'Private Infinity Pool, 24/7 Dedicated Butler, Bang & Olufsen Acoustics, Marble Soaking Tub, Walk-in Dressing Room, Private Wine Cellar, Dyson Airwrap & Haircare, Hermès Amenities', 'available', 1),

('201', 'Staycation Azure Horizon Villa', 'Ocean Front Villa', 18500.00, 2, 1400, 'Custom Plush California King', 'Direct Ocean Sunset Frontage', 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=1200&q=80', 'Suspended seamlessly over the coastal rock face, this modern minimalist haven boasts frameless glass folding walls, private sunset terrace with fire pit, and monolithic black stone aesthetics.', 'Sunset Fire Pit, Oceanfront Cantilever Terrace, Steam Shower, Marshall Sound System, In-villa Breakfast Service, Organic Cotton Robes, Le Labo Santal 33 Amenities', 'available', 1),

('301', 'Staycation Obsidian Penthouse Atelier', 'Penthouse Vista', 21000.00, 3, 1750, 'King Size Floating Platform', '360° Sky & Horizon Skyline', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80', 'Curated for the discerning aesthete. An ultra-minimalist penthouse featuring dark oak paneling, private rooftop stargazing lounge, deep Japanese Hinoki soaking tub, and custom bar counter.', 'Rooftop Stargazing Deck, Japanese Hinoki Tub, Smart Bioclimatic Pergola, Bespoke Cocktail Bar, Vinyl Listening Station, High-Speed Starlink, Priority Chauffeur', 'available', 1),

('401', 'Staycation Elysian Courtyard Suite', 'Deluxe Garden Suite', 12500.00, 2, 920, 'King Size Natural Linen Bed', 'Zen Bamboo Courtyard View', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1200&q=80', 'A quiet, meditative sanctuary bordered by private bamboo gardens and natural water cascades. Finished in smooth limestone, neutral linen textures, and warm diffused architectural lighting.', 'Private Zen Garden, Outdoor Rain Shower, Deep Stone Tub, Illy Espresso Bar, Acoustic Soundproofing, Yoga & Meditation Mats, Diptyque Bath Amenities', 'available', 0),

('501', 'Staycation Brutalist Panorama Loft', 'Executive Atelier', 15000.00, 2, 1100, 'King Size Custom Wool Platform', 'Dramatic Coastal Ridgeline View', 'https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=1200&q=80', 'Sculptural raw concrete meets soft cashmere and warm brass. Expansive floor-to-ceiling glass captures the untamed dramatic coastline, paired with an executive study space and fireplace.', 'Sculptural Gas Fireplace, Executive Workspace with Herman Miller Seating, Rain Forest Shower, Wine Climate Cabinet, 75-inch OLED Display, Aesop Grooming Kit', 'available', 0),

('601', 'Staycation Cascades Pavilion Suite', 'Deluxe Garden Suite', 9500.00, 2, 850, 'King Size Tufted Silk Bed', 'Tropical Botanical Gardens', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1200&q=80', 'Nestled among lush tropical flora and tranquil reflecting pools. Offering an open-concept flow between the bedroom, sunken lounge, and sheltered teakwood outdoor patio.', 'Sunken Lounge, Teakwood Patio, Deep Marble Basin, In-room High Tea Service, Nespresso Vertuo, Organic Silk Eye Masks, Acqua Di Parma Botanicals', 'available', 0);

-- 3. Bookings Table (in INR ₹)
CREATE TABLE `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_reference` VARCHAR(30) NOT NULL UNIQUE,
    `room_id` INT NOT NULL,
    `guest_name` VARCHAR(100) NOT NULL,
    `guest_email` VARCHAR(100) NOT NULL,
    `guest_phone` VARCHAR(30) NOT NULL,
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,
    `total_nights` INT NOT NULL,
    `num_guests` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `tax_amount` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `booking_status` ENUM('confirmed', 'checked-in', 'checked-out', 'cancelled') DEFAULT 'confirmed',
    `payment_status` ENUM('paid', 'pending', 'refunded') DEFAULT 'paid',
    `payment_method` VARCHAR(60) DEFAULT 'UPI / Card (Online)',
    `special_requests` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Active Bookings with Indian Phone numbers and INR pricing
INSERT INTO `bookings` (`booking_reference`, `room_id`, `guest_name`, `guest_email`, `guest_phone`, `check_in`, `check_out`, `total_nights`, `num_guests`, `subtotal`, `tax_amount`, `total_price`, `booking_status`, `payment_status`, `payment_method`, `special_requests`) VALUES
('STAY-892104', 1, 'Julian Sterling', 'julian.sterling@curator.art', '+91 98201 23456', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 6 DAY), 4, 2, 98000.00, 11760.00, 109760.00, 'confirmed', 'paid', 'UPI / Net Banking', 'Airport luxury transfer. Late night reception.'),
('STAY-741982', 2, 'Elena Rostova', 'elena.rostova@designworks.ch', '+91 98450 98765', DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 4, 2, 74000.00, 8880.00, 82880.00, 'checked-in', 'paid', 'Visa Infinite', 'Hypoallergenic pillows and morning tea at 8am.'),
('STAY-620491', 3, 'David Chen', 'david.chen@technova.io', '+91 99100 54321', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 4, 1, 84000.00, 10080.00, 94080.00, 'checked-out', 'paid', 'Mastercard / RuPay', 'Quiet corner room request fulfilled. Late checkout granted.');

-- 4. Messages / Contact Inquiries Table
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'archived') DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Inquiries with Indian phone numbers
INSERT INTO `messages` (`name`, `email`, `phone`, `subject`, `message`, `status`) VALUES
('Aarav Mehta', 'aarav.mehta@mumbaidesign.in', '+91 98190 12345', 'Exclusive Property Buyout', 'Inquiring about full buyout of the Obsidian Penthouse and courtyard rooms for an architecture retreat next month.', 'unread'),
('Ananya Sharma', 'ananya@sharmagroup.com', '+91 98220 54321', 'Annual Executive Stay', 'We are looking to reserve signature rooms for our senior leadership team over 3 nights in November.', 'read');

-- 5. Verified Guest Reviews Table
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `guest_name` VARCHAR(100) NOT NULL,
    `guest_location` VARCHAR(100) NOT NULL,
    `rating` INT DEFAULT 5,
    `comment` TEXT NOT NULL,
    `stay_date` VARCHAR(50) NOT NULL,
    `is_approved` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reviews` (`guest_name`, `guest_location`, `rating`, `comment`, `stay_date`, `is_approved`) VALUES
('Rohan Kapoor', 'Mumbai, India', 5, 'Staycation redefined luxury hospitality for us. The clifftop ocean views, seamless online booking, and warm hospitality were second to none.', 'October 2026', 1),
('Meera Nair', 'Bengaluru, India', 5, 'Every detail from the stone textures to the natural acoustic silence feels thoughtfully curated. Truly an unforgettable experience.', 'September 2026', 1),
('Kabir Malhotra', 'New Delhi, India', 5, 'The cliffside villa offers an otherworldly perspective of the sea. Absolute privacy, smooth check-in, and great comfort.', 'August 2026', 1);
