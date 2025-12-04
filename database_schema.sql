-- Regional Tourism Information System Database Schema
-- MySQL Database

-- Create database
CREATE DATABASE IF NOT EXISTS tourism_db;
USE tourism_db;

-- Create tourism_places table
CREATE TABLE IF NOT EXISTS tourism_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    rating DECIMAL(3,1) NOT NULL DEFAULT 3.0,
    image VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Rekomendasi Editor, 0=Tempat Biasa',
    views INT NOT NULL DEFAULT 0 COMMENT 'Jumlah halaman dilihat oleh pengunjung',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO tourism_places (name, description, location, category, rating, image, is_featured, views) VALUES
('Sunset Beach', 'A beautiful pristine beach with golden sand and crystal clear waters. Perfect for swimming, sunbathing, and water sports activities.', 'Coastal Region, West Province', 'beach', 4.5, 'sunset_beach.jpg', 1, 245),
('Mountain Peak Adventure', 'Breathtaking mountain views and hiking trails for all skill levels. Experience the thrill of reaching the summit.', 'Highland District, North Province', 'mountain', 4.8, 'mountain_peak.jpg', 1, 189),
('Ancient Temple Heritage', 'A historic temple dating back to the 12th century with stunning architecture and cultural significance.', 'Old City District, Central Province', 'temple', 4.6, 'ancient_temple.jpg', 0, 87),
('National History Museum', 'Discover the rich history and culture of our region through interactive exhibits and ancient artifacts.', 'Capital City, Central Province', 'museum', 4.3, 'history_museum.jpg', 0, 123),
('Riverside Park', 'A peaceful green space with walking trails, picnic areas, and playgrounds. Perfect for families and nature lovers.', 'Urban Area, East Province', 'park', 4.2, 'riverside_park.jpg', 1, 156),
('Crystal Waterfall', 'A majestic 100-meter waterfall surrounded by lush tropical forest. A hidden gem for nature enthusiasts.', 'Rainforest Region, South Province', 'waterfall', 4.9, 'crystal_waterfall.jpg', 1, 298),
('Heritage Village', 'Step back in time and experience traditional village life with authentic architecture and cultural demonstrations.', 'Cultural District, West Province', 'other', 4.4, 'heritage_village.jpg', 0, 65),
('Coastal Lighthouse', 'Historic lighthouse offering panoramic ocean views and maritime history exhibits. Climb to the top for spectacular scenery.', 'Harbor Town, East Province', 'other', 4.1, 'coastal_lighthouse.jpg', 0, 45),
('Tropical Botanical Garden', 'Explore diverse plant species from around the world in this beautifully landscaped garden with themed sections.', 'Green Valley, Central Province', 'park', 4.5, 'botanical_garden.jpg', 0, 92),
('Adventure Water Park', 'Family-friendly water park with thrilling slides, wave pools, and lazy rivers for hours of fun.', 'Resort Area, South Province', 'other', 4.0, 'water_park.jpg', 0, 78);

-- Add indexes for better performance
CREATE INDEX idx_tourism_places_category ON tourism_places(category);
CREATE INDEX idx_tourism_places_location ON tourism_places(location);
CREATE INDEX idx_tourism_places_rating ON tourism_places(rating);
CREATE INDEX idx_tourism_places_created_at ON tourism_places(created_at);
CREATE INDEX idx_tourism_places_featured ON tourism_places(is_featured);
CREATE INDEX idx_tourism_places_views ON tourism_places(views);

-- Create users table for future admin authentication (optional)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@tourism.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create view for tourism statistics (optional)
CREATE VIEW tourism_stats AS
SELECT
    category,
    COUNT(*) as total_places,
    AVG(rating) as avg_rating,
    MAX(rating) as max_rating,
    MIN(rating) as min_rating
FROM tourism_places
GROUP BY category
ORDER BY avg_rating DESC;

-- Create comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL COMMENT 'ID tempat wisata (Loose coupling)',
    name VARCHAR(100) NOT NULL,
    rating INT NOT NULL COMMENT 'Skala 1-5',
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add index for comments
CREATE INDEX idx_comments_place_id ON comments(place_id);

-- Create messages table for buku tamu (contact form)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL COMMENT 'Nama pengirim pesan',
    email VARCHAR(100) NOT NULL COMMENT 'Email pengirim pesan',
    message TEXT NOT NULL COMMENT 'Isi pesan dari pengunjung',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pesan dikirim',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for messages table
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_email ON messages(email);

-- Insert sample data for messages table (untuk testing)
INSERT INTO messages (sender_name, email, message) VALUES
('Ahmad Wijaya', 'ahmad@example.com', 'Website pariwisata ini sangat bagus! Informasinya lengkap dan mudah dimengerti. Terima kasih atas kerja kerasnya.'),
('Sarah Putri', 'sarah@email.com', 'Saya tertarik untuk mengunjungi Crystal Waterfall. Apakah ada informasi lebih lanjut tentang tiket dan transportasi?'),
('Budi Santoso', 'budi@travel.com', 'Mungkin bisa ditambahkan fitur peta interaktif untuk memudahkan pencarian lokasi. Overall, sangat membantu!'),
('Maya Anggraini', 'maya@email.com', 'Sunset Beach adalah tempat favorit saya! Sudah 3 kali berkunjung dan tidak pernah bosan.'),
('Rizki Pratama', 'rizki@example.com', 'Request untuk tambah kategori "Kuliner" ya, agar pengunjung tahu tempat makan enak di sekitar wisata.'),
('Dewi Lestari', 'dewi@company.com', 'Kami dari travel agency tertarik untuk kerja sama. Mohon info kontak marketing.'),
('Fajar Nugroho', 'fajar@email.com', 'Loading gambar agak lambat, mungkin bisa dioptimasi lagi. Tapi kontennya sangat berkualitas!'),
('Lisa Permata', 'lisa@tourist.com', 'As a foreign tourist, I really appreciate this website. The English translations are very helpful!'),
('Joko Widodo', 'joko@email.com', 'Saran: tambahkan filter berdasarkan harga tiket masuk agar sesuai budget.'),
('Ratna Sari', 'ratna@email.com', 'Terima kasih sudah mempromosikan tempat-tempat wisata di daerah kami. Semoga semakin dikenal!');