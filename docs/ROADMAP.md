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
9. Pedidos e controle transacional de estoque
10. Mercado Pago, Pix, webhooks e reembolsos
11. Painel administrativo completo
12. Avaliacoes, conteudo e notificacoes
13. SEO, acessibilidade e desempenho
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

**Fase 9:** Pedidos e controle transacional de estoque

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
