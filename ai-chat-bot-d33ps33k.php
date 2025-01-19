<?php
/*
Plugin Name: AI Chat Bot Agent D33PS33K
Description: A plugin to integrate a trainable AI chatbot agent into your WordPress site using a shortcode. The full version is available at d33ps33k.com
Version: 1.0.0
Author: d33ps33k
Text Domain: ai-chat-bot-d33ps33k
Domain Path: /languages/
Plugin URI: https://www.d33ps33k.com
Author URI: https://www.d33ps33k.com/author/blue/
License: GPL2
Requires at least: 5.0.17
Tested up to: 6.7.1
Requires PHP: 5.6
@package AI_Chat_Bot_Agent_D33ps33k
*/

// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/dsai-admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/dsai-shortcode-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/dsai-ajax-handler.php';

// Enqueue scripts and styles
function ds_chatbot_enqueue_scripts() {
    // Enqueue the JavaScript file
    wp_enqueue_script(
        'ds-chatbot-script',
        plugins_url('js/dsai-chatbot.js', __FILE__),
        array('jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'js/dsai-chatbot.js'),
        true
    );

    // Localize script to pass PHP variables to JavaScript
    wp_localize_script(
        'ds-chatbot-script',
        'ds_chatbot_vars',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ds_chatbot_nonce'),
            'error_message' => __('An error occurred', 'ai-chat-bot-d33ps33k'),
            'message_too_long' => __('Error: Message exceeds 15000 character limit', 'ai-chat-bot-d33ps33k'),
            'invalid_content' => __('Error: Invalid message content', 'ai-chat-bot-d33ps33k'),
            'generic_error' => __('An error occurred', 'ai-chat-bot-d33ps33k'),
            'request_error' => __('Unable to process your request', 'ai-chat-bot-d33ps33k'),
            'session_expired' => __('Session expired, please refresh the page', 'ai-chat-bot-d33ps33k')
        )
    );
}
add_action('wp_enqueue_scripts', 'ds_chatbot_enqueue_scripts');

function ds_chatbot_uninstall() {
    // Define the constant manually if it's not defined
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        define('WP_UNINSTALL_PLUGIN', true);
    }
    
    try {
        // Include the uninstall file
        $uninstall_file = plugin_dir_path(__FILE__) . 'dsai-uninstall.php';
        
        if (file_exists($uninstall_file)) {
            require_once $uninstall_file;
        } else {
            wp_die('Uninstall file not found');
        }
    } catch (Exception $e) {
        wp_die(esc_html__('Uninstall failed: ', 'ai-chat-bot-d33ps33k') . esc_html($e->getMessage()));
    }
}
register_uninstall_hook(__FILE__, 'ds_chatbot_uninstall');

// Add security headers for plugin-specific requests
add_action('wp_headers', function($headers) {
    // Verify nonce before processing
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'ds_chatbot_send_message') {
        if (!isset($_REQUEST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_ajax_nonce'])), 'ds_chatbot_nonce')) {
            return $headers; // Return headers unchanged if nonce verification fails
        }

        // Only add security headers if nonce verification passes
        $headers['Content-Security-Policy'] = "default-src 'self'";
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['X-Frame-Options'] = 'DENY';
        $headers['X-XSS-Protection'] = '1; mode=block';
    }
    return $headers;
});