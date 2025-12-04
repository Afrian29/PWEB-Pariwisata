<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inisialisasi database connection
$db = new Database();

// Ambil ID dari URL parameter
$place_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID - pastikan ID valid
if ($place_id <= 0) {
    // Redirect ke halaman utama jika ID tidak valid
    header('Location: index.php');
    exit();
}

// Ambil data tempat wisata berdasarkan ID
$query = "SELECT * FROM tourism_places WHERE id = ?";
$stmt = $db->conn->prepare($query);
$stmt->bind_param("i", $place_id);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah tempat wisata ditemukan
if ($result->num_rows === 0) {
    // Redirect jika tempat tidak ditemukan
    header('Location: index.php?error=not_found');
    exit();
}

$place = $result->fetch_assoc();
$stmt->close();

// INCREMENT VIEW COUNTER - Fitur utama
// Update view count: tambah 1 setiap kali halaman dibuka
$update_views_query = "UPDATE tourism_places SET views = views + 1 WHERE id = ?";
$update_stmt = $db->conn->prepare($update_views_query);
$update_stmt->bind_param("i", $place_id);

if ($update_stmt->execute()) {
    // Update success - increment local variable untuk display
    $place['views']++; // Tambah 1 ke view count yang ditampilkan
    $view_incremented = true;
} else {
    $view_incremented = false;
}
$update_stmt->close();

// Ambil tempat wisata terkait (kategori sama, kecuali tempat ini sendiri)
$related_query = "SELECT * FROM tourism_places
                  WHERE category = ? AND id != ?
                  ORDER BY is_featured DESC, views DESC
                  LIMIT 4";
$related_stmt = $db->conn->prepare($related_query);
$related_stmt->bind_param("si", $place['category'], $place_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
$related_stmt->close();
// Handle Comment Submission
$comment_success = false;
$comment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name']);
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    // Simple validation
    if (empty($name) || empty($comment)) {
        $comment_error = 'Nama dan komentar harus diisi.';
    } elseif ($rating < 1 || $rating > 5) {
        $comment_error = 'Rating harus antara 1 sampai 5.';
    } else {
        // Insert comment
        $insert_comment = "INSERT INTO comments (place_id, name, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt_comment = $db->conn->prepare($insert_comment);
        $stmt_comment->bind_param("isis", $place_id, $name, $rating, $comment);
        
        if ($stmt_comment->execute()) {
            $comment_success = true;
            // Redirect to avoid resubmission on refresh
            // header("Location: detail.php?id=$place_id&comment_success=1");
            // exit();
        } else {
            $comment_error = 'Gagal mengirim komentar. Silakan coba lagi.';
        }
        $stmt_comment->close();

        // ---------------------------------------------------------
        // LOGIKA BARU: Auto-Calculate Rating
        // ---------------------------------------------------------
        if ($comment_success) {
            // 1. Hitung rata-rata rating dari tabel comments
            $avg_query = "SELECT AVG(rating) as average_rating FROM comments WHERE place_id = ?";
            $stmt_avg = $db->conn->prepare($avg_query);
            $stmt_avg->bind_param("i", $place_id);
            $stmt_avg->execute();
            $result_avg = $stmt_avg->get_result();
            $row_avg = $result_avg->fetch_assoc();
            
            $new_rating = 0.0;
            if ($row_avg['average_rating'] !== null) {
                $new_rating = (float)$row_avg['average_rating'];
            }
            $stmt_avg->close();

            // 2. Update rating di tabel tourism_places
            $update_rating_query = "UPDATE tourism_places SET rating = ? WHERE id = ?";
            $stmt_update_rating = $db->conn->prepare($update_rating_query);
            $stmt_update_rating->bind_param("di", $new_rating, $place_id);
            $stmt_update_rating->execute();
            $stmt_update_rating->close();

            // Redirect to avoid resubmission on refresh (PRG Pattern)
            header("Location: detail.php?id=" . $place_id . "&status=success");
            exit();
        }
        // ---------------------------------------------------------
    }
}

// Get Comments
$comments_query = "SELECT * FROM comments WHERE place_id = ? ORDER BY created_at DESC";
$stmt_comments = $db->conn->prepare($comments_query);
$stmt_comments->bind_param("i", $place_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
$stmt_comments->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($place['name']); ?> - Detail Wisata</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($place['description'], 0, 160)); ?>">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Additional CSS untuk halaman detail -->
    <style>
        /* Styling khusus halaman detail */
        .detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .detail-header {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .detail-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: linear-gradient(135deg, #0077B6, #0096C7);
        }

        .detail-content {
            padding: 2rem;
        }

        .detail-title {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .detail-title h1 {
            color: #333;
            margin: 0;
            flex: 1;
        }

        .detail-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
        }

        .badge-category {
            background: #0077B6;
        }

        .badge-featured {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
        }

        .detail-meta {
            display: flex;
            gap: 2rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .meta-item strong {
            color: #333;
        }

        .detail-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
            margin: 2rem 0;
            text-align: justify;
        }

        .detail-stats {
            background: linear-gradient(135deg, #0077B6, #0096C7);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin: 2rem 0;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Related places section */
        .related-section {
            margin-top: 3rem;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .related-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .related-card:hover {
            transform: translateY(-5px);
        }

        .related-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .related-card-content {
            padding: 1rem;
        }

        .related-card h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1rem;
        }

        .related-meta {
            font-size: 0.9rem;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .detail-container {
                padding: 1rem;
            }

            .detail-image {
                height: 250px;
            }

            .detail-content {
                padding: 1.5rem;
            }

            .detail-title {
                flex-direction: column;
            }

            .detail-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* View counter animation */
        @keyframes viewIncrement {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); color: #28a745; }
            100% { transform: scale(1); }
        }

        .views-highlight {
            animation: viewIncrement 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Interactive Fixed Background -->
    <div class="parallax-background" id="parallaxBg" style="background-image: url('assets/images/bg1.jpeg');"></div>

    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>Pesona Gorontalo</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="index.php">← Kembali</a></li>
                        <li><a href="contact.php">Buku Tamu</a></li>
                        <li><a href="admin/login.php" class="btn-nav-login">Login</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="detail-container">
            <!-- Header Section dengan Gambar dan Informasi Utama -->
            <div class="detail-header">
                <?php if (!empty($place['image'])): ?>
                    <img src="assets/images/<?php echo htmlspecialchars($place['image']); ?>"
                         alt="<?php echo htmlspecialchars($place['name']); ?>"
                         class="detail-image"
                         onerror="this.src='assets/images/default-place.jpg'">
                <?php else: ?>
                    <div class="detail-image" style="display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                        🏞️
                    </div>
                <?php endif; ?>

                <div class="detail-content">
                    <!-- Judul dan Badges -->
                    <div class="detail-title">
                        <h1><?php echo htmlspecialchars($place['name']); ?></h1>
                        <div class="detail-badges">
                            <span class="badge badge-category">
                                <?php echo htmlspecialchars(ucfirst($place['category'])); ?>
                            </span>
                            <?php if ($place['is_featured'] == 1): ?>
                                <span class="badge badge-featured">✨ Rekomendasi Editor</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Meta Information -->
                    <div class="detail-meta">
                        <div class="meta-item">
                            <span>📍</span>
                            <div>
                                <strong>Lokasi:</strong>
                                <?php echo htmlspecialchars($place['location']); ?>
                            </div>
                        </div>

                        <div class="meta-item">
                            <span>⭐</span>
                            <div>
                                <strong>Rating:</strong>
                                <?php echo number_format($place['rating'], 1); ?> / 5.0
                            </div>
                        </div>

                        <div class="meta-item">
                            <span>👁️</span>
                            <div class="<?php echo $view_incremented ? 'views-highlight' : ''; ?>">
                                <strong>Dilihat:</strong>
                                <?php
                                    // Format angka views agar mudah dibaca
                                    echo number_format($place['views'], 0, ',', '.');
                                ?> kali
                            </div>
                        </div>

                        <div class="meta-item">
                            <span>📅</span>
                            <div>
                                <strong>Ditambahkan:</strong>
                                <?php echo format_date($place['created_at']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi Lengkap -->
                    <div class="detail-description">
                        <?php echo nl2br(htmlspecialchars($place['description'])); ?>
                    </div>

                    <!-- Statistik Populer -->
                    <div class="detail-stats">
                        <h3>Statistik Popularitas</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo number_format($place['views'], 0, ',', '.'); ?></span>
                                <span class="stat-label">Total Dilihat</span>
                            </div>

                            <div class="stat-item">
                                <span class="stat-number">⭐ <?php echo number_format($place['rating'], 1); ?></span>
                                <span class="stat-label">Rating Pengunjung</span>
                            </div>

                            <div class="stat-item">
                                <span class="stat-number"><?php echo htmlspecialchars(ucfirst($place['category'])); ?></span>
                                <span class="stat-label">Kategori</span>
                            </div>

                            <?php if ($place['is_featured'] == 1): ?>
                                <div class="stat-item">
                                    <span class="stat-number">✨</span>
                                    <span class="stat-label">Rekomendasi Editor</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="comments-section">
                <h3>Ulasan & Komentar</h3>
                
                <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                    <div class="alert alert-success">
                        Terima kasih! Komentar Anda telah berhasil dikirim.
                    </div>
                <?php endif; ?>
                
                <?php if ($comment_error): ?>
                    <div class="alert alert-danger">
                        <?php echo $comment_error; ?>
                    </div>
                <?php endif; ?>

                <div class="comments-container">
                    <!-- Comment Form -->
                    <div class="comment-form-card">
                        <h4>Tulis Ulasan Anda</h4>
                        <form action="" method="POST" class="comment-form">
                            <div class="form-group">
                                <label for="name">Nama Anda</label>
                                <input type="text" id="name" name="name" required placeholder="Masukkan nama lengkap">
                            </div>
                            
                            <div class="form-group">
                                <label for="rating">Rating</label>
                                <select id="rating" name="rating" required>
                                    <option value="5">⭐⭐⭐⭐⭐ (Sangat Bagus)</option>
                                    <option value="4">⭐⭐⭐⭐ (Bagus)</option>
                                    <option value="3">⭐⭐⭐ (Cukup)</option>
                                    <option value="2">⭐⭐ (Kurang)</option>
                                    <option value="1">⭐ (Sangat Kurang)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment">Komentar</label>
                                <textarea id="comment" name="comment" required placeholder="Bagikan pengalaman Anda di sini..."></textarea>
                            </div>
                            
                            <button type="submit" name="submit_comment" class="btn btn-primary">Kirim Ulasan</button>
                        </form>
                    </div>

                    <!-- Comments List -->
                    <div class="comments-list">
                        <h4>Apa Kata Pengunjung?</h4>
                        <?php if ($comments_result->num_rows > 0): ?>
                            <?php while ($comment = $comments_result->fetch_assoc()): ?>
                                <div class="comment-card">
                                    <div class="comment-header">
                                        <div class="comment-avatar">
                                            <?php echo strtoupper(substr($comment['name'], 0, 1)); ?>
                                        </div>
                                        <div class="comment-meta">
                                            <div class="comment-name"><?php echo htmlspecialchars($comment['name']); ?></div>
                                            <div class="comment-date"><?php echo format_date($comment['created_at']); ?></div>
                                        </div>
                                        <div class="comment-rating">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <span class="star"><?php echo $i <= $comment['rating'] ? '★' : '☆'; ?></span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="comment-body">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-comments">
                                <p>Belum ada ulasan. Jadilah yang pertama memberikan ulasan!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tempat Terkait -->
            <?php if ($related_result && $related_result->num_rows > 0): ?>
                <div class="related-section">
                    <h2>Tempat Terkait</h2>
                    <p>Temukan tempat wisata lain dengan kategori yang sama:</p>

                    <div class="related-grid">
                        <?php while ($related_place = $related_result->fetch_assoc()): ?>
                            <a href="detail.php?id=<?php echo $related_place['id']; ?>" class="related-card-link" style="text-decoration: none; color: inherit;">
                                <div class="related-card">
                                    <img src="assets/images/<?php echo !empty($related_place['image']) ? htmlspecialchars($related_place['image']) : 'default-place.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($related_place['name']); ?>"
                                         onerror="this.src='assets/images/default-place.jpg'">
                                    <div class="related-card-content">
                                        <h4><?php echo htmlspecialchars($related_place['name']); ?></h4>
                                        <div class="related-meta">
                                            <span>📍 <?php echo htmlspecialchars($related_place['location']); ?></span>
                                            <span style="margin-left: 1rem;">👁️ <?php echo number_format($related_place['views'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Regional Tourism Information System. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript untuk Parallax Effect -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const parallaxBg = document.getElementById('parallaxBg');
            let ticking = false;

            function updateBackground() {
                const scrollY = window.scrollY;
                let opacity = 1;

                // Fade settings - sama dengan halaman utama
                const fadeStart = 100;
                const fadeEnd = 500;

                if (scrollY > fadeStart) {
                    const fadeProgress = Math.min((scrollY - fadeStart) / (fadeEnd - fadeStart), 1);
                    opacity = 1 - (fadeProgress * 0.7); // Fade to 0.3 opacity
                }

                // Apply hanya opacity effect
                parallaxBg.style.opacity = opacity;
                parallaxBg.style.transform = 'none';
                parallaxBg.style.filter = 'none';

                ticking = false;
            }

            function requestTick() {
                if (!ticking) {
                    window.requestAnimationFrame(updateBackground);
                    ticking = true;
                }
            }

            // Initial update
            updateBackground();

            // Update on scroll
            window.addEventListener('scroll', requestTick);

            // Update on resize
            window.addEventListener('resize', function() {
                updateBackground();
            });

            // View increment animation effect
            <?php if ($view_incremented): ?>
            const viewsElement = document.querySelector('.views-highlight');
            if (viewsElement) {
                // Add visual feedback that view was incremented
                setTimeout(() => {
                    viewsElement.classList.remove('views-highlight');
                }, 500);
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>

<?php $db->close(); ?>