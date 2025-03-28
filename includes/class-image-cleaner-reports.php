<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Cleaner_Reports {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_reports_menu']);
    }

    /**
     * Register the "Reports" submenu page under "Image Cleaner".
     */
    public function register_reports_menu() {
        add_submenu_page(
            'image-cleaner-overview',
            __('Reports', 'image-cleaner'),
            __('Reports', 'image-cleaner'),
            'manage_options',
            'image-cleaner-reports',
            [$this, 'render_reports_page']
        );
    }

    /**
     * Render the reports page.
     */
    public function render_reports_page() {
        if (!defined('IMAGE_CLEANER_PLUGIN_DIR')) {
            error_log('IMAGE_CLEANER_PLUGIN_DIR is not defined.');
            echo '<div class="error"><p>' . esc_html__('Plugin directory not defined.', 'image-cleaner') . '</p></div>';
            return;
        }

        $reports_file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/reports-page.php';

        if (file_exists($reports_file)) {
            include $reports_file;
        } else {
            error_log('Reports page file not found: ' . $reports_file);
            echo '<div class="error"><p>' . esc_html__('Reports page file not found.', 'image-cleaner') . '</p></div>';
        }

        if (isset($_GET['report_type'])) {
            $report_type = sanitize_text_field($_GET['report_type']);
            if ($report_type === 'unused') {
                $reports_data = $this->get_unused_images_data();
                $this->display_reports_data($reports_data);
            } elseif ($report_type === 'large') {
                $reports_data = $this->get_large_images_data();
                $this->display_reports_data($reports_data);
            } else {
                echo '<p>' . esc_html__('Invalid report type.', 'image-cleaner') . '</p>';
            }
        } else {
            echo '<p>' . esc_html__('Please select a report type.', 'image-cleaner') . '</p>';
        }
    }

    /**
     * Retrieve data for generating reports.
     *
     * @return array List of image data for reports.
     */
    public function get_reports_data() {
        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($query_args);
        $data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attached_file = get_attached_file(get_the_ID());
                if ($attached_file) {
                    $data[] = [
                        'ID'       => get_the_ID(),
                        'filename' => basename($attached_file),
                        'url'      => wp_get_attachment_url(get_the_ID()),
                        'size'     => size_format(filesize($attached_file)),
                        'status'   => $this->get_image_usage_status(get_the_ID()),
                    ];
                } else {
                    error_log('Attached file not found for image ID: ' . get_the_ID());
                }
            }
        } else {
            error_log('No attachments found.');
        }

        wp_reset_postdata();
        return $data;
    }

    /**
     * Retrieve data for unused images.
     *
     * @return array List of unused image data.
     */
    private function get_unused_images_data() {
        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_thumbnail_id',
                    'value'   => '',
                    'compare' => '=',
                ],
            ],
        ];

        $query = new WP_Query($query_args);
        $data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attached_file = get_attached_file(get_the_ID());
                if ($attached_file) {
                    $data[] = [
                        'ID'       => get_the_ID(),
                        'filename' => basename($attached_file),
                        'url'      => wp_get_attachment_url(get_the_ID()),
                        'size'     => size_format(filesize($attached_file)),
                        'status'   => __('Not in Use', 'image-cleaner'),
                    ];
                } else {
                    error_log('Attached file not found for image ID: ' . get_the_ID());
                }
            }
        } else {
            error_log('No unused attachments found.');
        }

        wp_reset_postdata();
        return $data;
    }

    /**
     * Retrieve data for large images.
     *
     * @return array List of large image data.
     */
    private function get_large_images_data() {
        $size_threshold = 500000; // Example size threshold in bytes (500 KB)
        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($query_args);
        $data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attached_file = get_attached_file(get_the_ID());
                if ($attached_file && filesize($attached_file) > $size_threshold) {
                    $data[] = [
                        'ID'       => get_the_ID(),
                        'filename' => basename($attached_file),
                        'url'      => wp_get_attachment_url(get_the_ID()),
                        'size'     => size_format(filesize($attached_file)),
                        'status'   => __('Large Image', 'image-cleaner'),
                    ];
                } else {
                    error_log('Attached file not found or not large for image ID: ' . get_the_ID());
                }
            }
        } else {
            error_log('No large attachments found.');
        }

        wp_reset_postdata();
        return $data;
    }

    /**
     * Display the reports data in a table format.
     *
     * @param array $reports_data The data to display.
     */
    private function display_reports_data($reports_data) {
        if (empty($reports_data)) {
            echo '<p>' . esc_html__('No data available for reports.', 'image-cleaner') . '</p>';
            return;
        }

        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'image-cleaner') . '</th>';
        echo '<th>' . esc_html__('Filename', 'image-cleaner') . '</th>';
        echo '<th>' . esc_html__('URL', 'image-cleaner') . '</th>';
        echo '<th>' . esc_html__('Size', 'image-cleaner') . '</th>';
        echo '<th>' . esc_html__('Status', 'image-cleaner') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($reports_data as $data) {
            echo '<tr>';
            echo '<td>' . esc_html($data['ID']) . '</td>';
            echo '<td>' . esc_html($data['filename']) . '</td>';
            echo '<td><a href="' . esc_url($data['url']) . '" target="_blank">' . esc_html($data['url']) . '</a></td>';
            echo '<td>' . esc_html($data['size']) . '</td>';
            echo '<td>' . esc_html($data['status']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Determine the usage status of an image.
     *
     * @param int $image_id The ID of the image.
     * @return string Usage status (e.g., 'In Use', 'Not in Use').
     */
    private function get_image_usage_status($image_id) {
        $posts_using_image = get_posts([
            'post_type'  => 'any',
            'meta_query' => [
                [
                    'key'     => '_thumbnail_id',
                    'value'   => $image_id,
                    'compare' => '=',
                ],
            ],
        ]);

        return !empty($posts_using_image) ? __('In Use', 'image-cleaner') : __('Not in Use', 'image-cleaner');
    }
}