<?php
/**
 * Minimal Text Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="evo-flex-layout-inner minimal-text-wrap">
    <?php if ( ! empty($show_price) ) : ?>
        <div class="minimal-text-row">
            <span class="minimal-text-price"><?php echo wc_price( $discount_data['final_price'] ); ?></span>
            <span class="minimal-text-label"><?php printf( __( 'à vista no %s', 'evo-parcela-flex' ), \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ( $discount_data['discount'] > 0 && ! empty($show_savings) ) : 
        $savings = $calculator->get_base_price() - $discount_data['final_price'];
        $pct = $discount_data['discount'];
        ?>
        <div class="minimal-text-savings">
            <i class="fa-solid fa-arrow-trend-down"></i>
            <?php printf( __( 'Economize %s (%s%%)', 'evo-parcela-flex' ), wc_price($savings), $pct ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($show_installments) ) : 
        $best = $calculator->get_best_installment_scenario($gateway_id);
        if ( ! empty($best) ) : ?>
            <div class="minimal-text-installments">
                <?php if ( ! empty($best['not_allowed']) ) : ?>
                    <span class="inst-not-allowed"><?php echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']); ?></span>
                <?php else : ?>
                    <span class="inst-label"><?php printf( __( 'ou em até %sx de', 'evo-parcela-flex' ), $best['months'] ); ?></span>
                    <span class="inst-value"><?php echo wc_price($best['value']); ?></span>
                <?php endif; ?>
                <a href="#" class="evo-flex-toggle-installments"><i class="fa-solid fa-circle-info"></i></a>
            </div>
            
            <?php // Keep table for SlideDown mode
            include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table-data.php';
            ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
