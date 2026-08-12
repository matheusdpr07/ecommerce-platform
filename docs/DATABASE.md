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

### Planejadas (fases futuras)

- `products`, `product_variants`, `product_images`
- `stock_movements`
- `orders`, `order_items`, `coupons`, `promotions`
- `addresses`, `payments`, `webhook_events`
- `banners`, `reviews`, `admin_audit_logs`

## Diagrama simplificado

```
categories ──┬── categories (parent_id)
             └── products (fase 4)

brands ──────── products (fase 4)

users ──┬── orders (fase 9)
        └── addresses (fase 8)
```
