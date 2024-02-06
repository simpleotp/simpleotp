<?php

function create_otp_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . get_option('otp_db_table_suffix');

    $sql = "CREATE TABLE $table_name (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `otp_code` VARCHAR(32) NOT NULL,
	`expiry_date` DATETIME DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function remove_otp_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . get_option('otp_db_table_suffix');
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}

function init_smtp_email($phpmailer)
{
    if (defined('SMTP_USER') && defined('SMTP_PASS') && defined('SMTP_HOST')) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = SMTP_HOST;
        $phpmailer->SMTPAuth   = SMTP_AUTH;
        $phpmailer->Port       = SMTP_PORT;
        $phpmailer->SMTPSecure = SMTP_SECURE;
        $phpmailer->Username   = SMTP_USER;
        $phpmailer->Password   = SMTP_PASS;
        $phpmailer->From       = SMTP_FROM;
        $phpmailer->FromName   = SMTP_NAME; 
    }
    else {
        $otp_smtp_host = get_option('otp_smtp_host', '');
        $otp_smtp_port = get_option('otp_smtp_port', '');
        $otp_smtp_encryption_secure = get_option('otp_smtp_encryption_secure', '');
        $otp_smtp_user = get_option('otp_smtp_user', '');
        $otp_smtp_password = get_option('otp_smtp_password', '');
        $otp_smtp_from_address = get_option('otp_smtp_from_address', '');
        $otp_smtp_from_name = get_option('otp_smtp_from_name', '');
    
        $phpmailer->isSMTP();
        $phpmailer->Host       = $otp_smtp_host;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = $otp_smtp_port;
        $phpmailer->SMTPSecure = $otp_smtp_encryption_secure;
        $phpmailer->Username   = $otp_smtp_user;
        $phpmailer->Password   = $otp_smtp_password;
        $phpmailer->From       = $otp_smtp_from_address;
        $phpmailer->FromName   = $otp_smtp_from_name;
    }
}


function remove_otp_issuing_page() {
    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $page = get_page_by_path($issuing_page_slug);
    $result = wp_delete_post($page->ID, true);
    if (!$result || !$page) {
        error_log("OTP Plugin warning: The issuing page was not removed correctly. Slug: $issuing_page_slug");
    }
}

function remove_otp_success_page() {
    $success_page_slug = get_option('otp_success_page_slug');
    $page = get_page_by_path($success_page_slug);
    $result = wp_delete_post($page->ID, true);
    if (!$result || !$page) {
        error_log("OTP Plugin warning: The success page was not removed correctly. Slug: $success_page_slug");
    }
}

function protect_otp_pages_from_trash($trash, $post) {
    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $issuing_page = get_page_by_path($issuing_page_slug);
    $issuing_page_id = $issuing_page->ID;
    $success_page_slug = get_option('otp_success_page_slug');
    $success_page = get_page_by_path($success_page_slug);
    $success_page_id = $success_page->ID;

    $protected_ids = array($issuing_page_id, $success_page_id);
    if (!in_array($post->ID, $protected_ids)) {
        return $trash;
    }
    else {
        return false;
    }
}