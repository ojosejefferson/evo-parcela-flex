Preciso criar um plugin para 

Nome: Evo Parcela Flex for Woo
Autor: José Jefferson
Versão:5 
Padrões básicos
* MVC (Model-View-Controller)
* Clean Architecture
* Separation of Concerns

 Funcionar com shorcode e Gutenberg 

Vamos fazer um plugin novo para Wordpress/woocommerce  usando o máximo da ultima versão de ambos e máximo de segurança 

O plugin é para aplicar taxas e dar desconto por método de pagamento; 

O plugin precisa funcionar para  todos os tipos de produtos do woocommerce exemplo: (produtos simples,variáveis,grupos de produtos, externo ou afilhados. )



No painel do plugin  simples usando o estilo padrão do woocommerce 
- Configurar taxas  com botão + ou -  para adicionar ou remover taxa exemplo se for em 1x tantos de juros.. se for em 2x tanto de juros.. se caso não tiver ativado quer dizer que não tem juros.

- Mostrar emblema de juros na finalização da compra
Se ativo, irá exibir o emblema de juros na página de finalização de compra para a forma de desconto configurada.

- Juros por método de pagamento
Informe uma taxa de juros por método de pagamento para ser adicionado na finalização da compra.

- Opções checkbox de Exibir preço com desconto no carrinho e checkout por metodo de pagamento

- checkbox para : Remover ou não a faixa de preço em produtos variáveis

-Atualizar preço do produto a partir da quantidade
Ative esta opção para atualizar o preço do produto e seus derivados a partir do seletor de quantidade.

checkbox para Exibir emblema de aprovação imediata pelo metodo de pagamento que tiver ativado.




PAGINA DE PRODUTO WOOCOMMERCE
- Ativar desconto por quantidade Método de desconto
 Percentual (%) OU Valor fixo (R$)
Valor do desconto 
Quantidade mínima para desconto
 Ao ativar essa função colocar message: um Compre x UN e ganhe R$ de desconto



-Desativar descontos neste produto


-Ativar desconto do produto por metodo de pagamento
Método de desconto Percentual (%) ou Valor fixo (R$)		
Valor do desconto
Aplicar desconto para o gateway






1. Dependência de Gateways: Hoje, se na sua loja o Mercado Pago ou a Vindi dão 5% de desconto no Pix automaticamente, você precisa vir no plugin "Parcela Flex" e manualmente digitar 5% também, correndo o risco de esquecer e o desconto ficar defasado. Um plugin feito do zero poderia "ouvir" nativamente as taxas do seu gateway de pagamento real e atualizar as telas sozinho.
2. Remoção Pesada de AJAX (JS): O AJAX atual funciona, mas se sua vitrine for recheada de produtos, várias chamadas de servidor acontecem ao mesmo tempo. Num plugin do zero, o cálculo do loop poderia ser injetado nativamente em bloco PHP direto no momento do cache da página, diminuindo drasticamente requisições de servidor (redução de carga na hospedagem).


<?php
namespace EvoParcelaFlex\Pricing;

use WC_Product;

class Calculator {
    private WC_Product $product;

    public function __construct( WC_Product $product ) {
        $this->product = $product;
    }

    /**
     * Retorna o menor preço disponível dependendo do tipo do produto (Simples, Variável, Agrupado).
     */
    public function get_base_price(): float {
        $price = 0;

        switch ( $this->product->get_type() ) {
            case 'variable':
                $variations = $this->product->get_available_variations();
                $min_price  = PHP_FLOAT_MAX;
                foreach ( $variations as $variation ) {
                    if ( $variation['is_purchasable'] && $variation['is_in_stock'] ) {
                        $var_price = floatval( $variation['display_price'] );
                        if ( $var_price < $min_price ) {
                            $min_price = $var_price;
                        }
                    }
                }
                if ( $min_price !== PHP_FLOAT_MAX ) {
                    $price = $min_price;
                }
                break;

            case 'grouped':
                $children  = $this->product->get_children();
                $min_price = PHP_FLOAT_MAX;
                foreach ( $children as $child_id ) {
                    $child = wc_get_product( $child_id );
                    if ( $child && $child->is_purchasable() && $child->is_in_stock() ) {
                        $child_price = floatval( $child->get_price() );
                        if ( $child_price < $min_price ) {
                            $min_price = $child_price;
                        }
                    }
                }
                if ( $min_price !== PHP_FLOAT_MAX ) {
                    $price = $min_price;
                }
                break;

            default:
                if ( $this->product->is_purchasable() && ( $this->product->is_in_stock() || $this->product->get_type() === 'external' ) ) {
                    $price = floatval( $this->product->get_price() );
                }
                break;
        }

        return $price;
    }

    /**
     * Puxa e calcula o valor com base via Pix.
     */
    public function get_pix_price(): array {
        $base_price = $this->get_base_price();
        // Neste plugin do zero, pegaremos as configs em um array limpo
        $settings      = get_option('evo_flex_settings', []);
        $discount_pct  = floatval( $settings['desconto_pix'] ?? 0 );
        $savings       = $base_price * ( $discount_pct / 100 );
        $final_price   = $base_price - $savings;

        return [
            'base_price'  => $base_price,
            'discount'    => $discount_pct,
            'savings'     => $savings,
            'final_price' => $final_price,
        ];
    }

    /**
     * Calcula as matrizes de todas as parcelas disponíveis baseadas nos juros estipulados
     */
    public function get_installments(): array {
        $base_price    = $this->get_base_price();
        $settings      = get_option('evo_flex_settings', []);
        $min_value     = floatval( $settings['valor_minimo_parcela'] ?? 0 );
        $installments  = [];

        for ( $i = 1; $i <= 12; $i++ ) {
            $juros = $settings["parcelamento_juros_$i"] ?? '';
            
            if ( $juros !== '' && is_numeric( $juros ) ) {
                $juros_val   = floatval( $juros );
                $total_value = $base_price * ( 1 + ( $juros_val / 100 ) );
                $installment_val = $total_value / $i;

                // Apenas insere se atingir a premissa de valor da parcela
                if ( $installment_val >= $min_value || $i === 1 ) {
                    $installments[] = [
                        'months'       => $i,
                        'rate'         => $juros_val,
                        'total'        => $total_value,
                        'installment'  => $installment_val,
                        'has_interest' => $juros_val > 0
                    ];
                }
            }
        }

        return $installments;
    }
}
