/**
 * AI Guidance Copy functionality
 */
(function($) {
    'use strict';

    // AI Guidance configuration loaded from PHP
    const aiGuidanceConfig = window.wpvAiGuidance || {};
    const errorMetadata = window.wpvErrorMetadata || {};

    $(document).ready(function() {
        // Handle Copy for AI button clicks
        $(document).on('click', '.copy-for-ai', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const errorCode = button.data('code');
            const originalMessage = button.data('message');
            
            // Get AI guidance for this error code
            const guidance = aiGuidanceConfig[errorCode];
            let textToCopy = originalMessage;
            
            if (guidance && guidance.ai_guidance) {
                textToCopy += ' AI: ' + guidance.ai_guidance;
            }
            
            // Copy to clipboard
            copyToClipboard(textToCopy).then(function() {
                // Show success feedback
                const originalText = button.text();
                button.text('Copied!').prop('disabled', true);
                
                setTimeout(function() {
                    button.text(originalText).prop('disabled', false);
                }, 2000);
            }).catch(function() {
                alert('Failed to copy to clipboard');
            });
        });
        
        // Process results to add icons when they're rendered
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).hasClass('plugin-check__results-row') || $(e.target).find('.plugin-check__results-row').length) {
                setTimeout(processResultRows, 100);
            }
        });
        
        // Also process on page load and after AJAX
        $(document).ajaxComplete(function() {
            setTimeout(processResultRows, 200);
        });
        
        // Process any existing rows on page load
        setTimeout(processResultRows, 500);
    });

    /**
     * Process result rows to add icons
     */
    function processResultRows() {
        $('.error-icon[data-code]').each(function() {
            const iconElement = $(this);
            
            if (iconElement.hasClass('processed')) {
                return;
            }
            
            const errorCode = iconElement.data('code');
            const metadata = errorMetadata[errorCode];
            
            if (metadata) {
                const iconHtml = getIconHtml(metadata);
                iconElement.html(iconHtml).addClass('processed');
            } else {
                iconElement.html('<span class="dashicons dashicons-warning" style="color: #666;"></span>').addClass('processed');
            }
        });
    }

    /**
     * Generate icon HTML from metadata
     */
    function getIconHtml(metadata) {
        const icon = metadata.icon || 'warning';
        const color = metadata.color || '#666';
        const description = metadata.description || '';
        
        return `<span class="dashicons dashicons-${icon}" style="color: ${color};" title="${description}"></span>`;
    }

    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            return new Promise(function(resolve, reject) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    textArea.remove();
                    resolve();
                } catch (error) {
                    textArea.remove();
                    reject(error);
                }
            });
        }
    }

})(jQuery);