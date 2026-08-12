# Arquitetura

## Visao geral

Monolito modular Laravel com frontend Inertia (Vue 3 + TypeScript). Uma unica aplicacao serve loja publica, area do cliente e painel administrativo (`/admin`).

## Camadas

| Camada | Responsabilidade |
|--------|------------------|
| Controllers | Orquestracao HTTP; controllers pequenos |
| Form Requests | Validacao de entrada |
| Policies | Autorizacao no backend |
| Middleware | Controle de acesso (cliente vs admin) |
| Models (Eloquent) | Persistencia, relacionamentos, casts, scopes |
| Actions / Services | Regras de negocio complexas apenas quando necessario |
| API Resources | Respostas de API quando aplicavel |

## O que evitamos

- Microservicos nesta etapa
- Repository Pattern quando Eloquent bastar
- DTOs e interfaces sem beneficio claro
- Duplicar regras de negocio no frontend

## Regras de negocio criticas

- Valores monetarios em **centavos** (inteiros), nunca `float`
- Backend e fonte de verdade para precos, estoque, descontos e permissoes
- Operacoes financeiras, checkout e estoque usam **transacoes**
- Criacao e reembolso de pagamentos usam chaves de idempotencia persistidas
- Webhooks financeiros sao autenticados e reconciliados com consulta ao provedor
- Paginacao em listagens que possam crescer
- Eager loading para evitar N+1

## Frontend

- Vue 3 + TypeScript + Inertia.js + Tailwind CSS 4
- Componentes reutilizaveis; composables apenas quando necessario
- Interface em portugues do Brasil
- Layout responsivo com estados de carregamento, vazio e erro

## Seguranca (diretrizes)

- CSRF, validacao rigorosa, rate limiting em auth
- Webhook do Mercado Pago fora do CSRF, com assinatura HMAC, tolerancia temporal e rate limiting dedicado
- Policies para operacoes sensiveis
- Segredos somente em variaveis de ambiente
- Sem credenciais administrativas em seeders versionados

## Integracoes

- **Pagamentos:** `PaymentGateway` isola o dominio do `MercadoPagoGateway`; `PaymentService` concentra idempotencia, sincronizacao de estados e reversao de estoque/cupom
- **Mercado Pago:** Checkout API Orders para Pix, consulta da order no processamento de webhooks e reembolso integral administrativo
- **Frete externo:** provedores de transportadora em fase dedicada; Fase 8 usa metodos internos (`ShippingService`)

## Fluxo de pagamento

1. O checkout cria o pedido e reserva o estoque dentro da transacao local existente.
2. `PaymentService` persiste o pagamento e sua chave de idempotencia antes de chamar o provedor.
3. O Mercado Pago retorna os dados do Pix, armazenados para exibicao na area do cliente.
4. O webhook validado consulta `/v1/orders/{id}` e sincroniza pagamento e pedido em transacao.
5. Falha terminal, expiracao, cancelamento ou reembolso restauram estoque e cupom uma unica vez.

## Estrutura de rotas

```
/              Loja publica (vitrine)
/products/*    Pagina do produto
/categories/*  Listagem por categoria
/cart          Carrinho
/wishlist      Lista de desejos (auth)
/addresses     Enderecos do cliente (auth)
/checkout      Checkout (auth)
/orders        Pedidos do cliente (auth)
/orders/{id}/payment/pix  Criacao ou retry de Pix (auth)
/webhooks/mercado-pago    Notificacoes financeiras assinadas
/dashboard     Area autenticada (cliente)
/admin/*       Painel administrativo (pedidos, cupons, promocoes, frete, catalogo)
```
