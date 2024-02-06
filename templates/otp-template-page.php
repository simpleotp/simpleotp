<?php

/*
Template Name: OTP Template Page
*/

$id = get_the_ID();
$post = get_post($id);

$otp_styles_file_path = get_site_url() . dirname(__DIR__, 1). '/includes/css/otp.css';
$otp_styles_url_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $otp_styles_file_path);

?>
<link rel="stylesheet" type="text/css" href="<?php echo $otp_styles_url_path ?>">
<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
   function onSubmit(token) {
     document.getElementById("otp-issuing-form").submit();
   }
 </script>

<?php echo do_shortcode($post->post_content); ?>
