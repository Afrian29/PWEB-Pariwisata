# Fitur Buku Tamu (Contact Form)

## Ringkasan
Fitur ini menambahkan sistem buku tamu (contact form) yang memungkinkan pengunjung untuk mengirim pesan, saran, atau pertanyaan kepada admin website. Pesan akan disimpan di database dan dapat dikelola melalui panel admin.

## Struktur Database

### 1. Tabel Messages (`messages`)
**Struktur Tabel:**
```sql
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL COMMENT 'Nama pengirim pesan',
    email VARCHAR(100) NOT NULL COMMENT 'Email pengirim pesan',
    message TEXT NOT NULL COMMENT 'Isi pesan dari pengunjung',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pesan dikirim',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Penjelasan Kolom:**
- `id`: Primary key untuk identifikasi unik setiap pesan
- `sender_name`: Nama lengkap pengirim (max 100 karakter)
- `email`: Email pengirim untuk follow-up (max 100 karakter)
- `message`: Isi pesan dari pengunjung (unlimited text)
- `created_at`: Timestamp otomatis saat pesan dikirim
- `updated_at`: Timestamp update untuk tracking

**Index untuk Performa:**
```sql
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_email ON messages(email);
```

**Sample Data:**
```sql
INSERT INTO messages (sender_name, email, message) VALUES
('Ahmad Wijaya', 'ahmad@example.com', 'Website pariwisata ini sangat bagus! Informasinya lengkap dan mudah dimengerti.'),
('Sarah Putri', 'sarah@email.com', 'Saya tertarik untuk mengunjungi Crystal Waterfall. Apakah ada informasi lebih lanjut?'),
-- ... sample messages lainnya
```

## Frontend: Halaman Contact (`contact.php`)

### 2. Form Buku Tamu
**HTML Structure:**
```html
<form method="POST" action="contact.php" id="contactForm" novalidate>
    <div class="form-row">
        <div class="form-group">
            <label for="sender_name">Nama Lengkap *</label>
            <input type="text" id="sender_name" name="sender_name" maxlength="100" required>
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" maxlength="100" required>
        </div>
    </div>
    <div class="form-group">
        <label for="message">Pesan *</label>
        <textarea id="message" name="message" maxlength="1000" required></textarea>
        <div class="char-counter">
            <span id="charCount">0</span> / 1000 karakter
        </div>
    </div>
    <button type="submit" name="submit_message" class="submit-btn">📤 Kirim Pesan</button>
</form>
```

### 3. Validasi Server-Side
**PHP Validation Logic:**
```php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_message'])) {

    // Validasi input form
    $sender_name = trim($_POST['sender_name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Validasi Nama
    if (empty($sender_name)) {
        $errors['sender_name'] = 'Nama harus diisi';
    } elseif (strlen($sender_name) < 3) {
        $errors['sender_name'] = 'Nama minimal 3 karakter';
    } elseif (strlen($sender_name) > 100) {
        $errors['sender_name'] = 'Nama maksimal 100 karakter';
    }

    // Validasi Email
    if (empty($email)) {
        $errors['email'] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    }

    // Validasi Pesan
    if (empty($message)) {
        $errors['message'] = 'Pesan harus diisi';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Pesan minimal 10 karakter';
    } elseif (strlen($message) > 1000) {
        $errors['message'] = 'Pesan maksimal 1000 karakter';
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        // Escape input untuk mencegah XSS
        $sender_name = htmlspecialchars($sender_name);
        $email = htmlspecialchars($email);
        $message = htmlspecialchars($message);

        // Prepared statement untuk mencegah SQL injection
        $query = "INSERT INTO messages (sender_name, email, message) VALUES (?, ?, ?)";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("sss", $sender_name, $email, $message);

        if ($stmt->execute()) {
            $success_message = alert_message(
                "Terima kasih! Pesan Anda telah berhasil dikirim.",
                "success"
            );
        }
    }
}
```

**Keamanan yang Diterapkan:**
1. **XSS Prevention**: `htmlspecialchars()` untuk semua output
2. **SQL Injection Prevention**: Prepared statements dengan parameter binding
3. **Input Validation**: Length checks dan format validation
4. **Trim Input**: Menghapus whitespace tidak perlu
5. **CSRF Protection**: Form token (opsional untuk implementasi lanjutan)

### 4. User Experience Features
**Client-Side Validation:**
```javascript
// Character counter
function updateCharCount() {
    const message = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    if (message && charCount) {
        charCount.textContent = message.value.length;
    }
}

// Real-time validation
inputs.forEach(input => {
    input.addEventListener('blur', function() {
        validateField(this);
    });
});
```

**Form Features:**
- Real-time character counter
- Client-side validation feedback
- Error state styling with animations
- Auto-form fill pada error
- Responsive design untuk mobile

**Styling Responsif:**
```css
/* Mobile-first approach */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .contact-form-container {
        padding: 1.5rem;
    }
}
```

## Backend: Admin Messages (`admin/messages.php`)

### 5. Dashboard Statistik
**Statistics Cards:**
```php
// Get statistics
$stats_query = "SELECT
                   COUNT(*) as total_messages,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as today_messages,
                   COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_messages,
                   COUNT(DISTINCT email) as unique_senders
                 FROM messages";
$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch_assoc();
```

**Display Statistics:**
```html
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-number"><?php echo number_format($stats['total_messages'], 0, ',', '.'); ?></span>
        <span class="stat-label">Total Pesan</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo number_format($stats['today_messages'], 0, ',', '.'); ?></span>
        <span class="stat-label">Hari Ini</span>
    </div>
    <!-- ... other stats -->
</div>
```

### 6. Daftar Pesan dengan Pagination
**Pagination Logic:**
```php
// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get total messages count
$total_query = "SELECT COUNT(*) as total FROM messages";
$total_result = $db->query($total_query);
$total_items = $total_result->fetch_assoc()['total'];

// Get messages with pagination
$messages_query = "SELECT * FROM messages ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset";
$messages_result = $db->query($messages_query);
```

**Message Table:**
```html
<table>
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()"></th>
            <th>Pengirim</th>
            <th>Pesan</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($message = $messages_result->fetch_assoc()): ?>
            <tr class="message-row <?php echo $is_today ? 'new-message' : ''; ?>">
                <td>
                    <input type="checkbox" name="message_ids[]" value="<?php echo $message['id']; ?>">
                </td>
                <td>
                    <div class="message-sender"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                    <div class="message-email"><?php echo htmlspecialchars($message['email']); ?></div>
                </td>
                <td>
                    <div class="message-content">
                        <div class="message-preview">
                            <?php echo htmlspecialchars(substr($message['message'], 0, 150)) . '...'; ?>
                        </div>
                        <div class="message-full" style="display: none;">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        <?php if (strlen($message['message']) > 150): ?>
                            <button type="button" onclick="toggleMessage(<?php echo $message['id']; ?>)">
                                Baca Selengkapnya
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="message-date">
                        <?php echo format_date($message['created_at']); ?>
                        <br>
                        <small><?php echo date('H:i', strtotime($message['created_at'])); ?></small>
                    </div>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Hapus pesan ini?')">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <button type="submit" name="delete_message">🗑️ Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
```

### 7. Bulk Operations
**Bulk Delete Functionality:**
```php
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
        }
    }
}
```

**JavaScript for Bulk Operations:**
```javascript
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.message-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });

    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.message-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    if (bulkDeleteBtn) {
        bulkDeleteBtn.disabled = checkboxes.length === 0;
    }
}
```

### 8. Visual Feedback
**Message Highlighting:**
```css
/* Highlight new messages from today */
.new-message {
    background-color: #d1ecf1 !important;
    border-left: 4px solid #0c5460 !important;
}

/* Selected rows for bulk operations */
.message-row.selected {
    background-color: #e3f2fd !important;
}

/* Hover effects */
tr:hover {
    background-color: #f8f9fa;
}
```

## Update Navigasi

### 9. Navigation Updates
**Main Navigation (index.php):**
```html
<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="contact.php">Buku Tamu</a></li>
        <li><a href="admin/manage_places.php">Admin</a></li>
    </ul>
</nav>
```

**Detail Page Navigation (detail.php):**
```html
<nav>
    <ul>
        <li><a href="index.php">← Kembali</a></li>
        <li><a href="contact.php">Buku Tamu</a></li>
        <li><a href="admin/manage_places.php">Admin</a></li>
    </ul>
</nav>
```

**Admin Navigation (admin/manage_places.php):**
```html
<nav>
    <ul>
        <li><a href="../index.php">Home</a></li>
        <li><a href="../contact.php">Buku Tamu</a></li>
        <li><a href="manage_places.php">Tempat Wisata</a></li>
        <li><a href="messages.php" class="active">Pesan</a></li>
    </ul>
</nav>
```

## Cara Kerja Fitur

### User Journey:
1. **Access Form**: User klik "Buku Tamu" di navigation
2. **Fill Form**: User isi nama, email, dan pesan
3. **Validation**: Real-time validation feedback
4. **Submit**: Form disubmit ke server
5. **Server Processing**: Validasi dan simpan ke database
6. **Success Feedback**: Tampilkan pesan sukses
7. **Admin Notification**: Admin bisa lihat pesan baru

### Admin Workflow:
1. **View Dashboard**: Lihat statistik pesan masuk
2. **Browse Messages**: Lihat daftar pesan dengan pagination
3. **Read Messages**: Expand/collapse untuk baca pesan lengkap
4. **Delete Individual**: Hapus satu pesan dengan konfirmasi
5. **Bulk Delete**: Pilih multiple pesan untuk dihapus
6. **Analytics**: Monitor trends dan pengunjung

### Technical Flow:
1. **Form Submission**: POST request ke `contact.php`
2. **Input Sanitization**: Trim dan escape input
3. **Validation**: Server-side validation rules
4. **Database Insert**: Prepared statement dengan parameter binding
5. **Success Response**: Alert message dan form reset
6. **Admin Display**: Query dengan ORDER BY dan LIMIT
7. **Pagination**: Multi-page display dengan navigation

## Keamanan dan Best Practices

### 10. Security Measures
**Input Validation:**
- Length limits untuk semua field
- Email format validation dengan filter_var
- Required field validation
- SQL injection prevention dengan prepared statements

**Output Escaping:**
- HTML entities escaping dengan htmlspecialchars
- Context-aware escaping untuk berbagai output types
- XSS prevention pada display

**CSRF Protection (Future Enhancement):**
```php
// Generate CSRF token
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate CSRF token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token mismatch');
}
```

**Rate Limiting (Future Enhancement):**
```php
// Implement rate limiting
$rate_limit_key = 'contact_form_' . $_SERVER['REMOTE_ADDR'];
$recent_attempts = $redis->get($rate_limit_key);

if ($recent_attempts > 5) {
    die('Too many requests. Please try again later.');
}
```

### 11. Database Security
**Privilege Separation:**
- Gunakan user database dengan limited privileges
- Hanya berikan SELECT, INSERT, DELETE pada tabel messages
- Tidak berikan ALTER atau DROP permissions

**Data Integrity:**
- Foreign key constraints (jika ada relasi ke tabel users)
- Proper data types untuk optimasi storage
- Index untuk query performance

**Backup Strategy:**
```sql
-- Backup messages table
CREATE TABLE messages_backup_YYYY_MM_DD AS SELECT * FROM messages;
```

## Performance Optimizations

### 12. Database Optimization
**Index Strategy:**
```sql
-- Index untuk sorting dan filtering
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_email ON messages(email);
CREATE INDEX idx_messages_sender_name ON messages(sender_name);
```

**Query Optimization:**
```php
// Efficient pagination with prepared statements
$messages_query = "SELECT * FROM messages ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->conn->prepare($messages_query);
$stmt->bind_param("ii", $items_per_page, $offset);
```

**Caching Strategy (Future):**
```php
// Cache statistics for dashboard
$stats_key = 'message_stats_' . date('Y-m-d');
$cached_stats = $redis->get($stats_key);

if (!$cached_stats) {
    $stats = get_message_stats();
    $redis->setex($stats_key, 300, json_encode($stats));
}
```

### 13. Frontend Performance
**Lazy Loading:**
```javascript
// Lazy load long messages
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message-content');
    messages.forEach(message => {
        // Load full content hanya saat dibutuhkan
    });
});
```

**CSS Optimization:**
```css
/* Efficient selectors */
.message-row { /* ... */ }
.message-row.new-message { /* ... */ }
.message-row.selected { /* ... */ }

/* Minimal reflows */
.message-content {
    contain: layout;
}
```

## User Experience Enhancements

### 14. Form UX Improvements
**Progressive Enhancement:**
```javascript
// Enhance form with JavaScript if available
if ('addEventListener' in window) {
    // Add real-time validation
    // Auto-save draft
    // Character counter
}
```

**Accessibility:**
```html
<!-- Proper form labels -->
<label for="sender_name">Nama Lengkap *</label>
<input type="text" id="sender_name" name="sender_name" aria-required="true" aria-describedby="name-error">

<!-- Error messages -->
<div id="name-error" class="error-message" role="alert">
    <?php echo $errors['sender_name']; ?>
</div>
```

**Mobile Optimization:**
```css
/* Touch-friendly inputs */
input, textarea {
    font-size: 16px; /* Prevent zoom on iOS */
    padding: 12px;
    border-radius: 8px;
}

/* Better mobile layout */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
```

### 15. Admin UX Features
**Visual Feedback:**
```css
/* Smooth animations */
.message-row {
    transition: all 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message-row {
    animation: fadeIn 0.3s ease-out;
}
```

**Keyboard Navigation:**
```javascript
// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'a') {
        selectAllMessages();
        e.preventDefault();
    }
    if (e.key === 'Delete' && selectedMessages.length > 0) {
        bulkDeleteMessages();
        e.preventDefault();
    }
});
```

## Testing Strategy

### 16. Test Cases
**Form Testing:**
1. **Valid Submission**: Semua field terisi dengan benar
2. **Missing Required Fields**: Kirim form kosong
3. **Invalid Email**: Format email salah
4. **Too Long Input**: Input melebihi batas karakter
5. **XSS Attempt**: Input dengan malicious script

**Admin Panel Testing:**
1. **Pagination**: Navigate between pages
2. **Bulk Operations**: Select multiple messages
3. **Delete Confirmation**: Verify delete action
4. **Empty State**: Handle no messages scenario
5. **Message Expand**: Read full message functionality

**Security Testing:**
1. **SQL Injection**: Attempt SQL injection in inputs
2. **CSRF Protection**: Test cross-site request forgery
3. **XSS Prevention**: Attempt script injection
4. **Rate Limiting**: Test multiple submissions

### 17. Monitoring and Analytics
**Message Analytics:**
```php
// Track message patterns
$analytics_query = "SELECT
                     DATE(created_at) as date,
                     COUNT(*) as message_count,
                     COUNT(DISTINCT email) as unique_senders
                 FROM messages
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date DESC";
```

**Performance Monitoring:**
```php
// Log form submission times
$start_time = microtime(true);
// ... form processing
$processing_time = microtime(true) - $start_time;

if ($processing_time > 2.0) {
    error_log("Slow contact form processing: {$processing_time}s");
}
```

## Future Enhancements

### 18. Advanced Features
**Email Notifications:**
```php
// Send email to admin when new message received
function notify_admin($message) {
    $to = 'admin@tourism.com';
    $subject = 'Pesan Baru dari Buku Tamu';
    $body = "Nama: {$message['sender_name']}\n";
    $body .= "Email: {$message['email']}\n";
    $body .= "Pesan: {$message['message']}";

    mail($to, $subject, $body);
}
```

**Auto-Reply:**
```php
// Send automatic confirmation email
function send_auto_reply($email, $name) {
    $subject = 'Terima Kasih atas Pesan Anda';
    $body = "Dear {$name},\n\n";
    $body .= "Terima kasih telah menghubungi kami. ";
    $body .= "Pesan Anda telah kami terima dan akan kami balas secepatnya.\n\n";
    $body .= "Regards,\nRegional Tourism Team";

    mail($email, $subject, $body);
}
```

**Message Categorization:**
```sql
-- Add category field
ALTER TABLE messages ADD COLUMN category ENUM('inquiry', 'suggestion', 'complaint', 'praise') DEFAULT 'inquiry';
```

**File Attachments:**
```php
// Handle file uploads
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $upload_dir = 'attachments/';
    $file_name = time() . '_' . basename($_FILES['attachment']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
        // Save file path to database
        $attachment_path = $file_name;
    }
}
```

### 19. Integration Opportunities
**CRM Integration:**
```php
// Sync with external CRM
function sync_to_crm($message) {
    $crm_api_url = 'https://api.crm.com/leads';
    $data = [
        'name' => $message['sender_name'],
        'email' => $message['email'],
        'message' => $message['message'],
        'source' => 'Website Contact Form'
    ];

    // Send to CRM
    $response = file_get_contents($crm_api_url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ]));
}
```

**Analytics Integration:**
```javascript
// Track form events with Google Analytics
document.getElementById('contactForm').addEventListener('submit', function() {
    gtag('event', 'form_submit', {
        'event_category': 'Contact',
        'event_label': 'Buku Tamu'
    });
});
```

**Newsletter Subscription:**
```php
// Add newsletter checkbox
if (isset($_POST['newsletter']) && $_POST['newsletter'] === '1') {
    add_to_newsletter($email, $sender_name);
}
```

## Deployment Considerations

### 20. Production Checklist
**Security:**
- [ ] Implement CSRF tokens
- [ ] Add rate limiting
- [ ] Configure proper file permissions
- [ ] Set up SSL/TLS
- [ ] Configure security headers

**Performance:**
- [ ] Set up database indexing
- [ ] Configure caching
- [ ] Optimize images and assets
- [ ] Implement CDN if needed
- [ ] Set up monitoring

**Backup:**
- [ ] Configure automated database backups
- [ ] Test backup restoration
- [ ] Set up file backup for attachments
- [ ] Document recovery procedures

**Monitoring:**
- [ ] Set up error logging
- [ ] Configure performance monitoring
- [ ] Implement uptime monitoring
- [ ] Set up email alerts for issues

## Migration Instructions

### 21. Database Migration
**Update Schema:**
```sql
-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL COMMENT 'Nama pengirim pesan',
    email VARCHAR(100) NOT NULL COMMENT 'Email pengirim pesan',
    message TEXT NOT NULL COMMENT 'Isi pesan dari pengunjung',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pesan dikirim',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_email ON messages(email);

-- Insert sample data (optional)
INSERT INTO messages (sender_name, email, message) VALUES
('Sample User', 'sample@example.com', 'This is a sample message.');
```

**File Migration:**
1. Copy `contact.php` to web root
2. Copy `admin/messages.php` to admin directory
3. Update navigation in existing files
4. Test all functionality
5. Update documentation

## Conclusion

Fitur Buku Tamu (Contact Form) menyediakan:
- **User-Friendly Interface**: Form sederhana dengan validasi real-time
- **Admin Dashboard**: Manajemen pesan yang lengkap dengan statistik
- **Security**: Multi-layer security untuk proteksi data
- **Responsive Design**: Works di desktop, tablet, dan mobile
- **Performance**: Optimized database queries dan frontend
- **Extensibility**: Mudah ditambah fitur lanjutan

Fitur ini memungkinkan website pariwisata untuk:
- Menerima feedback dari pengunjung
- Menjawab pertanyaan secara profesional
- Memantau popularitas dan engagement
- Membangun komunitas yang terlibat
- Mengumpulkan data untuk improvement website