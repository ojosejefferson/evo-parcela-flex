jQuery(document).ready(function($) {
    // Check if settings were updated and force fragment refresh
    if (evoFlexData.settings_ver) {
        const lastVer = localStorage.getItem('evo_flex_settings_ver');
        if (lastVer && lastVer !== evoFlexData.settings_ver) {
            // Settings changed! Force WooCommerce to refresh fragments (mini-cart)
            console.log('Evo Parcela Flex: Settings updated, refreshing fragments...');
            $(document.body).trigger('wc_fragment_refresh');
            // Also clear sessionStorage just in case
            if (window.sessionStorage) {
                const bodyClass = jQuery('body').attr('class');
                if (bodyClass) {
                    const hashClass = bodyClass.split(' ').find(c => c && c.startsWith('wc-cart-fragments-hash-'));
                    if (hashClass) {
                        sessionStorage.removeItem('wc_fragments_' + hashClass);
                    }
                }
                sessionStorage.removeItem('wc_fragments_hash');
            }
        }
        localStorage.setItem('evo_flex_settings_ver', evoFlexData.settings_ver);
    }

    // Toggle installments table (SlideDown or Modal)
    $(document).on('click', '.evo-flex-toggle-installments', function(e) {
        e.preventDefault();
        const $wrapper = $(this).closest('.evo-flex-pricing-wrapper');
        const mode = $wrapper.data('table-mode') || evoFlexData.table_mode || 'toggle';

        if (mode === 'modal') {
            const $modal = $('#evo-flex-modal');
            const $inner = $('#evo-flex-modal-inner-content');
            
            // If inner content is empty (no Gutenberg custom content), fallback to standard table
            if (!$inner.html().trim()) {
                const $table = $wrapper.find('.evo-flex-all-installments').clone().show();
                $inner.html($table);
            }
            
            $modal.css('display', 'flex').hide().fadeIn(300);
            $('body').addClass('evo-flex-modal-open');
        } else {
            $(this).siblings('.evo-flex-all-installments').slideToggle();
        }
    });

    // Close Modal
    $(document).on('click', '.evo-flex-modal-close, .evo-flex-modal-overlay', function() {
        $('#evo-flex-modal').fadeOut(300);
        $('body').removeClass('evo-flex-modal-open');
    });

    let updateTimeout;
    function debounceUpdate(callback, delay = 100) {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(callback, delay);
    }

    // Capture WC variation change
    $(document).on('found_variation show_variation', 'form.variations_form', function(event, variation) {
        let price = 0;
        let variationId = 0;

        if (variation) {
            price = variation.display_price;
            variationId = variation.variation_id;
        }

        if (price) {
            debounceUpdate(() => {
                const currentQty = parseInt($('input.qty').val()) || 1;
                refreshGatewayInfo(variation.display_price, variation.variation_id, variation, currentQty);
            });
        }
    });

    // Reset to base price
    $(document).on('evo_flex_init_highlights', function() {
        const $pricingBox = $('.evo-flex-pricing-box, .evo-flex-pricing-wrapper, .evo-flex-best-installment-highlight').first();
        const basePrice = parseFloat($pricingBox.data('base-price'));
        const currentQty = parseInt($('input.qty').val()) || 1;
        if (basePrice) {
            refreshGatewayInfo(basePrice, 0, null, currentQty);
        }
    });

    // Optimized Batch AJAX Refresh
    function refreshGatewayInfo(price, variationId, variationData = null, qty = 1) {
        const containers = $('.evo-flex-pricing-box, .evo-flex-pricing-wrapper, .evo-flex-savings-highlight, .evo-flex-best-installment-highlight');
        if (!containers.length) return;

        // If we have pre-calculated HTML in variationData, use it instantly!
        if (variationData && variationData.evo_flex_html) {
            containers.each(function() {
                const $container = $(this);
                const gatewayId = $container.data('gateway') || 'pix';
                const displayType = $container.data('display') || 'full';
                const key = gatewayId + '_' + displayType;
                
                if (variationData.evo_flex_html[key]) {
                    $container.html(variationData.evo_flex_html[key]);
                }
                $container.css('opacity', '1'); // Always reset opacity
            });
            return; // STOP HERE, NO AJAX NEEDED!
        }

        const items = [];
        const $containerList = [];

        containers.each(function(index) {
            const $container = $(this);
            const parentId = $container.data('product-id');
            const gatewayId = $container.data('gateway') || 'pix';
            const displayType = $container.data('display') || 'full';
            const productIdToUse = variationId || parentId;

            items.push({
                gateway_id: gatewayId,
                display_type: displayType,
                product_id: productIdToUse,
                qty: qty
            });
            
            $containerList.push($container);
            $container.css('opacity', '0.5');
        });

        $.ajax({
            url: evoFlexData.ajax_url,
            type: 'POST',
            cache: false,
            data: {
                action: 'evo_flex_get_batch_info',
                nonce: evoFlexData.nonce,
                price: price,
                items: items,
                _t: Date.now()
            },
            success: function(response) {
                if (response.success) {
                    $.each(response.data, function(index, html) {
                        if ($containerList[index]) {
                            $containerList[index].html(html).css('opacity', '1');
                        }
                    });
                } else {
                    containers.css('opacity', '1');
                }
            },
            error: function() {
                containers.css('opacity', '1');
            }
        });
    }

    // Fallback Qty Update
    if (evoFlexData.update_price == 1) {
        const $qtyInput = $('form.cart input.qty');
        const $priceDisplay = $('.entry-summary p.price');

        if ($qtyInput.length && $priceDisplay.length) {
            $qtyInput.on('change input', function() {
                debounceUpdate(() => {
                    const qty = $qtyInput.val();
                    
                    // Retrieve active product ID (variation or simple product)
                    let productId = parseInt($('form.cart input[name="variation_id"]').val());
                    if (!productId) {
                        productId = $('form.cart .single_add_to_cart_button').val();
                    }

                    $.ajax({
                        url: evoFlexData.ajax_url,
                        type: 'POST',
                        cache: false,
                        data: { 
                            action: 'evo_flex_get_price', 
                            nonce: evoFlexData.nonce,
                            product_id: productId, 
                            qty: qty,
                            _t: Date.now()
                        },
                        success: function(response) {
                            if (response.success) {
                                $priceDisplay.html(response.data.display_unit);
                                // Pass the clean base_price to avoid double bulk discounting
                                refreshGatewayInfo(response.data.base_price, productId, null, qty);
                            }
                        }
                    });
                }, 300);
            });
        }
    }
});
