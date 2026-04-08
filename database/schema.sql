-- TRAGOS Database Schema
CREATE DATABASE IF NOT EXISTS tragos_db;
USE tragos_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    bio TEXT,
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.png',
    phone VARCHAR(20),
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Groups table
CREATE TABLE groups_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    destination VARCHAR(100),
    category ENUM('backpacking', 'luxury', 'adventure', 'cultural', 'food', 'photography', 'solo', 'family', 'business', 'other') DEFAULT 'other',
    privacy ENUM('public', 'private') DEFAULT 'public',
    owner_id INT NOT NULL,
    max_members INT DEFAULT 50,
    current_members INT DEFAULT 1,
    group_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Group members table
CREATE TABLE group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'admin', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups_table(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (group_id, user_id)
);

-- Join requests table
CREATE TABLE join_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'blocked') DEFAULT 'pending',
    message TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    responded_by INT,
    FOREIGN KEY (group_id) REFERENCES groups_table(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id),
    UNIQUE KEY unique_request (group_id, user_id)
);

-- Chat messages table
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'image', 'file') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (group_id) REFERENCES groups_table(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('join_request', 'join_approved', 'join_rejected', 'new_message', 'group_invite') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password reset tokens table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used BOOLEAN DEFAULT FALSE
);

-- Insert dummy data
INSERT INTO users (username, email, password, display_name, bio, location) VALUES
('admin', 'admin@tragos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'Welcome to TRAGOS! I manage this amazing platform.', 'Global'),
('john_traveler', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Explorer', 'Adventure seeker and photography enthusiast. Love exploring new cultures!', 'New York, USA'),
('sarah_wanderer', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Wanderer', 'Backpacker with a passion for authentic local experiences.', 'London, UK'),
('mike_foodie', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Foodie', 'Culinary explorer seeking the best food experiences around the world.', 'Tokyo, Japan'),
('emma_luxury', 'emma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Luxe', 'Luxury travel enthusiast who believes in traveling in style.', 'Paris, France');

INSERT INTO groups_table (name, description, destination, category, privacy, owner_id, group_image) VALUES
('Europe Backpackers 2024', 'Join us for an epic backpacking adventure across Europe! We will visit 15 countries in 3 months, staying in hostels and meeting fellow travelers.', 'Europe', 'backpacking', 'public', 2, 'europe-backpackers.png'),
('Asian Food Tours', 'Discover the authentic flavors of Asia! From street food in Bangkok to sushi in Tokyo, we explore the best culinary experiences.', 'Asia', 'food', 'public', 4, 'asian-food-tours.png'),
('Adventure Seekers Club', 'For thrill-seekers and adrenaline junkies! Rock climbing, bungee jumping, skydiving, and more extreme sports adventures.', 'Worldwide', 'adventure', 'private', 2, 'adventure-seekers.png'),
('Luxury European Getaway', 'Experience Europe in ultimate luxury. 5-star hotels, private tours, Michelin-starred restaurants, and exclusive experiences.', 'Europe', 'luxury', 'private', 5, 'luxury.png'),
('Photography Expedition', 'Capture the world through your lens! Join fellow photographers on stunning landscape and cultural photography trips.', 'Various', 'photography', 'public', 3, 'photography.png');

INSERT INTO group_members (group_id, user_id, role) VALUES
(1, 2, 'owner'),
(1, 3, 'member'),
(1, 4, 'member'),
(2, 4, 'owner'),
(2, 2, 'member'),
(2, 5, 'member'),
(3, 2, 'owner'),
(4, 5, 'owner'),
(5, 3, 'owner'),
(5, 2, 'member');

UPDATE groups_table SET current_members = (
    SELECT COUNT(*) FROM group_members WHERE group_members.group_id = groups_table.id
);

INSERT INTO chat_messages (group_id, user_id, message) VALUES
(1, 2, 'Welcome everyone to Europe Backpackers 2024! 🎒✈️'),
(1, 3, 'So excited for this trip! When do we start planning the itinerary?'),
(1, 4, 'I have some great hostel recommendations for Prague and Budapest!'),
(2, 4, 'Welcome to Asian Food Tours! Get ready for an amazing culinary journey 🍜🍣'),
(2, 2, 'Can not wait to try authentic ramen in Tokyo!'),
(2, 5, 'I know some hidden gem restaurants in Bangkok we should visit'),
(5, 3, 'Photography enthusiasts unite! Share your best travel shots here 📸'),
(5, 2, 'Just uploaded some shots from my Iceland trip. The Northern Lights were incredible!');

INSERT INTO notifications (user_id, type, title, message, related_id) VALUES
(3, 'join_approved', 'Welcome to Europe Backpackers!', 'Your request to join Europe Backpackers 2024 has been approved.', 1),
(2, 'new_message', 'New message in Asian Food Tours', 'Mike Foodie posted a new message in the group.', 2),
(5, 'join_approved', 'Welcome to Asian Food Tours!', 'Your request to join Asian Food Tours has been approved.', 2);