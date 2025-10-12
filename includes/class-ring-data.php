<?php

class RSP_Ring_Data {
    
    public function get_rings($params = array()) {
        $defaults = array(
            'post_type' => 'ring',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'page' => 1,
            'search' => '',
            'filters' => array(),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $params = wp_parse_args($params, $defaults);
        
        $args = array(
            'post_type' => 'ring',
            'post_status' => 'publish',
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['page'],
            'orderby' => $params['orderby'],
            'order' => $params['order'],
            'meta_query' => array('relation' => 'AND')
        );
        
        // Search
        if (!empty($params['search'])) {
            $args['s'] = $params['search'];
        }
        
        // Apply filters
        if (!empty($params['filters'])) {
            $this->apply_filters($args, $params['filters']);
        }
        
        // Category filter (for shortcode)
        if (!empty($params['categories'])) {
            $categories = explode(',', $params['categories']);
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'rings-category',
                    'field' => 'slug',
                    'terms' => array_map('trim', $categories)
                )
            );
        }
        
        // Vendor filter (for shortcode)
        if (!empty($params['vendors'])) {
            $vendors = explode(',', $params['vendors']);
            $args['meta_query'][] = array(
                'key' => 'ring_vendor',
                'value' => array_map('trim', $vendors),
                'compare' => 'IN'
            );
        }
        
        return new WP_Query($args);
    }
    
private function apply_filters(&$args, $filters) {
    // Price range filter
    if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
        $price_min = !empty($filters['price_min']) ? intval($filters['price_min']) : 0;
        $price_max = !empty($filters['price_max']) ? intval($filters['price_max']) : 999999;
        
        $args['meta_query'][] = array(
            'key' => 'ring_price',
            'value' => array($price_min, $price_max),
            'type' => 'NUMERIC',
            'compare' => 'BETWEEN'
        );
    }
    
    // Carat range filter
    if (!empty($filters['carat'])) {
        $carat_queries = array('relation' => 'OR');
        
        foreach ($filters['carat'] as $range) {
            switch ($range) {
                case '0.5-1':
                    $carat_queries[] = array(
                        'key' => 'ring_carat',
                        'value' => array(0.5, 1),
                        'type' => 'DECIMAL',
                        'compare' => 'BETWEEN'
                    );
                    break;
                case '1-1.5':
                    $carat_queries[] = array(
                        'key' => 'ring_carat',
                        'value' => array(1, 1.5),
                        'type' => 'DECIMAL',
                        'compare' => 'BETWEEN'
                    );
                    break;
                case '1.5-2':
                    $carat_queries[] = array(
                        'key' => 'ring_carat',
                        'value' => array(1.5, 2),
                        'type' => 'DECIMAL',
                        'compare' => 'BETWEEN'
                    );
                    break;
                case '2+':
                    $carat_queries[] = array(
                        'key' => 'ring_carat',
                        'value' => 2,
                        'type' => 'DECIMAL',
                        'compare' => '>='
                    );
                    break;
            }
        }
        
        if (count($carat_queries) > 1) {
            $args['meta_query'][] = $carat_queries;
        }
    }
    
    // Color filter
    if (!empty($filters['color'])) {
        $post_ids = $this->get_rings_by_colors($filters['color']);
        if (!empty($post_ids)) {
            $args['post__in'] = $post_ids;
        } else {
            $args['post__in'] = array(0);
        }
    }
    
    // Vendor filter
    if (!empty($filters['vendor'])) {
        $args['meta_query'][] = array(
            'key' => 'ring_vendor',
            'value' => $filters['vendor'],
            'compare' => 'IN'
        );
    }
    
    // Initialize tax_query if needed
    if (!isset($args['tax_query'])) {
        $args['tax_query'] = array('relation' => 'AND');
    }
    
    // Diamond Shape filter
    if (!empty($filters['diamond_shape'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'diamond-shape',
            'field' => 'slug',
            'terms' => $filters['diamond_shape']
        );
    }
    
    // Style filter
    if (!empty($filters['style'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'style',
            'field' => 'slug',
            'terms' => $filters['style']
        );
    }
    
    // Rings Category filter
    if (!empty($filters['rings_category'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'rings-category',
            'field' => 'slug',
            'terms' => $filters['rings_category']
        );
    }
}
    
    private function get_rings_by_colors($colors) {
        $post_ids = array();
        
        $all_rings = get_posts(array(
            'post_type' => 'ring',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($all_rings as $ring_id) {
            $ring_colors = get_field('ring_colors', $ring_id);
            if ($ring_colors && is_array($ring_colors)) {
                foreach ($ring_colors as $color) {
                    if (!empty($color['color_name']) && in_array($color['color_name'], $colors)) {
                        $post_ids[] = $ring_id;
                        break;
                    }
                }
            }
        }
        
        return array_unique($post_ids);
    }
    
    public function get_all_colors() {
        $colors = array();
        $color_cache = array();
        
        $rings = get_posts(array(
            'post_type' => 'ring',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($rings as $ring) {
            $ring_colors = get_field('ring_colors', $ring->ID);
            if ($ring_colors && is_array($ring_colors)) {
                foreach ($ring_colors as $color) {
                    if (!empty($color['color_name']) && !isset($color_cache[$color['color_name']])) {
                        $colors[] = array(
                            'name' => $color['color_name'],
                            'code' => $color['color_code'] ?: '#cccccc'
                        );
                        $color_cache[$color['color_name']] = true;
                    }
                }
            }
        }
        
        return $colors;
    }
    
    public function get_all_vendors() {
        global $wpdb;
        
        $vendors = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = 'ring' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'ring_vendor'
            AND pm.meta_value != ''
            ORDER BY pm.meta_value ASC
        ");
        
        return $vendors ?: array();
    }
    
    public function get_price_range() {
        global $wpdb;
        
        $result = $wpdb->get_row("
            SELECT 
                MIN(CAST(meta_value AS DECIMAL(10,2))) as min_price,
                MAX(CAST(meta_value AS DECIMAL(10,2))) as max_price
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = 'ring' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'ring_price'
            AND pm.meta_value != ''
        ");
        
        return array(
            'min' => $result ? intval($result->min_price) : 0,
            'max' => $result ? intval($result->max_price) : 2000
        );
    }
    
    public function get_ring_categories() {
        return get_terms(array(
            'taxonomy' => 'rings-category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
    }
}