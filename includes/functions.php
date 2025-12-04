<?php
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date) {
    return date('F d, Y', strtotime($date));
}

function alert_message($message, $type = 'success') {
    $class = ($type == 'success') ? 'alert-success' : 'alert-danger';
    return '<div class="alert ' . $class . '">' . $message . '</div>';
}

function paginate($total_items, $items_per_page, $current_page, $page_url) {
    $total_pages = ceil($total_items / $items_per_page);

    if ($total_pages <= 1) return '';

    // Preserve existing GET parameters
    $params = $_GET;
    unset($params['page']); // Remove current page from params
    $query_string = http_build_query($params);
    $separator = ($query_string) ? '&' : '';
    
    // Helper to build link
    $build_link = function($page) use ($page_url, $query_string, $separator) {
        return $page_url . '?' . ($query_string ? $query_string . $separator : '') . 'page=' . $page;
    };

    $pagination = '<div class="pagination">';

    // Previous button
    if ($current_page > 1) {
        $pagination .= '<a href="' . $build_link($current_page - 1) . '">&laquo; Previous</a>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active_class = ($i == $current_page) ? 'active' : '';
        $pagination .= '<a href="' . $build_link($i) . '" class="' . $active_class . '">' . $i . '</a>';
    }

    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . $build_link($current_page + 1) . '">Next &raquo;</a>';
    }

    $pagination .= '</div>';
    return $pagination;
}
?>