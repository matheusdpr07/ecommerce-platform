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
- Status financeiro (`OrderStatus`) e status logistico (`FulfillmentStatus`) evoluem separadamente
- Ajustes administrativos de estoque usam `lockForUpdate`, movimentacao e auditoria na mesma transacao
- Acoes operacionais sensiveis geram registros em `admin_audit_logs`
- Avaliacoes publicas exigem compra entregue e moderacao administrativa
- Notificacoes transacionais sao enfileiradas somente depois do commit da operacao
- Paginacao em listagens que possam crescer
- Eager loading para evitar N+1

## Frontend

- Vue 3 + TypeScript + Inertia.js + Tailwind CSS 4
- Componentes reutilizaveis; composables apenas quando necessario
- Interface em portugues do Brasil
- Layout responsivo com estados de carregamento, vazio e erro
- Identidade da loja configuravel por `STORE_*` e tokens CSS em `app.css`
- Movimento progressivo com Scroll-driven Animations, `transform`/`opacity` e fallback para `prefers-reduced-motion`
- Navegacao Inertia com View Transitions como melhoria progressiva, sem substituir a rolagem nativa
- `StoreScrollStory` transforma banners, categorias e produtos em quatro capitulos; um palco `sticky` muda por `IntersectionObserver`, enquanto os artigos permanecem no fluxo sem scroll hijacking
- A narrativa e decorativa no palco e semantica nos artigos; em movimento reduzido, o palco e removido e todo o conteudo volta ao fluxo estatico

## Seguranca (diretrizes)

- CSRF, validacao rigorosa, rate limiting em auth
- Webhook do Mercado Pago fora do CSRF, com assinatura HMAC, tolerancia temporal e rate limiting dedicado
- Policies para operacoes sensiveis
- Segredos somente em variaveis de ambiente
- Sem credenciais administrativas em seeders versionados

## Integracoes

- **Pagamentos:** `PaymentGateway` isola o dominio do `MercadoPagoGateway`; `PaymentService` concentra idempotencia, sincronizacao de estados e reversao de estoque/cupom
- **Mercado Pago:** Checkout API Orders para Pix, consulta da order no processamento de webhooks e reembolso integral administrativo
- **Operacao administrativa:** `InventoryService`, `FulfillmentService`, `DashboardService` e `AdminAuditService` concentram regras que nao pertencem aos controllers
- **Conteudo e confianca:** `BannerService` publica campanhas agendadas; `ReviewService` valida compra entregue, agrega notas e modera avaliacoes
- **Notificacoes:** `CustomerNotificationService` traduz eventos de pedido, pagamento, entrega e avaliacao em canais `database` e `mail`
- **Frete externo:** provedores de transportadora em fase dedicada; Fase 8 usa metodos internos (`ShippingService`)

## Fluxo de pagamento

1. O checkout cria o pedido e reserva o estoque dentro da transacao local existente.
2. `PaymentService` persiste o pagamento e sua chave de idempotencia antes de chamar o provedor.
3. O Mercado Pago retorna os dados do Pix, armazenados para exibicao na area do cliente.
4. O webhook validado consulta `/v1/orders/{id}` e sincroniza pagamento e pedido em transacao.
5. Falha terminal, expiracao, cancelamento ou reembolso restauram estoque e cupom uma unica vez.

## Fluxo operacional do pedido

1. O pedido aguarda confirmacao financeira sem iniciar a separacao.
2. Depois do pagamento, a operacao avanca sequencialmente por `pending`, `preparing`, `shipped` e `delivered`.
3. Codigo e link de rastreio ficam associados ao pedido e sao exibidos ao cliente.
4. Observacoes internas nunca integram o payload da area do cliente.
5. Encerramentos financeiros cancelam a operacao apenas enquanto o pedido ainda nao foi enviado.

## Fluxo de avaliacao verificada

1. `ReviewService` procura um `order_item` do cliente cujo pedido esteja entregue e financeiramente valido.
2. A avaliacao nasce em `pending` e nao participa da media publica.
3. O admin aprova ou rejeita em `/admin/reviews`; a acao fica em `admin_audit_logs`.
4. Aprovadas aparecem no produto com selo de compra verificada; rejeitadas retornam a orientacao ao cliente.
5. Qualquer edicao remove temporariamente a avaliacao da vitrine e inicia nova moderacao.

## Fluxo de notificacoes

1. Servicos de dominio detectam uma transicao efetiva, evitando mensagens duplicadas em retries.
2. `CustomerActivityNotification` e despachada para banco e e-mail com `afterCommit`.
3. O cliente recebe contador global e consulta somente suas notificacoes em `/notifications`.
4. Abrir a notificacao marca a leitura antes de redirecionar para pedido ou produto.

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
/notifications Central de notificacoes do cliente (auth)
/orders/{id}/payment/pix  Criacao ou retry de Pix (auth)
/webhooks/mercado-pago    Notificacoes financeiras assinadas
/dashboard     Area autenticada (cliente)
/admin/*       Painel administrativo
/admin/inventory       Inventario, alertas e movimentacoes
/admin/orders          Pagamento, separacao, rastreio e entrega
/admin/customers       Consulta de clientes, enderecos e compras
/admin/activity        Historico de auditoria operacional
/admin/banners         Conteudo editorial e campanhas agendadas
/admin/reviews         Moderacao de avaliacoes verificadas
```
