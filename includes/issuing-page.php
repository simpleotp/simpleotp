<?php

require_once 'enums/IssuingPageState.php';

function register_issuing_shortcode()
{
    add_shortcode('otp_issuing_form', 'render_otp_issuing_form');
}

function render_otp_issuing_form($attr = [], $content = null)
{
    $code = isset($_GET['code']) ? $_GET['code'] : '';

    $success = isset($_SESSION['SIMPLE_OTP']['otp_issuing_page_state']) ? $_SESSION['SIMPLE_OTP']['otp_issuing_page_state'] : IssuingPageState::FirstLoad;
    $is_code_form_active = $success === IssuingPageState::EmailValid || $success === IssuingPageState::CodeInvalid || $success === IssuingPageState::BadCodeResend;
    $action_value = $is_code_form_active ? 'otp_verify' : 'otp_login';
    $submit_label = $is_code_form_active ? StrConst\issue_submit_code_button_text : StrConst\issue_submit_email_button_text;

    $otp_resend_code_expiry_time = isset($_SESSION['SIMPLE_OTP']['otp_issued_code_expiry']) ? $_SESSION['SIMPLE_OTP']['otp_issued_code_expiry'] : "1970-01-01 00:00:00";
    $otp_resend_code_valid = $otp_resend_code_expiry_time > date('Y-m-d H:i:s');
    $otp_resend_code_disabled = $otp_resend_code_valid ? "" : "disabled";

    ob_start();
?> <HTML>

<script>
    var countDownDate = new Date("<?php echo $otp_resend_code_expiry_time ?>").getTime();

    var x = setInterval(function() {
        var now = new Date().getTime();
        var distance = countDownDate - now;
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        var message = "";
        if (minutes > 0) {
            message += minutes + "m ";
        }

        var resendCodeCountdownElement = document.getElementById("resend_code_countdown");
        if (!resendCodeCountdownElement) 
            return;

        document.getElementById("resend_code_countdown").innerHTML = message + seconds + "s";

        if (distance < 0) {
            clearInterval(x);
            document.getElementById("resend_code_countdown").innerHTML = "<?php echo StrConst\issue_submit_code_expired_button_text ?>";
            document.getElementById("resend_code_button").disabled = true;
        }
    }, 1000);

    function redirect_to_enter_value(e) {
        e.preventDefault();
        console.log(e);
        console.log(e.target.id);
        var redirectForm = null;
        if (e.target.id.includes('code')) {
            var redirectForm = document.getElementById("redirect-to-enter-code");
        }
        else if (e.target.id.includes('email')) {
            var redirectForm = document.getElementById("redirect-to-enter-email");
        }
        if (redirectForm !== null) {
            redirectForm.submit();
        }
    }
</script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web">

    <div class="otp-container">
        <div class="otp-box">
            <div class="otp-box-content">
                <a class="otp-logo" href="<?php echo home_url() ?>">
                    <?php echo StrConst\issue_heading_text ?>
                </a>
                
                <?php  if ($success === IssuingPageState::FirstLoad || $success === IssuingPageState::EmailInvalid || $success === IssuingPageState::RecaptchaInvalid) : ?>
                    <p class="otp-highlight-text"><?php echo StrConst\issue_login_prompt_text ?></p>
                <?php else : ?>
                    <p class="otp-highlight-text"><?php echo StrConst\issue_verify_prompt_text ?></p>
                <?php endif ?>
                <form id="otp-issuing-form" action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
                    <input type="hidden" name="action" value="<?php echo $action_value ?>">

                    <?php if ($success === IssuingPageState::EmailValid) : ?>
                        <div class="otp-input-row">
                            <input placeholder="<?php echo StrConst\issue_code_input_placeholder ?>" type="text" name="code" value="<?php echo $code ?>" />
                        </div>
                    
                    <?php  elseif ($success === IssuingPageState::EmailInvalid) : ?>

                        <div class="otp-input-row">
                            <label for="email"><?php echo StrConst\issue_email_input_label ?></label>
                            <input placeholder="<?php echo StrConst\issue_email_input_placeholder ?>" type="email" name="email" />
                            <span class="otp-issuing-form-error"><?php echo StrConst\issue_invalid_email_error_text ?></span>
                        </div>

                    <?php  elseif ($success === IssuingPageState::RecaptchaInvalid) : ?>

                        <div class="otp-input-row">
                            <label for="email"><?php echo StrConst\issue_email_input_label ?></label>
                            <input placeholder="<?php echo StrConst\issue_email_input_placeholder ?>" type="email" name="email" />
                            <span class="otp-issuing-form-error"><?php echo StrConst\issue_invalid_recaptcha_error_text ?></span>
                        </div>

                    <?php elseif ($success === IssuingPageState::CodeInvalid) : ?>

                        <div class="otp-input-row">
                            <input placeholder="<?php echo StrConst\issue_code_input_placeholder ?>" type="text" name="code" value="<?php echo $code ?>" />
                            <span class="otp-issuing-form-error"><?php echo StrConst\issue_invalid_code_error_text ?></span>
                        </div>
                    
                    <?php elseif ($success === IssuingPageState::BadCodeResend) : ?>

                        <div class="otp-input-row">
                            <input placeholder="<?php echo StrConst\issue_code_input_placeholder ?>" type="text" name="code" value="<?php echo $code ?>" />
                            <span class="otp-issuing-form-error"><?php echo StrConst\issue_invalid_resend_error_text ?></span>
                        </div>

                    <?php else : ?>

                        <div class="otp-input-row">
                            <label for="email"><?php echo StrConst\issue_email_input_label ?></label>
                            <input placeholder="<?php echo StrConst\issue_email_input_placeholder ?>" type="email" name="email" />
                        </div>

                    <?php endif ?>

                    <div class="otp-input-row">
                        <?php if (get_option('otp_recaptcha_enabled', '') === "otp_recaptcha_enabled") : ?>

                            <button class="g-recaptcha otp-button" 
                                data-sitekey="<?php echo get_option('otp_recaptcha_site_key'); ?>" 
                                data-callback='onSubmit' 
                                data-action='submit'
                            >
                                <?php echo $submit_label ?>
                            </button>

                        <?php else : ?>
                            
                            <button class="otp-button">
                                <?php echo $submit_label ?>
                            </button>

                        <?php endif ?>
                    </div>
                </form>


                <?php if ($success === IssuingPageState::FirstLoad || $success === IssuingPageState::EmailInvalid || $success === IssuingPageState::RecaptchaInvalid) : ?>
                    <form id="redirect-to-enter-code" action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
                        <input type="hidden" name="action" value="redirect_to_enter_code">
                        <div class="otp-input-row">
                            <?php echo StrConst\issue_redirect_to_code_prompt ?>&nbsp;
                            <a id="redirect-to-enter-code-link" href="" onclick="redirect_to_enter_value(event)" class="otp-anchor">
                                <?php echo StrConst\issue_redirect_to_code_link ?>
                            </a>
                        </div>
                    </form>
                    
                <?php else : ?>
                    <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
                        <input type="hidden" name="action" value="resend_code">
                        <div class="otp-input-row">
                            <?php echo StrConst\issue_resend_email_prompt ?>&nbsp;
                            <button id="resend_code_button" class="otp-button" <?php echo $otp_resend_code_disabled ?>>
                                <?php echo StrConst\issue_resend_email_button_text ?> (<span id="resend_code_countdown"></span>)
                            </button>
                        </div>
                    </form>
                    <form id="redirect-to-enter-email" action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
                        <input type="hidden" name="action" value="redirect_to_enter_email">
                        <div class="otp-input-row">
                            <?php echo StrConst\issue_redirect_to_email_prompt ?>&nbsp;
                            <a id="redirect-to-enter-email-link" href="" onclick="redirect_to_enter_value(event)" class="otp-anchor">
                                <?php echo StrConst\issue_redirect_to_email_link ?>
                            </a>
                        </div>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>

<?php
    return ob_get_clean();
}

?>