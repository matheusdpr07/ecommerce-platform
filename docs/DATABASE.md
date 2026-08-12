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
| reason | string | `initial`, `manual_adjustment`, `restock` |
| notes | text nullable | |
| created_at | timestamp | index |

### Planejadas (fases futuras)

- `orders`, `order_items`, `coupons`, `promotions`
- `addresses`, `payments`, `webhook_events`
- `banners`, `reviews`, `admin_audit_logs`

## Diagrama simplificado

```
categories ──┬── categories (parent_id)
             └── products

brands ──────── products (nullable)

products ──┬── product_variants ── stock_movements
           └── product_images

users ──┬── orders (fase 9)
        ├── addresses (fase 8)
        └── stock_movements (admin, fase 4)
```
