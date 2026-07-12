<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper template to render only the hidden installment table
 * for SlideDown effect in layouts.
 */
$installments = $calculator->get_installments( $gateway_id );
if ( empty($installments) ) return;
?>

<div class="evo-flex-all-installments" style="display:none;" data-gateway="<?php echo esc_attr($gateway_id); ?>">
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
