<?php

function otp_plugin_cron_activation() {
    if (! wp_next_scheduled('otp_remove_stale_codes')) {
        wp_schedule_event(time(), 'hourly', 'otp_remove_stale_codes');
    }
}

function otp_plugin_cron_deactivation() {
    wp_clear_scheduled_hook('otp_remove_stale_codes');
}

function otp_remove_stale_codes_func() {
    global $wpdb;
    $table_name = $wpdb->prefix . get_option('otp_db_table_suffix');

    $sql = "DELETE FROM $table_name WHERE CURRENT_TIMESTAMP() > expiry_date";
    $wpdb->query($sql);
}