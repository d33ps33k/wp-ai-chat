<?php
// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

try {
    // Check if we should delete settings
    $delete_on_uninstall = get_option('ds_chatbot_delete_on_uninstall', false);
    
    if ($delete_on_uninstall) {        
        // Initialize options array
        $options = [];
        // Add core options
        $core_options = [
            'ds_chatbot_api_key',
            'ds_chatbot_endpoint',
            'ds_chatbot_model',
            'ds_chatbot_system_role',
            'ds_chatbot_opening_prompt',
            'ds_chatbot_title',
            'ds_chatbot_user_color',
            'ds_chatbot_user_text_color',
            'ds_chatbot_bot_color',
            'ds_chatbot_bot_text_color',
            'ds_chatbot_input_placeholder',
            'ds_chatbot_delete_on_uninstall',
        ];
        $options = array_merge($options, $core_options);

        // Delete all options safely
        foreach ($options as $option) {
            // Verify the option exists before attempting to delete
            if (get_option($option) !== false) {
                delete_option($option);
                
                // For multisite installations, delete site options as well
                if (is_multisite()) {
                    delete_site_option($option);
                }
            }
        }

        // Clean up transients using WordPress API
        $transient_prefix = 'ds_chatbot_';
        
        // Get all transients with our prefix
        $transients = get_option('_transient_' . $transient_prefix . '*');
        
        // Delete each transient properly
        if (is_array($transients)) {
            foreach ($transients as $transient_name) {
                // Remove the transient and its timeout
                delete_transient(str_replace('_transient_', '', $transient_name));
                
                // Clear from cache if it exists
                wp_cache_delete($transient_name, 'transient');
                wp_cache_delete('_transient_timeout_' . $transient_name, 'transient');
            }
        }

        // Clean up any potential cron jobs
        wp_clear_scheduled_hook('ds_chatbot_cron_hook');

    }
} catch (Exception $e) {
    wp_die(esc_html__('Uninstall failed: ', 'ai-chat-bot-d33ps33k') . esc_html($e->getMessage()));
} 