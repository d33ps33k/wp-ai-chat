/**
 * AI Chat Bot D33ps33k Frontend Script
 * 
 * All user-facing strings are localized through wp_localize_script()
 * See ai-chat-bot-d33ps33k.php for localization setup
 * 
 * @package AI_Chat_Bot_Agent_D33ps33k
 * @since 1.0.0
 */

// Wrap everything in a jQuery document ready function
(function($) {
    // Initialize chatbot functionality
    $(document).ready(function() {
        // Verify required variables are defined
        if (typeof ds_chatbot_vars === 'undefined') {
            return;
        }

        // Event handler for send button
        $('#ds-chatbot-send').on('click', function() {
            const message = $('#ds-chatbot-input').val().trim();
            
            // Validate message length
            if (message.length > 15000) {
                $('#ds-chatbot-messages').append(
                    `<div class="bot-message">${ds_chatbot_vars.message_too_long}</div>`
                );
                return;
            }
            
            // Basic XSS protection
            if (/<[^>]*script.*?>.*?<\/script>/gi.test(message)) {
                $('#ds-chatbot-messages').append(
                    `<div class="bot-message">${ds_chatbot_vars.invalid_content}</div>`
                );
                return;
            }

            if (message) {
                $('#ds-chatbot-messages').append(`<div class="user-message">${message}</div>`);
                $('#ds-chatbot-input').val('');
                sendMessage(message);
            }
        });

        // Handle Enter key press
        $('#ds-chatbot-input').on('keypress', function(e) {
            if (e.which === 13) {
                $('#ds-chatbot-send').click();
            }
        });
    });

    /**
     * Send message to chatbot and handle response
     * @param {string} message - User's message to send
     */
    function sendMessage(message) {
        // Show typing indicator
        $('#ds-chatbot-typing-indicator').html(`
            <div class="typing-indicator-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `).show();

        // Make AJAX request
        $.ajax({
            url: ds_chatbot_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ds_chatbot_send_message',
                message: message,
                _ajax_nonce: ds_chatbot_vars.nonce
            },
            beforeSend: function() {
                // Disable input during request
                $('#ds-chatbot-input').prop('disabled', true);
                $('#ds-chatbot-send').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Create a temporary container for the HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = response.data;
                    
                    // Sanitize the HTML content
                    const sanitizedHTML = tempDiv.innerHTML;
                    
                    // Append the sanitized content
                    $('#ds-chatbot-messages').append(
                        $('<div>').addClass('bot-message').html(sanitizedHTML)
                    );
                    
                    // Process links
                    $('.bot-message a').each(function() {
                        const href = $(this).attr('href');
                        if ($(this).text() === href) {
                            $(this).html(href);
                        }
                        $(this).attr('target', '_blank');
                        $(this).attr('rel', 'noopener noreferrer');
                    });
                } else {
                    // Handle error
                    const errorMsg = response.data || 'An error occurred';
                    $('#ds-chatbot-messages').append(
                        $('<div>').addClass('bot-message').text(errorMsg)
                    );
                }
                
                // Scroll to bottom
                $('#ds-chatbot-messages').scrollTop($('#ds-chatbot-messages')[0].scrollHeight);
            },
            error: function(xhr) {
                let errorMsg = ds_chatbot_vars.request_error;
                if (xhr.status === 403) {
                    errorMsg = ds_chatbot_vars.session_expired;
                    // Optionally refresh the nonce
                    if (xhr.responseJSON && xhr.responseJSON.new_nonce) {
                        ds_chatbot_vars.nonce = xhr.responseJSON.new_nonce;
                    }
                }
                $('#ds-chatbot-messages').append(`<div class="bot-message error">${errorMsg}</div>`);
            },
            complete: function() {
                // Re-enable input and scroll to bottom
                $('#ds-chatbot-typing-indicator').hide();
                $('#ds-chatbot-input').prop('disabled', false).focus();
                $('#ds-chatbot-send').prop('disabled', false);
                $('#ds-chatbot-messages').scrollTop($('#ds-chatbot-messages')[0].scrollHeight);
            }
        });
    }
})(jQuery);