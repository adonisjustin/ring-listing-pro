<?php

class RSP_Ring_Filters {
    
    public function render_filters() {
        $ring_data = new RSP_Ring_Data();
        
        $colors = $ring_data->get_all_colors();
        $vendors = $ring_data->get_all_vendors();
        $price_range = $ring_data->get_price_range();
        $categories = $ring_data->get_ring_categories();
        
        // Get new taxonomies
        $diamond_shapes = $this->get_diamond_shapes();
        $ring_categories = $this->get_rings_categories();
        $styles = $this->get_styles();
        
        ob_start();
        ?>
        <div class="rsp-filters-container">
            <div class="rsp-filters-header">
                <h4><i class="fa fa-filter"></i> Filter Rings</h4>
                <button type="button" id="rsp-close-filters" class="rsp-close-filters">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="rsp-filters-grid">
                
                <!-- Diamond Shapes Filter with Images -->
                <?php if (!empty($diamond_shapes)): ?>
                <div class="rsp-filter-section">
                    <h5>Diamond Shapes</h5>
                    <div class="rsp-diamond-shapes-grid">
                        <?php foreach ($diamond_shapes as $shape): ?>
                        <label class="rsp-diamond-shape-item" title="<?php echo esc_attr($shape['name']); ?>">
                            <input type="checkbox" name="diamond_shape" value="<?php echo esc_attr($shape['slug']); ?>" />
                            <div class="rsp-diamond-shape-box">
                                <?php if (!empty($shape['image'])): ?>
                                <img src="<?php echo esc_url($shape['image']); ?>" alt="<?php echo esc_attr($shape['name']); ?>" />
                                <?php else: ?>
                                <div class="rsp-diamond-placeholder">
                                    <i class="fa fa-gem"></i>
                                </div>
                                <?php endif; ?>
                                <span class="rsp-diamond-name"><?php echo esc_html($shape['name']); ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Styles Filter -->
                <?php if (!empty($styles)): ?>
                <div class="rsp-filter-section">
                    <h5>Style</h5>
                    <div class="rsp-checkbox-list">
                        <?php foreach ($styles as $style): ?>
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="style" value="<?php echo esc_attr($style->slug); ?>">
                            <span class="rsp-checkmark"></span>
                            <?php echo esc_html($style->name); ?>
                            <span class="rsp-count">(<?php echo $style->count; ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Price Range Filter -->
                <div class="rsp-filter-section">
                    <h5>Price Range</h5>
                    <div class="rsp-price-filter">
                        <div class="rsp-price-inputs">
                            <input type="number" id="rsp-price-min" 
                                   placeholder="Min" 
                                   value="<?php echo $price_range['min']; ?>"
                                   min="<?php echo $price_range['min']; ?>"
                                   max="<?php echo $price_range['max']; ?>">
                            <span class="rsp-price-separator">to</span>
                            <input type="number" id="rsp-price-max" 
                                   placeholder="Max"
                                   value="<?php echo $price_range['max']; ?>"
                                   min="<?php echo $price_range['min']; ?>"
                                   max="<?php echo $price_range['max']; ?>">
                        </div>
                        <div class="rsp-price-range">
                            <input type="range" id="rsp-range-min" 
                                   min="<?php echo $price_range['min']; ?>" 
                                   max="<?php echo $price_range['max']; ?>" 
                                   value="<?php echo $price_range['min']; ?>" 
                                   step="50">
                            <input type="range" id="rsp-range-max" 
                                   min="<?php echo $price_range['min']; ?>" 
                                   max="<?php echo $price_range['max']; ?>" 
                                   value="<?php echo $price_range['max']; ?>" 
                                   step="50">
                        </div>
                        <div class="rsp-price-display">
                            $<span id="rsp-price-min-display"><?php echo number_format($price_range['min']); ?></span> - 
                            $<span id="rsp-price-max-display"><?php echo number_format($price_range['max']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Carat Filter -->
                <div class="rsp-filter-section">
                    <h5>Carat Weight</h5>
                    <div class="rsp-checkbox-list">
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="carat" value="0.5-1">
                            <span class="rsp-checkmark"></span>
                            0.5 - 1.0 CT
                        </label>
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="carat" value="1-1.5">
                            <span class="rsp-checkmark"></span>
                            1.0 - 1.5 CT
                        </label>
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="carat" value="1.5-2">
                            <span class="rsp-checkmark"></span>
                            1.5 - 2.0 CT
                        </label>
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="carat" value="2+">
                            <span class="rsp-checkmark"></span>
                            2.0+ CT
                        </label>
                    </div>
                </div>
                
                <!-- Metal Color Filter -->
                <?php if (!empty($colors)): ?>
                <div class="rsp-filter-section">
                    <h5>Metal Color</h5>
                    <div class="rsp-color-filters">
                        <?php foreach ($colors as $color): ?>
                        <label class="rsp-color-filter">
                            <input type="checkbox" name="color" value="<?php echo esc_attr($color['name']); ?>">
                            <span class="rsp-color-visual" style="background-color: <?php echo esc_attr($color['code']); ?>"></span>
                            <span class="rsp-color-text"><?php echo esc_html($color['name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Rings Category Filter -->
                <?php if (!empty($ring_categories)): ?>
                <div class="rsp-filter-section">
                    <h5>Ring Category</h5>
                    <div class="rsp-checkbox-list">
                        <?php foreach ($ring_categories as $category): ?>
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="rings_category" value="<?php echo esc_attr($category->slug); ?>">
                            <span class="rsp-checkmark"></span>
                            <?php echo esc_html($category->name); ?>
                            <span class="rsp-count">(<?php echo $category->count; ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Vendor Filter -->
                <?php if (!empty($vendors)): ?>
                <div class="rsp-filter-section">
                    <h5>Retailer</h5>
                    <div class="rsp-checkbox-list rsp-vendor-list">
                        <?php foreach ($vendors as $vendor): ?>
                        <label class="rsp-checkbox-item">
                            <input type="checkbox" name="vendor" value="<?php echo esc_attr($vendor); ?>">
                            <span class="rsp-checkmark"></span>
                            <?php echo esc_html($vendor); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Filter Actions -->
            <div class="rsp-filter-actions">
                <button type="button" id="rsp-clear-filters" class="rsp-btn rsp-btn-secondary">
                    <i class="fa fa-undo"></i> Clear All
                </button>
                <button type="button" id="rsp-apply-filters" class="rsp-btn rsp-btn-primary">
                    <i class="fa fa-check"></i> Apply Filters
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
private function get_diamond_shapes() {
    $shapes = get_terms(array(
        'taxonomy' => 'diamond-shape',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    if (is_wp_error($shapes) || empty($shapes)) {
        return array();
    }
    
    $result = array();
    foreach ($shapes as $shape) {
        $image_url = '';
        
        // Get ACF taxonomy field - correct field name from export
        if (function_exists('get_field')) {
            $image = get_field('diamond_shape_image', 'diamond-shape_' . $shape->term_id);
            if ($image && is_array($image)) {
                $image_url = $image['url'];
            } elseif ($image && is_string($image)) {
                $image_url = $image;
            }
        }
        
        // Fallback: Check for term meta
        if (empty($image_url)) {
            $image_id = get_term_meta($shape->term_id, 'diamond_shape_image', true);
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            }
        }
        
        $result[] = array(
            'name' => $shape->name,
            'slug' => $shape->slug,
            'count' => $shape->count,
            'image' => $image_url
        );
    }
    
    return $result;
}
    
    private function get_rings_categories() {
        return get_terms(array(
            'taxonomy' => 'rings-category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
    }
    
    private function get_styles() {
        return get_terms(array(
            'taxonomy' => 'style',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
    }
}