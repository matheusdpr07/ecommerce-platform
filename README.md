# Ecommerce Platform

Plataforma completa de e-commerce com catalogo, experiencia editorial, carrinho, checkout, Pix e painel administrativo operacional.

## Estado atual

A Fase 12 esta concluida. A aplicacao inclui:

- Loja publica, busca, filtros, carrinho, wishlist, cupons e promocoes
- Checkout autenticado com enderecos, frete, pedidos e controle transacional de estoque
- Pagamento Pix via Mercado Pago, webhooks assinados e reembolso integral
- Painel administrativo com dashboard, inventario, operacao de pedidos, clientes e auditoria
- Acompanhamento de separacao, envio, rastreio e entrega na area do cliente
- Identidade visual configuravel, homepage editorial e movimento acessivel ligado a rolagem
- Banners administraveis com imagem, tema, posicao, agendamento e CTA
- Avaliacoes moderadas exclusivas para compras entregues e verificadas
- Central de notificacoes com alertas no banco e por e-mail para pedido, pagamento, entrega e avaliacao

## Stack

- PHP 8.4
- Laravel 12
- MySQL
- Vue 3 + TypeScript + Inertia.js
- Tailwind CSS 4
- Pest + Laravel Pint

## Inicio rapido

Consulte [docs/SETUP.md](docs/SETUP.md) para configuracao detalhada do ambiente local.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

## Documentacao

| Documento | Descricao |
|-----------|-----------|
| [docs/ROADMAP.md](docs/ROADMAP.md) | Fases do projeto e progresso |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Arquitetura e padroes |
| [docs/DATABASE.md](docs/DATABASE.md) | Modelo de dados e convencoes |
| [docs/DECISIONS.md](docs/DECISIONS.md) | Decisoes tecnicas |
| [docs/SETUP.md](docs/SETUP.md) | Configuracao do ambiente |

## Desenvolvimento

```bash
composer dev          # servidor, fila, logs e Vite
php artisan test      # testes com Pest
./vendor/bin/pint     # formatacao PHP
npm run build         # build de producao do frontend
```

## Licenca

MIT — veja [LICENSE](LICENSE).
