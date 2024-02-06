<?php

function register_success_shortcode()
{
    add_shortcode('otp_success_page', 'render_otp_success_page');
}

function render_otp_success_page($attr = [], $content = null)
{
    $first_protected_page = get_pages(array('include' => get_option('otp_first_protected_page')))[0]->post_name;

    ob_start();
?> <HTML>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web">
    <div class="otp-container">
        <div class="otp-box">
            <div class="otp-box-content">
                <a class="otp-logo" href="<?php echo home_url() ?>">
                    <?php echo StrConst\success_heading_text ?>
                </a>
                <p><?php echo StrConst\success_portal_available_text ?></p>
                <a href="<?php echo "/$first_protected_page"?>"><?php echo StrConst\success_portal_available_link ?></a>
            </div>
        </div>
    </div>


<?php
    return ob_get_clean();
}

?>