<?php
namespace EvoParcelaFlex\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EvoParcelaFlex\Model\Calculator;

/**
 * Checkout Controller
 */
class CheckoutController {

    public function __construct() {
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'apply_gateway_adjustments' ] );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_bulk_discounts' ], 10, 1 );
        add_filter( 'woocommerce_cart_item_name', [ $this, 'add_bulk_badge_to_cart_item' ], 10, 3 );
        add_filter( 'woocommerce_cart_item_price', [ $this, 'format_cart_item_price_as_sale' ], 10, 3 );
        add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'format_cart_item_price_as_sale' ], 10, 3 );
        add_action( 'wp_footer', [ $this, 'refresh_checkout_on_gateway_change' ] );
        
        // WooCommerce Blocks Support
        add_action( 'woocommerce_blocks_loaded', [ $this, 'register_blocks_integration' ] );
        add_action( 'woocommerce_store_api_cart_calculate_totals', [ $this, 'apply_bulk_discounts' ] );
        add_filter( 'woocommerce_gateway_title', [ $this, 'add_gateway_badges' ], 10, 2 );
        add_action( 'woocommerce_review_order_before_order_total', [ $this, 'render_gateway_total_row' ] );
    }

    /**
     * Add badges and totals to gateway titles in checkout
     */
    public function add_gateway_badges( $title, $gateway_id ) {
        if ( is_admin() || ! is_checkout() || ! WC()->cart ) return $title;

        $settings = get_option( 'evo_flex_settings', [] );
        $gw_settings = $settings['gateways'][$gateway_id] ?? [];

        $badges = '';

        // Discount Percentage for this gateway
        $discount_pct = floatval($gw_settings['discount'] ?? 0);
        if ($discount_pct > 0) {
            // Compute directly without calling get_cart_savings() to avoid infinite loops
            // inside woocommerce_gateway_title. Apply discount only on product subtotal,
            // then add shipping/taxes/coupons to get the true projected order total.
            $cart            = WC()->cart;
            $products_base   = floatval( $cart->get_subtotal() ) + floatval( $cart->get_subtotal_tax() );
            $shipping        = floatval( $cart->get_shipping_total() ) + floatval( $cart->get_shipping_tax() );
            $coupon_discount = floatval( $cart->get_discount_total() ) + floatval( $cart->get_discount_tax() );
            $discount_amount = floatval( $cart->get_subtotal() ) * ( $discount_pct / 100 );
            $final_total     = $products_base + $shipping - $coupon_discount - $discount_amount;

            if ( $discount_amount > 0 ) {
                $badges .= ' <span class="evo-flex-checkout-total">(' . wc_price($final_total) . ' - ' . $discount_pct . '% ' . __('de desconto', 'evo-parcela-flex') . ')</span>';
            }
        }

        // Custom Badge from Settings
        if ( ($gw_settings['show_badge'] ?? 0) && ! empty($gw_settings['badge_text']) ) {
            $color = $gw_settings['badge_color'] ?? '#38a169';
            $badges .= ' <span class="evo-flex-checkout-badge custom" style="background-color:' . esc_attr($color) . '; color:#fff;">' . esc_html($gw_settings['badge_text']) . '</span>';
        }

        // Immediate Approval Badge
        if ( ( $settings['show_approval_badge'] ?? 0 ) && in_array( $gateway_id, [ 'pix', 'credit_card' ] ) ) {
            $badges .= ' <span class="evo-flex-checkout-badge approval">' . __( 'Aprovação Imediata', 'evo-parcela-flex' ) . '</span>';
        }

        // Icon for Gateway Title
        if ( ($gw_settings['show_icon_checkout'] ?? 0) && ! empty($gw_settings['icon_url']) ) {
            $title = '<img src="' . esc_url($gw_settings['icon_url']) . '" class="evo-flex-gw-icon-checkout" style="height:18px; vertical-align:middle; margin-right:8px;" />' . $title;
        }

        // Interest Badge
        if ( ( $settings['show_interest_badge'] ?? 0 ) ) {
            $has_interest = false;
            foreach ( $gw_settings['installments'] ?? [] as $inst ) {
                if ( floatval( $inst['rate'] ?? 0 ) > 0 ) {
                    $has_interest = true;
                    break;
                }
            }

            if ( $has_interest ) {
                $badges .= ' <span class="evo-flex-checkout-badge interest">' . __( 'Com Juros', 'evo-parcela-flex' ) . '</span>';
            } else if ( ! empty( $gw_settings['installments'] ) ) {
                $badges .= ' <span class="evo-flex-checkout-badge no-interest">' . __( 'Sem Juros', 'evo-parcela-flex' ) . '</span>';
            }
        }

        if ( ! empty( $badges ) ) {
            $badges = ' ' . $badges;
        }

        return $title . $badges;
    }

    /**
     * Render the total rows for configured gateways in checkout summary
     */
    public function render_gateway_total_row() {
        if ( ! WC()->cart ) return;

        $savings = Calculator::get_cart_savings();
        $chosen_gateway = WC()->session->get( 'chosen_payment_method' );
        
        foreach ( $savings as $id => $data ) {
            if ( ! ($data['show_in_checkout'] ?? 0) ) continue;

            $discount_amount = $data['amount'];
            $final_total = $data['final_total'];
            $icon_html = Calculator::get_gateway_icon_html( $data['icon_url'] ?? '', 'evo-flex-gw-icon', 'height:18px; vertical-align:middle; margin-right:5px;' );

            // Row for the simulated discount amount
            if ( $id !== $chosen_gateway && $discount_amount > 0 ) {
                ?>
                <tr class="evo-flex-checkout-gateway-discount fee">
                    <th><?php echo $icon_html; ?><?php printf( __( 'Desconto %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $id ) ); ?></th>
                    <td data-title="<?php printf( __( 'Desconto %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $id ) ); ?>">
                        -<?php echo wc_price( $discount_amount ); ?>
                    </td>
                </tr>
                <?php
            }

            ?>
            <tr class="evo-flex-checkout-gateway-total">
                <th><?php echo $icon_html; ?><?php printf( __( 'Total no %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $id ) ); ?></th>
                <td data-title="<?php printf( __( 'Total no %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $id ) ); ?>">
                    <strong><?php echo wc_price( $id === $chosen_gateway ? WC()->cart->get_total( 'edit' ) : $final_total ); ?></strong>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Apply fees or discounts based on the selected payment method
     */
    public function apply_gateway_adjustments( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        $chosen_gateway = WC()->session->get( 'chosen_payment_method' );
        
        // In Store API (Blocks), the gateway might be in the request data
        if ( ! $chosen_gateway && isset( $_REQUEST['payment_method'] ) ) {
            $chosen_gateway = sanitize_text_field( $_REQUEST['payment_method'] );
        }
        
        if ( ! $chosen_gateway ) return;

        $savings = Calculator::get_cart_savings();
        if ( isset( $savings[$chosen_gateway] ) && $savings[$chosen_gateway]['amount'] > 0 ) {
            $discount_amount = $savings[$chosen_gateway]['amount'] * -1;
            $cart->add_fee( sprintf( __( 'Desconto %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $chosen_gateway ) ), $discount_amount, false );
        }
    }

    /**
     * Force checkout refresh when payment method changes
     */
    public function refresh_checkout_on_gateway_change() {
        if ( ! is_checkout() ) return;
        ?>
        <script>
        jQuery(function($){
            $(document.body).on('updated_checkout', function(){
                $('input[name="payment_method"]').change(function(){
                    $(document.body).trigger('update_checkout');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Apply Quantity Discounts (Bulk) directly to the product price in the cart
     */
    public function apply_bulk_discounts( $cart ) {
        // Support for Store API (Blocks)
        $is_store_api = defined( 'WC_STORE_API_REQUEST' ) && WC_STORE_API_REQUEST;
        
        if ( is_admin() && ! defined( 'DOING_AJAX' ) && ! $is_store_api ) return;
        
        // Allow multiple runs in REST if needed, but be careful
        if ( ! $is_store_api && did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
            $qty = $cart_item['quantity'];
            
            // For variations, we need the parent for some meta checks (already handled in Calculator)
            $calculator = new Calculator( $product );
            
            if ( $calculator->are_discounts_disabled() ) continue;

            // This returns the discount AMOUNT for ONE unit based on total qty
            $unit_discount_amount = $calculator->get_bulk_discount( $qty );
            
            if ( $unit_discount_amount > 0 ) {
                $base_price = $calculator->get_base_price();
                $new_price = $base_price - $unit_discount_amount;
                
                if ( $new_price > 0 ) {
                    $product->set_price( $new_price );
                }
            }
        }
    }

    /**
     * Add a visual badge to the product name when bulk discount is active
     */
    public function add_bulk_badge_to_cart_item( $name, $cart_item, $cart_item_key ) {
        // Skip badge for Gutenberg Blocks as the price already shows the discount
        if ( defined( 'WC_STORE_API_REQUEST' ) && WC_STORE_API_REQUEST ) {
            return $name;
        }

        $product = $cart_item['data'];
        $qty = $cart_item['quantity'];
        
        $calculator = new Calculator( $product );
        $bulk_discount = $calculator->get_bulk_discount( $qty );

        if ( $bulk_discount > 0 ) {
            $badge_html = '<span class="evo-flex-bulk-cart-badge" style="display:inline-block; background:#4f46e5; color:#fff; font-size:9px; padding:2px 6px; border-radius:4px; margin-left:8px; font-weight:700; vertical-align:middle; text-transform:uppercase;">' . __( 'Atacado', 'evo-parcela-flex' ) . '</span>';
            return $name . $badge_html;
        }

        return $name;
    }

    /**
     * Format the price/subtotal as a sale (crossed out original)
     */
    public function format_cart_item_price_as_sale( $price_html, $cart_item, $cart_item_key ) {
        $product = $cart_item['data'];
        $qty = $cart_item['quantity'];
        
        $calculator = new Calculator( $product );
        $unit_discount = $calculator->get_bulk_discount( $qty );

        if ( $unit_discount > 0 ) {
            $base_price = $calculator->get_base_price();
            $new_unit_price = $base_price - $unit_discount;

            // Check if we are filtering the price or the subtotal
            $current_filter = current_filter();
            if ( $current_filter === 'woocommerce_cart_item_subtotal' ) {
                return sprintf( 
                    '<del style="opacity:0.5; font-size:0.85em; margin-right:5px;">%s</del> <ins style="text-decoration:none; font-weight:700; color:#4f46e5;">%s</ins>', 
                    wc_price( $base_price * $qty ), 
                    wc_price( $new_unit_price * $qty ) 
                );
            } else {
                return sprintf( 
                    '<del style="opacity:0.5; font-size:0.85em; margin-right:5px;">%s</del> <ins style="text-decoration:none; font-weight:700; color:#4f46e5;">%s</ins>', 
                    wc_price( $base_price ), 
                    wc_price( $new_unit_price ) 
                );
            }
        }

        return $price_html;
    }

    /**
     * Register integration for WooCommerce Blocks
     */
    public function register_blocks_integration() {
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) return;

        // Register the data in Store API
        if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
            woocommerce_store_api_register_endpoint_data( [
                'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
                'namespace'       => 'evo-parcela-flex',
                'data_callback'   => [ $this, 'get_cart_blocks_data' ],
                'schema_callback' => [ $this, 'get_cart_blocks_schema' ],
                'schema_type'     => ARRAY_A,
            ] );
        }
    }

    /**
     * Data callback for Store API
     */
    public function get_cart_blocks_data() {
        $total_savings = 0;
        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product = $cart_item['data'];
                $qty = $cart_item['quantity'];
                $calculator = new Calculator( $product );
                $total_savings += ($calculator->get_bulk_discount( $qty ) * $qty);
            }
        }

        return [
            'bulk_savings'       => $total_savings,
            'bulk_savings_html'  => wc_price($total_savings),
            'has_bulk_discount'  => $total_savings > 0,
        ];
    }

    /**
     * Schema callback for Store API
     */
    public function get_cart_blocks_schema() {
        return [
            'bulk_savings'      => [ 'type' => 'number', 'context' => [ 'view', 'edit' ] ],
            'bulk_savings_html' => [ 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
            'has_bulk_discount' => [ 'type' => 'boolean', 'context' => [ 'view', 'edit' ] ],
        ];
    }
}
