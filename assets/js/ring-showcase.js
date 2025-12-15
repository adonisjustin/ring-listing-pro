(function ($) {
    'use strict';

    class RingShowcase {
        constructor() {
            this.container = $('.rsp-showcase');
            this.grid = $('#rsp-grid');
            this.filters = {};
            this.searchQuery = '';
            this.currentPage = 1;
            this.perPage = 12;
            this.sortBy = 'menu_order-asc';
            this.loading = false;
            this.totalPages = 1;
            this.initialLoad = true;
            this.debugRingCards();

            if (this.container.length) {
                this.init();
            }
        }

        init() {
            this.bindEvents();
            this.setupPriceSliders();
            this.loadRings();
        }

        bindEvents() {
            // Search
            $('#rsp-search').on('input', $.proxy(this.debounce(this.handleSearch, 500), this));
            $('#rsp-search-btn').on('click', $.proxy(this.handleSearchClick, this));

            // Controls
            $('#rsp-per-page').on('change', $.proxy(this.changePerPage, this));
            $('#rsp-sort').on('change', $.proxy(this.changeSort, this));

            // Filter toggle
            $('#rsp-filter-toggle').on('click', this.toggleFilters);
            $('#rsp-close-filters').on('click', this.closeFilters);

            // Filter actions
            $('#rsp-apply-filters').on('click', $.proxy(this.applyFilters, this));
            $('#rsp-clear-filters, #rsp-clear-all').on('click', $.proxy(this.clearAllFilters, this));

            // Price inputs
            $('#rsp-price-min, #rsp-price-max').on('input', $.proxy(this.updatePriceFromInputs, this));
            $('#rsp-range-min, #rsp-range-max').on('input', $.proxy(this.updatePriceFromSliders, this));

            // Load more
            $('#rsp-load-more').on('click', $.proxy(this.loadMore, this));

            // Ring interactions
            $(document).on('mouseenter', '.rsp-ring-card', this.showHoverImage);
            $(document).on('mouseleave', '.rsp-ring-card', this.hideHoverImage);
            $(document).on('click', '.rsp-color-swatch', (e) => {
                this.switchColor(e);
            });
            $(document).on('click', '.rsp-quick-view', (e) => {
                this.openQuickView(e);
            });

            // Modal
            $(document).on('click', '.rsp-modal-close, .rsp-modal-overlay', this.closeModal);

            // Close filters when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#rsp-filters, #rsp-filter-toggle').length) {
                    $('#rsp-filters').removeClass('active');
                }
            });

            // Prevent filter panel close when clicking inside
            $('#rsp-filters').on('click', function (e) {
                e.stopPropagation();
            });
        }

        setupPriceSliders() {
            const $minRange = $('#rsp-range-min');
            const $maxRange = $('#rsp-range-max');

            // Sync sliders
            $minRange.on('input', function () {
                const min = parseInt($(this).val());
                const max = parseInt($maxRange.val());
                if (min > max - 100) {
                    $maxRange.val(min + 100);
                }
            });

            $maxRange.on('input', function () {
                const max = parseInt($(this).val());
                const min = parseInt($minRange.val());
                if (max < min + 100) {
                    $minRange.val(max - 100);
                }
            });
        }

        handleSearch() {
            this.searchQuery = $('#rsp-search').val().trim();
            this.currentPage = 1;
            this.loadRings();
        }

        handleSearchClick() {
            this.handleSearch();
        }

        changePerPage() {
            this.perPage = parseInt($('#rsp-per-page').val());
            this.currentPage = 1;
            this.loadRings();
        }

        changeSort() {
            this.sortBy = $('#rsp-sort').val();
            this.currentPage = 1;
            this.loadRings();
        }

        toggleFilters() {
            $('#rsp-filters').toggleClass('active');
            $('body').toggleClass('rsp-filters-open');
        }

        closeFilters() {
            $('#rsp-filters').removeClass('active');
            $('body').removeClass('rsp-filters-open');
        }

        updatePriceFromInputs() {
            const min = parseInt($('#rsp-price-min').val()) || 0;
            const max = parseInt($('#rsp-price-max').val()) || 2000;

            $('#rsp-range-min').val(min);
            $('#rsp-range-max').val(max);
            $('#rsp-price-min-display').text(min.toLocaleString());
            $('#rsp-price-max-display').text(max.toLocaleString());
        }

        updatePriceFromSliders() {
            const min = parseInt($('#rsp-range-min').val());
            const max = parseInt($('#rsp-range-max').val());

            $('#rsp-price-min').val(min);
            $('#rsp-price-max').val(max);
            $('#rsp-price-min-display').text(min.toLocaleString());
            $('#rsp-price-max-display').text(max.toLocaleString());
        }

        collectFilters() {
            this.filters = {};

            // Price
            const priceMin = parseInt($('#rsp-price-min').val());
            const priceMax = parseInt($('#rsp-price-max').val());
            const originalMin = parseInt($('#rsp-range-min').attr('min'));
            const originalMax = parseInt($('#rsp-range-max').attr('max'));

            if (priceMin > originalMin) this.filters.price_min = priceMin;
            if (priceMax < originalMax) this.filters.price_max = priceMax;

            // Checkboxes - UPDATED: replaced 'color' with 'metal_color'
            const filterTypes = ['carat', 'metal_color', 'vendor', 'diamond_shape', 'style', 'rings_category'];
            filterTypes.forEach(type => {
                const values = [];
                $(`input[name="${type}"]:checked`).each(function () {
                    values.push($(this).val());
                });
                if (values.length > 0) {
                    this.filters[type] = values;
                }
            });

            // Update filter badge
            const filterCount = Object.keys(this.filters).length;
            const $badge = $('.rsp-filter-badge');
            if (filterCount > 0) {
                $badge.text(filterCount).show();
                $('#rsp-filter-toggle').addClass('has-filters');
            } else {
                $badge.hide();
                $('#rsp-filter-toggle').removeClass('has-filters');
            }
        }

        applyFilters() {
            this.collectFilters();
            this.currentPage = 1;
            this.loadRings();
            this.closeFilters();
        }

        clearAllFilters() {
            // Reset all inputs
            $('#rsp-filters input[type="checkbox"]').prop('checked', false);
            $('#rsp-search').val('');

            // Reset price sliders
            const $minRange = $('#rsp-range-min');
            const $maxRange = $('#rsp-range-max');
            const originalMin = $minRange.attr('min');
            const originalMax = $maxRange.attr('max');

            $('#rsp-price-min').val(originalMin);
            $('#rsp-price-max').val(originalMax);
            $minRange.val(originalMin);
            $maxRange.val(originalMax);
            $('#rsp-price-min-display').text(parseInt(originalMin).toLocaleString());
            $('#rsp-price-max-display').text(parseInt(originalMax).toLocaleString());

            // Reset state
            this.filters = {};
            this.searchQuery = '';
            this.currentPage = 1;

            // Update UI
            $('.rsp-filter-badge').hide();
            $('#rsp-filter-toggle').removeClass('has-filters');
            $('.rsp-all-loaded').hide(); // Add this line

            // Reload
            this.loadRings();
            this.closeFilters();
        }

loadRings(append = false) {
    if (this.loading) return;
    
    this.loading = true;
    
    // Show loading overlay for filter changes (not initial load)
    if (!this.initialLoad && !append) {
        this.container.addClass('filtering');
    }
    
    if (!append) {
        this.grid.empty();
        $('#rsp-no-results').hide();
    }
    
    // Parse sort
    const [sortBy, sortOrder] = this.sortBy.split('-');
    
    // Determine orderby value for WordPress
    let orderby = sortBy;
    let metaKey = '';
    
    if (sortBy === 'price') {
        orderby = 'meta_value_num';
        metaKey = 'ring_price';
    }
    
    $.ajax({
        url: rsp_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'rsp_load_rings',
            nonce: rsp_ajax.nonce,
            page: this.currentPage,
            per_page: this.perPage,
            search: this.searchQuery,
            filters: this.filters,
            orderby: orderby,
            order: sortOrder.toUpperCase(),
            meta_key: metaKey
        },
        success: (response) => {
            this.handleLoadSuccess(response, append);
        },
        error: (xhr, status, error) => {
            this.handleLoadError(error);
        },
        complete: () => {
            this.loading = false;
            this.container.removeClass('filtering'); // Remove loading overlay
        }
    });
}

handleLoadSuccess(response, append) {
    console.log('AJAX Response:', response);
    
    if (response.success) {
        this.totalPages = response.pages;
        
        if (append) {
            response.rings.forEach(ring => {
                this.grid.append(ring);
            });
        } else {
            this.grid.html(response.rings.join(''));
        }
        
        // Mark as loaded on first successful load
        if (this.initialLoad) {
            this.container.addClass('loaded');
            this.initialLoad = false;
        }
        
        // Update results count
        $('#rsp-count').text(response.total.toLocaleString());
        
        // Show/hide load more and all loaded message
        const $loadMoreContainer = $('.rsp-load-more-container');
        const $allLoadedMessage = $('.rsp-all-loaded');
        
        if (this.currentPage >= response.pages) {
            // No more pages - hide button, show message
            $loadMoreContainer.removeClass('show');
            if (response.total > 0) {
                $allLoadedMessage.show();
            }
        } else {
            // More pages available - show button, hide message
            $loadMoreContainer.addClass('show');
            $allLoadedMessage.hide();
        }
        
        // Show no results
        if (response.total === 0) {
            $('#rsp-no-results').show();
            $loadMoreContainer.removeClass('show');
            $allLoadedMessage.hide();
        } else {
            $('#rsp-no-results').hide();
        }
        
        // Debug what was actually rendered
        setTimeout(() => {
            this.debugRingCards();
        }, 100);
        
        // Scroll to top if not appending
        if (!append && this.currentPage === 1 && !this.initialLoad) {
            $('html, body').animate({
                scrollTop: this.container.offset().top - 100
            }, 500);
        }
        
    } else {
        console.error('Error loading rings:', response);
        this.showError('Failed to load rings. Please try again.');
        this.container.addClass('loaded'); // Still mark as loaded to hide skeleton
    }
}



        handleLoadError(error) {
            console.error('AJAX error:', error);
            this.showError('Network error. Please check your connection.');
        }

showError(message) {
    const errorHtml = `
        <div class="rsp-error-message">
            <i class="fa fa-exclamation-triangle"></i>
            <span>${message}</span>
            <button onclick="location.reload()" class="rsp-retry-btn">Retry</button>
        </div>
    `;
    this.grid.html(errorHtml);
    this.container.addClass('loaded'); // Hide skeleton on error
}

        loadMore() {
            this.currentPage++;
            this.loadRings(true);
        }

        showHoverImage() {
            const $card = $(this);
            const $main = $card.find('.rsp-main-image');
            const $hover = $card.find('.rsp-hover-image');

            if ($hover.length && $hover.attr('src')) {
                $main.removeClass('active');
                $hover.addClass('active');
            }
        }

        hideHoverImage() {
            const $card = $(this);
            $card.find('.rsp-hover-image').removeClass('active');
            $card.find('.rsp-main-image').addClass('active');
        }

        // Add this method to the RingShowcase class for debugging
        debugRingCards() {
            console.log('=== Ring Cards Debug ===');
            $('.rsp-ring-card').each(function (index) {
                const $card = $(this);
                const ringId = $card.data('ring-id');
                const $dataElement = $card.find('.rsp-ring-data');

                console.log(`Card ${index}:`, {
                    ringId: ringId,
                    hasDataElement: $dataElement.length > 0,
                    dataAttr: $dataElement.attr('data-ring')?.substring(0, 100) + '...'
                });
            });
        }

        switchColor(e) {
            const $swatch = $(e.currentTarget);
            const $card = $swatch.closest('.rsp-ring-card');
            const colorIndex = $swatch.data('color-index');
            const $dataElement = $card.find('.rsp-ring-data');

            if ($dataElement.length === 0) {
                console.error('No ring data found for color switching');
                return;
            }

            try {
                const jsonText = $dataElement.attr('data-ring');
                const ringData = JSON.parse(jsonText);

                if (ringData.colors && ringData.colors[colorIndex]) {
                    const color = ringData.colors[colorIndex];

                    // Update active swatch
                    $swatch.siblings().removeClass('active');
                    $swatch.addClass('active');

                    // Update images
                    const $mainImg = $card.find('.rsp-main-image');
                    const $hoverImg = $card.find('.rsp-hover-image');

                    if (color.front_image) {
                        const frontSrc = color.front_image.sizes?.medium || color.front_image.url;
                        $mainImg.attr('src', frontSrc);
                    }

                    if (color.back_image) {
                        const backSrc = color.back_image.sizes?.medium || color.back_image.url;
                        $hoverImg.attr('src', backSrc);
                    }

                    // Update color name
                    if (color.color_name) {
                        $card.find('.rsp-color-name').text(color.color_name);
                    }
                }
            } catch (error) {
                console.error('Error switching color:', error);
            }
        }

        openQuickView(e) {
            const $clickedBtn = $(e.currentTarget);
            const ringId = $clickedBtn.data('ring-id');
            const $card = $clickedBtn.closest('.rsp-ring-card');
            const $dataElement = $card.find('.rsp-ring-data');

            // Debug logging
            console.log('Clicked button:', $clickedBtn);
            console.log('Ring ID:', ringId);
            console.log('Card found:', $card.length);
            console.log('Data element found:', $dataElement.length);

            if ($dataElement.length === 0) {
                console.error('No ring data element found');
                this.showQuickViewError('Ring data not available');
                return;
            }

            let ringData;
            try {
                const jsonText = $dataElement.attr('data-ring');
                console.log('Raw JSON from data-ring:', jsonText?.substring(0, 100) + '...');

                if (!jsonText) {
                    throw new Error('Empty JSON data');
                }
                ringData = JSON.parse(jsonText);
                console.log('Parsed ring data:', ringData);
            } catch (error) {
                console.error('Error parsing ring data:', error);
                console.error('Raw data:', $dataElement.attr('data-ring'));
                this.showQuickViewError('Unable to load ring details');
                return;
            }

            const modalContent = this.buildQuickViewContent(ringData);
            $('#rsp-modal-body').html(modalContent);
            $('#rsp-modal').fadeIn(300);
            $('body').addClass('rsp-modal-open');
        }

        showQuickViewError(message) {
            const errorContent = `
        <div class="rsp-quick-view-error">
            <div class="rsp-error-icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <h3>Unable to Load Ring Details</h3>
            <p>${message}</p>
            <button type="button" class="rsp-btn rsp-btn-primary" onclick="$('#rsp-modal').fadeOut(300); $('body').removeClass('rsp-modal-open');">
                Close
            </button>
        </div>
    `;
            $('#rsp-modal-body').html(errorContent);
            $('#rsp-modal').fadeIn(300);
            $('body').addClass('rsp-modal-open');
        }

        buildQuickViewContent(ring) {
            // Safely handle missing data
            const title = ring.title || 'Ring Details';
            const price = ring.price ? `$${ring.price.toLocaleString()}` : 'Price not available';
            const carat = ring.carat ? `${ring.carat} CT` : 'N/A';
            const vendor = ring.vendor || 'Unknown';

            // Safely get image
            let imageHtml = '<div class="rsp-no-image">No image available</div>';
            if (ring.colors && ring.colors.length > 0 && ring.colors[0]) {
                const color = ring.colors[0];
                if (color.front_image) {
                    const imgSrc = (color.front_image.sizes && color.front_image.sizes.large) ||
                        color.front_image.url || '';
                    if (imgSrc) {
                        imageHtml = `<img src="${imgSrc}" alt="${title}">`;
                    }
                }
            }

            return `
        <div class="rsp-quick-view-content">
            <div class="rsp-quick-view-images">
                ${imageHtml}
            </div>
            <div class="rsp-quick-view-details">
                <h3>${title}</h3>
                <div class="rsp-quick-price">${price}</div>
                <div class="rsp-quick-specs">
                    <div class="rsp-spec">
                        <strong>Carat:</strong> ${carat}
                    </div>
                    <div class="rsp-spec">
                        <strong>Vendor:</strong> ${vendor}
                    </div>
                </div>
                ${ring.vendor_url ?
                    `<a href="${ring.vendor_url}" target="_blank" class="rsp-view-vendor-btn">
                        <i class="fa fa-external-link-alt"></i> View on ${vendor}
                    </a>` :
                    '<p class="rsp-no-link">No store link available</p>'
                }
            </div>
        </div>
    `;
        }

        closeModal() {
            $('#rsp-modal').fadeOut(300);
            $('body').removeClass('rsp-modal-open');
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Initialize when ready
    $(document).ready(function () {
        window.ringShowcase = new RingShowcase();
    });

})(jQuery);