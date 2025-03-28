<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;

// Variables de configuración y paginación
$images_per_page_options = [20, 50, 100];
$images_per_page = isset($_GET['images_per_page']) && in_array(intval($_GET['images_per_page']), $images_per_page_options) 
    ? intval($_GET['images_per_page']) 
    : 20;

$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $images_per_page;

// Filtros
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'all';
$filter_date_start = isset($_GET['filter_date_start']) ? sanitize_text_field($_GET['filter_date_start']) : '';
$filter_date_end = isset($_GET['filter_date_end']) ? sanitize_text_field($_GET['filter_date_end']) : '';

// Procesar eliminación de imágenes seleccionadas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['images_to_delete']) && check_admin_referer('image_cleaner_delete_images', 'image_cleaner_nonce')) {
    $images_to_delete = array_map('intval', $_POST['images_to_delete']);
    foreach ($images_to_delete as $image_id) {
        wp_delete_attachment($image_id, true);
    }
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Selected images and their thumbnails have been deleted.', 'image-cleaner') . '</p></div>';
}

// Construir consulta de imágenes
$query = "SELECT ID, post_author, post_title, post_mime_type, post_date FROM {$wpdb->prefix}posts WHERE post_type = 'attachment'";
if ($filter_type !== 'all') {
    $query .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_type . '%');
}
if ($filter_date_start) {
    $query .= $wpdb->prepare(" AND post_date >= %s", $filter_date_start);
}
if ($filter_date_end) {
    $query .= $wpdb->prepare(" AND post_date <= %s", $filter_date_end);
}
$total_images = $wpdb->get_var(str_replace("SELECT ID, post_author, post_title, post_mime_type, post_date", "SELECT COUNT(*)", $query));
$query .= $wpdb->prepare(" ORDER BY post_date DESC LIMIT %d OFFSET %d", $images_per_page, $offset);
$images = $wpdb->get_results($query);

?>
<div class="wrap">
    <h1><?php esc_html_e('Filter Images', 'image-cleaner'); ?></h1>

    <style>
        .tablenav-pages a {
            padding: 8px 12px;
            margin: 0 4px;
            text-decoration: none;
            border: 1px solid #ddd;
            background-color: #f7f7f7;
            color: #0073aa;
            border-radius: 4px;
        }
        .tablenav-pages a:hover {
            background-color: #e2e2e2;
        }
        .tablenav-pages .current {
            background-color: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .tablenav-pages .dots {
            padding: 8px 12px;
            margin: 0 4px;
            color: #999;
        }
        .tablenav-pages .displaying-num {
            margin-right: 10px;
            font-weight: bold;
        }
        .image-cleaner-table-form {
            position: relative;
            margin-top: 20px;
        }
        .loading-indicator {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-size: 16px;
            color: #0073aa;
        }
        .loading-indicator.active {
            display: block;
        }
        .loading-indicator::after {
            content: '';
            display: block;
            margin: 10px auto;
            width: 40px;
            height: 40px;
            border: 4px solid #0073aa;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .image-filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            align-items: flex-end;
        }
        .image-filters-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .image-filters-form select,
        .image-filters-form input[type="date"],
        .image-filters-form button {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .image-filters-form select {
            min-width: 220px;
        }
        .image-filters-form button {
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
        }
        .image-filters-form button:hover {
            background-color: #005177;
        }
        .button.button-primary {
            background-color: #d63638;
            border-color: #d63638;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
        }
        .button.button-primary:hover {
            background-color: #a82a2b;
            border-color: #a82a2b;
        }
        .wp-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .wp-list-table th, .wp-list-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .wp-list-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        .wp-list-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .wp-list-table tr:hover {
            background-color: #f1f1f1;
        }
        .wp-list-table img {
            max-width: 50px;
            height: auto;
            border-radius: 4px;
        }
    </style>

    <!-- Formulario de filtros -->
    <form method="get" class="image-filters-form">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <div>
            <label for="filter_type"><?php esc_html_e('File Type', 'image-cleaner'); ?></label>
            <select name="filter_type" id="filter_type">
                <option value="all" <?php selected($filter_type, 'all'); ?>><?php esc_html_e('All', 'image-cleaner'); ?></option>
                <option value="image/jpeg" <?php selected($filter_type, 'image/jpeg'); ?>>JPEG</option>
                <option value="image/png" <?php selected($filter_type, 'image/png'); ?>>PNG</option>
                <option value="image/gif" <?php selected($filter_type, 'image/gif'); ?>>GIF</option>
                <option value="image/svg+xml" <?php selected($filter_type, 'image/svg+xml'); ?>>SVG</option>
                <option value="image/webp" <?php selected($filter_type, 'image/webp'); ?>>WEBP</option>
            </select>
        </div>
        <div>
            <label for="filter_date_start"><?php esc_html_e('Start Date', 'image-cleaner'); ?></label>
            <input type="date" name="filter_date_start" value="<?php echo esc_attr($filter_date_start); ?>">
        </div>
        <div>
            <label for="filter_date_end"><?php esc_html_e('End Date', 'image-cleaner'); ?></label>
            <input type="date" name="filter_date_end" value="<?php echo esc_attr($filter_date_end); ?>">
        </div>
        <div>
            <label for="images_per_page"><?php esc_html_e('Images per page', 'image-cleaner'); ?></label>
            <select name="images_per_page">
                <?php foreach ($images_per_page_options as $option) : ?>
                    <option value="<?php echo $option; ?>" <?php selected($images_per_page, $option); ?>>
                        <?php echo esc_html($option); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="button"><?php esc_html_e('Filter', 'image-cleaner'); ?></button>
        </div>
    </form>

    <!-- Tabla de imágenes -->
    <form method="post" class="image-cleaner-table-form" onsubmit="return confirm('<?php esc_html_e('Are you sure you want to delete the selected images?', 'image-cleaner'); ?>');">
        <div class="loading-indicator"><?php esc_html_e('Processing...', 'image-cleaner'); ?></div>
        <?php wp_nonce_field('image_cleaner_delete_images', 'image_cleaner_nonce'); ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th><?php esc_html_e('Image', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('File Name', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('File Type', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Size', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Dimensions', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('In Use?', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Usage Links', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Upload Date', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Uploaded By', 'image-cleaner'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($images)) : ?>
                    <?php foreach ($images as $image) : 
                        $is_in_use = !empty(get_posts(['post_type' => 'any', 'meta_key' => '_thumbnail_id', 'meta_value' => $image->ID]));
                        $usage_links = $wpdb->get_results($wpdb->prepare(
                            "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_value = %s", $image->ID
                        ));
                        $image_size = size_format(filesize(get_attached_file($image->ID)));
                        $image_dimensions = wp_get_attachment_metadata($image->ID);
                        $dimensions = isset($image_dimensions['width']) && isset($image_dimensions['height']) ? $image_dimensions['width'] . 'x' . $image_dimensions['height'] : 'N/A';
                        $author_name = get_the_author_meta('display_name', $image->post_author);
                    ?>
                        <tr>
                            <td><input type="checkbox" name="images_to_delete[]" value="<?php echo esc_attr($image->ID); ?>"></td>
                            <td><?php echo wp_get_attachment_image($image->ID, [50, 50]); ?></td>
                            <td><?php echo esc_html($image->post_title); ?></td>
                            <td><?php echo esc_html($image->post_mime_type); ?></td>
                            <td><?php echo esc_html($image_size); ?></td>
                            <td><?php echo esc_html($dimensions); ?></td>
                            <td><?php echo $is_in_use ? esc_html__('Yes', 'image-cleaner') : esc_html__('No', 'image-cleaner'); ?></td>
                            <td>
                                <?php if (!empty($usage_links)) : ?>
                                    <ul>
                                        <?php foreach ($usage_links as $link) : ?>
                                            <li><a href="<?php echo esc_url(get_permalink($link->post_id)); ?>" target="_blank"><?php echo esc_html(get_the_title($link->post_id)); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <?php esc_html_e('No links', 'image-cleaner'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date('Y-m-d', strtotime($image->post_date))); ?></td>
                            <td><?php echo esc_html($author_name); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="10"><?php esc_html_e('No images found.', 'image-cleaner'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button type="submit" class="button button-primary"><?php esc_html_e('Delete Selected Images', 'image-cleaner'); ?></button>
    </form>

    <!-- Paginación -->
    <div class="tablenav">
        <div class="tablenav-pages">
            <?php
            $total_pages = ceil($total_images / $images_per_page);
            $page_links = [];

            // Previous button
            if ($current_page > 1) {
                $prev_page = $current_page - 1;
                $prev_url = add_query_arg(['paged' => $prev_page, 'images_per_page' => $images_per_page]);
                $page_links[] = "<a href='" . esc_url($prev_url) . "' class='prev-page'>" . esc_html__('Previous', 'image-cleaner') . "</a>";
            }

            // Page numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if ($start_page > 1) {
                $first_url = add_query_arg(['paged' => 1, 'images_per_page' => $images_per_page]);
                $page_links[] = "<a href='" . esc_url($first_url) . "'>1</a>";
                if ($start_page > 2) {
                    $page_links[] = "<span class='dots'>…</span>";
                }
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $url = add_query_arg(['paged' => $i, 'images_per_page' => $images_per_page]);
                $class = ($i == $current_page) ? ' class="current"' : '';
                $page_links[] = "<a href='" . esc_url($url) . "'$class>$i</a>";
            }

            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    $page_links[] = "<span class='dots'>…</span>";
                }
                $last_url = add_query_arg(['paged' => $total_pages, 'images_per_page' => $images_per_page]);
                $page_links[] = "<a href='" . esc_url($last_url) . "'>$total_pages</a>";
            }

            // Next button
            if ($current_page < $total_pages) {
                $next_page = $current_page + 1;
                $next_url = add_query_arg(['paged' => $next_page, 'images_per_page' => $images_per_page]);
                $page_links[] = "<a href='" . esc_url($next_url) . "' class='next-page'>" . esc_html__('Next', 'image-cleaner') . "</a>";
            }

            echo '<span class="displaying-num">' . sprintf(esc_html__('Page %1$d of %2$d', 'image-cleaner'), $current_page, $total_pages) . '</span>';
            echo implode(' ', $page_links);
            ?>
        </div>
    </div>
</div>
<script>
    // Selección masiva de imágenes
    document.getElementById('select-all').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="images_to_delete[]"]');
        for (const checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    // Mostrar indicador de carga al enviar el formulario
    document.querySelector('.image-cleaner-table-form').addEventListener('submit', function() {
        document.querySelector('.loading-indicator').classList.add('active');
    });
</script>