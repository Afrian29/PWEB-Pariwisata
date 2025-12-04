<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();

// Handle search and filtering
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 6;
$offset = ($page - 1) * $items_per_page;

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Debug output - now removed since fix is complete

// Count total records
if (!empty($where_conditions)) {
    // Use prepared statement when we have conditions
    $count_query = "SELECT COUNT(*) as total FROM tourism_places $where_clause";
    $count_stmt = $db->conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_items = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    // Use regular query when no conditions
    $count_query = "SELECT COUNT(*) as total FROM tourism_places";
    $count_result = $db->query($count_query);
    $total_items = $count_result->fetch_assoc()['total'];
}

// Get tourism places with pagination
if (!empty($where_conditions)) {
    // Use prepared statement when we have conditions
    $query = "SELECT * FROM tourism_places $where_clause ORDER BY is_featured DESC, created_at DESC LIMIT $items_per_page OFFSET $offset";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // Use regular query when no conditions
    $query = "SELECT * FROM tourism_places ORDER BY is_featured DESC, created_at DESC LIMIT $items_per_page OFFSET $offset";
    $result = $db->query($query);
}

// Get categories for filter dropdown
$category_query = "SELECT DISTINCT category FROM tourism_places ORDER BY category";
$category_result = $db->query($category_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesona Gorontalo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FontAwesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="admin/login.php" class="btn-nav-login">Login</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Hero Section -->
            <section class="hero">
                <h2>Let's Go Traveling</h2>
                <p>Led by locals, built for those who want more than a pretty view. Real connection, fresh air, and stories to take home.</p>
                <a href="#placesGrid" class="btn-hero">Start Your Traveling <i class="fas fa-arrow-right"></i></a>
            </section>

            <!-- Search Section -->
            <section class="search-section">
                <form class="search-form" id="searchForm" method="GET" action="index.php">
                    <input type="text" id="searchInput" name="search" placeholder="Search by name, location, or description..." value="<?php echo htmlspecialchars($search); ?>">
                    <select id="categorySelect" name="category">
                        <option value="">All Categories</option>
                        <?php while ($cat_row = $category_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat_row['category']); ?>" <?php echo ($category == $cat_row['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat_row['category'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" id="searchButton">Search</button>
                    <button type="button" id="resetButton" style="background: #6c757d; margin-left: 0.5rem;">Reset</button>
                </form>
            </section>

            <!-- Tourism Places Grid -->
            <section class="places-grid" id="placesGrid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($place = $result->fetch_assoc()): ?>
                        <div class="place-card <?php echo ($place['is_featured'] == 1) ? 'featured-card' : ''; ?>">
                            <!-- Link ke halaman detail pada gambar -->
                            <a href="detail.php?id=<?php echo $place['id']; ?>" class="place-image-link">
                                <img src="assets/images/<?php echo !empty($place['image']) ? htmlspecialchars($place['image']) : 'bg1.jpeg'; ?>"
                                     alt="<?php echo htmlspecialchars($place['name']); ?>">
                            </a>
                            <div class="place-card-content">
                                <!-- Badge Rekomendasi Editor -->
                                <?php if ($place['is_featured'] == 1): ?>
                                    <span class="featured-badge">✨ Rekomendasi Editor</span>
                                <?php endif; ?>
                                <span class="place-category"><?php echo htmlspecialchars(ucfirst($place['category'])); ?></span>

                                <!-- Link ke halaman detail pada judul -->
                                <h3><a href="detail.php?id=<?php echo $place['id']; ?>" class="place-title-link"><?php echo htmlspecialchars($place['name']); ?></a></h3>

                                <p><?php echo htmlspecialchars(substr($place['description'], 0, 150)) . '...'; ?></p>

                                <div class="place-meta">
                                    <span>📍 <?php echo htmlspecialchars($place['location']); ?></span>
                                    <span class="place-rating">⭐ <?php echo number_format($place['rating'], 1); ?></span>
                                </div>

                                <!-- Tampilkan jumlah views - FITUR BARU -->
                                <div class="place-views">
                                    <span>👁️ Dilihat: <?php echo number_format($place['views'], 0, ',', '.'); ?> kali</span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <h3>No tourism places found</h3>
                        <p>Try adjusting your search criteria or <a href="admin/manage_places.php" style="color: #667eea;">add new places</a>.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Pagination -->
            <div id="paginationContainer">
            <?php
            if ($total_items > 0) {
                echo paginate($total_items, $items_per_page, $page, 'index.php');
            }
            ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Pesona Gorontalo Siap Memuaskan Anda!</p>
        </div>
    </footer>

    <!-- JavaScript for Responsive Interactive Background -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const parallaxBg = document.getElementById('parallaxBg');
            let ticking = false;

            // Detect device type based on screen width
            function getDeviceType() {
                const width = window.innerWidth;
                if (width < 768) {
                    return 'mobile'; // Mobile devices
                } else if (width < 1024) {
                    return 'tablet'; // Tablet devices
                } else {
                    return 'desktop'; // Desktop computers
                }
            }

            function updateBackground() {
                const scrollY = window.scrollY;

                let opacity = 1;

                // Fade settings - same for all devices
                const fadeStart = 100;
                const fadeEnd = 500;

                if (scrollY > fadeStart) {
                    const fadeProgress = Math.min((scrollY - fadeStart) / (fadeEnd - fadeStart), 1);
                    opacity = 1 - (fadeProgress * 0.7); // Fade to 0.3 opacity
                }

                // Apply only opacity effect - no movement, no blur
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

            // Update on resize (to detect device changes)
            window.addEventListener('resize', function() {
                updateBackground();
            });
        });

        // Real-time filtering functionality
        const searchInput = document.getElementById('searchInput');
        const categorySelect = document.getElementById('categorySelect');
        const searchForm = document.getElementById('searchForm');
        const resetButton = document.getElementById('resetButton');
        const placesGrid = document.getElementById('placesGrid');
        const paginationContainer = document.getElementById('paginationContainer');

        let debounceTimer;
        let currentPage = 1;

        // Function to fetch and update places
        function fetchPlaces(page = 1) {
            const search = searchInput.value.trim();
            const category = categorySelect.value;

            currentPage = page;

            // Show loading indicator
            placesGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem;"><div class="loading-spinner">Loading...</div></div>';

            // Build API URL
            const url = `api/filter_places.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&page=${page}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updatePlacesGrid(data.places);
                        updatePagination(data.pagination);
                    } else {
                        placesGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem;"><h3>Error loading places</h3></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    placesGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem;"><h3>Error loading places</h3></div>';
                });
        }

        // Function to update places grid
        function updatePlacesGrid(places) {
            if (places.length === 0) {
                placesGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <h3>No tourism places found</h3>
                        <p>Try adjusting your search criteria or <a href="admin/manage_places.php" style="color: #667eea;">add new places</a>.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            places.forEach(place => {
                html += `
                    <div class="place-card ${place.is_featured ? 'featured-card' : ''}">
                        <a href="detail.php?id=${place.id}" class="place-image-link">
                            <img src="assets/images/${place.image}" alt="${place.name}">
                        </a>
                        <div class="place-card-content">
                            ${place.is_featured ? '<span class="featured-badge">✨ Rekomendasi Editor</span>' : ''}
                            <span class="place-category">${place.category}</span>
                            <h3><a href="detail.php?id=${place.id}" class="place-title-link">${place.name}</a></h3>
                            <p>${place.description}</p>
                            <div class="place-meta">
                                <span>📍 ${place.location}</span>
                                <span class="place-rating">⭐ ${place.rating}</span>
                            </div>
                            <div class="place-views">
                                <span>👁️ Dilihat: ${place.views} kali</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            placesGrid.innerHTML = html;
        }

        // Function to update pagination
        function updatePagination(paginationHtml) {
            paginationContainer.innerHTML = paginationHtml;

            // Add click handlers to pagination links
            const paginationLinks = paginationContainer.querySelectorAll('.pagination-link');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.dataset.page);
                    if (!isNaN(page)) {
                        fetchPlaces(page);
                        // Scroll to top of results
                        document.querySelector('.places-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        }

        // Debounced search function
        function debouncedSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchPlaces(1);
            }, 500); // 500ms delay
        }

        // Event listeners for real-time filtering
        searchInput.addEventListener('input', debouncedSearch);
        categorySelect.addEventListener('change', () => fetchPlaces(1));

        // Search button click
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            fetchPlaces(1);
        });

        // Reset button click
        resetButton.addEventListener('click', function() {
            searchInput.value = '';
            categorySelect.value = '';
            fetchPlaces(1);
        });

        // Form submission
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchPlaces(1);
        });

    </script>
</body>
</html>

<?php $db->close(); ?>