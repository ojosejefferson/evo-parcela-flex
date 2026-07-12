<?php
/**
 * Dark Card Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="evo-flex-layout-inner dark-card-wrap">
    <div class="dark-card-header">
        <span class="dark-card-title"><?php _e( 'Melhor Preço', 'evo-parcela-flex' ); ?></span>
        <?php if ( $discount_data['discount'] > 0 && ! empty($show_savings) ) : ?>
            <div class="dark-card-badge"><?php echo $discount_data['discount']; ?>% OFF</div>
        <?php endif; ?>
    </div>
    
    <div class="dark-card-body">
        <?php if ( ! empty($show_price) ) : ?>
            <div class="dark-card-price">
                <?php echo wc_price( $discount_data['final_price'] ); ?>
            </div>
        <?php endif; ?>
        
        <?php if ( ! empty($show_installments) ) : 
            $best = $calculator->get_best_installment_scenario($gateway_id);
            if ( $best ) : ?>
                <div class="dark-card-subtext">
                    <?php if ( ! empty($best['not_allowed']) ) : ?>
                        <span class="evo-no-installments"><?php echo esc_html($best['not_allowed_msg']) . ' ' . wc_price($best['min_required']); ?></span>
                    <?php else : ?>
                        <?php printf( 
                            __( 'Ou %sx de %s no cartão', 'evo-parcela-flex' ), 
                            $best['months'], 
                            wc_price($best['value'])
                        ); ?>
                    <?php endif; ?>
                    <a href="#" class="evo-flex-toggle-installments"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
                <?php include EVO_PARCELA_FLEX_PATH . 'templates/frontend/installment-table-data.php'; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
