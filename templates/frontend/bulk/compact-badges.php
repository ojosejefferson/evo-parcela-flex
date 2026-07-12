<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Compact Badges Layout for Bulk Pricing
 */
if ( empty($bulk_data) ) return;
?>
<div class="evo-flex-bulk-compact">
    <div class="bulk-badge-item">
        <span class="label"><?php _e( 'Preço Normal', 'evo-parcela-flex' ); ?></span>
        <span class="value"><?php echo wc_price($bulk_data['base_price']); ?></span>
    </div>
    <div class="bulk-badge-item highlight">
        <span class="label"><?php printf( __( 'Leve %d+', 'evo-parcela-flex' ), $bulk_data['min_qty'] ); ?></span>
        <span class="value"><?php echo wc_price($bulk_data['unit_price']); ?></span>
        <div class="save-tag">
            <?php 
            if ( $bulk_data['discount_type'] === 'percent' ) {
                printf( __( 'Salva %s%%', 'evo-parcela-flex' ), $bulk_data['discount_value'] );
            } else {
                printf( __( 'Economiza %s', 'evo-parcela-flex' ), wc_price($bulk_data['discount_value']) );
            }
            ?>
        </div>
    </div>
</div>
