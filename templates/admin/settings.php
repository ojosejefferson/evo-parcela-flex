<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'evo_flex_settings', [] );
$gateways = WC()->payment_gateways->get_available_payment_gateways();

/**
 * Helper to render a toggle switch
 */
function evo_flex_render_toggle($name, $value) {
    $checked = checked( 1, $value, false );
    return '
    <label class="evo-flex-switch">
        <input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . $checked . '>
        <span class="evo-flex-slider"></span>
    </label>';
}
?>

<div class="wrap evo-flex-admin-wrap">
    <div class="evo-flex-header">
        <div class="evo-flex-header-content">
            <h1><i class="fa-solid fa-gears"></i> <?php _e( 'Evo Parcela Flex', 'evo-parcela-flex' ); ?></h1>
            <p><?php _e( 'Gerencie descontos, parcelamentos e a experiência de checkout de forma inteligente.', 'evo-parcela-flex' ); ?></p>
        </div>
    </div>

    <div class="evo-flex-dashboard">
        <!-- Sidebar Navigation -->
        <aside class="evo-flex-sidebar">
            <nav>
                <a href="#" class="evo-flex-nav-item active" data-target="#section-display">
                    <i class="fa-solid fa-palette"></i> <?php _e( 'Visual e Destaque', 'evo-parcela-flex' ); ?>
                </a>
                <a href="#" class="evo-flex-nav-item" data-target="#section-rules">
                    <i class="fa-solid fa-scale-balanced"></i> <?php _e( 'Regras de Negócio', 'evo-parcela-flex' ); ?>
                </a>
                <a href="#" class="evo-flex-nav-item" data-target="#section-gateways">
                    <i class="fa-solid fa-credit-card"></i> <?php _e( 'Métodos de Pagamento', 'evo-parcela-flex' ); ?>
                </a>
                <a href="#" class="evo-flex-nav-item" data-target="#section-checkout">
                    <i class="fa-solid fa-cart-shopping"></i> <?php _e( 'Checkout & Carrinho', 'evo-parcela-flex' ); ?>
                </a>
                <a href="#" class="evo-flex-nav-item" data-target="#section-json-models">
                    <i class="fa-solid fa-code"></i> <?php _e( 'Modelos JSON', 'evo-parcela-flex' ); ?>
                </a>
                <a href="#" class="evo-flex-nav-item" data-target="#section-support">
                    <i class="fa-solid fa-circle-question"></i> <?php _e( 'Suporte & Docs', 'evo-parcela-flex' ); ?>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="evo-flex-main">
            <form method="post" action="options.php">
                <?php settings_fields( 'evo_flex_group' ); ?>

                <!-- Section: Visual e Destaque -->
                <div id="section-display" class="evo-flex-section active">
                    <div class="evo-flex-card">
                        <div class="evo-flex-card-header">
                            <i class="fa-solid fa-palette"></i>
                            <h2><?php _e( 'Visual e Layout', 'evo-parcela-flex' ); ?></h2>
                        </div>
                        <div class="evo-flex-card-body">
                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Modelo de Layout (Página de Produto)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Selecione o estilo visual. O shortcode [evo_parcela_flex] seguirá esta escolha automaticamente.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <div class="evo-flex-layout-selector-wrap">
                                    <select name="evo_flex_settings[highlight_layout]" id="evo_flex_layout_select">
                                        <option value="default" <?php selected( $settings['highlight_layout'] ?? '', 'default' ); ?>><?php _e( 'Padrão (Lista)', 'evo-parcela-flex' ); ?></option>
                                        <option value="dark-card" <?php selected( $settings['highlight_layout'] ?? '', 'dark-card' ); ?>><?php _e( 'Modelo 11 - Dark Card', 'evo-parcela-flex' ); ?></option>
                                        <option value="light-border" <?php selected( $settings['highlight_layout'] ?? '', 'light-border' ); ?>><?php _e( 'Modelo 12 - Light Border', 'evo-parcela-flex' ); ?></option>
                                        <option value="brand-solid" <?php selected( $settings['highlight_layout'] ?? '', 'brand-solid' ); ?>><?php _e( 'Modelo 13 - Brand Solid', 'evo-parcela-flex' ); ?></option>
                                        <option value="minimal-text" <?php selected( $settings['highlight_layout'] ?? '', 'minimal-text' ); ?>><?php _e( 'Modelo 14 - Minimal Text', 'evo-parcela-flex' ); ?></option>
                                        <option value="horizontal-bar" <?php selected( $settings['highlight_layout'] ?? '', 'horizontal-bar' ); ?>><?php _e( 'Modelo 15 - Horizontal Bar', 'evo-parcela-flex' ); ?></option>
                                        
                                        <?php 
                                        $custom_layouts = get_option('evo_flex_custom_layouts', []);
                                        if ( ! empty($custom_layouts) ) : ?>
                                            <optgroup label="<?php _e( 'Modelos Customizados (Importados)', 'evo-parcela-flex' ); ?>">
                                                <?php foreach ( $custom_layouts as $id => $layout ) : ?>
                                                    <option value="<?php echo esc_attr($id); ?>" <?php selected( $settings['highlight_layout'] ?? '', $id ); ?>><?php echo esc_html($layout['name']); ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                    <div id="evo-flex-layout-preview" class="evo-flex-layout-preview">
                                        <div class="preview-placeholder"><?php _e( 'Selecione um modelo para ver o preview', 'evo-parcela-flex' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Exibir Preço do Gateway', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Mostra o valor final com desconto no card.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <?php echo evo_flex_render_toggle('evo_flex_settings[show_price_global]', $settings['show_price_global'] ?? 1); ?>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Exibir Nota de Economia', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Mostra quanto o cliente economiza ou o badge de % OFF.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <?php echo evo_flex_render_toggle('evo_flex_settings[show_savings_global]', $settings['show_savings_global'] ?? 1); ?>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Exibir Nota de Parcelamento', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Mostra as opções de parcelamento ou o aviso de valor mínimo.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <?php echo evo_flex_render_toggle('evo_flex_settings[show_installments_global]', $settings['show_installments_global'] ?? 1); ?>
                            </div>

                            <hr>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Método de Exibição (Página de Produto)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha se o destaque aparece automaticamente ou se você prefere usar o shortcode manualmente.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[display_method]">
                                    <option value="both" <?php selected( $settings['display_method'] ?? 'both', 'both' ); ?>><?php _e( 'Automático (Ganchos)', 'evo-parcela-flex' ); ?></option>
                                    <option value="shortcode" <?php selected( $settings['display_method'] ?? 'both', 'shortcode' ); ?>><?php _e( 'Manual (Apenas Shortcode)', 'evo-parcela-flex' ); ?></option>
                                </select>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Gateway em Destaque (Página de Produto)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha qual método de pagamento será o principal no layout de destaque.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[product_page_gateway]">
                                    <option value="pix" <?php selected( $settings['product_page_gateway'] ?? 'pix', 'pix' ); ?>><?php _e( 'Padrão (Pix)', 'evo-parcela-flex' ); ?></option>
                                    <?php foreach ( $gateways as $id => $gateway ) : ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected( $settings['product_page_gateway'] ?? '', $id ); ?>><?php echo esc_html($gateway->get_title()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Destaque na Listagem (Archives)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha qual gateway aparecerá nas páginas de loja e categorias.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[archive_gateway]">
                                    <option value=""><?php _e( 'Nenhum', 'evo-parcela-flex' ); ?></option>
                                    <?php foreach ( $gateways as $id => $gateway ) : ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected( $settings['archive_gateway'] ?? '', $id ); ?>><?php echo esc_html($gateway->get_title()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr>
                            <h4><?php _e( 'Tabela de Parcelas', 'evo-parcela-flex' ); ?></h4>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Modo de Exibição da Tabela', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Como a tabela detalhada deve ser aberta ao clicar no link.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[table_display_mode]">
                                    <option value="toggle" <?php selected( $settings['table_display_mode'] ?? 'toggle', 'toggle' ); ?>><?php _e( 'Slide Down (Abaixo do Card)', 'evo-parcela-flex' ); ?></option>
                                    <option value="modal" <?php selected( $settings['table_display_mode'] ?? 'toggle', 'modal' ); ?>><?php _e( 'Pop-up Modal (Centralizado)', 'evo-parcela-flex' ); ?></option>
                                </select>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Layout do Atacado (Alibaba)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha o visual da tabela de desconto por quantidade.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[bulk_layout]">
                                    <option value="alibaba" <?php selected( $settings['bulk_layout'] ?? 'alibaba', 'alibaba' ); ?>><?php _e( 'Alibaba Style (Cards)', 'evo-parcela-flex' ); ?></option>
                                    <option value="simple-list" <?php selected( $settings['bulk_layout'] ?? 'alibaba', 'simple-list' ); ?>><?php _e( 'Lista Minimalista', 'evo-parcela-flex' ); ?></option>
                                    <option value="compact-badges" <?php selected( $settings['bulk_layout'] ?? 'alibaba', 'compact-badges' ); ?>><?php _e( 'Badges Compactos', 'evo-parcela-flex' ); ?></option>
                                </select>
                            </div>

                            <div class="evo-flex-field-vertical">
                                <label><?php _e( 'Conteúdo Customizado do Modal (Opcional)', 'evo-parcela-flex' ); ?></label>
                                <span><?php _e( 'Se estiver usando o modo Modal, você pode personalizar o conteúdo aqui. Use o shortcode [evo_installment_table] para exibir a tabela dinâmica.', 'evo-parcela-flex' ); ?></span>
                                <textarea name="evo_flex_settings[modal_custom_content]" style="width: 100%; height: 100px; margin-top: 10px;" placeholder='Ex: [wp_block id="123"] ou [evo_installment_table]'><?php echo esc_textarea( $settings['modal_custom_content'] ?? '' ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Regras de Negócio -->
                <div id="section-rules" class="evo-flex-section">
                    <div class="evo-flex-card">
                        <div class="evo-flex-card-header">
                            <i class="fa-solid fa-scale-balanced"></i>
                            <h2><?php _e( 'Regras de Parcelamento', 'evo-parcela-flex' ); ?></h2>
                        </div>
                        <div class="evo-flex-card-body">
                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Destaque de Economia', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha qual gateway servirá de base para o shortcode [evo_savings].', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[savings_gateway]">
                                    <option value=""><?php _e( 'Nenhum', 'evo-parcela-flex' ); ?></option>
                                    <?php foreach ( $gateways as $id => $gateway ) : ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected( $settings['savings_gateway'] ?? '', $id ); ?>><?php echo esc_html($gateway->get_title()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Destaque de Parcelas (Shortcode Global)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha qual gateway servirá de base para o shortcode [evo_best_installment].', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[installment_gateway]">
                                    <option value=""><?php _e( 'Nenhum', 'evo-parcela-flex' ); ?></option>
                                    <?php foreach ( $gateways as $id => $gateway ) : ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected( $settings['installment_gateway'] ?? '', $id ); ?>><?php echo esc_html($gateway->get_title()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Aplicar Valor Mínimo para qual Gateway?', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Escolha se a restrição de preço mínimo deve afetar todos os métodos ou um específico (ex: Cartão de Crédito).', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <select name="evo_flex_settings[min_price_gateway]">
                                    <option value="all" <?php selected( $settings['min_price_gateway'] ?? 'all', 'all' ); ?>><?php _e( 'Todos os Gateways', 'evo-parcela-flex' ); ?></option>
                                    <?php foreach ( $gateways as $id => $gateway ) : ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected( $settings['min_price_gateway'] ?? '', $id ); ?>><?php echo esc_html($gateway->get_title()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Valor Mínimo do Produto para Parcelar (R$)', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Define o preço mínimo do produto para liberar o parcelamento no gateway selecionado acima.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <input type="number" step="0.01" name="evo_flex_settings[valor_minimo_produto_parcela]" value="<?php echo esc_attr( $settings['valor_minimo_produto_parcela'] ?? 100.00 ); ?>" style="width: 100px;" />
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Texto de Parcelamento Indisponível', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Texto exibido quando o produto não atinge o valor mínimo para parcelar.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <input type="text" name="evo_flex_settings[msg_parcelamento_indisponivel]" value="<?php echo esc_attr( $settings['msg_parcelamento_indisponivel'] ?? 'Parcelamento disponível apenas para compras acima de' ); ?>" class="widefat" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Checkout -->
                <div id="section-checkout" class="evo-flex-section">
                    <div class="evo-flex-card">
                        <div class="evo-flex-card-header">
                            <i class="fa-solid fa-shield-check"></i>
                            <h2><?php _e( 'Destaques e Selos', 'evo-parcela-flex' ); ?></h2>
                        </div>
                        <div class="evo-flex-card-body">
                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Selos de Juros no Checkout', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Exibe badges coloridos de "Sem Juros" ou "Com Juros" nos métodos de pagamento.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <?php echo evo_flex_render_toggle('evo_flex_settings[show_interest_badge]', $settings['show_interest_badge'] ?? 0); ?>
                            </div>

                            <div class="evo-flex-field">
                                <div class="evo-flex-field-info">
                                    <label><?php _e( 'Selo de Aprovação Imediata', 'evo-parcela-flex' ); ?></label>
                                    <span><?php _e( 'Mostra um selo de confiança para métodos como Pix e Cartão.', 'evo-parcela-flex' ); ?></span>
                                </div>
                                <?php echo evo_flex_render_toggle('evo_flex_settings[show_approval_badge]', $settings['show_approval_badge'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Gateways -->
                <div id="section-gateways" class="evo-flex-section">
                    <div class="evo-flex-card">
                        <div class="evo-flex-card-header">
                            <i class="fa-solid fa-list-check"></i>
                            <h2><?php _e( 'Configuração por Método', 'evo-parcela-flex' ); ?></h2>
                        </div>
                        <div class="evo-flex-card-body">
                            <div class="evo-flex-gateways-list">
                                <?php foreach ( $gateways as $id => $gateway ) : 
                                    $gw_settings = $settings['gateways'][$id] ?? [];
                                    $gw_installments = $gw_settings['installments'] ?? [];
                                    ?>
                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="fa-solid fa-chevron-right"></i>
                                                <strong><?php echo esc_html($gateway->get_title()); ?></strong>
                                                <span class="badge-shortcode copy-shortcode">[evo_<?php echo $id; ?>]</span>
                                            </div>
                                            <div class="gateway-summary">
                                                <span><?php _e( 'Desconto:', 'evo-parcela-flex' ); ?> <strong><?php echo esc_attr( $gw_settings['discount'] ?? 0 ); ?>%</strong></span>
                                            </div>
                                        </div>
                                        <div class="gateway-content">
                                            <div class="evo-flex-grid-2">
                                                <div class="gw-col">
                                                    <div class="evo-flex-field">
                                                        <div class="evo-flex-field-info">
                                                            <label><?php _e( 'Desconto Base (%)', 'evo-parcela-flex' ); ?></label>
                                                        </div>
                                                        <input type="number" step="0.01" name="evo_flex_settings[gateways][<?php echo $id; ?>][discount]" value="<?php echo esc_attr( $gw_settings['discount'] ?? 0 ); ?>" style="width: 80px;" />
                                                    </div>

                                                    <div class="evo-flex-field">
                                                        <div class="evo-flex-field-info">
                                                            <label><?php _e( 'Exibir Ícone no Produto', 'evo-parcela-flex' ); ?></label>
                                                        </div>
                                                        <?php echo evo_flex_render_toggle("evo_flex_settings[gateways][{$id}][show_icon_product]", $gw_settings['show_icon_product'] ?? 0); ?>
                                                    </div>
                                                    
                                                    <div class="evo-flex-field-vertical">
                                                        <label><?php _e( 'URL do Ícone Personalizado', 'evo-parcela-flex' ); ?></label>
                                                        <div style="display: flex; gap: 10px;">
                                                            <input type="text" name="evo_flex_settings[gateways][<?php echo $id; ?>][icon_url]" value="<?php echo esc_attr( $gw_settings['icon_url'] ?? '' ); ?>" class="widefat" />
                                                            <button type="button" class="button button-secondary evo-flex-upload-btn"><?php _e( 'Upload', 'evo-parcela-flex' ); ?></button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="gw-col">
                                                    <div class="evo-flex-field">
                                                        <div class="evo-flex-field-info">
                                                            <label><?php _e( 'Exibir no Carrinho', 'evo-parcela-flex' ); ?></label>
                                                        </div>
                                                        <?php echo evo_flex_render_toggle("evo_flex_settings[gateways][{$id}][show_in_cart]", $gw_settings['show_in_cart'] ?? 0); ?>
                                                    </div>
                                                    <div class="evo-flex-field">
                                                        <div class="evo-flex-field-info">
                                                            <label><?php _e( 'Exibir no Mini Carrinho', 'evo-parcela-flex' ); ?></label>
                                                        </div>
                                                        <?php echo evo_flex_render_toggle("evo_flex_settings[gateways][{$id}][show_in_mini_cart]", $gw_settings['show_in_mini_cart'] ?? 0); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="gateway-installments-section">
                                                <h3><i class="fa-solid fa-chart-line"></i> <?php _e( 'Tabela de Parcelamento', 'evo-parcela-flex' ); ?></h3>
                                                <div class="installments-list" data-gateway="<?php echo $id; ?>">
                                                    <?php 
                                                    if ( empty( $gw_installments ) ) {
                                                        $gw_installments = [ 1 => ['rate' => 0] ];
                                                    }
                                                    foreach ( $gw_installments as $months => $rate_data ) : ?>
                                                        <div class="installment-row">
                                                            <div class="inst-col">
                                                                <input type="number" name="evo_flex_settings[gateways][<?php echo $id; ?>][installments][<?php echo $months; ?>][months]" value="<?php echo esc_attr( $months ); ?>" class="inst-months" />
                                                                <span>x</span>
                                                            </div>
                                                            <div class="inst-col">
                                                                <input type="number" step="0.01" name="evo_flex_settings[gateways][<?php echo $id; ?>][installments][<?php echo $months; ?>][rate]" value="<?php echo esc_attr( $rate_data['rate'] ?? 0 ); ?>" class="inst-rate" />
                                                                <span>% <?php _e( 'juros', 'evo-parcela-flex' ); ?></span>
                                                            </div>
                                                            <button type="button" class="remove-inst-row"><i class="fa-solid fa-trash-can"></i></button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button type="button" class="add-inst-row button" data-gateway="<?php echo $id; ?>"><i class="fa-solid fa-plus"></i> <?php _e( 'Adicionar Parcela', 'evo-parcela-flex' ); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: JSON Models -->
                <div id="section-json-models" class="evo-flex-section">
                    <div class="evo-flex-card">
                        <div class="evo-flex-card-header">
                            <i class="fa-solid fa-code-merge"></i>
                            <h2><?php _e( 'Gerenciador de Modelos JSON', 'evo-parcela-flex' ); ?></h2>
                        </div>
                        <div class="evo-flex-card-body">
                            <p style="margin-bottom: 20px; color: #64748b; font-size: 14px;">
                                <?php _e( 'Aqui você pode importar novos designs colando o código JSON. Você também pode exportar suas configurações para usar em outros sites.', 'evo-parcela-flex' ); ?>
                            </p>

                            <div class="evo-flex-field-vertical">
                                <label><?php _e( 'Importar Novo Modelo (JSON)', 'evo-parcela-flex' ); ?></label>
                                <div class="evo-flex-template-help" style="background: #f0f9ff; border: 1px solid #bae6fd; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                                    <strong style="color: #0369a1; display: block; margin-bottom: 10px;"><i class="fa-solid fa-circle-info"></i> <?php _e( 'Variáveis Disponíveis:', 'evo-parcela-flex' ); ?></strong>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; color: #334155;">
                                        <code>{{price}}</code>: <?php _e( 'Preço final com desconto', 'evo-parcela-flex' ); ?><br>
                                        <code>{{base_price}}</code>: <?php _e( 'Preço original do produto', 'evo-parcela-flex' ); ?><br>
                                        <code>{{discount}}</code>: <?php _e( 'Porcentagem de desconto (ex: 10%)', 'evo-parcela-flex' ); ?><br>
                                        <code>{{gateway}}</code>: <?php _e( 'Nome do método (ex: PIX)', 'evo-parcela-flex' ); ?><br>
                                        <code>{{icon}}</code>: <?php _e( 'HTML do ícone do gateway', 'evo-parcela-flex' ); ?><br>
                                        <code>{{installments_text}}</code>: <?php _e( 'Texto da melhor parcela (ex: 12x de R$ 10)', 'evo-parcela-flex' ); ?><br>
                                        <code>{{installment_value}}</code>: <?php _e( 'Valor da parcela única', 'evo-parcela-flex' ); ?><br>
                                        <code>{{months}}</code>: <?php _e( 'Número de parcelas', 'evo-parcela-flex' ); ?>
                                    </div>
                                </div>
                                <textarea id="evo-flex-import-json" style="width: 100%; height: 200px; font-family: monospace; font-size: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px;" placeholder='{"id": "custom-layout-id", "name": "Nome do Modelo", "html": "...", "css": "..."}'></textarea>
                                <div style="margin-top: 10px;">
                                    <button type="button" id="evo-flex-btn-import" class="button button-secondary"><i class="fa-solid fa-file-import"></i> <?php _e( 'Processar e Salvar Modelo', 'evo-parcela-flex' ); ?></button>
                                </div>
                            </div>

                            <div class="custom-layouts-list-wrap" style="margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                <h3><?php _e( 'Modelos Customizados Instalados', 'evo-parcela-flex' ); ?></h3>
                                <div id="evo-flex-custom-list" class="evo-flex-grid-2">
                                    <!-- Listagem via JS -->
                                    <div class="no-custom-models" style="grid-column: span 2; padding: 40px; text-align: center; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                                        <i class="fa-solid fa-box-open" style="font-size: 30px; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                                        <?php _e( 'Nenhum modelo customizado instalado ainda.', 'evo-parcela-flex' ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Support / Documentation -->
                <div id="section-support" class="evo-flex-section">
                    <div class="evo-flex-card">
                        <div class="evo-flex-card-header">
                            <i class="fa-solid fa-book"></i>
                            <h2><?php _e( 'Guia de Shortcodes', 'evo-parcela-flex' ); ?></h2>
                        </div>
                        <div class="evo-flex-card-body">
                            <p><?php _e( 'Use os shortcodes abaixo para exibir informações de preços e parcelas em qualquer lugar do seu site.', 'evo-parcela-flex' ); ?></p>
                            
                            <div class="evo-flex-shortcode-guide">
                                <!-- Super Shortcode -->
                                <div class="shortcode-item" style="grid-column: span 2; background: #f0f9ff; border: 2px solid #0ea5e9;">
                                    <div class="shortcode-meta" style="flex-direction: row; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="color: #0369a1; font-size: 18px; margin-bottom: 5px;"><?php _e( 'O Shortcode Único', 'evo-parcela-flex' ); ?></h4>
                                        </div>
                                        <span class="copy-shortcode" style="background: #0ea5e9; color: #fff; padding: 10px 20px; font-size: 16px;">[evo_parcela_flex]</span>
                                    </div>
                                    <div class="shortcode-usage-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #bae6fd;">
                                        <div class="usage-col">
                                            <strong><?php _e( 'O que você quer mostrar?', 'evo-parcela-flex' ); ?></strong>
                                            <ul style="margin: 10px 0; font-size: 13px; color: #334155;">
                                                <li><code>type="full"</code> - <?php _e( 'Caixa completa (Padrão)', 'evo-parcela-flex' ); ?></li>
                                                <li><code>type="economia"</code> - <?php _e( 'Apenas o bloco de economia', 'evo-parcela-flex' ); ?></li>
                                                <li><code>type="parcelas"</code> - <?php _e( 'Apenas o bloco de parcelas', 'evo-parcela-flex' ); ?></li>
                                                <li><code>type="preco"</code> - <?php _e( 'Apenas o preço final', 'evo-parcela-flex' ); ?></li>
                                            </ul>
                                        </div>
                                        <div class="usage-col">
                                            <strong><?php _e( 'Customização Extra:', 'evo-parcela-flex' ); ?></strong>
                                            <ul style="margin: 10px 0; font-size: 13px; color: #334155;">
                                                <li><code>layout="dark-card"</code> - <?php _e( 'Forçar um visual específico', 'evo-parcela-flex' ); ?></li>
                                                <li><code>method="pix"</code> - <?php _e( 'Escolher o método de pagamento', 'evo-parcela-flex' ); ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Best Installment -->
                                <div class="shortcode-item">
                                    <div class="shortcode-meta">
                                        <span class="copy-shortcode">[evo_best_installment]</span>
                                        <h4><?php _e( 'Destaque de Parcelas (Inteligente)', 'evo-parcela-flex' ); ?></h4>
                                    </div>
                                    <p><?php _e( 'Exibe a melhor condição de parcelamento do gateway escolhido nas Configurações Gerais.', 'evo-parcela-flex' ); ?></p>
                                    <div class="shortcode-example"><strong><?php _e( 'Exemplo:', 'evo-parcela-flex' ); ?></strong> <em>Em até 12x de R$ 50,00 sem juros</em></div>
                                </div>

                                <!-- Savings -->
                                <div class="shortcode-item">
                                    <div class="shortcode-meta">
                                        <span class="copy-shortcode">[evo_savings]</span>
                                        <h4><?php _e( 'Destaque de Economia', 'evo-parcela-flex' ); ?></h4>
                                    </div>
                                    <p><?php _e( 'Mostra quanto o cliente economiza pagando à vista (ex: Pix).', 'evo-parcela-flex' ); ?></p>
                                    <div class="shortcode-example"><strong><?php _e( 'Exemplo:', 'evo-parcela-flex' ); ?></strong> <em>Economize R$ 10,00 (10%) - Pague apenas R$ 90,00</em></div>
                                </div>

                                <!-- Specific Gateway Table -->
                                <div class="shortcode-item">
                                    <div class="shortcode-meta">
                                        <span class="copy-shortcode">[evo_ID_DO_METODO]</span>
                                        <h4><?php _e( 'Tabela Completa de Gateway', 'evo-parcela-flex' ); ?></h4>
                                    </div>
                                    <p><?php _e( 'Exibe a caixa completa com desconto à vista e tabela de parcelamento de um gateway específico. Ex: [evo_cod]', 'evo-parcela-flex' ); ?></p>
                                </div>

                                <!-- Specific Gateway Price -->
                                <div class="shortcode-item">
                                    <div class="shortcode-meta">
                                        <span class="copy-shortcode">[evo_ID_DO_METODO type="price"]</span>
                                        <h4><?php _e( 'Preço Único de Gateway', 'evo-parcela-flex' ); ?></h4>
                                    </div>
                                    <p><?php _e( 'Exibe apenas o preço final com desconto para um método específico. Ex: [evo_cod type="price"]', 'evo-parcela-flex' ); ?></p>
                                </div>
                            </div>

                            <div class="evo-flex-support-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <div class="support-links">
                                    <a href="https://evosites.com.br" target="_blank" class="button"><i class="fa-solid fa-headset"></i> <?php _e( 'Abrir Ticket de Suporte', 'evo-parcela-flex' ); ?></a>
                                </div>
                                <div class="plugin-ver" style="font-size: 12px; color: #94a3b8;">
                                    Evo Parcela Flex v<?php echo EVO_PARCELA_FLEX_VERSION; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="evo-flex-footer">
                    <button type="submit" class="button button-primary large">
                        <i class="fa-solid fa-floppy-disk"></i> <?php _e( 'Salvar Configurações', 'evo-parcela-flex' ); ?>
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<style>
.evo-flex-shortcode-guide { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
.shortcode-item { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
.shortcode-meta { display: flex; flex-direction: column; gap: 10px; margin-bottom: 10px; }
.shortcode-meta h4 { margin: 0; font-size: 15px; color: #1e293b; }
.shortcode-item p { margin: 0 0 10px 0; font-size: 13px; color: #64748b; line-height: 1.5; }
.shortcode-example { background: #fff; padding: 8px 12px; border-radius: 6px; font-size: 12px; border: 1px dashed #cbd5e1; }
.copy-shortcode { width: fit-content; }
.evo-flex-field-vertical { margin-bottom: 15px; }
.evo-flex-field-vertical label { display: block; font-weight: 600; margin-bottom: 8px; }
.badge-shortcode { background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 11px; cursor: pointer; }
.gateway-summary span { font-size: 13px; color: #64748b; }
.gateway-installments-section { margin-top: 25px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
.gateway-installments-section h3 { font-size: 14px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; color: #475569; }
.installment-row { display: flex; align-items: center; gap: 15px; background: #fff; padding: 10px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #f1f5f9; }
.inst-col { display: flex; align-items: center; gap: 5px; }
.inst-col span { font-weight: 600; }
.inst-months { width: 60px !important; }
.inst-rate { width: 80px !important; color: #4f46e5; font-weight: 700; }
.remove-inst-row { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px; transition: opacity 0.2s; }
.remove-inst-row:hover { opacity: 0.7; }
.evo-flex-footer { position: sticky; bottom: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 20px; margin: 20px -20px -20px -20px; border-top: 1px solid #e2e8f0; text-align: right; z-index: 100; }
</style>
