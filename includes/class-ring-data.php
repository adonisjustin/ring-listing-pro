<?php

class RSP_Ring_Data
{

public function get_rings($params = array()) {
    $defaults = array(
        'post_type' => 'ring',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'page' => 1,
        'search' => '',
        'filters' => array(),
        'orderby' => 'menu_order', // Changed default to menu_order
        'order' => 'ASC' // Changed to ASC for menu_order
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
    
    // Handle price sorting
    if ($params['orderby'] === 'meta_value_num') {
        $args['meta_key'] = 'ring_price';
    }
    
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
    // Price range filter - UPDATED to handle text
    if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
        $price_min = !empty($filters['price_min']) ? intval($filters['price_min']) : 0;
        $price_max = !empty($filters['price_max']) ? intval($filters['price_max']) : 999999;
        
        // Use REGEXP to extract numbers from text fields
        global $wpdb;
        $ids_in_range = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'ring_price' 
            AND CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2)) BETWEEN %d AND %d
        ", $price_min, $price_max));
        
        if (!empty($ids_in_range)) {
            if (!isset($args['post__in'])) {
                $args['post__in'] = $ids_in_range;
            } else {
                $args['post__in'] = array_intersect($args['post__in'], $ids_in_range);
            }
        } else {
            $args['post__in'] = array(0); // No results
        }
    }
    
    // Carat range filter - UPDATED to handle text
    if (!empty($filters['carat'])) {
        global $wpdb;
        $carat_ids = array();
        
        foreach ($filters['carat'] as $range) {
            $range_ids = array();
            
            switch ($range) {
                case '0.5-1':
                    $range_ids = $wpdb->get_col("
                        SELECT DISTINCT post_id 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = 'ring_carat' 
                        AND CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2)) BETWEEN 0.5 AND 1
                    ");
                    break;
                case '1-1.5':
                    $range_ids = $wpdb->get_col("
                        SELECT DISTINCT post_id 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = 'ring_carat' 
                        AND CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2)) BETWEEN 1 AND 1.5
                    ");
                    break;
                case '1.5-2':
                    $range_ids = $wpdb->get_col("
                        SELECT DISTINCT post_id 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = 'ring_carat' 
                        AND CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2)) BETWEEN 1.5 AND 2
                    ");
                    break;
                case '2+':
                    $range_ids = $wpdb->get_col("
                        SELECT DISTINCT post_id 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = 'ring_carat' 
                        AND CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2)) >= 2
                    ");
                    break;
            }
            
            $carat_ids = array_merge($carat_ids, $range_ids);
        }
        
        if (!empty($carat_ids)) {
            $carat_ids = array_unique($carat_ids);
            if (!isset($args['post__in'])) {
                $args['post__in'] = $carat_ids;
            } else {
                $args['post__in'] = array_intersect($args['post__in'], $carat_ids);
            }
        } else {
            $args['post__in'] = array(0); // No results
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
    
    // Metal Color filter (Taxonomy)
    if (!empty($filters['metal_color'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'metal-color',
            'field' => 'slug',
            'terms' => $filters['metal_color']
        );
    }
}


    public function get_all_colors()
    {
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

    public function get_all_vendors()
    {
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
    
    // Extract numeric values from text fields
    $result = $wpdb->get_row("
        SELECT 
            MIN(CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2))) as min_price,
            MAX(CAST(REGEXP_REPLACE(meta_value, '[^0-9.]', '') AS DECIMAL(10,2))) as max_price
        FROM {$wpdb->postmeta} pm 
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
        WHERE p.post_type = 'ring' 
        AND p.post_status = 'publish' 
        AND pm.meta_key = 'ring_price'
        AND pm.meta_value != ''
        AND pm.meta_value REGEXP '[0-9]'
    ");
    
    return array(
        'min' => $result && $result->min_price ? intval($result->min_price) : 0,
        'max' => $result && $result->max_price ? intval($result->max_price) : 2000
    );
}

    public function get_ring_categories()
    {
        return get_terms(array(
            'taxonomy' => 'rings-category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
    }
}