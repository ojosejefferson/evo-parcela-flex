<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simple List Layout for Bulk Pricing
 */
if ( empty($bulk_data) ) return;
?>
<div class="evo-flex-bulk-simple-list">
    <p class="bulk-list-title"><i class="fa-solid fa-tags"></i> <?php _e( 'Ofertas por Atacado:', 'evo-parcela-flex' ); ?></p>
    <ul>
        <li>
            <span class="qty">1-<?php echo ($bulk_data['min_qty'] - 1); ?> un.:</span>
            <span class="price"><?php echo wc_price($bulk_data['base_price']); ?></span>
        </li>
        <li class="discount-highlight">
            <span class="qty"><?php echo $bulk_data['min_qty']; ?>+ un.:</span>
            <span class="price"><?php echo wc_price($bulk_data['unit_price']); ?></span>
            <span class="badge">
                <?php 
                if ( $bulk_data['discount_type'] === 'percent' ) {
                    echo '-' . $bulk_data['discount_value'] . '%';
                } else {
                    echo '-' . wc_price($bulk_data['discount_value']);
                }
                ?>
            </span>
        </li>
    </ul>
</div>
