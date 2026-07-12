<?php
/**
 * Default Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="evo-flex-layout-inner">
    <?php if ( $display_type === 'price' ) : ?>
        <div class="evo-flex-single-price evo-flex-pix-price">
            <?php echo $icon_html; ?>
            <span class="evo-flex-value"><?php echo wc_price( $discount_data['final_price'] ); ?></span>
            <span class="evo-flex-label"><?php printf( __( 'no %s', 'evo-parcela-flex' ), \EvoParcelaFlex\Model\Calculator::get_gateway_title( $gateway_id ) ); ?></span>
            <?php if ( $discount_data['discount'] > 0 ) : ?>
                <span class="evo-flex-badge">-<?php echo $discount_data['discount']; ?>%</span>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <?php 
        $current_gateway_id = $gateway_id;
        $pix_data = $discount_data;
        include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table.php'; 
        ?>
    <?php endif; ?>
</div>
