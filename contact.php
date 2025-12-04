<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inisialisasi database connection
$db = new Database();

// Inisialisasi variabel untuk form dan pesan
$form_data = [
    'sender_name' => '',
    'email' => '',
    'message' => ''
];

$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_message'])) {

    // Validasi input form
    $sender_name = trim($_POST['sender_name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Simpan data ke variabel form untuk re-fill jika error
    $form_data = [
        'sender_name' => $sender_name,
        'email' => $email,
        'message' => $message
    ];

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
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email maksimal 100 karakter';
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
            // Reset form setelah berhasil
            $form_data = [
                'sender_name' => '',
                'email' => '',
                'message' => ''
            ];

            $success_message = alert_message(
                "Terima kasih! Pesan Anda telah berhasil dikirim. Kami akan segera merespon pesan Anda.",
                "success"
            );
        } else {
            $success_message = alert_message(
                "Maaf, terjadi kesalahan saat mengirim pesan. Silakan coba lagi.",
                "danger"
            );
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Hubungi Kami</title>
    <meta name="description" content="Kirim pesan dan feedback melalui buku tamu website pariwisata kami">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Additional CSS untuk halaman contact -->
    <style>
        .contact-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .contact-header {
            text-align: center;
            margin: 0 auto 3rem auto;
            color: white;
            /* Glassmorphism Styles */
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 3rem 2rem;
            max-width: 800px;
        }

        .contact-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .contact-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .contact-form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }

        .contact-form-container h2 {
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.8rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0077B6;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .form-group.error input,
        .form-group.error textarea {
            border-color: #dc3545;
        }

        .char-counter {
            text-align: right;
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .submit-btn {
            background: linear-gradient(135deg, #0077B6, #0096C7);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .contact-info {
            background: linear-gradient(135deg, #0077B6, #0096C7);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
        }

        .contact-info h3 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .contact-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .contact-method {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .contact-method h4 {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .contact-method p {
            margin: 0;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .contact-container {
                padding: 1rem;
            }

            .contact-header h1 {
                font-size: 2rem;
            }

            .contact-form-container {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .contact-methods {
                grid-template-columns: 1fr;
            }
        }

        /* Alert styling override untuk halaman contact */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Form validation animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group.error {
            animation: shake 0.3s ease-in-out;
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="contact.php" class="active">Contact</a></li>
                        <li><a href="admin/login.php" class="btn-nav-login">Login</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="contact-container">
            <!-- Header Section -->
            <div class="contact-header">
                <h1>Ulasan</h1>
                <p>Kami senang mendengarkan Anda! Kirim pesan, saran, atau pertanyaan melalui form di bawah ini.</p>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-container">
                <h2>Kirim Pesan</h2>

                <!-- Display Success/Error Messages -->
                <?php if (!empty($success_message)): ?>
                    <?php echo $success_message; ?>
                <?php endif; ?>

                <form method="POST" action="contact.php" id="contactForm" novalidate>
                    <div class="form-row">
                        <div class="form-group <?php echo isset($errors['sender_name']) ? 'error' : ''; ?>">
                            <label for="sender_name">Nama Lengkap *</label>
                            <input type="text"
                                   id="sender_name"
                                   name="sender_name"
                                   value="<?php echo htmlspecialchars($form_data['sender_name']); ?>"
                                   maxlength="100"
                                   required>
                            <?php if (isset($errors['sender_name'])): ?>
                                <div class="error-message"><?php echo $errors['sender_name']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                            <label for="email">Email *</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                   maxlength="100"
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-message"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group <?php echo isset($errors['message']) ? 'error' : ''; ?>">
                        <label for="message">Pesan *</label>
                        <textarea id="message"
                                  name="message"
                                  maxlength="1000"
                                  required
                                  oninput="updateCharCount()"><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount"><?php echo strlen($form_data['message']); ?></span> / 1000 karakter
                        </div>
                        <?php if (isset($errors['message'])): ?>
                            <div class="error-message"><?php echo $errors['message']; ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="submit_message" class="submit-btn">
                        Kirim Pesan
                    </button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="contact-info">
                <h3>Informasi Kontak</h3>
                <p>Anda juga bisa menghubungi kami melalui:</p>

                <div class="contact-methods">
                    <div class="contact-method">
                        <h4>Email</h4>
                        <p>adminPesona@gmail.com</p>
                    </div>

                    <div class="contact-method">
                        <h4>Telepon</h4>
                        <p>+62895-1064-8396</p>
                    </div>

                    <div class="contact-method">
                        <h4>Alamat</h4>
                        <p>Jl. Panjaitan No. 123<br>Kota Gorontalo, Indonesia</p>
                    </div>

                    <div class="contact-method">
                        <h4>Jam Operasional</h4>
                        <p>Senin - Jumat: 09:00 - 17:00<br>Sabtu - Minggu: 10:00 - 15:00</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Regional Tourism Information System. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript untuk Parallax Effect dan Form Validation -->
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

            // Form validation enhancements
            const form = document.getElementById('contactForm');
            const inputs = form.querySelectorAll('input, textarea');

            inputs.forEach(input => {
                // Remove error class on input
                input.addEventListener('input', function() {
                    this.closest('.form-group').classList.remove('error');
                });

                // Add blur validation
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });

            function validateField(field) {
                const formGroup = field.closest('.form-group');
                let isValid = true;

                // Basic validation rules
                if (field.hasAttribute('required') && !field.value.trim()) {
                    isValid = false;
                }

                // Email validation
                if (field.type === 'email' && field.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.value)) {
                        isValid = false;
                    }
                }

                // Length validation
                if (field.value.length > field.maxLength) {
                    isValid = false;
                }

                // Add/remove error class
                if (!isValid) {
                    formGroup.classList.add('error');
                } else {
                    formGroup.classList.remove('error');
                }

                return isValid;
            }
        });

        // Character counter function
        function updateCharCount() {
            const message = document.getElementById('message');
            const charCount = document.getElementById('charCount');

            if (message && charCount) {
                charCount.textContent = message.value.length;
            }
        }

        // Initial character count
        updateCharCount();
    </script>
</body>
</html>

<?php $db->close(); ?>