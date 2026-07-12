# WooCommerce Payment Gateway Discount Plugin

Plugin para WooCommerce que adiciona descontos personalizados por método de pagamento, exibição dinâmica de preços com desconto e badges promocionais no checkout, carrinho e página do produto.

## Recursos

- Desconto por gateway de pagamento
- Atualização automática do desconto em produtos variáveis
- Exibir melhor preço no checkout
- Badge personalizado por método de pagamento
- Mostrar desconto no carrinho
- Compatibilidade com WooCommerce
- Configuração individual por gateway

---

## Problema Corrigido

### Produtos Variáveis

Ao selecionar uma variação com preço diferente, o desconto configurado no gateway não era recalculado dinamicamente.

### Solução

O plugin agora:

- Detecta alterações de variação
- Atualiza o valor com desconto em tempo real
- Atualiza badges e preços promocionais automaticamente
- Mantém compatibilidade com AJAX do WooCommerce

---

## Recursos Planejados / Ajustes

### Checkout

Adicionar opção nos gateways para:

- Mostrar preço original
- Mostrar preço com desconto
- Mostrar texto:
  
  "Melhor preço pagando com PIX"

Exemplo:

De: R$ 199,90  
Por: R$ 179,90 no PIX

---

### Badge Individual

Cada método de pagamento poderá ter:

- Ativar/desativar badge
- Texto personalizado
- Cor personalizada
- Exibição no produto
- Exibição no checkout

Exemplo:

-5% no PIX  
Ou  
Economize no boleto

---

### Carrinho WooCommerce

Adicionar opção:

- Mostrar economia por gateway no carrinho

Exemplo:

Você economiza R$ 20,00 pagando com PIX.

---

## Compatibilidade

- WooCommerce
- Produtos simples
- Produtos variáveis
- Checkout padrão WooCommerce

---

## Melhorias Técnicas

- Atualização dinâmica via JavaScript
- Hooks WooCommerce
- Compatibilidade com AJAX
- Cálculo em tempo real

---

## Objetivo

Melhorar conversão e aumentar pagamentos via métodos com menor taxa, como PIX e boleto.

---

## Modelos de Layout JSON (Exemplos)

Você pode copiar e colar os modelos abaixo na aba **Modelos JSON** do plugin.

### 1. Premium Glassmorphism Card
Um card moderno com efeito de desfoque (glassmorphism), gradientes e animações suaves.

**JSON para Importar:**
```json
{
  "id": "premium-glass-card",
  "name": "Premium Glassmorphism Card",
  "html": "<div class='evo-premium-card'><div class='evo-badge'>{{discount}} OFF</div><div class='evo-main-info'>{{icon}} <div class='evo-price-wrap'><span class='evo-old-price'>{{base_price}}</span><span class='evo-current-price'>{{price}}</span></div></div><div class='evo-gateway-info'>Pagamento à vista no <strong>{{gateway}}</strong></div><div class='evo-divider'></div><div class='evo-installments'><i class='fa-solid fa-credit-card'></i> ou {{installments_text}}</div><div class='evo-footer-note'>Parcelas de apenas {{installment_value}}</div></div>",
  "css": ".evo-premium-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 24px; padding: 25px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); font-family: 'Outfit', sans-serif; position: relative; overflow: hidden; margin: 20px 0; transition: all 0.3s ease; } .evo-premium-card:hover { transform: translateY(-5px); box-shadow: 0 20px 50px -15px rgba(0,0,0,0.15); } .evo-badge { position: absolute; top: 0; right: 0; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 6px 18px; border-bottom-left-radius: 20px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; } .evo-main-info { display: flex; align-items: center; gap: 20px; margin-bottom: 12px; } .evo-flex-gw-icon-product { width: 45px !important; height: auto !important; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); } .evo-price-wrap { display: flex; flex-direction: column; } .evo-old-price { text-decoration: line-through; color: #94a3b8; font-size: 15px; margin-bottom: -5px; } .evo-current-price { color: #1e293b; font-size: 32px; font-weight: 800; letter-spacing: -1px; } .evo-gateway-info { font-size: 14px; color: #64748b; margin-left: 2px; } .evo-divider { height: 1px; background: linear-gradient(to right, rgba(0,0,0,0.08), transparent); margin: 20px 0; } .evo-installments { font-size: 16px; color: #334155; font-weight: 600; display: flex; align-items: center; gap: 10px; } .evo-installments i { color: #6366f1; font-size: 18px; } .evo-footer-note { font-size: 12px; color: #94a3b8; margin-top: 8px; font-style: italic; }"
}
```