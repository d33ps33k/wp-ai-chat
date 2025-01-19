<?php
// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Register shortcode using ds_chatbot as the prefix.
add_shortcode('ds-chat-bot', 'ds_chatbot_shortcode');

function ds_chatbot_shortcode() {
    // Get the Site Icon
    $site_icon = get_site_icon_url();
    $default_icon = plugins_url('assets/icon.svg', dirname(__FILE__));
    
    // Use site icon if available, otherwise use default
    $icon_url = $site_icon ? $site_icon : $default_icon;

    // Get the chatbot title
    $title = esc_html(get_option('ds_chatbot_title', __('Chat with Us', 'ai-chat-bot-d33ps33k')));

    // Get the opening prompt
    $opening_prompt = esc_html(get_option('ds_chatbot_opening_prompt', ''));

    // Get the input placeholder
    $placeholder = esc_attr(get_option('ds_chatbot_input_placeholder', __('Type your message...', 'ai-chat-bot-d33ps33k')));

    // Get colors
    $user_color = esc_attr(get_option('ds_chatbot_user_color', '#0073aa'));
    $user_text_color = esc_attr(get_option('ds_chatbot_user_text_color', '#ffffff'));
    $bot_color = esc_attr(get_option('ds_chatbot_bot_color', '#8d0303'));
    $bot_text_color = esc_attr(get_option('ds_chatbot_bot_text_color', '#ffffff'));

    // Get the style version from options
    $style_version = get_option('ds_chatbot_style_version', '1.0');

    /*
        wp_register_style(
            'ds-chatbot-styles',
            plugins_url('css/dsai-chatbot.css', dirname(__FILE__)),
            array(),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'css/dsai-chatbot.css')
        );
        wp_enqueue_style('ds-chatbot-styles');
    */

    // Add dynamic styles using wp_add_inline_style
    $dynamic_css = "
        .user-message {
            background: {$user_color} !important;
            color: {$user_text_color} !important;
        }
        .bot-message {
            background: {$bot_color} !important;
            color: {$bot_text_color} !important;
        }
        .bot-message a,
        .bot-message a:hover,
        .bot-message a:visited,
        .bot-message a:active {
            color: {$bot_text_color} !important;
        }
    ";

    wp_add_inline_style('ds-chatbot-styles', $dynamic_css);

    // Chatbot HTML without inline styles
    ob_start();
    ?>
    <div id="ds-chatbot-container">
        <div id="ds-chatbot-header">
            <img src="<?php echo esc_url($icon_url); ?>" alt="<?php esc_attr_e('Chatbot Icon', 'ai-chat-bot-d33ps33k'); ?>" />
            <h3><?php echo esc_html($title); ?></h3>
        </div>
        <div id="ds-chatbot-messages">
            <?php if (!empty($opening_prompt)) : ?>
                <div class="bot-message"><?php echo wp_kses_post($opening_prompt); ?></div>
            <?php endif; ?>
        </div>
        <div id="ds-chatbot-typing-indicator"></div>
        <div id="ds-chatbot-input-area">
            <input type="text" id="ds-chatbot-input" placeholder="<?php echo esc_attr($placeholder); ?>" />
            <button id="ds-chatbot-send"><?php esc_html_e('Send', 'ai-chat-bot-d33ps33k'); ?></button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function ds_chatbot_enqueue_styles() {
    // Register and enqueue external CSS
    wp_register_style(
        'ds-chatbot-styles',
        plugins_url('css/dsai-chatbot.css', dirname(__FILE__)),
        array(),
        filemtime(plugin_dir_path(dirname(__FILE__)) . 'css/dsai-chatbot.css')
    );
    wp_enqueue_style('ds-chatbot-styles');
    
    // Get color settings
    $user_color = get_option('ds_chatbot_user_color', '#0000ff');
    $user_text_color = get_option('ds_chatbot_user_text_color', '#ffffff');
    $bot_color = get_option('ds_chatbot_bot_color', '#ff0000');
    $bot_text_color = get_option('ds_chatbot_bot_text_color', '#ffffff');

    // Add inline styles
    $dynamic_css = "
        .user-message {
            background: {$user_color} !important;
            color: {$user_text_color} !important;
        }
        .bot-message {
            background: {$bot_color} !important;
            color: {$bot_text_color} !important;
        }
        .bot-message a,
        .bot-message a:hover,
        .bot-message a:visited,
        .bot-message a:active {
            color: {$bot_text_color} !important;
        }
    ";
    wp_add_inline_style('ds-chatbot-styles', $dynamic_css);
}
add_action('wp_enqueue_scripts', 'ds_chatbot_enqueue_styles');