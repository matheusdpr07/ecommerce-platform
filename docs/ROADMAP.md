# Roadmap

## Fases

1. **Fundacao, documentacao e ambiente** — concluida
2. **Autenticacao, usuarios e autorizacao administrativa** — concluida
3. **Categorias e marcas** — concluida
4. **Produtos, variacoes, imagens e estoque** — concluida
5. **Loja publica, busca, filtros e pagina do produto** — concluida
6. **Carrinho e lista de desejos** — concluida
7. **Cupons e promocoes** — concluida
8. **Enderecos, frete e checkout** — concluida
9. **Pedidos e controle transacional de estoque** — concluida
10. **Mercado Pago, Pix, webhooks e reembolsos** — concluida
11. **Painel administrativo completo** — concluida
12. **Avaliacoes, conteudo e notificacoes** — concluida
13. **SEO, acessibilidade e desempenho** — em andamento
14. Seguranca, testes, CI e preparacao para producao

---

## Fase 3 — Categorias e marcas

**Status:** concluida

### Concluido

- Migracao do banco principal para **MySQL** (`.env.example`)
- Tabelas `categories` e `brands` com slug, status, SEO basico e soft deletes
- Hierarquia de categorias via `parent_id`
- CRUD administrativo em `/admin/categories` e `/admin/brands`
- Paginacao, busca e filtro por status nas listagens
- Policies `CategoryPolicy` e `BrandPolicy` (somente admin)
- Form Requests com validacao e mensagens em portugues
- Layout administrativo com navegacao dedicada
- Protecao contra exclusao de categoria com subcategorias

### Pendente nesta fase

- Nenhum

### Decisoes

- MySQL substitui PostgreSQL por decisao do projeto (documentado em `DECISIONS.md`)
- Slug gerado automaticamente quando omitido no formulario
- Soft deletes em categorias e marcas

### Testes relacionados

- `tests/Feature/Admin/CategoryManagementTest.php`
- `tests/Feature/Admin/BrandManagementTest.php`

### Proxima fase recomendada

**Fase 4:** Produtos, variacoes, imagens e estoque

---

## Fase 4 — Produtos, variacoes, imagens e estoque

**Status:** concluida

### Concluido

- Tabelas `products`, `product_variants`, `product_images` e `stock_movements`
- Produtos vinculados a categoria (obrigatoria) e marca (opcional)
- Precos em centavos nas variacoes; estoque por variacao
- CRUD administrativo em `/admin/products`
- Upload de imagens no disco `public` (max. 2 MB por arquivo)
- Historico de movimentacao de estoque (`initial`, `manual_adjustment`, `restock`)
- Paginacao, busca (nome, slug, SKU) e filtros por status e categoria
- Policies `ProductPolicy` (somente admin)
- Exclusao de categoria/marca bloqueada quando houver produtos vinculados
- Soft delete em produtos

### Pendente nesta fase

- Nenhum

### Decisoes

- Preco e estoque ficam na variacao, nao no produto pai
- SKU unico globalmente entre variacoes
- Imagens associadas ao produto (nao a variacao individual)
- Movimentacoes de estoque registradas ao criar variacao e ao alterar quantidade

### Testes relacionados

- `tests/Feature/Admin/ProductManagementTest.php`

### Proxima fase recomendada

**Fase 5:** Loja publica, busca, filtros e pagina do produto

---

## Fase 5 — Loja publica, busca, filtros e pagina do produto

**Status:** concluida

### Concluido

- Vitrine publica em `/` acessivel sem autenticacao
- Listagem paginada de produtos ativos com categoria, marca e variacao ativa
- Busca por nome, descricao, slug e SKU
- Filtros por categoria, marca, faixa de preco e ordenacao
- Pagina de categoria em `/categories/{slug}`
- Pagina de produto em `/products/{slug}` com galeria, variacoes e estoque
- Layout dedicado da loja (`StoreLayout`) separado do admin
- Produtos inativos ou com categoria/marca inativa ocultos da vitrine

### Pendente nesta fase

- Nenhum

### Decisoes

- Loja publica nao exige cadastro para navegar
- Apenas produtos visiveis no storefront (ativos, categoria ativa, variacao ativa)
- Compra e carrinho ficam para a Fase 6

### Testes relacionados

- `tests/Feature/Store/StorefrontTest.php`

### Proxima fase recomendada

**Fase 6:** Carrinho e lista de desejos

---

## Fase 6 — Carrinho e lista de desejos

**Status:** concluida

### Concluido

- Tabelas `carts`, `cart_items` e `wishlist_items`
- Carrinho para visitantes (sessao) e usuarios autenticados
- Merge automatico do carrinho de convidado ao login ou cadastro
- Pagina `/cart` com atualizacao de quantidade e remocao de itens
- Lista de desejos autenticada em `/wishlist`
- Adicionar aos favoritos na pagina do produto
- Mover item da wishlist para o carrinho
- Contadores de carrinho e favoritos no layout da loja
- Validacao de estoque e precos calculados no backend

### Pendente nesta fase

- Nenhum

### Decisoes

- Carrinho aberto a visitantes; wishlist exige autenticacao
- Preco e subtotal sempre recalculados a partir das variacoes no backend
- Checkout e reserva de estoque ficam para fases futuras

### Testes relacionados

- `tests/Feature/Store/CartTest.php`
- `tests/Feature/Store/WishlistTest.php`

### Proxima fase recomendada

**Fase 7:** Cupons e promocoes

---

## Fase 7 — Cupons e promocoes

**Status:** concluida

### Concluido

- Tabelas `coupons`, `promotions` e `coupon_id` em `carts`
- CRUD administrativo em `/admin/coupons` e `/admin/promotions`
- Cupons percentuais ou valor fixo, com pedido minimo, limite de uso e vigencia
- Promocoes automaticas por produto, categoria, marca ou catalogo inteiro
- Precos promocionais na vitrine, pagina do produto, carrinho e wishlist
- Aplicacao e remocao de cupom no carrinho (`/cart`)
- Desconto de cupom calculado no backend sobre o subtotal promocional
- Merge de cupom de convidado ao login/cadastro

### Pendente nesta fase

- Nenhum

### Decisoes

- Promocoes aplicadas automaticamente; cupons exigem codigo no carrinho
- Entre promocoes elegiveis, prevalece o menor preco final para o cliente
- Contagem de uso do cupom permanece para a fase de pedidos (checkout)

### Testes relacionados

- `tests/Feature/Admin/CouponManagementTest.php`
- `tests/Feature/Admin/PromotionManagementTest.php`
- `tests/Feature/Store/CouponTest.php`

### Proxima fase recomendada

**Fase 8:** Enderecos, frete e checkout

---

## Fase 8 — Enderecos, frete e checkout

**Status:** concluida

### Concluido

- Tabelas `addresses`, `shipping_methods` e campos de checkout em `carts`
- CRUD de enderecos do cliente em `/addresses` (auth + e-mail verificado)
- CRUD administrativo de frete em `/admin/shipping-methods`
- Checkout em `/checkout` com selecao de endereco e metodo de envio
- Calculo de frete no backend (`ShippingService`), incluindo frete gratis por subtotal
- Total do checkout = subtotal promocional − desconto do cupom + frete
- Limpeza dos campos de checkout ao esvaziar o carrinho
- Validacao de UF brasileira via `BrazilianStates`

### Pendente nesta fase

- Nenhum (criacao de pedido e reserva de estoque na Fase 9)

### Decisoes

- Checkout exige login e e-mail verificado; carrinho de convidado redireciona para auth
- Frete configurado internamente; integracao com transportadoras fica para fase dedicada
- Pedido ainda nao e criado nesta fase — apenas preparacao do carrinho para a Fase 9

### Testes relacionados

- `tests/Feature/Store/AddressManagementTest.php`
- `tests/Feature/Admin/ShippingMethodManagementTest.php`
- `tests/Feature/Store/CheckoutTest.php`

### Proxima fase recomendada

**Fase 9:** Pedidos e controle transacional de estoque

---

## Fase 9 — Pedidos e controle transacional de estoque

**Status:** concluida

### Concluido

- Tabelas `orders` e `order_items` com snapshot de precos, endereco e frete
- Enum `OrderStatus` (`pending_payment`) e motivo `sale` em movimentacoes de estoque
- Confirmacao de pedido via `POST /checkout` com transacao de banco
- Baixa transacional de estoque com `lockForUpdate` e registro em `stock_movements`
- Incremento de `usage_count` do cupom ao confirmar pedido
- Limpeza do carrinho apos pedido criado
- Listagem e detalhe de pedidos em `/orders` e `/orders/{order}`
- Botao "Confirmar pedido" no checkout quando endereco e frete estao selecionados

### Pendente nesta fase

- Nenhum (pagamento e mudanca de status na Fase 10)

### Decisoes

- Pedidos iniciam com status `pending_payment` ate integracao de pagamento
- Precos, endereco e frete congelados no pedido (snapshot), independente de alteracoes futuras
- Estoque decrementado na confirmacao, nao na selecao do checkout

### Testes relacionados

- `tests/Feature/Store/OrderTest.php`

### Proxima fase recomendada

**Fase 10:** Mercado Pago, Pix, webhooks e reembolsos

---

## Fase 10 — Mercado Pago, Pix, webhooks e reembolsos

**Status:** concluida

### Concluido

- Tabelas `payments` e `webhook_events` com estados tipados e payloads auditaveis
- Abstracao `PaymentGateway` e integracao Mercado Pago Checkout API Orders
- Criacao automatica de Pix no checkout, com QR Code, Copia e Cola e link de pagamento
- Chaves de idempotencia persistidas para criacao e reembolso
- Webhook publico `POST /webhooks/mercado-pago` com validacao HMAC e tolerancia temporal
- Consulta da order no provedor antes de sincronizar estados financeiros locais
- Transicoes de pedido para pago, falha, cancelamento, expiracao, reembolso e contestacao
- Reversao idempotente de estoque e uso de cupom quando a cobranca e encerrada ou reembolsada
- Listagem e detalhe de pedidos no admin, com reembolso integral autorizado por policy
- Interface do cliente para acompanhar o pagamento e gerar novamente um Pix ainda nao criado

### Pendente nesta fase

- Nenhum

### Decisoes

- O banco local e a fonte persistente das chaves de idempotencia e do historico de webhooks
- Notificacoes nunca atualizam o pagamento apenas com o payload recebido; a order e consultada na API
- Reembolso administrativo e integral nesta fase; reembolso parcial permanece fora do escopo
- Falha, expiracao, cancelamento e reembolso liberam estoque e uso do cupom no maximo uma vez

### Testes relacionados

- `tests/Feature/Store/PaymentTest.php`
- `tests/Feature/Webhooks/MercadoPagoWebhookTest.php`
- `tests/Feature/Admin/OrderManagementTest.php`
- `tests/Feature/Store/OrderTest.php`

### Proxima fase recomendada

**Fase 11:** Painel administrativo completo

---

## Fase 11 — Painel administrativo completo

**Status:** concluida

### Concluido

- Dashboard administrativo com periodos de 7, 30 e 90 dias
- Receita liquida de reembolsos, pedidos pagos, ticket medio, pagamentos pendentes, reembolsos e novos clientes
- Tendencia diaria, pedidos recentes, alertas operacionais e atividade administrativa
- Inventario dedicado em `/admin/inventory`, com busca e filtros de saldo, estoque baixo, falta e inatividade
- Reposicao e correcao atomica de saldo com `lockForUpdate`, movimentacao e auditoria
- Limite de estoque baixo configuravel por variacao
- Status logistico separado do financeiro, com fluxo `pending` → `preparing` → `shipped` → `delivered`
- Codigo e link de rastreio, datas de cada etapa e observacoes internas do pedido
- Acompanhamento da entrega na area do cliente sem exposicao de observacoes internas
- Filtros administrativos de pedidos por financeiro, logistica e periodo
- Consulta somente leitura de clientes, enderecos, pedidos, gasto liquido e ticket medio
- Auditoria de ajustes de estoque, rastreio, etapas logisticas, observacoes e reembolsos
- Historico filtravel em `/admin/activity`, sem exposicao dos metadados tecnicos
- Navegacao administrativa reorganizada em menu lateral responsivo

### Pendente nesta fase

- Nenhum

### Decisoes

- `OrderStatus` permanece financeiro; `FulfillmentStatus` representa exclusivamente a operacao de entrega
- A logistica so avanca depois do pagamento e sempre na ordem definida
- Encerramentos financeiros cancelam a logistica somente antes do envio
- Clientes sao apenas consultados no admin; papel, senha e exclusao nao sao alterados por essas telas
- Receita e gasto de cliente usam `amount_cents - refunded_amount_cents`
- Graficos do dashboard usam Vue e CSS, sem dependencia externa

### Testes relacionados

- `tests/Feature/Admin/AdminOperationalFoundationTest.php`
- `tests/Feature/Admin/InventoryManagementTest.php`
- `tests/Feature/Admin/OrderFulfillmentManagementTest.php`
- `tests/Feature/Admin/CustomerManagementTest.php`
- `tests/Feature/Admin/DashboardTest.php`
- `tests/Feature/Admin/AdminActivityTest.php`
- `tests/Feature/Admin/OrderManagementTest.php`
- `tests/Feature/Store/PaymentTest.php`
- `tests/Feature/Webhooks/MercadoPagoWebhookTest.php`

### Proxima fase recomendada

**Fase 12:** Avaliacoes, conteudo e notificacoes

---

## Fase 12 — Avaliacoes, conteudo e notificacoes

**Status:** concluida

### Concluido

- Sistema visual editorial configuravel com identidade, navegacao, rodape e estados vazios consistentes
- Rolagem nativa aprimorada por `animation-timeline`, barra de progresso e revelacoes progressivas
- Preferencia `prefers-reduced-motion` respeitada e transicoes Inertia usadas como melhoria progressiva
- Homepage editorial responsiva com descoberta por categorias, catalogo, busca e filtros moveis
- Banners administraveis com upload, texto alternativo, tema, posicao, ordem, CTA e janela de publicacao
- Avaliacoes restritas a clientes com compra entregue e verificacao pelo `order_item`
- Media, distribuicao de notas e nome publico parcialmente anonimizado na pagina do produto
- Edicao de avaliacao com retorno automatico para moderacao
- Fila de moderacao administrativa com aprovacao, rejeicao, retorno ao cliente e auditoria
- Central privada de notificacoes com contador, leitura individual e leitura em massa
- Notificacoes transacionais por banco e e-mail enfileirado depois do commit
- Eventos cobertos: pedido criado, pagamento, reembolso, entrega e moderacao de avaliacao
- Redesign das jornadas de produto, carrinho, checkout, favoritos, pedidos e area do cliente

### Pendente nesta fase

- Nenhum

### Decisoes

- A rolagem continua nativa; animacoes CSS complementam a experiencia sem scroll hijacking
- Conteudo de campanha usa `banners` e agendamento no backend, sem textos promocionais obrigatoriamente fixos no frontend
- Somente compras entregues podem avaliar; alteracoes deixam de ser publicas ate nova aprovacao
- Notificacoes usam os canais `database` e `mail`, com fila configurada para disparo apos commit
- Identidade da loja usa variaveis `STORE_*`, preservando a plataforma como base reutilizavel

### Testes relacionados

- `tests/Feature/Admin/BannerManagementTest.php`
- `tests/Feature/Store/ReviewTest.php`
- `tests/Feature/Store/NotificationCenterTest.php`
- `tests/Feature/Store/StorefrontTest.php`
- `tests/Feature/Store/CartTest.php`
- `tests/Feature/Store/CheckoutTest.php`
- `tests/Feature/Store/CheckoutAuthenticationFlowTest.php`
- `tests/Feature/Store/WishlistTest.php`
- `tests/Feature/Store/OrderTest.php`
- `tests/Feature/Admin/OrderFulfillmentManagementTest.php`

### Proxima fase recomendada

**Fase 13:** SEO, acessibilidade e desempenho

---

## Fase 13 — SEO, acessibilidade e desempenho

**Status:** em andamento

### Concluido

- Homepage reorganizada em quatro capitulos narrativos ligados a rolagem
- Cenarios construidos com CSS, banners e produtos reais da loja, sem ativos copiados da referencia visual
- Troca de cena por `IntersectionObserver`, sem listener continuo durante a rolagem
- Um gesto vertical da roda avanca um capitulo, com bloqueio curto guiado pelo fim da inercia e liberacao natural na primeira e na ultima cena
- Rolagem nativa preservada para toque, teclado, movimento reduzido e para o restante da pagina
- Experiencia responsiva validada visualmente em desktop e celular
- Fallback de `prefers-reduced-motion` validado com todos os capitulos visiveis e interativos
- Hierarquia da homepage corrigida para um unico `h1`
- Ferramenta `website-scraper` isolada em `devDependencies`, fora do bundle entregue ao cliente
- Vite local travado identificado e o build de producao restaurado para a navegacao local

### Pendente nesta fase

- Metadados canonicos, Open Graph e Twitter Cards por pagina
- Dados estruturados de organizacao, produto, breadcrumbs e avaliacoes
- `sitemap.xml`, `robots.txt` e politica de indexacao
- Variantes responsivas, dimensoes e formatos modernos para imagens administraveis
- Auditoria completa de teclado, contraste, landmarks, rotulos e mensagens dinamicas
- Medicao Lighthouse e orcamento para Core Web Vitals
- Cache e compressao recomendados para o ambiente de producao

### Decisoes

- Referencias externas orientam principios de composicao e interacao, nunca a copia de marca, texto, imagem ou codigo
- A narrativa usa `position: sticky` e `IntersectionObserver`; o encaixe da roda e local e nenhuma biblioteca de scroll hijacking entra no runtime
- Produtos, categorias e banners continuam sendo a unica fonte de conteudo comercial da homepage
- Sem movimento, os capitulos voltam ao fluxo normal e nenhuma acao fica oculta

### Testes relacionados

- `tests/Feature/Store/StorefrontTest.php`
- `npm run build`
- Revisao visual automatizada em 1440x1000 e 390x844
- Emulacao de `prefers-reduced-motion: reduce`
