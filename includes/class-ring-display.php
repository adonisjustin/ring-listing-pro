<?php

class RSP_Ring_Display
{

    public function render_showcase($atts)
    {
        $ring_data = new RSP_Ring_Data();
        $ring_filters = new RSP_Ring_Filters();

        ob_start();
        ?>
        <div class="rsp-showcase lajoya-style" data-config="<?php echo esc_attr(json_encode($atts)); ?>">

            <div class="rsp-showcase-container">
                <h1 class="rsp-showcase-title">Best Deals</h1>
            </div>

            <!-- Top Controls Bar -->
            <div class="rsp-top-bar">
                <div class="rsp-controls-left">
                    <?php if ($atts['show_search'] === 'true'): ?>
                        <div class="rsp-search-box">
                            <input type="text" id="rsp-search" placeholder="Search rings..." />
                            <button type="button" id="rsp-search-btn"><i class="fa fa-search"></i></button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rsp-controls-right">
                    <div class="rsp-results-count">
                        Showing <span id="rsp-count">0</span> rings
                    </div>

                    <?php if ($atts['show_filters'] === 'true'): ?>
                        <button type="button" id="rsp-filter-toggle" class="rsp-filter-btn">
                            <i class="fa fa-sliders-h"></i> Filters
                            <span class="rsp-filter-badge" style="display: none;">0</span>
                        </button>
                    <?php endif; ?>

                    <select id="rsp-per-page" class="rsp-per-page">
                        <option value="12" <?php selected($atts['posts_per_page'], '12'); ?>>12 per page</option>
                        <option value="20" <?php selected($atts['posts_per_page'], '20'); ?>>20 per page</option>
                        <option value="30" <?php selected($atts['posts_per_page'], '30'); ?>>30 per page</option>
                        <option value="50" <?php selected($atts['posts_per_page'], '50'); ?>>50 per page</option>
                    </select>

                    <select id="rsp-sort" class="rsp-sort">
                        <option value="menu_order-asc">Default Order (Custom)</option>
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                        <!-- <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option> -->
                        <option value="title-asc">Name: A to Z</option>
                    </select>
                </div>
            </div>

            <?php if ($atts['show_filters'] === 'true'): ?>
                <!-- Filters Panel -->
                <div id="rsp-filters" class="rsp-filters">
                    <?php echo $ring_filters->render_filters(); ?>
                </div>
            <?php endif; ?>

            <!-- Skeleton Loader (shown on initial load) -->
            <div class="rsp-skeleton-grid">
                <?php for ($i = 0; $i < 12; $i++): ?>
                    <div class="rsp-skeleton-card">
                        <div class="rsp-skeleton-image"></div>
                        <div class="rsp-skeleton-content">
                            <div class="rsp-skeleton-title"></div>
                            <div class="rsp-skeleton-title short"></div>
                            <div class="rsp-skeleton-meta">
                                <div class="rsp-skeleton-badge"></div>
                                <div class="rsp-skeleton-badge"></div>
                            </div>
                            <div class="rsp-skeleton-price"></div>
                            <div class="rsp-skeleton-vendor"></div>
                            <div class="rsp-skeleton-button"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Ring Grid -->
            <div id="rsp-grid" class="rsp-grid rsp-columns-<?php echo esc_attr($atts['columns']); ?>">
                <!-- Rings loaded here via AJAX -->
            </div>

            <!-- Loading & States -->
            <!-- <div id="rsp-loading" class="rsp-loading">
                <div class="rsp-spinner"></div>
                <p>Loading rings...</p>
            </div> -->

            <div id="rsp-no-results" class="rsp-no-results" style="display: none;">
                <div class="no-results-content">
                    <i class="fa fa-search fa-3x"></i>
                    <h3>No rings found</h3>
                    <p>Try adjusting your search criteria or filters</p>
                    <button type="button" id="rsp-clear-all" class="rsp-clear-btn">Clear All Filters</button>
                </div>
            </div>

            <!-- Load More -->
            <div class="rsp-load-more-container show">
                <button type="button" id="rsp-load-more" class="rsp-load-more">
                    <i class="fa fa-plus"></i> Load More Rings
                </button>
            </div>

            <!-- All Loaded Message (NEW) -->
            <div class="rsp-all-loaded" style="display: none;">
                <div class="rsp-all-loaded-content">
                    <i class="fa fa-check-circle"></i>
                    <p>You've reached the end! All rings have been loaded.</p>
                </div>
            </div>
        </div>

        <!-- Quick View Modal -->
        <div id="rsp-modal" class="rsp-modal" style="display: none;">
            <div class="rsp-modal-overlay"></div>
            <div class="rsp-modal-content">
                <button class="rsp-modal-close">&times;</button>
                <div id="rsp-modal-body">
                    <!-- Quick view content loaded here -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_simple_grid($atts)
    {
        $ring_data = new RSP_Ring_Data();
        $rings = $ring_data->get_rings($atts);

        ob_start();
        ?>
        <div class="rsp-simple-grid">
            <div class="rsp-grid rsp-columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php
                if ($rings->have_posts()) {
                    while ($rings->have_posts()) {
                        $rings->the_post();
                        echo $this->render_ring_card();
                    }
                    wp_reset_postdata();
                } else {
                    echo '<div class="rsp-no-rings"><p>No rings found.</p></div>';
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_ring_card()
    {
        $ring_id = get_the_ID();

        // Get ACF fields - these are now TEXT fields
        $ring_colors = get_field('ring_colors', $ring_id);
        $ring_carat = get_field('ring_carat', $ring_id); // TEXT field (e.g., "1.5 CT" or "1.5 Carat")
        $ring_price = get_field('ring_price', $ring_id); // TEXT field (e.g., "$2,500" or "2500 USD")
        $ring_vendor = get_field('ring_vendor', $ring_id);
        $ring_vendor_url = get_field('ring_vendor_url', $ring_id);

        // Extract numeric values for filtering/sorting
        $price_numeric = preg_replace('/[^0-9.]/', '', $ring_price);
        $carat_numeric = preg_replace('/[^0-9.]/', '', $ring_carat);

        // Ensure ring_colors is an array
        if (!$ring_colors || !is_array($ring_colors)) {
            $ring_colors = array();
        }

        // Find default color or first color
        $default_color = null;
        $first_color = null;

        foreach ($ring_colors as $color) {
            if ($first_color === null) {
                $first_color = $color;
            }
            if (!empty($color['is_default'])) {
                $default_color = $color;
                break;
            }
        }

        $display_color = $default_color ?: $first_color;

        // Prepare JSON data
        $json_data = array(
            'id' => intval($ring_id),
            'title' => get_the_title(),
            'price' => $ring_price, // Keep original text
            'price_numeric' => floatval($price_numeric),
            'carat' => $ring_carat, // Keep original text
            'carat_numeric' => floatval($carat_numeric),
            'vendor' => $ring_vendor ?: '',
            'vendor_url' => $ring_vendor_url ?: '',
            'colors' => $ring_colors
        );

        ob_start();
        ?>
        <div class="rsp-ring-card" data-ring-id="<?php echo esc_attr($ring_id); ?>">
            <div class="rsp-ring-image-wrapper">

                <!-- Main Image Container -->
                <div class="rsp-ring-images">
                    <?php if ($display_color && !empty($display_color['front_image'])): ?>
                        <img class="rsp-main-image active"
                            src="<?php echo esc_url($display_color['front_image']['sizes']['medium'] ?? $display_color['front_image']['url']); ?>"
                            alt="<?php echo esc_attr(get_the_title()); ?>" />
                    <?php else: ?>
                        <div class="rsp-no-image">No image available</div>
                    <?php endif; ?>

                    <?php if ($display_color && !empty($display_color['back_image'])): ?>
                        <img class="rsp-hover-image"
                            src="<?php echo esc_url($display_color['back_image']['sizes']['medium'] ?? $display_color['back_image']['url']); ?>"
                            alt="<?php echo esc_attr(get_the_title()); ?> - Alternate View" />
                    <?php endif; ?>
                </div>

                <!-- Color Options -->
                <?php if (count($ring_colors) > 1): ?>
                    <div class="rsp-color-swatches">
                        <?php foreach ($ring_colors as $index => $color): ?>
                            <button type="button" class="rsp-color-swatch <?php echo !empty($color['is_default']) ? 'active' : ''; ?>"
                                data-color-index="<?php echo esc_attr($index); ?>"
                                style="background-color: <?php echo esc_attr($color['color_code'] ?? '#ccc'); ?>"
                                title="<?php echo esc_attr($color['color_name'] ?? ''); ?>">
                                <span class="sr-only"><?php echo esc_html($color['color_name'] ?? ''); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Badge/Sale indicator -->
                <?php if ($price_numeric && $price_numeric < 1000): ?>
                    <div class="rsp-price-badge">Under $1K</div>
                <?php endif; ?>
            </div>

            <!-- Ring Info -->
            <div class="rsp-ring-info">
                <h3 class="rsp-ring-title">
                    <a href="<?php echo esc_url($ring_vendor_url ?: '#'); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html(get_the_title()); ?>
                    </a>
                </h3>

                <div class="rsp-ring-meta">
                    <?php if ($ring_carat): ?>
                        <span class="rsp-carat"><?php echo esc_html($ring_carat); ?></span>
                    <?php endif; ?>

                    <?php if ($display_color && !empty($display_color['color_name'])): ?>
                        <span class="rsp-color-name"><?php echo esc_html($display_color['color_name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="rsp-price-section">
                    <?php if ($ring_price): ?>
                        <div class="rsp-price">
                            <span class="rsp-amount">$ <?php echo esc_html($ring_price); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($ring_vendor): ?>
                        <div class="rsp-vendor">on <?php echo esc_html($ring_vendor); ?></div>
                    <?php endif; ?>
                </div>

                <div class="rsp-ring-actions">
                    <?php if ($ring_vendor_url): ?>
                        <a href="<?php echo esc_url($ring_vendor_url); ?>" target="_blank" rel="noopener" class="rsp-view-ring">
                            View Ring <i class="fa fa-external-link-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hidden JSON data for JavaScript -->
            <div class="rsp-ring-data" style="display: none;"
                data-ring='<?php echo esc_attr(json_encode($json_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>'>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}