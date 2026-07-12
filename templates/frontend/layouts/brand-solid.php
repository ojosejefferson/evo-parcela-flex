<?php
/**
 * Brand Solid Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="evo-flex-layout-inner brand-solid-wrap">
    <div class="brand-solid-watermark"><?php echo esc_html( \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?></div>

    <div class="brand-solid-header">
        <?php printf( __( 'Preço Especial no %s', 'evo-parcela-flex' ), \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?>
    </div>
    
    <?php if ( ! empty($show_price) ) : ?>
        <div class="brand-solid-price">
            <?php echo wc_price( $discount_data['final_price'] ); ?>
        </div>
    <?php endif; ?>
    
    <?php if ( $discount_data['discount'] > 0 && ! empty($show_savings) ) : 
        $savings = $calculator->get_base_price() - $discount_data['final_price'];
        ?>
        <div class="brand-solid-savings">
            <?php printf( __( 'Você economiza %s', 'evo-parcela-flex' ), wc_price($savings) ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($show_installments) ) : 
        $best = $calculator->get_best_installment_scenario($gateway_id);
        if ( ! empty($best) ) : ?>
            <div class="brand-solid-installments">
                <?php if ( ! empty($best['not_allowed']) ) : ?>
                    <span class="inst-not-allowed"><?php echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']); ?></span>
                <?php else : ?>
                    <span class="inst-text"><?php printf( __( 'Ou em até %sx de %s', 'evo-parcela-flex' ), $best['months'], wc_price($best['value']) ); ?></span>
                <?php endif; ?>
                <a href="#" class="evo-flex-toggle-installments"><?php _e( 'Ver parcelas', 'evo-parcela-flex' ); ?></a>
            </div>
            
            <?php include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table-data.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
