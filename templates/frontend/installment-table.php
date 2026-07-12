<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var array $installments
 * @var array $pix_data
 * @var WC_Product $product
 * @var EvoParcelaFlex\Model\Calculator $calculator
 * @var string $current_gateway_id
 */

$qty_min = $product->get_meta( '_evo_flex_qty_min' );
$qty_discount = $product->get_meta( '_evo_flex_qty_discount_value' );
?>

<div class="evo-flex-pricing-box" data-base-price="<?php echo esc_attr($calculator->get_base_price()); ?>">
    <?php if ( ! empty($show_price) && ! empty( $pix_data ) && $pix_data['discount'] > 0 ) : ?>
        <div class="evo-flex-pix-price">
            <?php if ( ! empty( $icon_html ) ) echo $icon_html; ?>
            <span class="evo-flex-label">
                <?php 
                $label = ( isset($current_gateway_id) && $current_gateway_id !== 'pix' ) ? sprintf(__('Pagando com %s:', 'evo-parcela-flex'), \EvoParcelaFlex\Model\Calculator::get_gateway_title( $current_gateway_id )) : __('Pagando com Pix:', 'evo-parcela-flex');
                echo $label;
                ?>
            </span>
            <span class="evo-flex-value"><?php echo wc_price( $pix_data['final_price'] ); ?></span>
            <?php if ( ! empty($show_savings) ) : ?>
                <span class="evo-flex-badge"><?php printf( __( '%s de desconto', 'evo-parcela-flex' ), $pix_data['discount'] . '%' ); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($show_installments) && ! empty( $installments ) ) : 
        $best = $installments[0]; 
        if ( ! empty($best['not_allowed']) ) : ?>
            <div class="evo-flex-best-installment evo-no-installments-msg" style="padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #64748b; border: 1px solid #e2e8f0; margin-top: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-circle-info" style="color: #3b82f6; font-size: 16px;"></i> 
                <span><?php echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']); ?></span>
            </div>
        <?php else : ?>
            <div class="evo-flex-best-installment">
                <span class="evo-flex-label"><?php echo sprintf( __( 'Ou em até %dx de', 'evo-parcela-flex' ), $best['months'] ); ?></span>
                <span class="evo-flex-value"><?php echo wc_price( $best['installment'] ); ?></span>
                <?php if ( ! $best['has_interest'] ) : ?>
                    <span class="evo-flex-badge no-interest"><?php _e( 'Sem Juros', 'evo-parcela-flex' ); ?></span>
                <?php endif; ?>
            </div>

            <a href="#" class="evo-flex-toggle-installments"><?php _e( 'Ver todas as formas de parcelamento', 'evo-parcela-flex' ); ?></a>

            <div class="evo-flex-all-installments" style="display:none;" data-gateway="<?php echo esc_attr($current_gateway_id); ?>">
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
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( $qty_min && $qty_discount ) : ?>
        <div class="evo-flex-qty-discount">
            <i class="dashicons dashicons-tag"></i>
            <?php printf( __( 'Compre %d UN e ganhe %s de desconto', 'evo-parcela-flex' ), $qty_min, ( is_numeric( $qty_discount ) ? wc_price( $qty_discount ) : $qty_discount ) ); ?>
        </div>
    <?php endif; ?>
</div>
