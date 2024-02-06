<?php

require_once 'classes/codemanager.php';
require_once 'classes/email.php';
require_once 'enums/IssuingPageState.php';

function otp_login()
{
    $_SESSION['SIMPLE_OTP'] = null;

    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $is_recaptcha_valid = verify_recaptcha($recaptcha_response);

    if ($is_recaptcha_valid) {
        $email_address = $_POST['email'];
        $email = new OTP\Email($email_address);

        if ($email->is_valid()) {
            $otp = OTP\CodeManager::get_instance();
            $code = $otp->generate($email_address);

            $msg = "Code: $code\nURL: " . get_site_url() . "/$issuing_page_slug/?code=$code";

            $successful_send = $email->send($msg);

            if ($successful_send) {
                global $wpdb;
                $expiry_minutes = get_option('otp_code_expiry_length');
                $expiry_time = date('Y-m-d H:i:s', strtotime("+$expiry_minutes minutes"));

                $table_name = $wpdb->prefix . get_option('otp_db_table_suffix');
                $sql = "INSERT INTO $table_name (";
                $sql .= "`otp_code`, `expiry_date`)";
                $sql .= "VALUES ('{$code}', '{$expiry_time}')";

                $wpdb->query($sql);

                $_SESSION['SIMPLE_OTP']['otp_session_user_resend_msg'] = $msg;
                $_SESSION['SIMPLE_OTP']['otp_session_user_resend_email'] = $email_address;
                $_SESSION['SIMPLE_OTP']['otp_issued_code_expiry'] = $expiry_time;

                $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::EmailValid;
                wp_safe_redirect("/$issuing_page_slug");
                exit;
            }
        }
        $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::EmailInvalid;
        wp_safe_redirect("/$issuing_page_slug");
        exit;
    }
    $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::RecaptchaInvalid;
    wp_safe_redirect("/$issuing_page_slug");
    exit;
}

function otp_verify()
{

    $code = isset($_POST['code']) ? $_POST['code'] : '';
    $issuing_page_slug = get_option('otp_issuing_page_slug');

    if (empty($code)) {
        $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::CodeInvalid;
        wp_safe_redirect("/$issuing_page_slug");
        exit;
    }

    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $is_recaptcha_valid = verify_recaptcha($recaptcha_response);

    if ($is_recaptcha_valid) {
        global $wpdb;
        $table_name = $wpdb->prefix . get_option('otp_db_table_suffix');
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE CURRENT_TIMESTAMP() < expiry_date AND otp_code=%s;", [$code]);
        $query_result = $wpdb->get_results($sql);

        if (!empty($query_result) && is_object($query_result[0])) {
            $sql = "DELETE FROM $table_name WHERE id=" . $query_result[0]->id;
            $delete_result = $wpdb->query($sql);

            if ($delete_result > 0) {
                $expiry_minutes = get_option('otp_session_expiry_length');
                $expiry_time = date('Y-m-d H:i:s', strtotime("+$expiry_minutes minutes"));

                $_SESSION['SIMPLE_OTP']['otp_session_expiry'] = $expiry_time;
                $_SESSION['SIMPLE_OTP']['otp_issued_code_expiry'] = "";
                $_SESSION['SIMPLE_OTP']['otp_session_user_resend_msg'] = "";
                $_SESSION['SIMPLE_OTP']['otp_session_user_resend_email'] = "";
                $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::FirstLoad;

                $success_page_slug = get_option('otp_success_page_slug');
                wp_safe_redirect("/$success_page_slug");
                exit;
            }
        }
        $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::CodeInvalid;
        wp_safe_redirect("/$issuing_page_slug");
        exit;
    }
    $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::RecaptchaInvalid;
    wp_safe_redirect("/$issuing_page_slug");
    exit;
}

function clear_session()
{
    $_SESSION['SIMPLE_OTP'] = null;

    $issuing_page_slug = get_option('otp_issuing_page_slug');

    wp_safe_redirect("/$issuing_page_slug");
}

function resend_code()
{
    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $valid_session_vars = isset($_SESSION['SIMPLE_OTP']['otp_session_user_resend_msg']) 
        && isset($_SESSION['SIMPLE_OTP']['otp_session_user_resend_email']) 
        && isset($_SESSION['SIMPLE_OTP']['otp_issued_code_expiry']);

    if ($valid_session_vars && $_SESSION['SIMPLE_OTP']['otp_issued_code_expiry'] > date('Y-m-d H:i:s')) {
        $email = new OTP\Email($_SESSION['SIMPLE_OTP']['otp_session_user_resend_email']);
        $successful_send = $email->send($_SESSION['SIMPLE_OTP']['otp_session_user_resend_msg']);
        $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = $successful_send ? IssuingPageState::EmailValid : IssuingPageState::BadCodeResend;
        wp_safe_redirect("/$issuing_page_slug");
        exit;
    }
    $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::BadCodeResend;
    wp_safe_redirect("/$issuing_page_slug");
    exit;
}

function redirect_to_enter_email()
{
    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::FirstLoad;
    wp_safe_redirect("/$issuing_page_slug");
    exit;
}

function redirect_to_enter_code()
{
    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] = IssuingPageState::EmailValid;
    wp_safe_redirect("/$issuing_page_slug");
    exit;
}

function register_form_actions()
{
    add_action('admin_post_clear_session', 'clear_session');
    add_action('admin_post_nopriv_clear_session', 'clear_session');
    add_action('admin_post_otp_verify', 'otp_verify');
    add_action('admin_post_nopriv_otp_verify', 'otp_verify');
    add_action('admin_post_otp_login', 'otp_login');
    add_action('admin_post_nopriv_otp_login', 'otp_login');
    add_action('admin_post_resend_code', 'resend_code');
    add_action('admin_post_nopriv_resend_code', 'resend_code');
    add_action('admin_post_redirect_to_enter_code', 'redirect_to_enter_code');
    add_action('admin_post_nopriv_redirect_to_enter_code', 'redirect_to_enter_code');
    add_action('admin_post_redirect_to_enter_email', 'redirect_to_enter_email');
    add_action('admin_post_nopriv_redirect_to_enter_email', 'redirect_to_enter_email');
}

function verify_recaptcha($g_recaptcha_response) {

    if (get_option('otp_recaptcha_enabled') !== "otp_recaptcha_enabled") {
        return true;
    }

    if (!isset($g_recaptcha_response)) {
        return false;
    }

    $recaptcha_secret_key = get_option('otp_recaptcha_secret_key');
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

    do {
        $is_valid = false;
        $query = http_build_query(array(
            'secret' => $recaptcha_secret_key,
            'response' => $g_recaptcha_response
        ), '', '&');

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                'content' => $query,
            ),
        ));

        $response = file_get_contents($recaptcha_url, false, $context);
        
        $result = json_decode($response);
        if (empty($result)) {
            break;
        }
        if (!isset($result->success)) {
            break;
        }
        $is_valid = $result->success;

    } while ( false );

    return $is_valid;
}
