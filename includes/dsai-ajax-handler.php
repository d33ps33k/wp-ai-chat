<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Handle AJAX request
add_action('wp_ajax_ds_chatbot_send_message', 'ds_chatbot_send_message');
add_action('wp_ajax_nopriv_ds_chatbot_send_message', 'ds_chatbot_send_message');


function ds_chatbot_send_message() {
    // Verify nonce with sanitization
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'ds_chatbot_nonce')) {
        wp_send_json_error(
            esc_html__('Invalid request. Please refresh the page and try again.', 'ai-chat-bot-d33ps33k'),
            403
        );
    }

    // Get and sanitize input
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    // Validate message length
    if (strlen($message) > 15000) {
        wp_send_json_error(
            sprintf(
                /* translators: %d: Maximum allowed message length in characters */
                esc_html__('Message exceeds maximum length of %d characters', 'ai-chat-bot-d33ps33k'),
                15000
            )
        );
    }

    // Enhanced security checks with error handling
    try {
        $disallowed_patterns = [
            // Script tags
            '/<script\b[^>]*>(.*?)<\/script>/is',
            
            // Event handlers
            '/\bon\w+\s*=\s*["\']?[^"\'>]*["\']?/i',
            
            // Dangerous protocols
            '/(javascript|vbscript|data):/i',
            
            // Iframe/object/embed tags
            '/<(iframe|object|embed)[^>]*>/i',
            
            // Style tags and attributes
            '/<style\b[^>]*>(.*?)<\/style>/is',
            '/style\s*=\s*["\']?[^"\'>]*["\']?/i',
            
            // HTML comments
            '/<!--.*?-->/s',
            
            // Base64 encoded content
            '/base64\s*:/i'
        ];

        foreach ($disallowed_patterns as $pattern) {
            if (@preg_match($pattern, $message) === 1) {
                wp_send_json_error('Invalid message content detected');
            }
        }
    } catch (Exception $e) {
        wp_send_json_error(
            esc_html__('Security validation error', 'ai-chat-bot-d33ps33k')
        );
    }

    // Additional security checks
    if (empty($message) || !is_string($message)) {
        wp_send_json_error(__('Invalid message format', 'ai-chat-bot-d33ps33k'));
    }

    // Get the API key, endpoint, and system role from the plugin settings
    $api_key = get_option('ds_chatbot_api_key');
    $endpoint = get_option('ds_chatbot_endpoint');
    $system_role = get_option('ds_chatbot_system_role', 'You are a helpful assistant.');

    // Check if API credentials are set
    if (empty($api_key) || empty($endpoint)) {
        wp_send_json_error(
            esc_html__('API credentials not configured. Please check plugin settings.', 'ai-chat-bot-d33ps33k')
        );
    }

    // Build the knowledge base context 
    $kb_context = sprintf(
        /* translators: %s: System role */
        __('Your response should be in the role of "%s" ', 'ai-chat-bot-d33ps33k'),
        esc_html($system_role)
    );

    // Prepare the API request
    $model = get_option('ds_chatbot_model', 'deepseek-chat');
    $request_body = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $kb_context,
            ],
            [
                'role' => 'user',
                'content' => $message,
            ],
        ],
    ];

    // Add caching for API responses
    $cache_key = 'ds_chatbot_response_' . md5($message);
    if ($cached_response = get_transient($cache_key)) {
        return $cached_response;
    }

    // Send request to AI API
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($request_body),
        'timeout' => 30, // Increased timeout
    ]);

    // Check for errors in the API response
    if (is_wp_error($response)) {
        wp_send_json_error(
            sprintf(
                /* translators: %s: Error message from API */
                esc_html__('API request failed: %s', 'ai-chat-bot-d33ps33k'),
                esc_html($response->get_error_message())
            )
        );
    }

    // Retrieve the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check if the response contains valid data
    if (isset($data['choices'][0]['message']['content'])) {
        $response_content = $data['choices'][0]['message']['content'];
        $response_content = str_replace(["\r\n", "\r"], "\n", $response_content);
        
        // Apply formatting
        $formatted_content = ds_chatbot_format_bot_response($response_content);
        wp_send_json_success($formatted_content);
    } else {
        if (isset($data['error']['message'])) {
            wp_send_json_error(
                sprintf(
                    /* translators: %s: Error message from API */
                    esc_html__('API Error: %s', 'ai-chat-bot-d33ps33k'),
                    esc_html($data['error']['message'])
                )
            );
        } else {
            wp_send_json_error(
                esc_html__('Invalid API response format. Please check your API configuration.', 'ai-chat-bot-d33ps33k')
            );
        }
    }

    // Set cache for API response
    set_transient($cache_key, $response, HOUR_IN_SECONDS);
}

function ds_chatbot_format_bot_response($text) {
    // First, normalize all line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Handle the specific pattern of dashes with spaces
    $text = preg_replace('/(\n\s*-{2,}\s*\n){1,}/', "\n", $text);
    
    // Then handle general multiple newlines
    $text = preg_replace('/(\n\s*){3,}/', "\n\n", $text);
    
    // Trim and continue with existing processing
    $text = trim($text);
    
    // 1. FIRST: Extract and protect all code blocks with more robust pattern
    $code_blocks = [];
    $text = preg_replace_callback('/```(?:[a-z]*\n)?(.*?)```/s', function($matches) use (&$code_blocks) {
        $placeholder = '%%CODEBLOCK_' . count($code_blocks) . '%%';
        // Store the code content with preserved newlines
        $code_blocks[] = trim($matches[1]);
        return $placeholder;
    }, $text);

    // 2. SECOND: Convert inline code
    $text = preg_replace_callback('/`(.*?)`/', function($matches) {
        return '<code>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</code>';
    }, $text);

    // 3. THEN: Process other markdown elements
    $text = preg_replace('/####\s*(.*)/', '<h4>$1</h4>', $text);
    $text = preg_replace('/###\s*(.*)/', '<h3>$1</h3>', $text);
    $text = preg_replace('/##\s*(.*)/', '<h2>$1</h2>', $text);
    $text = preg_replace('/#\s*(.*)/', '<h1>$1</h1>', $text);

    // 4. FINALLY: Restore code blocks with proper escaping
    $text = preg_replace_callback('/%%CODEBLOCK_(\d+)%%/', function($matches) use ($code_blocks) {
        $index = (int)$matches[1];
        if (isset($code_blocks[$index])) {
            // Escape HTML but preserve formatting
            $code_content = htmlspecialchars($code_blocks[$index], ENT_QUOTES, 'UTF-8');
            // Remove any accidental HTML tags using WordPress function
            $code_content = wp_strip_all_tags($code_content);
            // Preserve whitespace and newlines
            return '<pre><code>' . $code_content . '</code></pre>';
        }
        return '';
    }, $text);

    // Add this more aggressive pattern to remove all <p>---</p> completely
    $text = preg_replace('/\s*<p>\s*-{2,}\s*<\/p>\s*/', '', $text);
    
    // Add this pattern to remove any remaining standalone dashed lines
    $text = preg_replace('/\n\s*-{2,}\s*\n/', "\n", $text);
    
    // Convert bold and italic
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

    // Convert images - updated pattern
    $text = preg_replace_callback(
        '/!\[(.*?)\]\((.*?)\)/',
        function($matches) {
            $alt = esc_attr($matches[1]);
            $src = esc_url($matches[2]);
            return '<img src="' . $src . '" alt="' . $alt . '" class="chat-image" loading="lazy">';
        },
        $text
    );

    // Convert markdown links to proper HTML
    $text = preg_replace_callback(
        '/\[(.*?)\]\((.*?)\)/',
        function($matches) {
            // Check if it's an image link
            if (preg_match('/\.(png|jpg|jpeg|webp)(\?.*)?$/i', $matches[2])) {
                $alt = esc_attr($matches[1]);
                $src = esc_url($matches[2]);
                return '<img src="' . $src . '" alt="' . $alt . '" class="chat-image" loading="lazy">';
            }
            // Regular link
            $text = esc_html($matches[1]);
            $url = esc_url($matches[2]);
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
        },
        $text
    );

    // Convert ordered lists with proper nesting
    $text = preg_replace_callback('/(\n\s*\d+\.\s+.*)+/', function($matches) {
        $items = preg_split('/\n\s*\d+\.\s+/', trim($matches[0]));
        array_shift($items); // Remove first empty item
        $list = '<ol>';
        foreach ($items as $item) {
            // Handle nested unordered lists within ordered lists
            if (preg_match('/-\s+(.*)/', $item)) {
                $nestedItems = preg_split('/\n\s*-\s+/', trim($item));
                $list .= '<li>';
                $list .= array_shift($nestedItems); // First item is the main point
                if (count($nestedItems) > 0) {
                    $list .= '<ul>';
                    foreach ($nestedItems as $nestedItem) {
                        $list .= '<li>' . trim($nestedItem) . '</li>';
                    }
                    $list .= '</ul>';
                }
                $list .= '</li>';
            } else {
                $list .= '<li>' . trim($item) . '</li>';
            }
        }
        $list .= '</ol>';
        return $list;
    }, $text);

    // Convert unordered lists with proper nesting
    $text = preg_replace_callback('/(\n\s*-\s+.*)+/', function($matches) {
        $items = preg_split('/\n\s*-\s+/', trim($matches[0]));
        array_shift($items); // Remove first empty item
        $list = '<ul>';
        foreach ($items as $item) {
            // Handle nested ordered lists within unordered lists
            if (preg_match('/\d+\.\s+(.*)/', $item)) {
                $nestedItems = preg_split('/\n\s*\d+\.\s+/', trim($item));
                $list .= '<li>';
                $list .= array_shift($nestedItems); // First item is the main point
                if (count($nestedItems) > 0) {
                    $list .= '<ol>';
                    foreach ($nestedItems as $nestedItem) {
                        $list .= '<li>' . trim($nestedItem) . '</li>';
                    }
                    $list .= '</ol>';
                }
                $list .= '</li>';
            } else {
                $list .= '<li>' . trim($item) . '</li>';
            }
        }
        $list .= '</ul>';
        return $list;
    }, $text);

    // Convert line breaks to paragraphs
    $text = wpautop($text);

    // Clean up any empty paragraphs
    $text = str_replace('<p></p>', '', $text);

    // Fix any malformed HTML
    $text = force_balance_tags($text);

    // Convert YouTube links to embedded videos
    $text = preg_replace_callback(
        '/\[(.*?)\]\((https:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]+))\)/',
        function($matches) {
            $title = $matches[1];
            $videoId = $matches[3];
            return '<div class="video-container">'
                . '<iframe src="https://www.youtube.com/embed/'.$videoId.'" '
                . 'title="'.$title.'" frameborder="0" '
                . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" '
                . 'allowfullscreen></iframe>'
                . '</div>';
        },
        $text
    );

    return $text;
}