# Banco de dados

## Motor

MySQL em desenvolvimento e producao. SQLite in-memory apenas nos testes automatizados.

## Convencoes

- Tabelas e colunas em ingles, snake_case
- Valores monetarios em centavos (`integer`)
- Quantidades de estoque como inteiros
- Soft deletes apenas quando fizer sentido de negocio
- Indices em colunas de filtro, busca e foreign keys

## Entidades implementadas

### Usuarios e autenticacao

- `users` — clientes e administradores via coluna `role` (`customer`, `admin`)
- `password_reset_tokens`, `sessions` — padrao Laravel

### Catalogo

#### `categories`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| parent_id | bigint nullable | FK self, hierarquia |
| name | string | index |
| slug | string unique | URLs amigaveis |
| description | text nullable | |
| is_active | boolean | index, default true |
| sort_order | unsigned int | default 0, index |
| meta_title | string nullable | SEO |
| meta_description | string nullable | SEO |
| deleted_at | timestamp nullable | soft delete |

#### `brands`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| name | string | index |
| slug | string unique | |
| description | text nullable | |
| is_active | boolean | index, default true |
| meta_title | string nullable | SEO |
| meta_description | string nullable | SEO |
| deleted_at | timestamp nullable | soft delete |

#### `products`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| category_id | bigint | FK `categories`, restrict on delete |
| brand_id | bigint nullable | FK `brands`, null on delete |
| name | string | index |
| slug | string unique | URLs amigaveis |
| description | text nullable | |
| is_active | boolean | index, default true |
| meta_title | string nullable | SEO |
| meta_description | string nullable | SEO |
| deleted_at | timestamp nullable | soft delete |

#### `product_variants`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| product_id | bigint | FK `products`, cascade on delete |
| sku | string unique | identificador de estoque |
| name | string | ex.: "P / Azul" |
| price_cents | unsigned int | preco de venda |
| compare_at_price_cents | unsigned int nullable | preco comparativo |
| stock_quantity | unsigned int | default 0 |
| low_stock_threshold | unsigned int | limite de alerta, default 5 |
| is_active | boolean | index, default true |
| sort_order | unsigned int | default 0, index |

#### `product_images`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| product_id | bigint | FK `products`, cascade on delete |
| path | string | caminho no disco `public` |
| alt_text | string nullable | acessibilidade / SEO |
| sort_order | unsigned int | default 0, index |
| is_primary | boolean | index, default false |

#### `stock_movements`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| product_variant_id | bigint | FK `product_variants`, cascade on delete |
| user_id | bigint nullable | FK `users`, admin que registrou |
| quantity_change | integer | positivo ou negativo |
| quantity_after | unsigned int | saldo apos movimentacao |
| reason | string | `initial`, `manual_adjustment`, `restock`, `sale`, `order_reversal` |
| notes | text nullable | |
| created_at | timestamp | index |

#### `carts`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| user_id | bigint nullable | FK `users`, unique, cascade on delete |
| session_id | uuid nullable | unique, carrinho de convidado |
| coupon_id | bigint nullable | FK `coupons`, null on delete |
| shipping_address_id | bigint nullable | FK `addresses`, null on delete |
| shipping_method_id | bigint nullable | FK `shipping_methods`, null on delete |
| shipping_cents | unsigned int nullable | frete calculado no checkout |
| created_at, updated_at | timestamp | |

#### `cart_items`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| cart_id | bigint | FK `carts`, cascade on delete |
| product_variant_id | bigint | FK `product_variants`, cascade on delete |
| quantity | unsigned int | |
| unique(cart_id, product_variant_id) | | |

#### `wishlist_items`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| user_id | bigint | FK `users`, cascade on delete |
| product_id | bigint | FK `products`, cascade on delete |
| unique(user_id, product_id) | | |

#### `coupons`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| code | string unique | codigo informado no carrinho |
| name | string | identificacao interna |
| type | string | `percentage`, `fixed_amount` |
| value | unsigned int | percentual (1-100) ou centavos |
| min_order_cents | unsigned int nullable | pedido minimo |
| max_discount_cents | unsigned int nullable | teto para percentual |
| usage_limit | unsigned int nullable | limite total de usos |
| usage_count | unsigned int | usos registrados |
| starts_at | timestamp nullable | inicio da vigencia |
| expires_at | timestamp nullable | fim da vigencia |
| is_active | boolean | index, default true |

#### `promotions`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| name | string | |
| type | string | `percentage`, `fixed_amount` |
| value | unsigned int | percentual ou centavos |
| scope | string | `all_products`, `category`, `brand`, `product` |
| category_id | bigint nullable | FK `categories` |
| brand_id | bigint nullable | FK `brands` |
| product_id | bigint nullable | FK `products` |
| priority | unsigned int | desempate administrativo |
| starts_at | timestamp nullable | |
| expires_at | timestamp nullable | |
| is_active | boolean | index, default true |

#### `addresses`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| user_id | bigint | FK `users`, cascade on delete |
| label | string | ex.: "Casa", "Trabalho" |
| recipient_name | string | destinatario |
| recipient_phone | string nullable | |
| postal_code | string(8) | CEP sem mascara |
| street | string | logradouro |
| number | string | |
| complement | string nullable | |
| neighborhood | string | bairro |
| city | string | |
| state | string(2) | UF (enum validado no backend) |
| is_default | boolean | index, default false |

#### `shipping_methods`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| name | string | |
| description | string nullable | |
| price_cents | unsigned int | valor base do frete |
| free_above_cents | unsigned int nullable | frete gratis acima deste subtotal |
| min_order_cents | unsigned int nullable | pedido minimo para elegibilidade |
| max_order_cents | unsigned int nullable | pedido maximo para elegibilidade |
| estimated_days_min | unsigned tinyint nullable | prazo minimo em dias |
| estimated_days_max | unsigned tinyint nullable | prazo maximo em dias |
| sort_order | unsigned int | default 0, index |
| is_active | boolean | index, default true |

#### `orders`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| user_id | bigint | FK `users`, cascade on delete |
| number | string unique | ex.: `PED-00000001` |
| status | string | enum `OrderStatus`, index |
| fulfillment_status | string | enum `FulfillmentStatus`, index |
| tracking_code | string nullable | codigo de rastreio |
| tracking_url | text nullable | link HTTP/HTTPS de rastreio |
| internal_notes | text nullable | uso exclusivo do admin |
| subtotal_cents | unsigned int | subtotal promocional |
| discount_cents | unsigned int | desconto do cupom |
| shipping_cents | unsigned int | frete |
| total_cents | unsigned int | total final |
| coupon_id | bigint nullable | FK `coupons`, null on delete |
| coupon_code | string nullable | snapshot |
| coupon_name | string nullable | snapshot |
| shipping_method_id | bigint nullable | FK `shipping_methods`, null on delete |
| shipping_method_name | string | snapshot |
| recipient_name | string | snapshot do endereco |
| recipient_phone | string nullable | |
| postal_code | string(8) | |
| street | string | |
| street_number | string | numero do logradouro |
| complement | string nullable | |
| neighborhood | string | |
| city | string | |
| state | string(2) | |
| placed_at | timestamp | index |
| preparing_at | timestamp nullable | inicio da separacao |
| shipped_at | timestamp nullable | confirmacao do envio |
| delivered_at | timestamp nullable | confirmacao da entrega |
| fulfillment_cancelled_at | timestamp nullable | encerramento antes do envio |

Estados: `pending_payment`, `paid`, `payment_failed`, `cancelled`, `partially_refunded`, `refunded`, `charged_back`.

Estados logisticos: `pending`, `preparing`, `shipped`, `delivered`, `cancelled`.

#### `order_items`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| order_id | bigint | FK `orders`, cascade on delete |
| product_variant_id | bigint | FK `product_variants`, restrict on delete |
| product_id | bigint | FK `products`, restrict on delete |
| product_name | string | snapshot |
| product_slug | string | snapshot |
| variant_name | string | snapshot |
| variant_sku | string | snapshot |
| quantity | unsigned int | |
| unit_price_cents | unsigned int | preco promocional no pedido |
| original_unit_price_cents | unsigned int nullable | preco original se houve promocao |
| line_total_cents | unsigned int | |

#### `payments`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| order_id | bigint unique | FK `orders`, cascade on delete; um pagamento por pedido |
| provider | string | default `mercado_pago`, index |
| provider_order_id | string nullable unique | identificador da order no provedor |
| provider_payment_id | string nullable unique | identificador da transacao no provedor |
| method | string | default `pix` |
| status | string | enum `PaymentStatus`, index |
| status_detail | string nullable | detalhe retornado pelo provedor |
| amount_cents | unsigned int | valor financeiro local |
| refunded_amount_cents | unsigned int | total reembolsado, default 0 |
| idempotency_key | uuid unique | criacao segura do Pix |
| refund_idempotency_key | uuid nullable unique | retry seguro do reembolso |
| pix_qr_code | long text nullable | codigo Copia e Cola |
| pix_qr_code_base64 | long text nullable | imagem do QR Code |
| pix_ticket_url | text nullable | pagina hospedada pelo provedor |
| expires_at | timestamp nullable | validade do Pix |
| paid_at | timestamp nullable | confirmacao do pagamento |
| refunded_at | timestamp nullable | primeira confirmacao de reembolso |
| inventory_released_at | timestamp nullable | guarda idempotente da reversao local |
| provider_payload | json nullable | ultimo snapshot reconciliado |

Estados: `pending`, `processing`, `approved`, `failed`, `cancelled`, `expired`, `partially_refunded`, `refunded`, `charged_back`.

#### `webhook_events`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| provider | string | index |
| provider_event_id | string | unico em conjunto com `provider` |
| event_type | string | topico da notificacao, index |
| action | string nullable | acao informada pelo provedor |
| resource_id | string nullable | order relacionada, index |
| request_id | uuid nullable | header de rastreio do Mercado Pago |
| payload | json | notificacao recebida |
| status | string | `pending`, `processed`, `ignored`, `failed`; index |
| error | text nullable | erro sanitizado de processamento |
| processed_at | timestamp nullable | conclusao do evento |

#### `admin_audit_logs`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| user_id | bigint nullable | FK `users`, null on delete |
| action | string | identificador da acao, index |
| auditable_type | string nullable | tipo polimorfico do registro afetado |
| auditable_id | bigint nullable | id polimorfico do registro afetado |
| description | string | resumo seguro para exibicao |
| metadata | json nullable | contexto tecnico nao exibido na listagem |
| ip_address | string(45) nullable | origem da requisicao |
| user_agent | text nullable | agente da requisicao |
| created_at, updated_at | timestamp | `created_at` indexado |

#### `banners`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| title | string | titulo editorial |
| eyebrow | string nullable | chamada curta |
| description | text nullable | apoio do destaque |
| image_path | string nullable | arquivo no disco publico |
| image_alt | string nullable | texto alternativo |
| cta_label | string nullable | texto do CTA |
| cta_url | string(2048) nullable | caminho interno ou URL HTTP(S) |
| theme | string | `paper`, `ink`, `accent` |
| placement | string | `hero`, `editorial` |
| is_active | boolean | habilitacao administrativa |
| starts_at, ends_at | timestamp nullable | janela de publicacao |
| sort_order | unsigned int | prioridade visual |

#### `reviews`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | bigint | PK |
| user_id | bigint | FK `users`, unico com `product_id` |
| product_id | bigint | FK `products` |
| order_item_id | bigint unique | comprova a compra entregue |
| rating | unsigned tinyint | nota de 1 a 5 |
| title | string nullable | resumo do cliente |
| body | text | experiencia detalhada |
| status | string | `pending`, `approved`, `rejected`; index |
| is_verified_purchase | boolean | snapshot de verificacao |
| moderation_notes | text nullable | retorno da equipe |
| moderated_by | bigint nullable | FK `users`, null on delete |
| moderated_at | timestamp nullable | data da decisao |

#### `notifications`

| Coluna | Tipo | Observacao |
|--------|------|------------|
| id | uuid | PK nativa do Laravel |
| type | string | classe da notificacao |
| notifiable_type, notifiable_id | morph | destinatario, index composto |
| data | text | payload com titulo, mensagem, acao e tom |
| read_at | timestamp nullable | controle de leitura |
| created_at, updated_at | timestamp | ordenacao da caixa de entrada |

### Relacionamentos adicionados na Fase 12

- `banners` e conteudo independente, filtrado por posicao e janela de publicacao.
- `products`, `users` e `order_items` convergem em `reviews`; o item do pedido comprova a compra.
- `users` recebem `notifications` pelo relacionamento polimorfico nativo do Laravel.
- `reviews` tambem podem apontar para o usuario administrador em `moderated_by`.

## Diagrama simplificado

```
categories ──┬── categories (parent_id)
             └── products

brands ──────── products (nullable)

products ──┬── product_variants ── stock_movements
           ├── product_images
           ├── wishlist_items
           └── promotions (escopo product)

categories ── promotions (escopo category)
brands ────── promotions (escopo brand)

users ──┬── carts ── cart_items
        │            ├── coupons (nullable FK em carts)
        │            ├── addresses (shipping_address_id)
        │            └── shipping_methods (shipping_method_id)
        ├── addresses
        ├── orders ──┬── order_items
        │            └── payments
        ├── wishlist_items
        ├── stock_movements (admin e vendas)
        └── admin_audit_logs (acoes administrativas)

shipping_methods ──┬── carts (frete selecionado)
                   └── orders (snapshot)

coupons ── orders (nullable)

payments ── webhook_events (vinculo logico por provider/resource_id)

orders e product_variants ── admin_audit_logs (vinculo polimorfico)
```
