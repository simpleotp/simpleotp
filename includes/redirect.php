<?php

function otp_redirect()
{
    if (session_id()) {
        setcookie("PHPSESSID", session_id(), path: '/');
    }

    if (!wp_next_scheduled('otp_remove_stale_codes') || wp_next_scheduled('otp_remove_stale_codes') > strtotime('+1 hour')) {
        wp_schedule_event(time(), 'hourly', 'otp_remove_stale_codes');
    }

    global $post;
    
    $current_page_id = $post->ID;
    $current_page_post_type = $post->post_type;

    $success_page_slug = get_option('otp_success_page_slug');
    $success_page = get_page_by_path($success_page_slug);
    if (!$success_page) {
        return wp_redirect(home_url());
    }
    $protected_success_page = $post->ID === $success_page->ID;

    $protected_pages_ids = array_column(get_pages(array('include' => get_option('otp_protected_pages'))), 'ID');
    $protected_post_types = get_option('otp_protected_post_types', array());
    empty($protected_post_types) && $protected_post_types = array();

    $protected_by_surgical_selection = in_array($current_page_id, $protected_pages_ids);
    $protected_by_post_type = in_array($current_page_post_type, $protected_post_types);

    $issuing_page_slug = get_option('otp_issuing_page_slug');

    if ($protected_by_surgical_selection || $protected_by_post_type || $protected_success_page) {
        $session_expiry = isset($_SESSION['SIMPLE_OTP']['otp_session_expiry']) ? $_SESSION['SIMPLE_OTP']['otp_session_expiry'] : '';

        if (empty($session_expiry) || $session_expiry < date('Y-m-d H:i:s')) {
            $_SESSION['SIMPLE_OTP'] = null;

            return wp_redirect("/$issuing_page_slug");
        }
    }

    $otp_dev_mode = get_option('otp_dev_mode', '');
    if ($otp_dev_mode === 'otp_dev_mode' && !is_admin()) {
        ob_start();
        ?> <HTML>
        <?php if ($otp_dev_mode === 'otp_dev_mode') : ?>
            <a href="/<?php echo $issuing_page_slug ?>">Issuing page</a>
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
                <div>
                    <input type="hidden" name="action" value="clear_session">
                    <button>CLEAR SESSION</button>
                </div>
            </form>
            
        <?php endif ?>
    
        <?php
        print_r(ob_get_clean());    
        $simple_otp_session_vars = isset($_SESSION['SIMPLE_OTP']) ? $_SESSION['SIMPLE_OTP'] : array();
        print_r($simple_otp_session_vars);
    }
}
