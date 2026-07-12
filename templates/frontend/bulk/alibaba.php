<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bulk Pricing Table Template (Alibaba Style)
 */
if ( empty($bulk_data) ) return;
?>
<div class="evo-flex-bulk-pricing-wrap">
    <div class="evo-flex-bulk-header">
        <i class="fa-solid fa-layer-group"></i>
        <span><?php _e( 'Preços por Atacado', 'evo-parcela-flex' ); ?></span>
    </div>
    
    <div class="evo-flex-bulk-tiers">
        <!-- Tier 1: Standard -->
        <div class="evo-flex-bulk-tier">
            <div class="bulk-qty">1 - <?php echo ($bulk_data['min_qty'] - 1); ?> un.</div>
            <div class="bulk-price"><?php echo wc_price($bulk_data['base_price']); ?></div>
            <div class="bulk-label"><?php _e( 'Preço Unitário', 'evo-parcela-flex' ); ?></div>
        </div>

        <!-- Tier 2: Bulk -->
        <div class="evo-flex-bulk-tier highlight">
            <div class="bulk-qty">≥ <?php echo $bulk_data['min_qty']; ?> un.</div>
            <div class="bulk-price"><?php echo wc_price($bulk_data['unit_price']); ?></div>
            <div class="bulk-label">
                <?php 
                if ( $bulk_data['discount_type'] === 'percent' ) {
                    printf( __( '%s%% de Desconto', 'evo-parcela-flex' ), $bulk_data['discount_value'] );
                } else {
                    printf( __( '-%s por un.', 'evo-parcela-flex' ), wc_price($bulk_data['discount_value']) );
                }
                ?>
            </div>
            <div class="bulk-badge"><?php _e( 'MELHOR PREÇO', 'evo-parcela-flex' ); ?></div>
        </div>
    </div>
</div>
