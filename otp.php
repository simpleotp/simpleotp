<?php

/**
 * 
 * Plugin Name: Simple OTP
 * Description: Protect selected pages on your WP website with a one-time-password system
 * Author: Xwerx
 * Version: 1.0.0
 * Text Domain:  simple-otp
 * 
 * Simple OTP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * Simple OTP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Simple OTP. If not, see https://www.gnu.org/licenses/gpl-2.0.HTML.
 **/

if (!session_id()) {
    session_start();
}

require_once 'includes/init.php';
require_once 'includes/form-actions.php';
require_once 'includes/issuing-page.php';
require_once 'includes/success-page.php';
require_once 'includes/option-settings.php';
require_once 'includes/redirect.php';
require_once 'includes/cron.php';
require_once 'includes/resources/string-constants.php';

// issuing-page.php
add_action('init', 'register_issuing_shortcode');
add_action('init', 'register_success_shortcode');

// init.php
register_activation_hook(__FILE__, 'create_otp_table');
register_deactivation_hook(__FILE__, 'remove_otp_table');
add_action('phpmailer_init', 'init_smtp_email');
register_activation_hook(__FILE__, 'init_otp_issuing_page');
register_deactivation_hook(__FILE__, 'remove_otp_issuing_page');
register_activation_hook(__FILE__, 'init_otp_success_page');
register_deactivation_hook(__FILE__, 'remove_otp_success_page');
add_filter('pre_trash_post', 'protect_otp_pages_from_trash', 10, 2);

// form-actions.php
add_action('init', 'register_form_actions');

// option-settings.php
add_action('admin_init', 'otp_options_init');
add_action('admin_menu', 'otp_options_page');

// redirect.php
add_action('template_redirect', 'otp_redirect');

// cron.php
register_activation_hook(__FILE__, 'otp_plugin_cron_activation');
register_deactivation_hook(__FILE__, 'otp_plugin_cron_deactivation');
add_action('otp_remove_stale_codes', 'otp_remove_stale_codes_func' );

// rest
add_filter('template_include', 'add_issuing_page_template_to_ui');
add_filter('theme_page_templates', 'register_issuing_page_template');

// These functions were left here because the reference to "__FILE__" being this file is important
function add_issuing_page_template_to_ui($template) {
    $issuing_page_slug = get_option('otp_issuing_page_slug');
    $success_page_slug = get_option('otp_success_page_slug');
    if (is_page($issuing_page_slug) || is_page($success_page_slug)) {
        $template = dirname(__FILE__) . '/templates/otp-template-page.php';
    }
    return $template;
}

function register_issuing_page_template($templates) {
   $templates[dirname(__FILE__) . '/templates/otp-template-page.php'] = 'OTP Template Page';
   return $templates;
}

function init_otp_issuing_page() {
    $user_id = get_current_user_id();
    $new_post = array(
        'post_author'  => $user_id,
        'post_title'   => 'OTP Issuing Page',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'otp-issuing-page',
        'page_template' => dirname(__FILE__) . '/templates/otp-template-page.php',
        'post_content' => '[otp_issuing_form]'
    );
    
    $post_id = wp_insert_post($new_post, true);

    if (is_wp_error($post_id)) {
        error_log("OTP Plugin Error: " . $post_id->get_error_message());
    }
    else {
        $new_post_name = get_post($post_id)->post_name;
        update_option('otp_issuing_page_slug', $new_post_name);
    }
}

function init_otp_success_page() {
    $user_id = get_current_user_id();
    $new_post = array(
        'post_author'  => $user_id,
        'post_title'   => 'OTP Success Page',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'otp-success-page',
        'page_template' => dirname(__FILE__) . '/templates/otp-template-page.php',
        'post_content' => '[otp_success_page]'
    );
    
    $post_id = wp_insert_post($new_post, true);

    if (is_wp_error($post_id)) {
        error_log("OTP Plugin Error: " . $post_id->get_error_message());
    }
    else {
        $new_post_name = get_post($post_id)->post_name;
        update_option('otp_success_page_slug', $new_post_name);
    }
}