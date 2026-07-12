<?php
namespace EvoParcelaFlex\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EvoParcelaFlex\Model\Calculator;

/**
 * Handle Frontend Displays
 */
class FrontendController {

    /**
     * True while rendering the main single product's own summary block,
     * where a full discount panel is already shown via render_installment_info().
     * Related/upsell product cards on that same page must NOT be skipped,
     * so this is more precise than checking is_product() alone.
     */
    private $in_main_summary = false;

    public function __construct() {
        add_action( 'woocommerce_before_single_product_summary', function () { $this->in_main_summary = true; } );
        add_action( 'woocommerce_after_single_product_summary', function () { $this->in_main_summary = false; }, 1 );

        add_filter( 'woocommerce_variable_price_html', [ $this, 'modify_variable_price' ], 100, 2 );
        add_filter( 'woocommerce_available_variation', [ $this, 'add_data_to_variations' ], 10, 3 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_bulk_pricing' ], 22 );
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_installment_info' ], 25 );
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'render_bulk_pricing' ], 4 );
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'render_installment_info' ], 5 );
        add_shortcode( 'evo_parcela_flex', [ $this, 'render_shortcode' ] );
        
        // Mantemos os antigos apenas por compatibilidade (Legacy)
        add_shortcode( 'evo_savings', [ $this, 'render_savings_shortcode' ] );
        add_shortcode( 'evo_best_installment', [ $this, 'render_best_installment_shortcode' ] );

        add_action( 'woocommerce_cart_totals_after_shipping', [ $this, 'render_cart_savings' ], 20 );
        add_action( 'woocommerce_widget_shopping_cart_before_buttons', [ $this, 'render_mini_cart_savings' ] );
        
        // AJAX for price update (Qty based)
        add_action( 'wp_ajax_evo_flex_get_price', [ $this, 'ajax_get_price' ] );
        add_action( 'wp_ajax_nopriv_evo_flex_get_price', [ $this, 'ajax_get_price' ] );

        // Optimized AJAX Batch Info
        add_action( 'wp_ajax_evo_flex_get_batch_info', [ $this, 'ajax_get_batch_info' ] );
        add_action( 'wp_ajax_nopriv_evo_flex_get_batch_info', [ $this, 'ajax_get_batch_info' ] );

        // Shortcodes para gateways dinâmicos
        add_action( 'init', [ $this, 'register_dynamic_shortcodes' ] );
        add_shortcode( 'evo_installment_table', [ $this, 'render_installment_table_shortcode' ] );

        add_action( 'wp_footer', [ $this, 'render_modal_structure' ] );

        // Archives & Loops
        add_filter( 'woocommerce_get_price_html', [ $this, 'render_archive_price' ], 99, 2 );
    }

    /**
     * AJAX Batch Refresh for all pricing containers on page
     */
    public function ajax_get_batch_info() {
        check_ajax_referer( 'evo_flex_frontend_nonce', 'nonce' );
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $items = isset($_POST['items']) ? $_POST['items'] : [];
        
        if (!$price || empty($items)) wp_send_json_error('Dados inválidos');

        $results = [];
        $settings = get_option( 'evo_flex_settings', [] );

        foreach ($items as $index => $item) {
            $gateway_id = sanitize_text_field($item['gateway_id'] ?? 'pix');
            
            // Security check: validate that gateway_id is configured
            if ( ! isset( $settings['gateways'][$gateway_id] ) ) {
                continue;
            }

            $display_type = sanitize_text_field($item['display_type'] ?? 'full');
            $product_id = intval($item['product_id'] ?? 0);
            $qty = intval($item['qty'] ?? 1);

            $product = $product_id ? wc_get_product($product_id) : null;
            if (!$product) continue;

            $results[$index] = $this->get_rendered_layout($product, $price, $gateway_id, $display_type, $qty, $settings);
        }

        wp_send_json_success($results);
    }

    /**
     * Centralized layout rendering engine (Supports Native & Custom JSON)
     */
    private function get_rendered_layout($product, $price, $gateway_id, $display_type, $qty = 1, $settings = null) {
        if ( $settings === null ) $settings = get_option( 'evo_flex_settings', [] );
        $calculator = new Calculator($product, $price, $qty);

        $layout_id = $settings['highlight_layout'] ?? 'default';
        $discount_data = $calculator->get_discounted_price($gateway_id);
        $installments = $calculator->get_installments($gateway_id);

        $gw_settings = $settings['gateways'][$gateway_id] ?? [];
        $icon_html = ! empty( $gw_settings['show_icon_product'] )
            ? Calculator::get_gateway_icon_html( $gw_settings['icon_url'] ?? '', 'evo-flex-gw-icon-product', 'height:22px; vertical-align:middle; margin-right:8px;' )
            : '';

        $template_data = [
            'product'           => $product,
            'calculator'        => $calculator,
            'discount_data'     => $discount_data,
            'installments'      => $installments,
            'gateway_id'        => $gateway_id,
            'settings'          => $settings,
            'icon_html'         => $icon_html,
            'display_type'      => $display_type,
            'layout_class'      => 'evo-flex-layout-' . $layout_id,
            'show_price'        => $settings['show_price_global'] ?? 1,
            'show_savings'      => $settings['show_savings_global'] ?? 1,
            'show_installments' => $settings['show_installments_global'] ?? 1
        ];

        ob_start();

        // --- Check for Custom Layout (JSON) ---
        $custom_layouts = get_option( 'evo_flex_custom_layouts', [] );
        
        if ( isset($custom_layouts[$layout_id]) ) {
            $custom = $custom_layouts[$layout_id];
            if ( ! empty($custom['css']) ) echo '<style>' . $custom['css'] . '</style>';
            
            $html = $custom['html'];
            
            $final_price_val = ! empty($settings['show_price_global']) ? wc_price($discount_data['final_price']) : '';
            $base_price_val  = ! empty($settings['show_price_global']) ? wc_price($discount_data['base_price']) : '';
            $discount_val_tag = ( ! empty($settings['show_savings_global']) && $discount_data['discount'] > 0 ) ? $discount_data['discount'] . '%' : '';

            $html = str_replace('{{price}}', $final_price_val, $html);
            $html = str_replace('{{base_price}}', $base_price_val, $html);
            $html = str_replace('{{discount}}', $discount_val_tag, $html);
            $html = str_replace('{{icon}}', $icon_html, $html);
            $html = str_replace('{{gateway}}', Calculator::get_gateway_title($gateway_id), $html);
            
            $best = $calculator->get_best_installment_scenario($gateway_id);
            
            $inst_text = '';
            $inst_val  = '';
            $months    = '1';

            if ( ! empty($settings['show_installments_global']) ) {
                if ( $best && ! empty($best['not_allowed']) ) {
                    $inst_text = $best['not_allowed_msg'] . ' ' . wc_price($best['min_required']);
                } else {
                    $inst_text = $best ? sprintf('%sx de %s', $best['months'], wc_price($best['value'])) : '';
                    $inst_val  = $best ? wc_price($best['value']) : '';
                    $months    = $best ? $best['months'] : '1';
                }
            }

            $html = str_replace('{{installments_text}}', $inst_text, $html);
            $html = str_replace('{{installment_value}}', $inst_val, $html);
            $html = str_replace('{{months}}', $months, $html);
            
            echo '<div class="evo-flex-layout-inner ' . esc_attr('evo-flex-layout-' . $layout_id) . '">' . $html . '</div>';
            return ob_get_clean();
        }

        // --- Handle Native Layouts & Display Types ---
        if ($display_type === 'price') {
            echo '<div class="evo-flex-layout-inner ' . $template_data['layout_class'] . '">';
            echo '<div class="evo-flex-single-price evo-flex-pix-price">' . $icon_html;
            echo '<span class="evo-flex-value">' . wc_price( $discount_data['final_price'] ) . '</span>';
            echo '<span class="evo-flex-label">' . sprintf( __( 'no %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $gateway_id ) ) . '</span>';
            if ($discount_data['discount'] > 0) echo '<span class="evo-flex-badge">-' . $discount_data['discount'] . '%</span>';
            echo '</div>';
            echo '</div>';
        } else if ($display_type === 'savings') {
            echo '<div class="evo-flex-layout-inner ' . $template_data['layout_class'] . '">';
            $discount_value = $price - $discount_data['final_price'];
            echo '<i class="fa-solid fa-arrow-trend-down"></i>';
            echo '<span class="evo-flex-savings-text">';
            printf( __( 'Economize %s (%s%%)', 'evo-parcela-flex' ), wc_price($discount_value), $discount_data['discount'] );
            echo '<span class="evo-flex-final-price-note">';
            printf( __( ' - Pague apenas %s', 'evo-parcela-flex' ), '<strong>' . wc_price($discount_data['final_price']) . '</strong>' );
            echo '</span></span>';
            echo '</div>';
        } else if ($display_type === 'best_installment') {
            echo '<div class="evo-flex-layout-inner ' . $template_data['layout_class'] . '">';
            $best = $calculator->get_best_installment_scenario($gateway_id);
            
            if ( $best && ! empty($best['not_allowed']) ) {
                $inst_text = $best['not_allowed_msg'] . ' ' . wc_price($best['min_required']);
                echo '<div class="evo-flex-best-installment-wrap">' . $inst_text . '</div>';
            } else if ( ! empty($best) ) {
                echo '<div class="evo-flex-best-installment-wrap">';
                printf( __( 'Em até %sx de %s%s', 'evo-parcela-flex' ), '<strong>' . $best['months'] . '</strong>', '<strong>' . wc_price($best['value']) . '</strong>', ( $best['has_interest'] ? '' : ' ' . __('sem juros', 'evo-parcela-flex') ) );
                echo '</div>';
            }
            echo '</div>';
        } else {
            $template_file = EVO_PARCELA_FLEX_PATH . 'templates/frontend/layouts/' . $layout_id . '.php';
            if ( ! file_exists( $template_file ) ) $template_file = EVO_PARCELA_FLEX_PATH . 'templates/frontend/layouts/default.php';
            
            if ( file_exists( $template_file ) ) {
                extract( $template_data );
                $pix_data = $discount_data; // Legacy support for some templates
                $current_gateway_id = $gateway_id;
                include $template_file;
            } else {
                echo '<div class="evo-flex-layout-inner ' . $template_data['layout_class'] . '">';
                $current_gateway_id = $gateway_id;
                $pix_data = $discount_data;
                include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table.php';
                echo '</div>';
            }
        }
        return ob_get_clean();
    }

    public function render_best_installment_shortcode() {
        global $product;
        if ( ! $product || ! is_a($product, 'WC_Product') ) {
            if ( is_product() ) $product = wc_get_product( get_the_ID() );
        }
        if ( ! $product ) return '';

        $settings = get_option( 'evo_flex_settings', [] );
        $gateway_id = $settings['installment_gateway'] ?? '';
        if ( ! $gateway_id ) {
            foreach ( $settings['gateways'] ?? [] as $id => $gw_data ) {
                if ( ! empty( $gw_data['installments'] ) ) {
                    $gateway_id = $id;
                    break;
                }
            }
        }
        if ( ! $gateway_id ) return '';

        $calculator = new Calculator( $product );
        if ( $calculator->get_base_price() <= 0 ) return '';
        $best = $calculator->get_best_installment_scenario( $gateway_id );
        if ( empty($best) ) return '';

        ob_start();
        ?>
        <div class="evo-flex-best-installment-highlight evo-flex-dynamic-display" 
             data-product-id="<?php echo esc_attr($product->get_id()); ?>" 
             data-base-price="<?php echo esc_attr($calculator->get_base_price()); ?>"
             data-gateway="<?php echo esc_attr($gateway_id); ?>" 
             data-display="best_installment">
            <div class="evo-flex-best-installment-wrap">
                <?php
                if ( ! empty($best['not_allowed']) ) {
                    echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']);
                } else {
                    printf( 
                        __( 'Em até %sx de %s%s', 'evo-parcela-flex' ), 
                        '<strong>' . $best['months'] . '</strong>', 
                        '<strong>' . wc_price($best['value']) . '</strong>',
                        ( $best['has_interest'] ? '' : ' ' . __('sem juros', 'evo-parcela-flex') )
                    );
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Registra shortcodes para cada gateway ativo automaticamente
     */
    public function register_dynamic_shortcodes() {
        if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) {
            return;
        }
        
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        
        foreach ( $gateways as $id => $gateway ) {
            add_shortcode( 'evo_' . $id, function( $atts ) use ( $id ) {
                $atts = is_array( $atts ) ? $atts : [];
                $atts['method'] = $id;
                return $this->render_shortcode( $atts );
            } );
        }
    }

    public function add_data_to_variations( $data, $product, $variation ) {
        $price = floatval( $variation->get_price() );
        $settings = get_option( 'evo_flex_settings', [] );
        $calculator = new Calculator( $variation, $price );
        $layout_id = $settings['highlight_layout'] ?? 'default';

        $data['evo_flex_html'] = [];
        $gateways = $settings['gateways'] ?? [];
        $savings_gateway = $settings['savings_gateway'] ?? 'pix';
        
        foreach ( ['full', 'price', 'savings', 'best_installment'] as $type ) {
            foreach ( $gateways as $gw_id => $gw_settings ) {
                if ( $type === 'savings' && $gw_id !== $savings_gateway ) continue;
                $html = $this->get_rendered_layout($variation, $price, $gw_id, $type, 1, $settings);
                if ( ! empty($html) ) $data['evo_flex_html'][$gw_id . '_' . $type] = $html;
            }
        }
        return $data;
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style( 'evo-flex-frontend', EVO_PARCELA_FLEX_URL . 'assets/css/frontend.css', [], time() );
        wp_enqueue_style( 'font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0' );
        wp_enqueue_script( 'evo-flex-frontend', EVO_PARCELA_FLEX_URL . 'assets/js/frontend.js', [ 'jquery' ], time(), true );
        $settings = get_option( 'evo_flex_settings', [] );
        
        wp_localize_script( 'evo-flex-frontend', 'evoFlexData', [
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'evo_flex_frontend_nonce' ),
            'settings_ver'  => get_option( 'evo_flex_settings_ver', '1' ),
            'update_price'  => $settings['update_price_qty'] ?? 0,
            'table_mode'    => $settings['table_display_mode'] ?? 'toggle'
        ] );
    }

    public function modify_variable_price( $price, $product ) {
        if ( is_admin() ) return $price;
        $settings = get_option( 'evo_flex_settings', [] );
        if ( ( $settings['remove_price_range'] ?? 0 ) ) {
            $min_price = $product->get_variation_price( 'min', false );
            $price = wc_price( $min_price );
        }
        return $price;
    }

    public function render_installment_info( $gateway_id = '', $display_type = 'full', $layout_override = '', $from_hook = true ) {
        static $rendered_ids = [];
        global $product;
        if ( ! $product && is_singular('product') ) $product = wc_get_product( get_the_ID() );
        if ( ! $product ) return;
        
        $calculator = new Calculator( $product );
        if ( $calculator->get_base_price() <= 0 || $calculator->are_discounts_disabled() ) return;

        $settings = get_option( 'evo_flex_settings', [] );
        $display_method = $settings['display_method'] ?? 'both';

        // Se for chamado via hook e o método for apenas shortcode, cancelamos
        if ( $from_hook && $display_method === 'shortcode' ) return;

        // Prevent duplication on same product page (only if not a shortcode override)
        $unique_id = $product->get_id() . '_' . $display_type;
        if ( empty($layout_override) && in_array( $unique_id, $rendered_ids ) ) return;
        $rendered_ids[] = $unique_id;

        // Determine layout
        $layout_id = ! empty($layout_override) ? $layout_override : ($settings['highlight_layout'] ?? 'default');
        
        // Determine Gateway (Prioridade: Atributo do Shortcode > Configuração Global > Fallback Pix)
        $discount_gateway = ! empty( $gateway_id ) ? $gateway_id : ($settings['product_page_gateway'] ?? 'pix');

        \EvoParcelaFlex\Model\Logger::log( "Iniciando renderização: Layout={$layout_id}, Gateway={$discount_gateway}, Type={$display_type}" );

        // Fallback for gateway
        if ( empty($settings['gateways'][$discount_gateway]) ) {
            $configured_gateways = array_keys($settings['gateways'] ?? []);
            if ( ! empty($configured_gateways) ) $discount_gateway = $configured_gateways[0];
        }

        echo '<div class="evo-flex-pricing-wrapper evo-flex-layout-' . esc_attr($layout_id) . '" 
                  data-product-id="' . esc_attr($product->get_id()) . '" 
                  data-base-price="' . esc_attr($calculator->get_base_price()) . '" 
                  data-gateway="' . esc_attr($discount_gateway) . '" 
                  data-display="' . esc_attr($display_type) . '"
                  data-table-mode="' . esc_attr($settings['table_display_mode'] ?? 'toggle') . '">';
        
        echo $this->get_rendered_layout($product, $calculator->get_base_price(), $discount_gateway, $display_type, 1, $settings);
        
        echo '</div>';
    }

    public function render_bulk_pricing() {
        global $product;
        if ( ! $product && is_singular('product') ) $product = wc_get_product( get_the_ID() );
        if ( ! $product ) return;

        $calculator = new Calculator( $product );
        if ( $calculator->get_base_price() <= 0 || $calculator->are_discounts_disabled() ) return;

        $bulk_data = $calculator->get_bulk_pricing_data();
        if ( ! $bulk_data ) return;

        $settings = get_option( 'evo_flex_settings', [] );
        $layout_id = $settings['bulk_layout'] ?? 'alibaba';
        
        $template_file = EVO_PARCELA_FLEX_PATH . "templates/frontend/bulk/{$layout_id}.php";
        
        if ( file_exists( $template_file ) ) {
            include $template_file;
        } else {
            include EVO_PARCELA_FLEX_PATH . 'templates/frontend/bulk/alibaba.php';
        }
    }

    public function render_savings_shortcode() {
        global $product;
        if ( ! $product ) return '';
        
        $calculator = new Calculator( $product );
        if ( $calculator->get_base_price() <= 0 || $calculator->are_discounts_disabled() ) return '';

        $settings = get_option( 'evo_flex_settings', [] );
        $gateway_id = $settings['savings_gateway'] ?? '';
        if ( ! $gateway_id ) return '';
        $data = $calculator->get_discounted_price( $gateway_id );
        if ( empty($data) || $data['discount'] <= 0 ) return '';

        $discount_value = $data['base_price'] - $data['final_price'];
        $discount_pct = $data['discount'];
        $final_price = $data['final_price'];

        ob_start();
        ?>
        <div class="evo-flex-savings-highlight evo-flex-dynamic-display" 
             data-product-id="<?php echo esc_attr($product->get_id()); ?>" 
             data-base-price="<?php echo esc_attr($calculator->get_base_price()); ?>"
             data-gateway="<?php echo esc_attr($gateway_id); ?>" 
             data-display="savings">
            <i class="fa-solid fa-arrow-trend-down"></i>
            <span class="evo-flex-savings-text">
                <?php printf( __( 'Economize %s (%s%%)', 'evo-parcela-flex' ), wc_price($discount_value), $discount_pct ); ?>
                <span class="evo-flex-final-price-note">
                    <?php printf( __( ' - Pague apenas %s', 'evo-parcela-flex' ), '<strong>' . wc_price($final_price) . '</strong>' ); ?>
                </span>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_cart_savings() {
        $savings = Calculator::get_cart_savings();
        if ( empty($savings) ) return;
        $settings = get_option( 'evo_flex_settings', [] );
        ?>
        <tr class="evo-flex-cart-savings-summary">
            <td colspan="2">
                <table class="evo-flex-cart-savings-table" style="width: 100%; margin: 10px 0; border-collapse: collapse;">
                    <?php foreach ( $savings as $id => $data ) : 
                        $gw_settings = $settings['gateways'][$id] ?? [];
                        if ( ! ($gw_settings['show_in_cart'] ?? 0) ) continue;
                        $label = sprintf(__('Total no %s', 'evo-parcela-flex'), $data['gateway_name']);
                        $discount_label = sprintf(__('Desconto %s', 'evo-parcela-flex'), $data['gateway_name']);
                        if ( $data['amount'] > 0 ) : ?>
                            <tr class="evo-flex-cart-savings-row fee">
                                <th><?php echo esc_html($discount_label); ?></th>
                                <td>-<?php echo wp_kses_post($data['formatted_amount']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="evo-flex-cart-savings-row">
                            <th><?php echo esc_html($label); ?></th>
                            <td><strong><?php echo wp_kses_post($data['formatted_total']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
        <?php
    }

    public function render_mini_cart_savings() {
        static $mini_cart_rendered = false;
        if ( $mini_cart_rendered ) return;
        $settings = get_option( 'evo_flex_settings', [] );
        $savings = Calculator::get_cart_savings( true );
        if ( empty($savings) ) return;
        $has_display = false;
        ob_start();
        foreach ( $savings as $id => $data ) {
            $gw_settings = $settings['gateways'][$id] ?? [];
            if ( ! ($gw_settings['show_in_mini_cart'] ?? 0) ) continue;
            $has_display = true;
            echo '<div class="evo-flex-saving-item-wrap" style="margin-bottom: 5px;">';
            echo '<p class="evo-flex-saving-total" style="margin:0; font-size: 13px; font-weight: 700;">' . wp_kses_post($data['subtotal_label']) . '</p>';
            echo '<p class="evo-flex-saving-item" style="margin:0; font-size: 11px; color: #2f855a;">' . wp_kses_post($data['mini_label']) . '</p>';
            echo '</div>';
        }
        $content = ob_get_clean();
        if ( $has_display ) {
            $mini_cart_rendered = true;
            echo '<div class="evo-flex-mini-cart-savings">' . $content . '</div>';
        }
    }

    public function render_shortcode( $atts ) {
        $a = shortcode_atts( [
            'method'     => '', 
            'type'       => 'full', // full, price, savings/economia, installments/parcelas
            'product_id' => 0,
            'layout'     => ''
        ], $atts );
        
        global $product;
        $old_product = $product;
        if ( ! empty($a['product_id']) ) $product = wc_get_product( $a['product_id'] );
        if ( ! $product ) $product = wc_get_product( get_the_ID() );
        if ( ! $product ) return '';
        
        ob_start();
        
        // Alias para facilitar o uso em português
        $type = $a['type'];
        if ( $type === 'economia' ) $type = 'savings';
        if ( $type === 'parcelas' ) $type = 'installments';
        if ( $type === 'preco' ) $type = 'price';

        switch ( $type ) {
            case 'savings':
                echo $this->render_savings_shortcode();
                break;
            case 'installments':
                echo $this->render_best_installment_shortcode();
                break;
            default:
                $this->render_installment_info( $a['method'], $type, $a['layout'], false );
                break;
        }

        $content = ob_get_clean();
        $product = $old_product;
        return $content;
    }

    public function ajax_get_price() {
        check_ajax_referer( 'evo_flex_frontend_nonce', 'nonce' );
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $qty = intval( $_POST['qty'] );
        $product = wc_get_product( $product_id );
        if ( ! $product ) wp_send_json_error();
        $calculator = new Calculator( $product );
        $base_price = $calculator->get_base_price();
        $discount = $calculator->get_bulk_discount( $qty );
        $final_unit_price = $base_price - $discount;
        wp_send_json_success( [
            'base_price'   => $base_price,
            'unit_price'   => $final_unit_price,
            'total_price'  => $final_unit_price * $qty,
            'display_unit' => wc_price( $final_unit_price ),
            'display_total'=> wc_price( $final_unit_price * $qty ),
        ] );
    }

    public function render_archive_price( $price_html, $product ) {
        if ( is_admin() || ! is_a( $product, 'WC_Product' ) ) return $price_html;
        if ( $this->in_main_summary ) return $price_html;

        $settings = get_option( 'evo_flex_settings', [] );
        $gateway_id = $settings['archive_gateway'] ?? '';
        if ( ! $gateway_id ) return $price_html;

        $calculator = new Calculator( $product );
        if ( $calculator->get_base_price() <= 0 ) return $price_html;
        $data = $calculator->get_discounted_price( $gateway_id );
        if ( empty( $data ) || $data['discount'] <= 0 ) return $price_html;

        $gw_settings = $settings['gateways'][$gateway_id] ?? [];
        $icon_html = ! empty( $gw_settings['show_icon_archive'] )
            ? Calculator::get_gateway_icon_html( $gw_settings['icon_url'] ?? '', 'evo-flex-gw-icon-archive', 'height:14px; vertical-align:middle; margin-right:4px;' )
            : '';

        $archive_html = '<div class="evo-flex-archive-pricing">';
        $archive_html .= '<span class="evo-flex-archive-prefix">' . __( 'ou ', 'evo-parcela-flex' ) . '</span>';
        $archive_html .= $icon_html;
        $archive_html .= '<span class="evo-flex-archive-value">' . wc_price( $data['final_price'] ) . '</span>';
        $archive_html .= '<span class="evo-flex-archive-label">' . sprintf( __( ' no %s', 'evo-parcela-flex' ), Calculator::get_gateway_title( $gateway_id ) ) . '</span>';
        $archive_html .= '</div>';

        return $price_html . $archive_html;
    }

    /**
     * Shortcode to render only the installment table
     */
    public function render_installment_table_shortcode( $atts ) {
        global $product;
        $atts = shortcode_atts( [
            'gateway' => '',
            'product_id' => 0
        ], $atts );

        $target_product = null;

        // Check global product first, but only if it's an object
        if ( is_a($product, 'WC_Product') ) {
            $target_product = $product;
        }

        // If ID provided, override
        if ( ! empty($atts['product_id']) ) {
            $target_product = wc_get_product( $atts['product_id'] );
        }
        
        // Final fallback to current page ID
        if ( ! is_a($target_product, 'WC_Product') && is_singular('product') ) {
            $target_product = wc_get_product( get_the_ID() );
        }

        if ( ! is_a($target_product, 'WC_Product') ) return '';

        $settings = get_option( 'evo_flex_settings', [] );
        $gateway_id = ! empty( $atts['gateway'] ) ? $atts['gateway'] : ($settings['product_page_gateway'] ?? 'pix');
        
        $calculator = new \EvoParcelaFlex\Model\Calculator( $target_product );
        $installments = $calculator->get_installments( $gateway_id );
        
        if ( empty($installments) ) return '';

        ob_start();
        ?>
        <div class="evo-flex-installments-table-wrapper" data-gateway="<?php echo esc_attr($gateway_id); ?>">
            <table class="evo-flex-installments-table">
                <tbody>
                    <?php foreach ( $installments as $inst ) : ?>
                        <tr class="evo-flex-inst-row" data-months="<?php echo $inst['months']; ?>" data-rate="<?php echo $inst['rate']; ?>">
                            <td><?php printf( __( '%dx de', 'evo-parcela-flex' ), $inst['months'] ); ?></td>
                            <td class="evo-flex-inst-value"><?php echo wc_price( $inst['installment'] ); ?></td>
                            <td class="evo-flex-inst-total">
                                <?php 
                                if ( $inst['has_interest'] ) {
                                    printf( __( 'Total: %s', 'evo-parcela-flex' ), wc_price( $inst['total'] ) );
                                } else {
                                    _e( 'Sem juros', 'evo-parcela-flex' );
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the Modal structure in the footer
     */
    public function render_modal_structure() {
        if ( ! is_product() && ! is_cart() && ! is_checkout() ) return;
        
        $settings = get_option( 'evo_flex_settings', [] );
        if ( ($settings['table_display_mode'] ?? 'toggle') !== 'modal' ) return;

        ?>
        <div id="evo-flex-modal" class="evo-flex-modal" style="display:none;">
            <div class="evo-flex-modal-overlay"></div>
            <div class="evo-flex-modal-container">
                <button type="button" class="evo-flex-modal-close">&times;</button>
                <div class="evo-flex-modal-content">
                    <div id="evo-flex-modal-inner-content">
                        <?php 
                        $modal_content = $settings['modal_custom_content'] ?? '';
                        if ( ! empty($modal_content) ) {
                            echo do_shortcode($modal_content);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
