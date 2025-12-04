<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Inisialisasi database connection
$db = new Database();

// Handle delete action
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $message_id = (int)$_POST['message_id'];

    // Prepared statement untuk mencegah SQL injection
    $delete_query = "DELETE FROM messages WHERE id = ?";
    $stmt = $db->conn->prepare($delete_query);
    $stmt->bind_param("i", $message_id);

    if ($stmt->execute()) {
        $message = alert_message("Pesan berhasil dihapus!", "success");
    } else {
        $message = alert_message("Gagal menghapus pesan. Silakan coba lagi.", "danger");
    }

    $stmt->close();
}

// Handle bulk delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && isset($_POST['message_ids'])) {
    $message_ids = array_map('intval', $_POST['message_ids']);

    if (!empty($message_ids)) {
        // Create placeholders untuk prepared statement
        $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
        $types = str_repeat('i', count($message_ids));

        $bulk_delete_query = "DELETE FROM messages WHERE id IN ($placeholders)";
        $stmt = $db->conn->prepare($bulk_delete_query);
        $stmt->bind_param($types, ...$message_ids);

        if ($stmt->execute()) {
            $deleted_count = $stmt->affected_rows;
            $message = alert_message("$deleted_count pesan berhasil dihapus!", "success");
        } else {
            $message = alert_message("Gagal menghapus pesan. Silakan coba lagi.", "danger");
        }

        $stmt->close();
    } else {
        $message = alert_message("Tidak ada pesan yang dipilih untuk dihapus.", "warning");
    }
}

// Handle mark as read/unread (untuk fitur masa depan)
// $is_read = isset($_POST['mark_read']) ? 1 : 0;

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get total messages count
$total_query = "SELECT COUNT(*) as total FROM messages";
$total_result = $db->query($total_query);
$total_items = $total_result->fetch_assoc()['total'];

// Get messages with pagination
// Diurutkan dari yang terbaru
$messages_query = "SELECT * FROM messages ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset";
$messages_result = $db->query($messages_query);

// Get statistics
$stats_query = "SELECT
                   COUNT(*) as total_messages,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as today_messages,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_messages,
                   COUNT(DISTINCT email) as unique_senders
                 FROM messages";
$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesan - Admin</title>
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
                        <li><a href="manage_places.php">🏖️ Tempat Wisata</a></li>
                        <li><a href="messages.php" class="active">💬 Pesan</a></li>
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
                        <span class="admin-stat-number"><?php echo number_format($stats['total_messages'], 0, ',', '.'); ?></span>
                        <span class="admin-stat-label">Total Pesan</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-number"><?php echo number_format($stats['today_messages'], 0, ',', '.'); ?></span>
                        <span class="admin-stat-label">Hari Ini</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-number"><?php echo number_format($stats['week_messages'], 0, ',', '.'); ?></span>
                        <span class="admin-stat-label">7 Hari Terakhir</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-number"><?php echo number_format($stats['unique_senders'], 0, ',', '.'); ?></span>
                        <span class="admin-stat-label">Pengirim Unik</span>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="admin-header-card">
                    <div class="admin-header-content">
                        <div class="admin-header-icon">💬</div>
                        <div class="admin-header-text">
                            <h2>Kelola Pesan Buku Tamu</h2>
                            <p>Lihat, kelola, dan respons pesan dari pengunjung website</p>
                        </div>
                        <div class="admin-header-decoration">
                            <div class="wave-animation"></div>
                        </div>
                    </div>
                </div>

                <!-- Display Messages -->
                <?php
                if ($message) {
                    echo $message;
                }
                ?>

                <!-- Messages Table -->
                <div class="admin-card">
                    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <h3 style="margin: 0; color: #0077B6; font-size: 1.5rem;">📨 Daftar Pesan Masuk</h3>

                        <div class="bulk-actions" style="display: flex; gap: 0.5rem; align-items: center;">
                            <form method="POST" id="bulkDeleteForm" style="display: flex; gap: 0.5rem; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; color: #444; font-weight: 500;">
                                    <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()" style="width: auto;">
                                    Pilih Semua
                                </label>
                                <button type="submit" name="bulk_delete" class="admin-btn admin-btn-danger" id="bulkDeleteBtn" disabled>
                                    Hapus Dipilih
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">
                                            <input type="checkbox" id="headerCheckbox" onchange="toggleAllCheckboxes()" style="width: auto;">
                                        </th>
                                        <th>👤 Pengirim</th>
                                        <th>💬 Pesan</th>
                                        <th style="width: 150px;">📅 Tanggal</th>
                                        <th style="width: 100px;">⚙️ Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $message_count = 0;
                                    while ($message = $messages_result->fetch_assoc()):
                                        $message_count++;

                                        // Check if message is from today (for highlighting)
                                        $is_today = (strtotime($message['created_at']) >= strtotime('today'));
                                    ?>
                                        <tr class="message-row <?php echo $is_today ? 'new-message' : ''; ?>"
                                            <?php if ($is_today): ?>style="background-color: rgba(0, 119, 182, 0.05); border-left: 4px solid #0077B6;"<?php endif; ?>>
                                            <td style="text-align: center;">
                                                <input type="checkbox"
                                                       name="message_ids[]"
                                                       value="<?php echo $message['id']; ?>"
                                                       class="message-checkbox"
                                                       onchange="updateBulkDeleteButton()"
                                                       style="width: auto;">
                                            </td>
                                            <td>
                                                <div class="message-sender" style="font-weight: 700; color: #333; margin-bottom: 0.5rem; font-size: 1.1rem;">
                                                    <?php echo htmlspecialchars($message['sender_name']); ?>
                                                </div>
                                                <div class="message-email" style="color: #0077B6; font-size: 0.9rem;">
                                                    📧 <?php echo htmlspecialchars($message['email']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="message-content" style="line-height: 1.6; color: #444;">
                                                    <div class="message-preview" id="preview-<?php echo $message['id']; ?>">
                                                        <?php
                                                        $preview = htmlspecialchars(substr($message['message'], 0, 150));
                                                        echo $preview . (strlen($message['message']) > 150 ? '...' : '');
                                                        ?>
                                                    </div>
                                                    <div class="message-full" id="full-<?php echo $message['id']; ?>" style="display: none;">
                                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                    </div>
                                                    <?php if (strlen($message['message']) > 150): ?>
                                                        <button type="button"
                                                                class="admin-btn admin-btn-primary"
                                                                onclick="toggleMessage(<?php echo $message['id']; ?>)"
                                                                id="btn-<?php echo $message['id']; ?>"
                                                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem; margin-top: 0.5rem;">
                                                            📖 Baca Selengkapnya
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="message-date" style="color: #666; font-size: 0.9rem;">
                                                    <div style="margin-bottom: 0.3rem;">
                                                        📅 <?php echo format_date($message['created_at']); ?>
                                                    </div>
                                                    <small style="color: #888;">🕒 <?php echo date('H:i', strtotime($message['created_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="message-actions">
                                                    <form method="POST" style="display: inline; margin: 0;"
                                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan ini?')">
                                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                        <button type="submit" name="delete_message" class="admin-btn admin-btn-danger"
                                                                style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                                            🗑️ Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div style="margin-top: 2rem;">
                            <?php
                            if ($total_items > 0) {
                                echo paginate($total_items, $items_per_page, $page, 'admin/messages.php');
                            }
                            ?>
                        </div>

                    <?php else: ?>
                        <div class="admin-card" style="text-align: center; padding: 3rem; background: linear-gradient(135deg, rgba(0,119,182,0.02), rgba(0,150,199,0.05));">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                            <h3 style="color: #0077B6; margin-bottom: 1rem; font-size: 1.5rem;">Belum Ada Pesan</h3>
                            <p style="color: #666; font-size: 1.1rem; margin-bottom: 1.5rem;">
                                Belum ada pesan masuk dari buku tamu. Pesan baru akan muncul di sini.
                            </p>
                            <div style="color: #888; font-size: 0.9rem;">
                                💡 Pesan dari pengunjung akan ditampilkan di sini untuk dikelola
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Regional Tourism Information System. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript untuk Interaksi -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize bulk delete button state
            updateBulkDeleteButton();
        });

        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const headerCheckbox = document.getElementById('headerCheckbox');
            const checkboxes = document.querySelectorAll('.message-checkbox');

            const isChecked = selectAll.checked || headerCheckbox.checked;

            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });

            // Sync both select all checkboxes
            if (selectAll && headerCheckbox) {
                selectAll.checked = isChecked;
                headerCheckbox.checked = isChecked;
            }

            updateBulkDeleteButton();
            updateRowSelection();
        }

        function updateBulkDeleteButton() {
            const checkboxes = document.querySelectorAll('.message-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

            if (bulkDeleteBtn) {
                bulkDeleteBtn.disabled = checkboxes.length === 0;
            }

            updateRowSelection();
        }

        function updateRowSelection() {
            const checkboxes = document.querySelectorAll('.message-checkbox');
            const rows = document.querySelectorAll('.message-row');

            rows.forEach((row, index) => {
                if (checkboxes[index] && checkboxes[index].checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
        }

        function toggleMessage(messageId) {
            const preview = document.getElementById('preview-' + messageId);
            const full = document.getElementById('full-' + messageId);
            const btn = document.getElementById('btn-' + messageId);

            if (preview.style.display === 'none') {
                preview.style.display = 'block';
                full.style.display = 'none';
                btn.textContent = 'Baca Selengkapnya';
            } else {
                preview.style.display = 'none';
                full.style.display = 'block';
                btn.textContent = 'Sembunyikan';
            }
        }

        // Auto-refresh untuk pesan baru (opsional, setiap 30 detik)
        // Uncomment jika ingin auto-refresh
        /*
        setInterval(function() {
            if (!confirm('Ada pesan baru. Muat ulang halaman?')) {
                return;
            }
            window.location.reload();
        }, 30000);
        */
    </script>
</body>
</html>

<?php $db->close(); ?>