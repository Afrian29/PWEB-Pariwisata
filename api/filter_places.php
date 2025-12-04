<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

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

// Build response data
$places = [];
if ($result && $result->num_rows > 0) {
    while ($place = $result->fetch_assoc()) {
        $places[] = [
            'id' => $place['id'],
            'name' => htmlspecialchars($place['name']),
            'description' => htmlspecialchars(substr($place['description'], 0, 150)) . '...',
            'location' => htmlspecialchars($place['location']),
            'category' => htmlspecialchars(ucfirst($place['category'])),
            'rating' => number_format($place['rating'], 1),
            'image' => !empty($place['image']) ? htmlspecialchars($place['image']) : 'bg1.jpeg',
            'is_featured' => $place['is_featured'] == 1,
            'views' => number_format($place['views'], 0, ',', '.')
        ];
    }
}

// Generate pagination HTML
$pagination_html = '';
if ($total_items > 0) {
    $total_pages = ceil($total_items / $items_per_page);

    $pagination_html .= '<div class="pagination">';

    // Previous button
    if ($page > 1) {
        $pagination_html .= '<a href="#" class="pagination-link" data-page="' . ($page - 1) . '">&laquo; Previous</a>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active_class = ($i == $page) ? 'active' : '';
        $pagination_html .= '<a href="#" class="pagination-link ' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
    }

    // Next button
    if ($page < $total_pages) {
        $pagination_html .= '<a href="#" class="pagination-link" data-page="' . ($page + 1) . '">Next &raquo;</a>';
    }

    $pagination_html .= '</div>';
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'places' => $places,
    'pagination' => $pagination_html,
    'total_items' => $total_items,
    'current_page' => $page
]);

$db->close();
?>