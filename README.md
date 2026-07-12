# Evo Parcela Flex for Woo

Plugin WordPress/WooCommerce para gerenciamento inteligente de descontos, taxas e parcelamento por método de pagamento, com suporte a bulk pricing e múltiplos layouts visuais.

## Recursos

- **Desconto por gateway** — configure % ou valor fixo de desconto para PIX, boleto, cartão e qualquer outro método
- **Parcelamento dinâmico** — exibe parcelas com ou sem juros por gateway, com cálculo automático
- **Bulk pricing** — desconto progressivo por quantidade de itens
- **Badges no checkout** — exibe desconto, juros, aprovação imediata e badges customizados por método
- **Economia no carrinho e mini-carrinho** — mostra quanto o cliente economiza por gateway
- **6 layouts nativos** — Default, Brand Solid, Dark Card, Horizontal Bar, Light Border, Minimal Text
- **Layouts JSON customizados** — crie e importe layouts próprios via painel admin
- **Shortcodes** — exiba parcelamento e descontos em qualquer lugar do site
- **Suporte a WooCommerce Blocks** — compatível com o checkout em blocos (Store API)
- **Override por produto** — desative desconto ou configure valores individuais por produto

## Requisitos

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+

## Instalação

1. Faça o upload da pasta `evo-parcela-woo` para `/wp-content/plugins/`
2. Ative o plugin em **WordPress → Plugins**
3. Acesse **WooCommerce → Evo Parcela Flex** para configurar

## Configuração

### Gateways de Pagamento

Em **WooCommerce → Evo Parcela Flex**, adicione os gateways desejados e configure:

| Campo | Descrição |
|---|---|
| Desconto (%) | Percentual de desconto à vista |
| Parcelamento | Número de parcelas, taxa de juros por parcela |
| Badge customizado | Texto e cor do badge exibido no checkout |
| Ícone | URL do ícone exibido ao lado do método |

### Shortcodes

```
[evo_parcela_flex method="pix" type="full"]
[evo_parcela_flex method="pix" type="price"]
[evo_parcela_flex method="pix" type="economia"]
[evo_parcela_flex method="pix" type="parcelas"]
[evo_installment_table gateway="pix"]
[evo_savings]
[evo_best_installment]
```

### Override por Produto

Na aba **Evo Parcela Flex** dentro de cada produto é possível:
- Desativar desconto para aquele produto
- Definir preço mínimo por parcela
- Configurar desconto por quantidade individual
- Sobrescrever desconto por gateway

## Layouts

### Nativos

| Layout | Descrição |
|---|---|
| `default` | Layout padrão com tabela de parcelamento |
| `brand-solid` | Card sólido com marca do gateway em destaque |
| `dark-card` | Card escuro com preço em evidência |
| `horizontal-bar` | Barra horizontal compacta |
| `light-border` | Badge de desconto + preço com borda |
| `minimal-text` | Texto minimalista sem elementos visuais extras |

### Bulk Pricing

| Estilo | Descrição |
|---|---|
| `alibaba` | Tabela estilo Alibaba com faixas de quantidade |
| `compact-badges` | Badges compactos lado a lado |
| `simple-list` | Lista simples de descontos |

### Layouts JSON Customizados

Importe layouts em JSON com HTML e CSS próprios via **WooCommerce → Evo Parcela Flex → Layouts Customizados**.

Placeholders disponíveis: `{{price}}`, `{{base_price}}`, `{{discount}}`, `{{icon}}`, `{{gateway}}`, `{{installments_text}}`, `{{installment_value}}`, `{{months}}`

## Arquitetura

```
evo-parcela-woo/
├── evo-parcela-flex.php          # Entry point
├── includes/
│   ├── Autoloader.php            # PSR-4 autoloader
│   ├── Model/
│   │   ├── Calculator.php        # Engine de cálculo de preços e parcelamentos
│   │   └── Logger.php            # Sistema de logs (debug mode)
│   └── Controller/
│       ├── AdminController.php   # Painel de configurações
│       ├── FrontendController.php# Shortcodes, exibição e AJAX
│       └── CheckoutController.php# Descontos no checkout e carrinho
├── templates/
│   ├── admin/settings.php
│   └── frontend/
│       ├── layouts/              # 6 layouts nativos
│       └── bulk/                 # 3 estilos de bulk pricing
└── assets/
    ├── css/
    └── js/
```

## Autor

**José Jefferson** — [GitHub](https://github.com/ojosejefferson)

## Licença

GPL-2.0-or-later
