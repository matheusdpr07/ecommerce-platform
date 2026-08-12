# Banco de dados

## Motor

PostgreSQL em desenvolvimento e producao. SQLite in-memory apenas nos testes automatizados.

## Convencoes

- Tabelas e colunas em ingles, snake_case
- Valores monetarios em centavos (`integer`)
- Quantidades de estoque como inteiros
- Soft deletes apenas quando fizer sentido de negocio
- Indices em colunas de filtro, busca e foreign keys

## Entidades planejadas (fases futuras)

### Usuarios e autenticacao

- `users` — clientes e administradores (role separada na fase 2)
- `password_reset_tokens`, `sessions` — padrao Laravel

### Catalogo

- `categories` — hierarquia de categorias
- `brands` — marcas
- `products` — produtos com slug, precos, status, SEO
- `product_variants` — variacoes (tamanho, cor, SKU, estoque)
- `product_images` — galeria ordenada

### Estoque

- `stock_movements` — historico imutavel de movimentacoes

### Vendas

- `orders` — pedidos com estados controlados
- `order_items` — snapshot imutavel dos itens na compra
- `coupons`, `promotions` — descontos

### Enderecos e frete

- `addresses` — enderecos de clientes
- Campos de endereco copiados no pedido no checkout

### Pagamentos

- `payments` — status, gateway externo, historico
- `webhook_events` — processamento idempotente

### Conteudo e admin

- `banners`, `reviews`, `admin_audit_logs`

## Diagrama simplificado (visao futura)

```
users ──┬── orders ── order_items
        │              │
        └── addresses    └── product_variants ── products ── categories
                                              └── brands
```

Detalhamento das migrations sera adicionado conforme cada fase for implementada.
