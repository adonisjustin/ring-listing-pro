<?php
/**
 * Plugin Name: Ring Showcase Pro
 * Plugin URI: https://yourwebsite.com
 * Description: Professional ring showcase with ACF integration, inspired by La Joya Jewelry
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ring-showcase-pro
 * Requires: Advanced Custom Fields
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RSP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RSP_VERSION', '1.0.0');

class RingShowcasePro {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'check_acf_dependency'));
        
        // AJAX handlers
        add_action('wp_ajax_rsp_load_rings', array($this, 'ajax_load_rings'));
        add_action('wp_ajax_nopriv_rsp_load_rings', array($this, 'ajax_load_rings'));
        
        // Shortcodes
        add_shortcode('ring_showcase', array($this, 'ring_showcase_shortcode'));
        add_shortcode('ring_grid_simple', array($this, 'ring_grid_simple_shortcode'));
    }
    
    public function init() {
        // Check if ACF is active
        if (!function_exists('get_field')) {
            return;
        }
        
        // Include classes
        require_once RSP_PLUGIN_PATH . 'includes/class-ring-data.php';
        require_once RSP_PLUGIN_PATH . 'includes/class-ring-display.php';
        require_once RSP_PLUGIN_PATH . 'includes/class-ring-filters.php';
    }
    
    public function check_acf_dependency() {
        if (!function_exists('get_field')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Ring Showcase Pro:</strong> This plugin requires Advanced Custom Fields to be installed and activated.';
            echo '</p></div>';
        }
    }
    
    public function enqueue_scripts() {
        global $post;
        
        // Only load on pages with shortcodes or ring archive
        $should_load = false;
        
        if (is_post_type_archive('ring')) {
            $should_load = true;
        }
        
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'ring_showcase') || 
            has_shortcode($post->post_content, 'ring_grid_simple')
        )) {
            $should_load = true;
        }
        
        if (!$should_load) {
            return;
        }
        
        wp_enqueue_style(
            'ring-showcase-pro', 
            RSP_PLUGIN_URL . 'assets/css/ring-showcase.css', 
            array(), 
            RSP_VERSION
        );
        
        wp_enqueue_script(
            'ring-showcase-pro', 
            RSP_PLUGIN_URL . 'assets/js/ring-showcase.js', 
            array('jquery'), 
            RSP_VERSION, 
            true
        );
        
        wp_localize_script('ring-showcase-pro', 'rsp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rsp_nonce'),
            'debug' => WP_DEBUG
        ));
        
        // Font Awesome
        wp_enqueue_style(
            'font-awesome', 
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
        );
    }
    
    public function ring_showcase_shortcode($atts) {
        if (!function_exists('get_field')) {
            return '<p style="color: red;">Advanced Custom Fields is required for Ring Showcase Pro.</p>';
        }
        
        $atts = shortcode_atts(array(
            'posts_per_page' => 20,
            'show_filters' => 'true',
            'show_search' => 'true',
            'columns' => 3,
            'style' => 'lajoya' // lajoya style layout
        ), $atts);
        
        $display = new RSP_Ring_Display();
        return $display->render_showcase($atts);
    }
    
    public function ring_grid_simple_shortcode($atts) {
        if (!function_exists('get_field')) {
            return '<p style="color: red;">Advanced Custom Fields is required for Ring Showcase Pro.</p>';
        }
        
        $atts = shortcode_atts(array(
            'posts_per_page' => 12,
            'columns' => 4,
            'categories' => '',
            'vendors' => '',
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts);
        
        $display = new RSP_Ring_Display();
        return $display->render_simple_grid($atts);
    }
    
    public function ajax_load_rings() {
        check_ajax_referer('rsp_nonce', 'nonce');
        
        if (!function_exists('get_field')) {
            wp_send_json_error('ACF not available');
        }
        
        $ring_data = new RSP_Ring_Data();
        $ring_display = new RSP_Ring_Display();
        
        $params = array(
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'filters' => $_POST['filters'] ?? array()
        );
        
        $query = $ring_data->get_rings($params);
        
        $response = array(
            'success' => true,
            'rings' => array(),
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $params['page']
        );
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $response['rings'][] = $ring_display->render_ring_card();
            }
            wp_reset_postdata();
        }
        
        wp_send_json($response);
    }
}

// Initialize plugin
new RingShowcasePro();

// Activation hook
register_activation_hook(__FILE__, function() {
    if (!function_exists('get_field')) {
        wp_die('Ring Showcase Pro requires Advanced Custom Fields plugin to be installed and activated.');
    }
});

// Disable Gutenberg completely
function disable_gutenberg_completely($use_block_editor, $post_type) {
    return false;
}
add_filter('use_block_editor_for_post_type', 'disable_gutenberg_completely', 10, 2);