<?php
namespace EvoParcelaFlex\Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WC_Product;

/**
 * Core Pricing Engine
 */
class Calculator {
    private WC_Product $product;
    private ?float $custom_price;
    private int $qty;

    /**
     * Cache parent products to avoid redundant database calls for variations.
     */
    private static array $parent_products_cache = [];

    public function __construct( WC_Product $product, $custom_price = null, $qty = 1 ) {
        $this->product = $product;
        $this->custom_price = $custom_price ? floatval( $custom_price ) : null;
        $this->qty = max( 1, intval( $qty ) );
    }

    /**
     * Resolve the human-readable title configured for a payment gateway,
     * falling back to a formatted version of its ID if not found.
     */
    public static function get_gateway_title( string $gateway_id ): string {
        if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
            $gateways = WC()->payment_gateways()->payment_gateways();
            if ( isset( $gateways[ $gateway_id ] ) ) {
                return $gateways[ $gateway_id ]->get_title();
            }
        }
        return ucwords( str_replace( [ '-', '_' ], ' ', $gateway_id ) );
    }

    /**
     * Build the <img> tag for a gateway icon, or an empty string if no URL is set.
     */
    public static function get_gateway_icon_html( string $icon_url, string $css_class, string $style = '' ): string {
        if ( empty( $icon_url ) ) return '';
        $style_attr = $style ? ' style="' . esc_attr( $style ) . '"' : '';
        return '<img src="' . esc_url( $icon_url ) . '" class="' . esc_attr( $css_class ) . '"' . $style_attr . ' />';
    }

    /**
     * Cache parent product lookup for variation products.
     */
    private function get_meta_product(): WC_Product {
        if ( $this->product->is_type( 'variation' ) ) {
            $parent_id = $this->product->get_parent_id();
            if ( ! isset( self::$parent_products_cache[$parent_id] ) ) {
                self::$parent_products_cache[$parent_id] = wc_get_product( $parent_id );
            }
            return self::$parent_products_cache[$parent_id] ?: $this->product;
        }
        return $this->product;
    }

    /**
     * Retorna o menor preço disponível dependendo do tipo do produto.
     */
    public function get_base_price(): float {
        // Se um preço customizado foi passado (AJAX), usa ele
        if ( $this->custom_price !== null && $this->custom_price > 0 ) {
            return floatval($this->custom_price);
        }

        // Fetch a fresh product object to avoid using modified cart prices (only in cart/checkout/REST/AJAX contexts)
        $clean_product = null;
        if ( is_cart() || is_checkout() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
            $clean_product = wc_get_product( $this->product->get_id() );
        }
        if ( ! $clean_product ) {
            $clean_product = $this->product;
        }

        $price = 0;
        if ( $clean_product->is_type( 'variable' ) ) {
            $price = $clean_product->get_variation_price( 'min', true );
        } else {
            $price = $clean_product->get_price();
        }

        return $price ? floatval( $price ) : 0.0;
    }

    /**
     * Checks if all discounts are disabled for this product.
     */
    public function are_discounts_disabled(): bool {
        $meta_product = $this->get_meta_product();
        return $meta_product->get_meta( '_evo_flex_disable_all' ) === 'yes';
    }

    /**
     * Calcula o preço com desconto para um método específico (ex: Pix).
     */
    public function get_discounted_price( $gateway_id = 'pix' ): array {
        if ( $this->are_discounts_disabled() ) {
            $base_price = $this->get_base_price();
            return [
                'base_price'  => $base_price,
                'discount'    => 0,
                'savings'     => 0,
                'final_price' => $base_price,
            ];
        }

        $base_price = $this->get_base_price();
        $settings   = get_option( 'evo_flex_settings', [] );
        
        // Check per-product override first (handling variations)
        $meta_product = $this->get_meta_product();

        $product_discount_type = $meta_product->get_meta( '_evo_flex_discount_type' );
        $product_discount_val  = $meta_product->get_meta( '_evo_flex_discount_value' );
        $product_gateway       = $meta_product->get_meta( '_evo_flex_discount_gateway' );
        $disable_all           = $meta_product->get_meta( '_evo_flex_disable_all' );

        if ( $disable_all === 'yes' || $disable_all == 1 ) {
            return [
                'base_price'  => $base_price,
                'discount'    => 0,
                'savings'     => 0,
                'final_price' => $base_price,
            ];
        }

        $discount_pct = 0;

        // If product-specific type is 'none', we disable gateway discounts for this item
        if ( $product_discount_type === 'none' ) {
            $discount_pct = 0;
        } 
        // If product-specific type is 'percent' or 'fixed', apply it
        elseif ( in_array($product_discount_type, ['percent', 'fixed']) && ! empty( $product_discount_val ) && ( empty($product_gateway) || $product_gateway === $gateway_id ) ) {
            $discount_pct = ( $product_discount_type === 'percent' ) ? floatval( $product_discount_val ) : ( ( $base_price > 0 ) ? ( floatval( $product_discount_val ) / $base_price * 100 ) : 0 );
        } 
        // Otherwise (global or default), fallback to global settings
        else {
            $discount_pct = floatval( $settings['gateways'][$gateway_id]['discount'] ?? 0 );
        }

        // Add Bulk (Quantity) Discount if applicable
        $bulk_discount_amount = $this->get_bulk_discount( $this->qty );
        $bulk_discount_pct = ( $base_price > 0 ) ? ( $bulk_discount_amount / $base_price ) * 100 : 0;
        
        $total_discount_pct = $discount_pct + $bulk_discount_pct;

        $savings     = $base_price * ( $total_discount_pct / 100 );
        $final_price = $base_price - $savings;

        return [
            'base_price'  => $base_price,
            'discount'    => $total_discount_pct,
            'savings'     => $savings,
            'final_price' => $final_price,
        ];
    }

    /**
     * Calcula as parcelas baseadas nos juros estipulados.
     * Busca o gateway padrão (geralmente cartão de crédito) para exibir na vitrine.
     */
    /**
     * Retorna a lista de parcelas para um gateway específico.
     */
    public function get_installments( $gateway_id = '' ): array {
        $settings      = get_option( 'evo_flex_settings', [] );
        $base_price    = $this->get_base_price();
        
        if ( $base_price <= 0 ) return [];

        $meta_product = $this->get_meta_product();

        $disable_all = $meta_product->get_meta( '_evo_flex_disable_all' );
        if ( $disable_all === 'yes' || $disable_all == 1 ) {
             return [
                [
                    'months'       => 1,
                    'rate'         => 0,
                    'total'        => $base_price,
                    'installment'  => $base_price,
                    'has_interest' => false
                ]
            ];
        }

        $product_min_price = $meta_product->get_meta( '_evo_flex_min_price' );
        $min_product_price = ! empty($product_min_price) ? floatval($product_min_price) : floatval( $settings['valor_minimo_produto_parcela'] ?? 0 );
        $min_price_gw      = $settings['min_price_gateway'] ?? 'all';
        
        // If product price is below minimum, no installments allowed (except 1x)
        $is_target = ( $min_price_gw === 'all' || $min_price_gw === $gateway_id );
        if ( $is_target && $base_price < $min_product_price ) {
            return [
                [
                    'months'       => 1,
                    'rate'         => 0,
                    'total'        => $base_price,
                    'installment'  => $base_price,
                    'has_interest' => false,
                    'not_allowed'  => true,
                    'min_required' => $min_product_price
                ]
            ];
        }

        $installments  = [];

        // Fallback for gateway selection
        if ( empty( $gateway_id ) ) {
            $gateway_id = $settings['installment_gateway'] ?? '';
            if ( ! $gateway_id ) {
                foreach ( $settings['gateways'] ?? [] as $id => $gw_data ) {
                    if ( ! empty( $gw_data['installments'] ) ) {
                        $gateway_id = $id;
                        break;
                    }
                }
            }
        }

        if ( empty( $gateway_id ) ) return [];

        $gw_installments = $settings['gateways'][$gateway_id]['installments'] ?? [];

        // If no installments defined for this gateway, add 1x as default
        if ( empty( $gw_installments ) ) {
            $gw_installments = [ 1 => [ 'months' => 1, 'rate' => 0 ] ];
        }

        foreach ( $gw_installments as $data ) {
            $i = intval( $data['months'] ?? 0 );
            $juros = $data['rate'] ?? 0;
            
            if ( $i > 0 ) {
                $juros_val   = floatval( $juros );
                $total_value = $base_price * (1 + ($juros_val / 100));
                $installment_val = $total_value / $i;

                $installments[] = [
                    'months'       => $i,
                    'rate'         => $juros_val,
                    'total'        => $total_value,
                    'installment'  => $installment_val,
                    'has_interest' => $juros_val > 0
                ];
            }
        }

        // Sort by months ascending
        usort( $installments, function( $a, $b ) {
            return $a['months'] - $b['months'];
        } );

        return $installments;
    }

    /**
     * Identifies the best installment scenario (max months) respecting the minimum installment value.
     */
    public function get_best_installment_scenario( $gateway_id = '' ): array {
        $installments = $this->get_installments( $gateway_id );
        
        if ( empty( $installments ) ) return [];

        // Sort by months descending to find the maximum possible
        usort( $installments, function($a, $b) {
            return $b['months'] <=> $a['months'];
        });

        // The first one is the "best" (maximum months allowed)
        $best = $installments[0];

        $settings = get_option( 'evo_flex_settings', [] );
        $msg = $settings['msg_parcelamento_indisponivel'] ?? 'Parcelamento disponível apenas para compras acima de';

        $meta_product = $this->get_meta_product();

        $product_min_price = $meta_product->get_meta( '_evo_flex_min_price' );
        $min_product_price = ! empty($product_min_price) ? floatval($product_min_price) : floatval( $settings['valor_minimo_produto_parcela'] ?? 0 );
        $min_price_gw      = $settings['min_price_gateway'] ?? 'all';
        $not_allowed       = false;

        if ( $min_product_price > 0 ) {
            $is_target = ( $min_price_gw === 'all' || $min_price_gw === $gateway_id );
            if ( $is_target && $this->get_base_price() < $min_product_price ) {
                $not_allowed = true;
            }
        }

        return [
            'months'            => $best['months'],
            'value'             => $best['installment'],
            'total'             => $best['total'],
            'has_interest'      => $best['has_interest'],
            'not_allowed'       => $not_allowed,
            'min_required'      => $min_product_price,
            'not_allowed_msg'   => $msg,
            'gateway_id'        => $gateway_id
        ];
    }

    /**
     * Calcula o desconto em lote (bulk) baseado na quantidade.
     */
    public function get_bulk_pricing_data(): ?array {
        $meta_product = $this->get_meta_product();

        $discount_type = $meta_product->get_meta( '_evo_flex_qty_discount_type' );
        $discount_val  = $meta_product->get_meta( '_evo_flex_qty_discount_value' );
        $min_qty       = intval($meta_product->get_meta( '_evo_flex_qty_min' ));

        if ( empty( $discount_val ) || $min_qty <= 1 ) {
            return null;
        }

        $base_price = $this->get_base_price();
        $discount_amount = ($discount_type === 'fixed') ? floatval($discount_val) : ($base_price * (floatval($discount_val) / 100));
        $unit_price = $base_price - $discount_amount;

        return [
            'min_qty'         => $min_qty,
            'discount_type'   => $discount_type,
            'discount_value'  => floatval($discount_val),
            'unit_price'      => $unit_price,
            'discount_amount' => $discount_amount,
            'base_price'      => $base_price
        ];
    }

    public function get_bulk_discount( $qty ): float {
        $meta_product = $this->get_meta_product();

        $discount_type = $meta_product->get_meta( '_evo_flex_qty_discount_type' );
        $discount_val  = $meta_product->get_meta( '_evo_flex_qty_discount_value' );
        $min_qty       = intval( $meta_product->get_meta( '_evo_flex_qty_min' ) );

        // Handle 'none' or 'global' (default behavior)
        if ( $discount_type === 'none' || empty($discount_val) || $qty < $min_qty ) {
            return 0;
        }

        if ( $discount_type === 'fixed' ) {
            return floatval( $discount_val );
        } else {
            // Default to percent (or if 'global', it should probably use global rules, but we don't have global bulk yet)
            return $this->get_base_price() * ( floatval( $discount_val ) / 100 );
        }
    }

    /**
     * Calcula a economia potencial para todos os gateways no carrinho.
     */
    /**
     * Calcula a economia potencial para todos os gateways no carrinho.
     */
    public static function get_cart_savings( $use_subtotal = false ): array {
        if ( ! function_exists('WC') || ! WC()->cart ) return [];
        
        $settings = get_option( 'evo_flex_settings', [] );
        $gateways = $settings['gateways'] ?? [];
        $savings_results = [];

        // Initialize results for each gateway
        foreach ( $gateways as $id => $gw_data ) {
            $savings_results[$id] = [
                'amount'         => 0,
                'gateway_name'   => self::get_gateway_title( $id ),
                'icon_url'       => $gw_data['icon_url'] ?? '',
                'show_in_cart'   => ! empty( $gw_data['show_in_cart'] ),
                'show_in_mini_cart' => ! empty( $gw_data['show_in_mini_cart'] ),
                'show_in_checkout' => ! empty( $gw_data['show_in_checkout'] )
            ];
        }

        // Loop through cart items to calculate individual savings
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            $price = floatval( $cart_item['line_subtotal'] ); // Use line subtotal (price * qty)
            
            $calc = new self( $product );
            
            // If discounts are disabled for this product, skip it
            if ( $calc->are_discounts_disabled() ) continue;

            foreach ( $gateways as $id => $gw_data ) {
                $discount_data = $calc->get_discounted_price( $id );
                $item_discount_pct = floatval( $discount_data['discount'] );
                
                if ( $item_discount_pct > 0 ) {
                    $item_saving = $price * ( $item_discount_pct / 100 );
                    $savings_results[$id]['amount'] += $item_saving;
                }
            }
        }

        // Finalize and format results
        $final_savings = [];
        $base_total = floatval( WC()->cart->get_subtotal() );

        foreach ( $savings_results as $id => $data ) {
            // Only show if there is an actual discount amount
            if ( $data['amount'] > 0 ) {
                $final_total = $base_total - $data['amount'];
                
                $final_savings[$id] = array_merge( $data, [
                    'final_total'     => $final_total,
                    'formatted_total' => wc_price( $final_total ),
                    'formatted_amount'=> wc_price( $data['amount'] ),
                    'label'           => sprintf( __( 'Você economiza %s', 'evo-parcela-flex' ), wc_price( $data['amount'] ) ),
                    'total_label'     => sprintf( __( 'Total no %s: %s', 'evo-parcela-flex' ), self::get_gateway_title( $id ), wc_price( $final_total ) ),
                    'subtotal_label'  => sprintf( __( 'Subtotal no %s: %s', 'evo-parcela-flex' ), self::get_gateway_title( $id ), wc_price( $final_total ) ),
                    'mini_label'      => sprintf( __( 'Você economiza %s', 'evo-parcela-flex' ), wc_price( $data['amount'] ) )
                ]);
            }
        }

        return $final_savings;
    }
}
