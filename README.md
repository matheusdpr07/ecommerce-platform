# Ecommerce Platform

Plataforma completa de e-commerce com catalogo, carrinho, pagamentos e painel administrativo.

## Stack

- PHP 8.4
- Laravel 12
- PostgreSQL
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
| [docs/DATABASE.md](docs/DATABASE.md) | Modelo de dados planejado |
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
