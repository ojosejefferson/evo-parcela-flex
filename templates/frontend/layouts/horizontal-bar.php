<?php
/**
 * Horizontal Bar Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="evo-flex-layout-inner horizontal-bar-wrap">
    <div class="horizontal-bar-content">
        <div class="horizontal-bar-left">
            <?php if ( ! empty($icon_html) ) : ?>
                <div class="horizontal-bar-icon"><?php echo $icon_html; ?></div>
            <?php else : ?>
                <div class="horizontal-bar-icon-default"><i class="fa-solid fa-bolt"></i></div>
            <?php endif; ?>
            <span class="horizontal-bar-prefix"><?php _e( 'à vista', 'evo-parcela-flex' ); ?></span>
        </div>
        
        <?php if ( ! empty($show_price) ) : ?>
            <div class="horizontal-bar-center">
                <span class="horizontal-bar-price"><?php echo wc_price( $discount_data['final_price'] ); ?></span>
                <span class="horizontal-bar-label"><?php printf( __( 'no %s', 'evo-parcela-flex' ), \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ( $discount_data['discount'] > 0 && ! empty($show_savings) ) : ?>
            <div class="horizontal-bar-right">
                <span class="horizontal-bar-badge">-<?php echo $discount_data['discount']; ?>%</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( ! empty($show_installments) ) : 
        $best = $calculator->get_best_installment_scenario($gateway_id);
        if ( ! empty($best) ) : ?>
            <div class="horizontal-bar-footer">
                <?php if ( ! empty($best['not_allowed']) ) : ?>
                    <span class="inst-not-allowed"><?php echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']); ?></span>
                <?php else : ?>
                    <span class="inst-label"><?php printf( __( 'ou em até %sx de %s', 'evo-parcela-flex' ), $best['months'], wc_price($best['value']) ); ?></span>
                <?php endif; ?>
                <a href="#" class="evo-flex-toggle-installments"><i class="fa-solid fa-circle-plus"></i></a>
            </div>
            <?php include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table-data.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
