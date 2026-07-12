<?php
namespace EvoParcelaFlex\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Controller
 */
class AdminController {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        
        // Product Tabs & Fields
        add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tab' ] );
        add_action( 'woocommerce_product_data_panels', [ $this, 'render_product_data_panel' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_fields' ] );
        add_action( 'update_option_evo_flex_settings', [ $this, 'clear_all_caches' ], 10, 3 );

        // AJAX for Custom Layouts
        add_action( 'wp_ajax_evo_flex_import_layout', [ $this, 'ajax_import_layout' ] );
        add_action( 'wp_ajax_evo_flex_delete_layout', [ $this, 'ajax_delete_layout' ] );
        add_action( 'wp_ajax_evo_flex_get_custom_layouts', [ $this, 'ajax_get_custom_layouts' ] );
        add_action( 'wp_ajax_evo_flex_get_layout_preview', [ $this, 'ajax_get_layout_preview' ] );
    }

    public function clear_all_caches() {
        // Update settings version for frontend refresh
        update_option( 'evo_flex_settings_ver', time() );

        // Clear WP Object Cache
        wp_cache_flush();

        // Clear WooCommerce fragments
        if ( class_exists( 'WC_Cache_Helper' ) ) {
            \WC_Cache_Helper::get_transient_version( 'shipping', true );
            \WC_Cache_Helper::get_transient_version( 'cart_fragments', true );
        }

        // Try to clear common caching plugins
        if ( function_exists( 'w3tc_flush_all' ) ) w3tc_flush_all();
        if ( function_exists( 'wp_cache_clear_cache' ) ) wp_cache_clear_cache();
        if ( class_exists( 'WpFastestCache' ) ) {
            $wpfc = new \WpFastestCache();
            $wpfc->deleteCache();
        }
        if ( class_exists( 'RocketClient' ) && function_exists( 'rocket_clean_domain' ) ) rocket_clean_domain();
        if ( class_exists( 'LiteSpeed_Cache_API' ) ) \LiteSpeed_Cache_API::purge_all();
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'woocommerce_page_evo-parcela-flex' !== $hook ) return;
        wp_enqueue_media();
        wp_enqueue_style( 'font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0' );
        wp_enqueue_style( 'evo-flex-admin', EVO_PARCELA_FLEX_URL . 'assets/css/admin.css', [], time() );
        wp_enqueue_style( 'evo-flex-frontend', EVO_PARCELA_FLEX_URL . 'assets/css/frontend.css', [], time() );
        wp_enqueue_script( 'evo-flex-admin', EVO_PARCELA_FLEX_URL . 'assets/js/admin.js', [ 'jquery' ], time(), true );
        wp_localize_script( 'evo-flex-admin', 'evoFlexAdmin', [
            'plugin_url' => EVO_PARCELA_FLEX_URL,
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'evo_flex_admin_nonce' )
        ] );
    }

    public function add_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Evo Parcela Flex', 'evo-parcela-flex' ),
            __( 'Evo Parcela Flex', 'evo-parcela-flex' ),
            'manage_options',
            'evo-parcela-flex',
            [ $this, 'render_settings' ]
        );
    }

    public function register_settings() {
        register_setting( 'evo_flex_group', 'evo_flex_settings', [
            'sanitize_callback' => [ $this, 'sanitize_settings' ]
        ] );
    }

    /**
     * Sanitize and ensure checkbox values are saved correctly
     */
    public function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) return $input;

        // Ensure gateways array exists
        if ( ! isset( $input['gateways'] ) ) $input['gateways'] = [];

        // Checkboxes in general settings
        $general_checkboxes = [
            'remove_price_range',
            'update_price_qty',
            'show_interest_badge',
            'show_approval_badge',
            'show_price_global',
            'show_savings_global',
            'show_installments_global'
        ];

        foreach ( $general_checkboxes as $key ) {
            $input[$key] = isset( $input[$key] ) ? 1 : 0;
        }

        $input['highlight_layout'] = isset( $input['highlight_layout'] ) ? sanitize_text_field( $input['highlight_layout'] ) : 'default';
        $input['archive_gateway'] = isset( $input['archive_gateway'] ) ? sanitize_text_field( $input['archive_gateway'] ) : '';
        $input['min_price_gateway'] = sanitize_text_field( $input['min_price_gateway'] ?? 'all' );
        $input['table_display_mode'] = sanitize_text_field( $input['table_display_mode'] ?? 'toggle' );
        $input['modal_custom_content'] = wp_kses_post( $input['modal_custom_content'] ?? '' );
        $input['valor_minimo_produto_parcela'] = isset( $input['valor_minimo_produto_parcela'] ) ? floatval( $input['valor_minimo_produto_parcela'] ) : 0;
        $input['msg_parcelamento_indisponivel'] = isset( $input['msg_parcelamento_indisponivel'] ) ? sanitize_text_field( $input['msg_parcelamento_indisponivel'] ) : '';

        // Checkboxes in gateways
        foreach ( $input['gateways'] as $id => &$gw_data ) {
            $gw_data['show_badge'] = isset( $gw_data['show_badge'] ) ? 1 : 0;
            $gw_data['checkout_highlight'] = isset( $gw_data['checkout_highlight'] ) ? 1 : 0;
            $gw_data['show_in_cart'] = isset( $gw_data['show_in_cart'] ) ? 1 : 0;
            $gw_data['show_in_mini_cart'] = isset( $gw_data['show_in_mini_cart'] ) ? 1 : 0;
            $gw_data['show_in_checkout'] = isset( $gw_data['show_in_checkout'] ) ? 1 : 0;
            $gw_data['show_icon_mini'] = isset( $gw_data['show_icon_mini'] ) ? 1 : 0;
            $gw_data['show_icon_cart'] = isset( $gw_data['show_icon_cart'] ) ? 1 : 0;
            $gw_data['show_icon_checkout'] = isset( $gw_data['show_icon_checkout'] ) ? 1 : 0;
            $gw_data['show_icon_product'] = isset( $gw_data['show_icon_product'] ) ? 1 : 0;
            $gw_data['show_in_archive'] = isset( $gw_data['show_in_archive'] ) ? 1 : 0;
            $gw_data['icon_url'] = isset( $gw_data['icon_url'] ) ? esc_url_raw( $gw_data['icon_url'] ) : '';
            
            // Ensure installments is an array
            if ( ! isset( $gw_data['installments'] ) ) {
                $gw_data['installments'] = [];
            } else {
                // Filter out empty rows
                $gw_data['installments'] = array_filter( $gw_data['installments'], function($item) {
                    return !empty($item['months']);
                });
            }
        }

        return $input;
    }

    public function render_settings() {
        include EVO_PARCELA_FLEX_PATH . 'templates/admin/settings.php';
    }

    public function add_product_data_tab( $tabs ) {
        $tabs['evo_parcela_flex'] = [
            'label'    => __( 'Evo Parcela Flex', 'evo-parcela-flex' ),
            'target'   => 'evo_parcela_flex_product_data',
            'class'    => [ 'show_if_simple', 'show_if_variable' ],
            'priority' => 75,
        ];
        return $tabs;
    }

    public function render_product_data_panel() {
        global $post;
        ?>
        <div id="evo_parcela_flex_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <div style="padding: 10px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin-bottom: 15px;">
                    <h3 style="margin:0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-gears" style="margin-right: 10px; color: #4f46e5;"></i> <?php _e( 'Configurações Inteligentes', 'evo-parcela-flex' ); ?></h3>
                </div>

                <?php
                wp_nonce_field( 'evo_flex_product_save', 'evo_flex_product_nonce' );
                
                woocommerce_wp_checkbox( [
                    'id'            => '_evo_flex_disable_all',
                    'label'         => __( 'Desativar tudo neste produto', 'evo-parcela-flex' ),
                    'description'   => __( 'Se marcado, nenhuma regra de desconto ou parcelamento deste plugin será aplicada.', 'evo-parcela-flex' ),
                    'desc_tip'      => true,
                ] );

                woocommerce_wp_text_input( [
                    'id'            => '_evo_flex_min_price',
                    'label'         => __( 'Preço Mínimo Especial (R$)', 'evo-parcela-flex' ),
                    'placeholder'   => __( 'Ex: 150.00', 'evo-parcela-flex' ),
                    'description'   => __( 'Sobrescreve a regra global de preço mínimo para este produto.', 'evo-parcela-flex' ),
                    'desc_tip'      => true,
                    'type'          => 'text',
                    'custom_attributes' => [ 'step' => '0.01', 'min' => '0' ],
                ] );
                ?>
            </div>

            <div class="options_group">
                <div style="padding: 10px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin: 15px 0;">
                    <h4 style="margin:0; font-size: 14px; color: #1e293b;"><i class="fa-solid fa-layer-group" style="margin-right: 10px; color: #4f46e5;"></i> <?php _e( 'Desconto por Quantidade', 'evo-parcela-flex' ); ?></h4>
                </div>
                <?php
                woocommerce_wp_select( [
                    'id'      => '_evo_flex_qty_discount_type',
                    'label'   => __( 'Tipo de Desconto', 'evo-parcela-flex' ),
                    'options' => [
                        'global'  => __( 'Seguir Regra Global', 'evo-parcela-flex' ),
                        'none'    => __( 'Nenhum (Desativar)', 'evo-parcela-flex' ),
                        'percent' => __( 'Percentual (%)', 'evo-parcela-flex' ),
                        'fixed'   => __( 'Valor Fixo (R$)', 'evo-parcela-flex' ),
                    ],
                ] );

                woocommerce_wp_text_input( [
                    'id'    => '_evo_flex_qty_discount_value',
                    'label' => __( 'Valor do Desconto', 'evo-parcela-flex' ),
                ] );

                woocommerce_wp_text_input( [
                    'id'    => '_evo_flex_qty_min',
                    'label' => __( 'Quantidade Mínima', 'evo-parcela-flex' ),
                    'type'  => 'number',
                ] );
                ?>
            </div>

            <div class="options_group">
                <div style="padding: 10px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin: 15px 0;">
                    <h4 style="margin:0; font-size: 14px; color: #1e293b;"><i class="fa-solid fa-credit-card" style="margin-right: 10px; color: #4f46e5;"></i> <?php _e( 'Desconto por Método de Pagamento', 'evo-parcela-flex' ); ?></h4>
                </div>
                <?php
                woocommerce_wp_select( [
                    'id'      => '_evo_flex_discount_type',
                    'label'   => __( 'Tipo de Desconto', 'evo-parcela-flex' ),
                    'options' => [
                        'global'  => __( 'Seguir Regra Global', 'evo-parcela-flex' ),
                        'none'    => __( 'Nenhum (Desativar)', 'evo-parcela-flex' ),
                        'percent' => __( 'Percentual (%)', 'evo-parcela-flex' ),
                        'fixed'   => __( 'Valor Fixo (R$)', 'evo-parcela-flex' ),
                    ],
                ] );

                woocommerce_wp_text_input( [
                    'id'    => '_evo_flex_discount_value',
                    'label' => __( 'Valor do Desconto', 'evo-parcela-flex' ),
                ] );

                $gateways = WC()->payment_gateways->get_available_payment_gateways();
                $gateway_options = [ '' => __( 'Todos os Gateways', 'evo-parcela-flex' ) ];
                foreach ( $gateways as $id => $gateway ) {
                    $gateway_options[$id] = $gateway->get_title();
                }

                woocommerce_wp_select( [
                    'id'      => '_evo_flex_discount_gateway',
                    'label'   => __( 'Aplicar para', 'evo-parcela-flex' ),
                    'options' => $gateway_options,
                ] );
                ?>
            </div>
            <style>
                #evo_parcela_flex_product_data .options_group { border-bottom: none !important; }
                #evo_parcela_flex_product_data label { width: 200px !important; }
            </style>
        </div>
        <?php
    }

    public function save_product_fields( $product_id ) {
        if ( ! isset( $_POST['evo_flex_product_nonce'] ) || ! wp_verify_nonce( $_POST['evo_flex_product_nonce'], 'evo_flex_product_save' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $product_id ) ) {
            return;
        }

        $fields = [
            '_evo_flex_disable_all',
            '_evo_flex_min_price',
            '_evo_flex_qty_discount_type',
            '_evo_flex_qty_discount_value',
            '_evo_flex_qty_min',
            '_evo_flex_discount_type',
            '_evo_flex_discount_value',
            '_evo_flex_discount_gateway',
        ];

        foreach ( $fields as $field ) {
            $value = isset( $_POST[$field] ) ? sanitize_text_field( $_POST[$field] ) : '';
            update_post_meta( $product_id, $field, $value );
        }
    }

    /**
     * AJAX: Import Layout JSON
     */
    public function ajax_import_layout() {
        check_ajax_referer( 'evo_flex_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permissão negada' );
        
        $data = isset($_POST['layout_data']) ? $_POST['layout_data'] : [];

        if ( empty($data) || empty($data['id']) || empty($data['html']) ) {
            wp_send_json_error( 'Dados inválidos ou campos obrigatórios ausentes (id, html, css)' );
        }

        $custom_layouts = get_option( 'evo_flex_custom_layouts', [] );
        
        $layout_id = sanitize_title($data['id']);
        
        $custom_layouts[$layout_id] = [
            'name' => sanitize_text_field($data['name'] ?? $layout_id),
            'html' => stripslashes($data['html']), // Permitir HTML aqui (Admin only)
            'css'  => stripslashes($data['css'] ?? ''),
            'date' => current_time('mysql')
        ];

        update_option( 'evo_flex_custom_layouts', $custom_layouts );
        wp_send_json_success( 'Modelo importado com sucesso!' );
    }

    /**
     * AJAX: Delete Layout
     */
    public function ajax_delete_layout() {
        check_ajax_referer( 'evo_flex_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permissão negada' );
        
        $id = isset($_POST['id']) ? sanitize_title($_POST['id']) : '';
        $custom_layouts = get_option( 'evo_flex_custom_layouts', [] );

        if ( isset($custom_layouts[$id]) ) {
            unset($custom_layouts[$id]);
            update_option( 'evo_flex_custom_layouts', $custom_layouts );
            wp_send_json_success( 'Modelo removido' );
        }
        wp_send_json_error( 'Modelo não encontrado' );
    }

    /**
     * AJAX: Get Custom Layouts List
     */
    public function ajax_get_custom_layouts() {
        check_ajax_referer( 'evo_flex_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permissão negada' );

        $custom_layouts = get_option( 'evo_flex_custom_layouts', [] );
        wp_send_json_success( $custom_layouts );
    }

    /**
     * AJAX: Get Layout Preview for Admin
     */
    public function ajax_get_layout_preview() {
        check_ajax_referer( 'evo_flex_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permissão negada' );

        $layout_id = isset($_POST['layout_id']) ? sanitize_text_field($_POST['layout_id']) : 'default';
        
        // Dummy Data for Preview
        $dummy_price = 100.00;
        $dummy_discount = 10; // 10%
        $final_price = $dummy_price * (1 - ($dummy_discount / 100));

        $products = wc_get_products(['limit' => 1]);
        if (!empty($products)) {
            $product = $products[0];
        } else {
            $product = new \WC_Product_Simple();
            $product->set_price($dummy_price);
            $product->set_regular_price($dummy_price);
        }
        
        $custom_layouts = get_option( 'evo_flex_custom_layouts', [] );
        
        $calculator = new \EvoParcelaFlex\Model\Calculator($product, $dummy_price);
        $best = $calculator->get_best_installment_scenario('pix'); // Use pix as preview default
        
        ob_start();
        if ( isset($custom_layouts[$layout_id]) ) {
            $custom = $custom_layouts[$layout_id];
            if ( ! empty($custom['css']) ) echo '<style class="evo-flex-dynamic-preview-css">' . $custom['css'] . '</style>';
            
            $html = $custom['html'];
            $html = str_replace('{{price}}', wc_price($final_price), $html);
            $html = str_replace('{{base_price}}', wc_price($dummy_price), $html);
            $html = str_replace('{{discount}}', $dummy_discount . '%', $html);
            $html = str_replace('{{icon}}', '<i class="fa-solid fa-qrcode" style="font-size:24px; color:#4f46e5;"></i>', $html);
            $html = str_replace('{{gateway}}', 'PIX (PREVIEW)', $html);
            
            $inst_text = $best ? sprintf('%sx de %s', $best['months'], wc_price($best['value'])) : '';
            $inst_val  = $best ? wc_price($best['value']) : '';
            $months    = $best ? $best['months'] : '1';

            $html = str_replace('{{installments_text}}', $inst_text, $html);
            $html = str_replace('{{installment_value}}', $inst_val, $html);
            $html = str_replace('{{months}}', $months, $html);
            
            echo '<div class="evo-flex-layout-inner ' . esc_attr('evo-flex-layout-' . $layout_id) . '">' . $html . '</div>';
        } else {
            // Render Native Layouts for Preview
            $template_file = EVO_PARCELA_FLEX_PATH . 'templates/frontend/layouts/' . $layout_id . '.php';
            if ( ! file_exists( $template_file ) ) $template_file = EVO_PARCELA_FLEX_PATH . 'templates/frontend/layouts/default.php';
            
            if ( file_exists( $template_file ) ) {
                $discount_data = [
                    'base_price'  => $dummy_price,
                    'discount'    => $dummy_discount,
                    'final_price' => $final_price
                ];
                $pix_data = $discount_data;
                $gateway_id = 'pix';
                $icon_html = '<i class="fa-solid fa-qrcode" style="font-size:20px; margin-right:8px;"></i>';
                $display_type = 'full';
                
                $installments = $calculator->get_installments($gateway_id);

                echo '<div class="evo-flex-layout-' . esc_attr($layout_id) . '">';
                include $template_file;
                echo '</div>';
            }
        }
        
        wp_send_json_success( ob_get_clean() );
    }
}
