<?php
/**
 * Plugin Settings Page
 * 
 * @package AI_Chat_Bot_Agent_D33ps33k
 * @since 1.0.0
 */
// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
add_action('admin_menu', 'ds_chatbot_admin_menu');

function ds_chatbot_admin_menu() {
    add_menu_page(
        __('D33PS33K Chatbot Settings', 'ai-chat-bot-d33ps33k'),
        __('D33PS33K Chatbot', 'ai-chat-bot-d33ps33k'),
        'manage_options',
        'ds-chatbot-settings',
        'ds_chatbot_settings_page',
        'dashicons-format-chat',
        100
    );
    
    // Register settings when the menu is created
    add_action('admin_init', 'ds_chatbot_register_settings');
}

function ds_chatbot_register_settings() {
    // 1. Uninstall Settings
    register_setting('ds_chatbot_options_group', 'ds_chatbot_delete_on_uninstall', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));
   
    // 2. API Configurations
    register_setting('ds_chatbot_options_group', 'ds_chatbot_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    register_setting('ds_chatbot_options_group', 'ds_chatbot_endpoint', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw'
    ));
    
    register_setting(
        'ds_chatbot_options_group', 
        'ds_chatbot_system_role',
        array(
            'type' => 'string',
            'sanitize_callback' => 'ds_chatbot_validate_system_role',
            'default' => __('You are a helpful AI assistant.', 'ai-chat-bot-d33ps33k'),
            'show_in_rest' => true // Add REST API support
        )
    );
    
    register_setting('ds_chatbot_options_group', 'ds_chatbot_model', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    // 3. UI Options
    register_setting('ds_chatbot_options_group', 'ds_chatbot_title', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    register_setting(
        'ds_chatbot_options_group',
        'ds_chatbot_user_color',
        array(
            'sanitize_callback' => 'ds_chatbot_update_settings',
            'default' => '#0000ff'
        )
    );
    
    register_setting(
        'ds_chatbot_options_group',
        'ds_chatbot_bot_color',
        array(
            'sanitize_callback' => 'ds_chatbot_update_settings',
            'default' => '#ff0000'
        )
    );
    
    register_setting(
        'ds_chatbot_options_group',
        'ds_chatbot_user_text_color',
        array(
            'sanitize_callback' => 'ds_chatbot_update_settings',
            'default' => '#ffffff'
        )
    );
    
    register_setting(
        'ds_chatbot_options_group',
        'ds_chatbot_bot_text_color',
        array(
            'sanitize_callback' => 'ds_chatbot_update_settings',
            'default' => '#ffffff'
        )
    );
    
    register_setting('ds_chatbot_options_group', 'ds_chatbot_input_placeholder', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    register_setting('ds_chatbot_options_group', 'ds_chatbot_opening_prompt', array(
        'type' => 'string',
        'sanitize_callback' => 'wp_kses_post' // Allows basic HTML
    ));
}

// Settings page content
function ds_chatbot_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'ai-chat-bot-d33ps33k'));
    }

    // 1. Uninstall Settings Section
    add_settings_section(
        'ds_chatbot_uninstall_section',
        __('Uninstall Settings', 'ai-chat-bot-d33ps33k'),
        function() {
            echo '<p>' . esc_html__('Manage plugin uninstallation settings.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings'
    );

    add_settings_field(
        'ds_chatbot_delete_on_uninstall',
        __('Delete Settings on Uninstall', 'ai-chat-bot-d33ps33k'),
        'ds_chatbot_delete_on_uninstall_field',
        'ds-chatbot-settings',
        'ds_chatbot_uninstall_section'
    );

    // 2. API Configurations Section
    add_settings_section(
        'ds_chatbot_api_section',
        __('API Configurations', 'ai-chat-bot-d33ps33k'),
        function() {
            echo '<p>' . esc_html__('Configure API settings for the chatbot.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings'
    );

    add_settings_field(
        'ds_chatbot_api_key',
        __('API Key', 'ai-chat-bot-d33ps33k'),
        'ds_chatbot_api_key_field',
        'ds-chatbot-settings',
        'ds_chatbot_api_section'
    );

    add_settings_field(
        'ds_chatbot_endpoint',
        __('API Endpoint', 'ai-chat-bot-d33ps33k'),
        'ds_chatbot_endpoint_field',
        'ds-chatbot-settings',
        'ds_chatbot_api_section'
    );

    // Add model field to API Configurations section
    add_settings_field(
        'ds_chatbot_model',
        __('AI Model', 'ai-chat-bot-d33ps33k'),
        function() {
            $current_model = get_option('ds_chatbot_model', 'deepseek-chat');
            $models = [
                'deepseek-chat' => 'DeepSeek Chat (deepseek-chat)',
                'gpt-4o-mini' => 'GPT-4 Mini (gpt-4o-mini)',
                'gpt-4' => 'GPT-4 (gpt-4)',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (gpt-3.5-turbo)',
            ];
            echo '<select name="ds_chatbot_model" class="regular-text">';
            foreach ($models as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Select which AI model to use for responses.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_api_section'
    );

    // System Role field to API Configurations section
    add_settings_field(
        'ds_chatbot_system_role',
        __('System Role', 'ai-chat-bot-d33ps33k'),
        function() {
            $system_role = get_option('ds_chatbot_system_role');
            echo '<textarea name="ds_chatbot_system_role" class="large-text" rows="5">' . esc_textarea($system_role) . '</textarea>';
            echo '<p class="description">' . esc_html__('Define the AI\'s system role and behavior (max 500 words). This helps guide how the AI responds to users.', 'ai-chat-bot-d33ps33k') . '</p>';
            echo '<div class="description" style="color: #2271b1; font-weight: bold;">';
            printf(
                /* translators: %1$s: Opening span tag, %2$s: Closing span tag, %3$s: Upgrade link */
                esc_html__('Want unlimited system role length? Or better yet, %1$sthe full knowledge base?%2$s %3$s', 'ai-chat-bot-d33ps33k'),
                '<span style="font-size: 120%">',  // %1$s
                '</span>',                         // %2$s
                '<a href="' . esc_url('https://d33ps33k.com/product/ai-chat-bot-d33ps33k-wp-plugin-short-code-full-v1/') . '" target="_blank" rel="noopener noreferrer" style="font-size: 177%">' . 
                esc_html__('Upgrade to Pro', 'ai-chat-bot-d33ps33k') . 
                '</a>'                             // %3$s
            );
            echo '</div>';
            echo '<p class="description">' .
                esc_html__('For video and image formatting, you can add this to your system role (without quotes): "Responses shall include a picture or video at message end. Use video and img src urls exactly as shown on a line by themselves. iframe in youtube videos. Use html video tags for YOURDOMAIN.com video"', 'ai-chat-bot-d33ps33k') .
            '</p>';
            echo '<p class="description" style="color: #2271b1; font-weight: bold; font-size: 121%;">' . 
                sprintf(
                    /* translators: %s: Documentation link (do not translate the URL) */
                    esc_html__('Learn more here: %s', 'ai-chat-bot-d33ps33k'),
                    '<a href="https://D33PS33K.com/system-role#system-role-faqs" target="_blank" rel="noopener noreferrer">D33PS33K.com/system-role</a>'
                ) . 
            '</p>';
            echo '<div id="system_role_word_count" style="margin-top: 5px; color: #666;">' . esc_html__('Current word count:', 'ai-chat-bot-d33ps33k') . ' <span>0</span></div>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_api_section'
    );

    // 3. UI Options Section
    add_settings_section(
        'ds_chatbot_ui_section',
        __('UI Options', 'ai-chat-bot-d33ps33k'),
        function() {
            echo '<p>' . esc_html__('Customize the chatbot\'s user interface.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings'
    );

    add_settings_field(
        'ds_chatbot_title',
        __('Chatbot Title', 'ai-chat-bot-d33ps33k'),
        function() {
            $option = get_option('ds_chatbot_title', __('Chat with Us', 'ai-chat-bot-d33ps33k'));
            echo '<input type="text" name="ds_chatbot_title" value="' . esc_attr($option) . '" class="regular-text" />';
            echo '<p class="description">' . esc_html__('Enter the title for the chatbot (e.g., "Chat with Us").', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    add_settings_field(
        'ds_chatbot_user_color',
        __('User Message Color', 'ai-chat-bot-d33ps33k'),
        function() {
            $color = get_option('ds_chatbot_user_color', '#0000ff');
            echo '<input type="color" name="ds_chatbot_user_color" value="' . esc_attr($color) . '" />';
            echo '<p class="description">' . esc_html__('Choose the color for user messages.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    add_settings_field(
        'ds_chatbot_user_text_color',
        __('User Message Text Color', 'ai-chat-bot-d33ps33k'),
        function() {
            $color = get_option('ds_chatbot_user_text_color', '#ffffff');
            echo '<input type="color" name="ds_chatbot_user_text_color" value="' . esc_attr($color) . '" />';
            echo '<p class="description">' . esc_html__('Choose the text color for user messages.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    add_settings_field(
        'ds_chatbot_bot_color',
        __('Bot Message Color', 'ai-chat-bot-d33ps33k'),
        function() {
            $color = get_option('ds_chatbot_bot_color', '#ff0000');
            echo '<input type="color" name="ds_chatbot_bot_color" value="' . esc_attr($color) . '" />';
            echo '<p class="description">' . esc_html__('Choose the color for bot messages.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    add_settings_field(
        'ds_chatbot_bot_text_color',
        __('Bot Message Text Color', 'ai-chat-bot-d33ps33k'),
        function() {
            $color = get_option('ds_chatbot_bot_text_color', '#ffffff');
            echo '<input type="color" name="ds_chatbot_bot_text_color" value="' . esc_attr($color) . '" />';
            echo '<p class="description">' . esc_html__('Choose the text color for bot messages.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    add_settings_field(
        'ds_chatbot_input_placeholder',
        __('Input Placeholder Text', 'ai-chat-bot-d33ps33k'),
        function() {
            $placeholder = get_option('ds_chatbot_input_placeholder', __('Type your message...', 'ai-chat-bot-d33ps33k'));
            echo '<input type="text" name="ds_chatbot_input_placeholder" value="' . esc_attr($placeholder) . '" class="regular-text" />';
            echo '<p class="description">' . esc_html__('Enter the placeholder text for the input field.', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    add_settings_field(
        'ds_chatbot_opening_prompt',
        __('Opening Prompt', 'ai-chat-bot-d33ps33k'),
        function() {
            $opening_prompt = get_option('ds_chatbot_opening_prompt');
            echo '<textarea name="ds_chatbot_opening_prompt" class="large-text" rows="3">' . esc_textarea($opening_prompt) . '</textarea>';
            echo '<p class="description">' . esc_html__('Define the opening prompt for the chatbot (e.g., "Hello! How can I assist you today?").', 'ai-chat-bot-d33ps33k') . '</p>';
        },
        'ds-chatbot-settings',
        'ds_chatbot_ui_section'
    );

    // Added proper sanitization
    function ds_chatbot_sanitize_system_role($input) {
        $allowed_tags = wp_kses_allowed_html('post');
        return wp_kses($input, $allowed_tags);
    }

    // Added nonce verification (should be in form submission handling)
    wp_nonce_field('ds_chatbot_options_group-options');

    // Display the settings page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('D33PS33K Chatbot Settings', 'ai-chat-bot-d33ps33k'); ?></h1>
        <p><?php 
            echo esc_html__('This plugin is designed to help you create a chatbot for your website. It uses the DeepSeek or OpenAI API to generate responses. For the D33PS33K Full Version. Visit ', 'ai-chat-bot-d33ps33k');
            echo '<a href="' . esc_url('https://d33ps33k.com') . '" target="_blank" rel="noopener noreferrer">';
            echo esc_html__('d33ps33k.com', 'ai-chat-bot-d33ps33k');
            echo '</a>';
        ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields('ds_chatbot_options_group');
            do_settings_sections('ds-chatbot-settings');
            submit_button(__('Save Settings', 'ai-chat-bot-d33ps33k'));
            ?>
        </form>
    </div>
    <?php
}

// Field functions
function ds_chatbot_delete_on_uninstall_field() {
    $delete_on_uninstall = get_option('ds_chatbot_delete_on_uninstall', false);
    echo '<label><input type="checkbox" name="ds_chatbot_delete_on_uninstall" value="1" ' . checked(1, $delete_on_uninstall, false) . ' /> ' . esc_html__('Delete all settings when uninstalling the plugin', 'ai-chat-bot-d33ps33k') . '</label>';
    echo '<p class="description">' . esc_html__('If checked, all plugin settings will be permanently deleted when the plugin is uninstalled.', 'ai-chat-bot-d33ps33k') . '</p>';
}

function ds_chatbot_api_key_field() {
    $api_key = get_option('ds_chatbot_api_key');
    echo '<input type="text" name="ds_chatbot_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__('Enter your API key for the chatbot service. Need help? Visit D33pS33k.com for more information.', 'ai-chat-bot-d33ps33k') . '</p>';
}

function ds_chatbot_endpoint_field() {
    $endpoint = get_option('ds_chatbot_endpoint');
    echo '<input type="url" name="ds_chatbot_endpoint" value="' . esc_url($endpoint) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__('Enter the API endpoint URL for the chatbot service. Typically, https://api.deepseek.com/chat/completions or https://api.openai.com/v1/chat/completions', 'ai-chat-bot-d33ps33k') . '</p>';
}

// Add validation function
function ds_chatbot_validate_system_role($input) {
    // Check if input is empty
    if (empty($input)) {
        return __('You are a helpful AI assistant.', 'ai-chat-bot-d33ps33k');
    }

    // Improved word count calculation
    $word_count = count(preg_split('/\s+/', $input, -1, PREG_SPLIT_NO_EMPTY));
    
    if ($word_count > 500) {
        add_settings_error(
            'ds_chatbot_system_role',
            'system_role_word_limit',
            /* translators: %d: The current word count */
            sprintf(__('System role cannot exceed 500 words. Current word count: %d', 'ai-chat-bot-d33ps33k'), $word_count),
            'error'
        );
        return get_option('ds_chatbot_system_role'); // Return the old value
    }

    // Sanitize and return
    return sanitize_textarea_field($input);
}

// Remove the inline script from admin_footer hook and replace with proper enqueue
function ds_chatbot_enqueue_admin_scripts($hook) {
    // Only load on our plugin's settings page
    if ($hook !== 'toplevel_page_ds-chatbot-settings') {
        return;
    }

    // Register and enqueue the script without file dependency
    wp_register_script(
        'ds-chatbot-admin-js',
        false, // No external file
        array('jquery'),
        time(), // Use current timestamp as version
        true
    );

    // Add inline script
    $inline_script = "
        jQuery(document).ready(function($) {
            const textarea = $('textarea[name=\"ds_chatbot_system_role\"]');
            const wordCountDisplay = $('#system_role_word_count span');
            
            function updateWordCount() {
                const text = textarea.val();
                const wordCount = text.split(/\\s+/).filter(Boolean).length;
                wordCountDisplay.text(wordCount);
                
                if (wordCount > 500) {
                    wordCountDisplay.css('color', 'red');
                } else {
                    wordCountDisplay.css('color', '');
                }
            }
            
            textarea.on('input', updateWordCount);
            updateWordCount(); // Initial count
        });
    ";

    wp_add_inline_script('ds-chatbot-admin-js', $inline_script);
    wp_enqueue_script('ds-chatbot-admin-js');
}
add_action('admin_enqueue_scripts', 'ds_chatbot_enqueue_admin_scripts');

// Add this when saving settings to bust cache
function ds_chatbot_save_settings() {
    // Update style version with sanitized timestamp
    update_option(
        'ds_chatbot_style_version', 
        absint(time()) // Ensure integer value
    );
}

// Add this to your settings save function to bust cache
function ds_chatbot_update_settings($input) {
    // Sanitize input based on setting type
    if (current_filter() === 'pre_update_option_ds_chatbot_user_color' ||
        current_filter() === 'pre_update_option_ds_chatbot_bot_color') {
        $input = sanitize_hex_color($input);
    }
    elseif (current_filter() === 'pre_update_option_ds_chatbot_user_text_color' ||
            current_filter() === 'pre_update_option_ds_chatbot_bot_text_color') {
        $input = sanitize_hex_color($input);
    }
    
    // Update style version
    update_option(
        'ds_chatbot_style_version', 
        absint(time())
    );
    
    return $input;
}

// Add this near your other admin hooks to bust cache
add_action('update_option_ds_chatbot_user_color', 'ds_chatbot_save_settings');
add_action('update_option_ds_chatbot_user_text_color', 'ds_chatbot_save_settings');
add_action('update_option_ds_chatbot_bot_color', 'ds_chatbot_save_settings');
add_action('update_option_ds_chatbot_bot_text_color', 'ds_chatbot_save_settings');
