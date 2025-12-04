<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$db = new Database();

// Handle form submissions
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_place'])) {
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        $location = clean_input($_POST['location']);
        $category = clean_input($_POST['category']);
        // Rating is now dynamic, default to 0.0 for new places
        $rating = 0.0;
        // Initialize image variable
        $image = '';

        // Ambil nilai checkbox untuk rekomendasi editor
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Handle file upload
        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === 0) {
            $upload_dir = '../assets/images/';
            $file_name = time() . '_' . basename($_FILES['image_upload']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $target_file)) {
                $image = $file_name;
            }
        }

      
        // Query INSERT dengan kolom is_featured
        $query = "INSERT INTO tourism_places (name, description, location, category, rating, image, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ssssdsi", $name, $description, $location, $category, $rating, $image, $is_featured);

        if ($stmt->execute()) {
            $message = alert_message("Tourism place added successfully!", "success");
        } else {
            $message = alert_message("Error adding tourism place.", "danger");
        }
        $stmt->close();
    }

    if (isset($_POST['edit_place'])) {
        $id = (int)$_POST['id'];
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        $location = clean_input($_POST['location']);
        $category = clean_input($_POST['category']);
        // Rating is dynamic, do not update manually

        // Get existing image from database first
        $existing_query = "SELECT image FROM tourism_places WHERE id = ?";
        $existing_stmt = $db->conn->prepare($existing_query);
        $existing_stmt->bind_param("i", $id);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result();
        $existing_place = $existing_result->fetch_assoc();
        $image = $existing_place ? $existing_place['image'] : '';
        $existing_stmt->close();

        // Ambil nilai checkbox untuk rekomendasi editor
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Handle file upload - update image if new file is uploaded
        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === 0) {
            $upload_dir = '../assets/images/';
            $file_name = time() . '_' . basename($_FILES['image_upload']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $target_file)) {
                $image = $file_name;
            }
        }

        // Query UPDATE tanpa kolom rating (rating otomatis dari ulasan)
        $query = "UPDATE tourism_places SET name = ?, description = ?, location = ?, category = ?, image = ?, is_featured = ? WHERE id = ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("sssssii", $name, $description, $location, $category, $image, $is_featured, $id);

        if ($stmt->execute()) {
            $message = alert_message("Tourism place updated successfully!", "success");
        } else {
            $message = alert_message("Error updating tourism place.", "danger");
        }
        $stmt->close();
        $action = '';
    }

    if (isset($_POST['delete_place'])) {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM tourism_places WHERE id = ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = alert_message("Tourism place deleted successfully!", "success");
        } else {
            $message = alert_message("Error deleting tourism place.", "danger");
        }
        $stmt->close();
    }
}

// Handle edit action
$edit_place = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM tourism_places WHERE id = ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_place = $result->fetch_assoc();
    $stmt->close();
}

// Get all tourism places
$places_query = "SELECT * FROM tourism_places ORDER BY created_at DESC";
$places_result = $db->query($places_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tourism Places - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>Admin Panel</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="../index.php">🏠 Home</a></li>
                        <li><a href="manage_places.php" class="active">🏖️ Tempat Wisata</a></li>
                        <li><a href="messages.php">💬 Pesan</a></li>
                        <li><a href="logout.php" style="color: #dc3545;">🚪 Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="admin-wrapper">
        <main>
            <div class="container">
                <!-- Statistics Dashboard -->
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <span class="admin-stat-number"><?php echo $places_result ? $places_result->num_rows : 0; ?></span>
                        <span class="admin-stat-label">Total Tempat Wisata</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-number">
                            <?php
                            if ($places_result) {
                                $featured_count = 0;
                                $places_result->data_seek(0); // Reset result pointer
                                while ($place = $places_result->fetch_assoc()) {
                                    if ($place['is_featured'] == 1) $featured_count++;
                                }
                                echo $featured_count;
                                $places_result->data_seek(0); // Reset again for table
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                        <span class="admin-stat-label">Tempat Unggulan</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-number">
                            <?php
                            if ($places_result) {
                                $total_views = 0;
                                $places_result->data_seek(0); // Reset result pointer
                                while ($place = $places_result->fetch_assoc()) {
                                    $total_views += (int)$place['views'];
                                }
                                echo number_format($total_views, 0, ',', '.');
                                $places_result->data_seek(0); // Reset again for table
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                        <span class="admin-stat-label">Total Views</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-number">
                            <?php
                            if ($places_result) {
                                $categories = [];
                                $places_result->data_seek(0); // Reset result pointer
                                while ($place = $places_result->fetch_assoc()) {
                                    if (!in_array($place['category'], $categories)) {
                                        $categories[] = $place['category'];
                                    }
                                }
                                echo count($categories);
                                $places_result->data_seek(0); // Reset again for table
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                        <span class="admin-stat-label">Kategori</span>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="admin-header-card">
                    <div class="admin-header-content">
                        <div class="admin-header-icon">🏖️</div>
                        <div class="admin-header-text">
                            <h2>Kelola Tempat Wisata</h2>
                            <p>Tambah, edit, dan kelola database tempat wisata regional</p>
                        </div>
                        <div class="admin-header-decoration">
                            <div class="wave-animation"></div>
                        </div>
                    </div>
                </div>

                <?php
                if ($message) {
                    echo $message;
                }
                ?>

                <!-- Add/Edit Form -->
                <div class="admin-card">
                    <h3><?php echo ($action === 'edit') ? '✏️ Edit Tempat Wisata' : 'Tambah Tempat Wisata Baru'; ?></h3>
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="<?php echo ($action === 'edit') ? 'edit_place' : 'add_place'; ?>" value="1">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_place['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name">Nama Tempat *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_place['name']) : ''; ?>"
                                   placeholder="Masukkan nama tempat wisata">
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi *</label>
                            <textarea id="description" name="description" required
                                    placeholder="Deskripsikan keindahan dan daya tarik tempat ini..."><?php echo ($action === 'edit') ? htmlspecialchars($edit_place['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="location">Lokasi *</label>
                            <input type="text" id="location" name="location" required
                                   value="<?php echo ($action === 'edit') ? htmlspecialchars($edit_place['location']) : ''; ?>"
                                   placeholder="Contoh: Kabupaten Badung, Bali">
                        </div>

                        <div class="form-group">
                            <label for="category">Kategori *</label>
                            <select id="category" name="category" required>
                                <option value="">Pilih Kategori</option>
                                <option value="beach" <?php echo ($action === 'edit' && $edit_place['category'] === 'beach') ? 'selected' : ''; ?>>Pantai</option>
                                <option value="mountain" <?php echo ($action === 'edit' && $edit_place['category'] === 'mountain') ? 'selected' : ''; ?>>Gunung</option>
                                <option value="temple" <?php echo ($action === 'edit' && $edit_place['category'] === 'temple') ? 'selected' : ''; ?>>Pura/Candi</option>
                                <option value="museum" <?php echo ($action === 'edit' && $edit_place['category'] === 'museum') ? 'selected' : ''; ?>>Museum</option>
                                <option value="park" <?php echo ($action === 'edit' && $edit_place['category'] === 'park') ? 'selected' : ''; ?>>Taman</option>
                                <option value="waterfall" <?php echo ($action === 'edit' && $edit_place['category'] === 'waterfall') ? 'selected' : ''; ?>>Air Terjun</option>
                                <option value="other" <?php echo ($action === 'edit' && $edit_place['category'] === 'other') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>

                        <!-- Rating input removed - handled dynamically via user reviews -->

                        <div class="form-group">
                            <label for="image_upload">Upload Gambar</label>
                            <input type="file" id="image_upload" name="image_upload" accept="image/*">
                            <small style="color: #666;">Gambar saat ini: <?php echo ($action === 'edit') ? htmlspecialchars($edit_place['image']) : 'Belum ada'; ?></small>
                        </div>

                        <div class="form-group">
                            <label for="is_featured" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="is_featured" name="is_featured"
                                       <?php echo ($action === 'edit' && $edit_place['is_featured'] == 1) ? 'checked' : ''; ?>
                                       value="1"
                                       style="width: auto;">
                                Jadikan Rekomendasi Editor (Featured Place)
                            </label>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <?php echo ($action === 'edit') ? 'Update Tempat' : 'Tambah Tempat'; ?>
                            </button>
                            <?php if ($action === 'edit'): ?>
                                <a href="manage_places.php" class="admin-btn admin-btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Places Table -->
                <div class="admin-card">
                    <h3>Daftar Tempat Wisata</h3>
                    <div class="table-container" style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Tempat</th>
                                    <th>Lokasi</th>
                                    <th>Kategori</th>
                                    <th>Rating</th>
                                    <th>Unggulan</th>
                                    <th>Views</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($places_result && $places_result->num_rows > 0): ?>
                                    <?php while ($place = $places_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $place['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($place['name']); ?></td>
                                            <td><?php echo htmlspecialchars($place['location']); ?></td>
                                            <td>
                                                <?php
                                                $category_icons = [
                                                    'beach' => '🏖️',
                                                    'mountain' => '⛰️',
                                                    'temple' => '⛩️',
                                                    'museum' => '🏛️',
                                                    'park' => '🌳',
                                                    'waterfall' => '💦',
                                                    'other' => '📍'
                                                ];
                                                $icon = isset($category_icons[$place['category']]) ? $category_icons[$place['category']] : '📍';
                                                echo $icon . ' ' . htmlspecialchars(ucfirst($place['category']));
                                                ?>
                                            </td>
                                            <td><span style="color: #ffa500;">⭐</span> <?php echo number_format($place['rating'], 1); ?></td>
                                            <td>
                                                <?php if ($place['is_featured'] == 1): ?>
                                                    <span class="admin-badge featured">Unggulan</span>
                                                <?php else: ?>
                                                    <span class="admin-badge normal">Biasa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $view_count = isset($place['views']) ? (int)$place['views'] : 0;
                                                // Tampilkan dengan styling berbeda berdasarkan popularitas
                                                if ($view_count >= 200) {
                                                    $badge_class = 'admin-badge views-high';
                                                    $icon = '🔥';
                                                } elseif ($view_count >= 100) {
                                                    $badge_class = 'admin-badge views-medium';
                                                    $icon = '⭐';
                                                } else {
                                                    $badge_class = 'admin-badge views-low';
                                                    $icon = '👁️';
                                                }
                                                ?>
                                                <span class="<?php echo $badge_class; ?>">
                                                    <?php echo $icon . ' ' . number_format($view_count, 0, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($place['created_at']); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <a href="manage_places.php?action=edit&id=<?php echo $place['id']; ?>"
                                                       class="admin-btn admin-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                                        Edit
                                                    </a>
                                                    <form method="POST" style="display: inline; margin: 0;">
                                                        <input type="hidden" name="delete_place" value="1">
                                                        <input type="hidden" name="id" value="<?php echo $place['id']; ?>">
                                                        <button type="submit" class="admin-btn admin-btn-danger"
                                                                style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                                                onclick="return confirm('Apakah Anda yakin ingin menghapus tempat wisata ini?')">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 2rem;">
                                            <div style="color: #666; font-size: 1.1rem;">
                                                📭 Belum ada tempat wisata. Tambah tempat wisata pertama di atas!
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Regional Tourism Information System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

<?php $db->close(); ?>