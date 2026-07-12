<?php
/**
 * Light Border Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="evo-flex-layout-inner light-border-wrap">
    <?php if ( $discount_data['discount'] > 0 && ! empty($show_savings) ) : ?>
        <div class="light-border-badge">
            <?php printf( __( '%s%% OFF NO %s', 'evo-parcela-flex' ), $discount_data['discount'], \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?>
        </div>
    <?php endif; ?>
    
    <div class="light-border-content">
        <?php if ( ! empty($show_price) ) : ?>
            <div class="light-border-old-price"><?php echo wc_price( $calculator->get_base_price() ); ?></div>
            <div class="light-border-final-price"><?php echo wc_price( $discount_data['final_price'] ); ?></div>
        <?php endif; ?>
        <div class="light-border-note">
            <?php printf( __( 'Aproveite o melhor preço pagando com %s.', 'evo-parcela-flex' ), \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?>
        </div>

        <?php if ( ! empty($show_installments) ) : 
            $best = $calculator->get_best_installment_scenario($gateway_id);
            if ( ! empty($best) ) : ?>
                <div class="light-border-installments">
                    <?php if ( ! empty($best['not_allowed']) ) : ?>
                        <span class="inst-not-allowed"><?php echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']); ?></span>
                    <?php else : ?>
                        <span class="inst-label"><?php printf( __( 'ou %sx de %s', 'evo-parcela-flex' ), $best['months'], wc_price($best['value']) ); ?></span>
                    <?php endif; ?>
                    <a href="#" class="evo-flex-toggle-installments"><i class="fa-solid fa-up-right-from-square"></i></a>
                </div>
                <?php include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table-data.php'; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
