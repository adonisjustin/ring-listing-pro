<?php
/**
 * Force External Browser Opening from Social Media Apps
 */

class RSP_Social_Redirect {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_meta_tags'), 1);
        add_action('wp_footer', array($this, 'add_redirect_script'));
        add_filter('the_content', array($this, 'modify_ring_links'));
    }
    
    /**
     * Add meta tags to force external browser
     */
    public function add_meta_tags() {
        // Only on ring single pages or pages with ring shortcode
        if (!$this->should_apply_redirect()) {
            return;
        }
        ?>
        <!-- Force External Browser Opening -->
        <meta property="al:ios:url" content="<?php echo esc_url(get_permalink()); ?>" />
        <meta property="al:ios:app_store_id" content="" />
        <meta property="al:ios:app_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
        <meta property="al:android:url" content="<?php echo esc_url(get_permalink()); ?>" />
        <meta property="al:android:package" content="" />
        <meta property="al:android:app_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
        <meta property="al:web:url" content="<?php echo esc_url(get_permalink()); ?>" />
        <meta property="al:web:should_fallback" content="true" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="<?php echo esc_url(get_permalink()); ?>" />
        
        <!-- Open Graph -->
        <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>" />
        <?php
    }
    
    /**
     * Add JavaScript to detect and redirect from social media in-app browsers
     */
    public function add_redirect_script() {
        if (!$this->should_apply_redirect()) {
            return;
        }
        ?>
        <script>
        (function() {
            'use strict';
            
            /**
             * Detect if page is opened in social media in-app browser
             */
            function isInAppBrowser() {
                var ua = navigator.userAgent || navigator.vendor || window.opera;
                
                // Instagram
                if (ua.indexOf('Instagram') > -1) {
                    return 'instagram';
                }
                
                // Facebook (includes Messenger)
                if (ua.indexOf('FBAN') > -1 || ua.indexOf('FBAV') > -1 || ua.indexOf('FB_IAB') > -1) {
                    return 'facebook';
                }
                
                // TikTok
                if (ua.indexOf('musical_ly') > -1 || ua.indexOf('TikTok') > -1 || ua.indexOf('BytedanceWebview') > -1) {
                    return 'tiktok';
                }
                
                // Twitter
                if (ua.indexOf('Twitter') > -1) {
                    return 'twitter';
                }
                
                // LinkedIn
                if (ua.indexOf('LinkedInApp') > -1) {
                    return 'linkedin';
                }
                
                // Snapchat
                if (ua.indexOf('Snapchat') > -1) {
                    return 'snapchat';
                }
                
                // Pinterest
                if (ua.indexOf('Pinterest') > -1) {
                    return 'pinterest';
                }
                
                // WhatsApp
                if (ua.indexOf('WhatsApp') > -1) {
                    return 'whatsapp';
                }
                
                return false;
            }
            
            /**
             * Get instructions for opening in external browser
             */
            function getInstructions(platform) {
                var instructions = {
                    'instagram': 'Tap the three dots (•••) at the top right and select "Open in Browser"',
                    'facebook': 'Tap the three dots (•••) and select "Open in External Browser"',
                    'tiktok': 'Tap the three dots (•••) at the bottom right and select "Open in Browser"',
                    'twitter': 'Tap "Open in Browser" or the share icon and select your browser',
                    'linkedin': 'Tap the three dots and select "Open in External Browser"',
                    'snapchat': 'Swipe down and tap "Open in Browser"',
                    'pinterest': 'Tap the three dots and select "Open in Browser"',
                    'whatsapp': 'Tap the three dots and select "Open in Browser"'
                };
                
                return instructions[platform] || 'Please open this page in your mobile browser (Safari, Chrome, etc.)';
            }
            
            /**
             * Show banner with instructions
             */
            function showBanner(platform) {
                // Check if banner already shown
                if (document.getElementById('rsp-browser-banner')) {
                    return;
                }
                
                var banner = document.createElement('div');
                banner.id = 'rsp-browser-banner';
                banner.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 14px; line-height: 1.5; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
                
                var platformName = platform.charAt(0).toUpperCase() + platform.slice(1);
                var instruction = getInstructions(platform);
                
                banner.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="flex-shrink: 0; font-size: 24px;">⚠️</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; margin-bottom: 4px;">Open in Your Browser</div>
                            <div style="font-size: 13px; opacity: 0.95;">
                                For the best experience and to complete your purchase, please open this page in your mobile browser.
                            </div>
                            <div style="margin-top: 8px; padding: 8px 12px; background: rgba(255,255,255,0.2); border-radius: 6px; font-size: 12px;">
                                <strong>How:</strong> ${instruction}
                            </div>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" style="flex-shrink: 0; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 20px; padding: 4px 10px; cursor: pointer; border-radius: 4px; line-height: 1;">×</button>
                    </div>
                `;
                
                document.body.insertBefore(banner, document.body.firstChild);
                
                // Add padding to body to prevent content from being hidden
                document.body.style.paddingTop = (banner.offsetHeight + 10) + 'px';
                
                // Auto-hide after 15 seconds
                setTimeout(function() {
                    if (banner && banner.parentNode) {
                        banner.style.transition = 'opacity 0.5s';
                        banner.style.opacity = '0';
                        setTimeout(function() {
                            if (banner && banner.parentNode) {
                                banner.remove();
                                document.body.style.paddingTop = '0';
                            }
                        }, 500);
                    }
                }, 15000);
            }
            
            /**
             * Try to force open in external browser (iOS Safari)
             */
            function tryForceOpen() {
                var currentUrl = window.location.href;
                
                // For iOS, try to open in Safari
                if (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) {
                    // Try using custom scheme
                    var safariUrl = 'x-safari-' + currentUrl.replace('http://', '').replace('https://', '');
                    window.location.href = safariUrl;
                    
                    // Fallback: prompt user after 1 second
                    setTimeout(function() {
                        showBanner(isInAppBrowser());
                    }, 1000);
                } else {
                    // For Android and others, show banner
                    showBanner(isInAppBrowser());
                }
            }
            
            /**
             * Initialize
             */
            function init() {
                var platform = isInAppBrowser();
                
                if (platform) {
                    console.log('Detected ' + platform + ' in-app browser');
                    
                    // Try to force open immediately
                    tryForceOpen();
                    
                    // Also intercept all "View Ring" button clicks
                    document.addEventListener('click', function(e) {
                        var target = e.target.closest('.rsp-view-ring');
                        if (target) {
                            e.preventDefault();
                            var href = target.getAttribute('href');
                            
                            // Show alert with instructions
                            alert('To complete your purchase:\n\n1. Copy this link: ' + href + '\n2. Open it in your browser (Safari/Chrome)\n\nOr tap the menu and select "Open in Browser"');
                            
                            // Try to open in external browser
                            window.open(href, '_blank');
                            
                            return false;
                        }
                    });
                }
            }
            
            // Run on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
            
        })();
        </script>
        <?php
    }
    
    /**
     * Modify ring vendor links to include target="_blank" and special attributes
     */
    public function modify_ring_links($content) {
        if (!$this->should_apply_redirect()) {
            return $content;
        }
        
        // Add rel="noopener noreferrer" to all external links
        $content = preg_replace_callback(
            '/<a\s+([^>]*?)href=["\'](https?:\/\/[^"\']+)["\']([^>]*?)>/i',
            function($matches) {
                $attributes = $matches[1] . $matches[3];
                $url = $matches[2];
                
                // Check if it's an external link
                if (strpos($url, home_url()) === false) {
                    // Add target="_blank" if not present
                    if (strpos($attributes, 'target=') === false) {
                        $attributes .= ' target="_blank"';
                    }
                    
                    // Add rel="noopener noreferrer" if not present
                    if (strpos($attributes, 'rel=') === false) {
                        $attributes .= ' rel="noopener noreferrer"';
                    } else {
                        // Append to existing rel
                        $attributes = preg_replace('/rel=["\']([^"\']*)["\']/', 'rel="$1 noopener noreferrer"', $attributes);
                    }
                }
                
                return '<a ' . $attributes . ' href="' . $url . '">';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Check if redirect should apply
     */
    private function should_apply_redirect() {
        global $post;
        
        // Apply on ring post type
        if (is_singular('ring')) {
            return true;
        }
        
        // Apply on pages with ring shortcode
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'ring_showcase') || 
            has_shortcode($post->post_content, 'ring_grid_simple')
        )) {
            return true;
        }
        
        // Apply on ring archive
        if (is_post_type_archive('ring')) {
            return true;
        }
        
        return false;
    }
}

// Initialize
new RSP_Social_Redirect();