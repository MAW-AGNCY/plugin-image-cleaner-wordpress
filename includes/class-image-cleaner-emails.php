<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Cleaner_Emails {

    public function __construct() {
        add_action('image_cleaner_daily_summary', [$this, 'send_daily_summary']);
    }

    /**
     * Schedule a daily summary email event.
     */
    public static function schedule_email_event() {
        if (!wp_next_scheduled('image_cleaner_daily_summary')) {
            wp_schedule_event(time(), 'daily', 'image_cleaner_daily_summary');
        }
    }

    /**
     * Clear the scheduled email event on plugin deactivation.
     */
    public static function clear_email_event() {
        wp_clear_scheduled_hook('image_cleaner_daily_summary');
    }

    /**
     * Send a daily summary email.
     */
    public function send_daily_summary() {
        $email_recipient = get_option('image_cleaner_email_recipient', get_option('admin_email'));
        $subject = __('Daily Image Cleaner Summary', 'image-cleaner');
        $message = $this->generate_summary_message();

        if (!is_email($email_recipient)) {
            error_log(__('Invalid email address for Image Cleaner summary.', 'image-cleaner'));
            return;
        }

        $sent = wp_mail($email_recipient, $subject, $message);

        if (!$sent) {
            error_log(__('Failed to send Image Cleaner summary email.', 'image-cleaner'));
        }
    }

    /**
     * Generate the summary message for the email.
     *
     * @return string The summary message.
     */
    private function generate_summary_message() {
        $data = $this->get_summary_data();
        $message = __('Here is the daily summary of the Image Cleaner plugin:', 'image-cleaner') . "\n\n";

        foreach ($data as $key => $value) {
            $message .= sprintf('%s: %s', ucfirst($key), $value) . "\n";
        }

        return $message;
    }

    /**
     * Retrieve data for the summary email.
     *
     * @return array Summary data.
     */
    private function get_summary_data() {
        // Example data, replace with actual logic.
        return [
            'total_images' => 1200,
            'cleaned_images' => 45,
            'recovered_images' => 5,
        ];
    }
}