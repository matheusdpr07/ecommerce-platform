# Decisoes tecnicas

Registro objetivo de decisoes relevantes. Novas entradas devem incluir data, contexto e consequencias.

---

## 2026-08-11 — Stack inicial

**Contexto:** Fase 1 — fundacao do projeto.

**Decisao:** Laravel 12 + Breeze (Inertia/Vue/TS) + PostgreSQL + Pest + Pint + Tailwind 4.

**Motivo:** Stack alinhada ao plano do projeto; Breeze acelera autenticacao base sem lock-in excessivo.

**Consequencias:** Fase 2 partira do scaffolding Breeze e adicionara roles admin/cliente.

---

## 2026-08-11 — Monolito modular

**Contexto:** Arquitetura da plataforma.

**Decisao:** Monolito Laravel modular, sem microservicos.

**Motivo:** Simplicidade operacional, menor custo e adequado ao escopo atual.

**Consequencias:** Integracoes externas (pagamento, frete) via abstracoes internas, nao servicos separados.

---

## 2026-08-11 — PostgreSQL como banco principal

**Contexto:** Configuracao de ambiente.

**Decisao:** PostgreSQL em `.env.example`; SQLite apenas para testes.

**Motivo:** Requisito do projeto; PostgreSQL oferece recursos adequados para e-commerce.

**Consequencias:** Desenvolvedores precisam de PostgreSQL local (Herd, Docker ou instalacao nativa).

---

## 2026-08-11 — Tailwind CSS 4

**Contexto:** Breeze instalou Tailwind 3 por padrao.

**Decisao:** Migrar para Tailwind 4 com `@tailwindcss/vite` e configuracao via CSS (`@theme`).

**Motivo:** Requisito obrigatorio da stack; alinhamento com skeleton Laravel 12.

**Consequencias:** Removidos `tailwind.config.js` e `postcss.config.js`; tema definido em `resources/css/app.css`.

---

## 2026-08-11 — Locale pt_BR

**Contexto:** Interface em portugues do Brasil.

**Decisao:** `APP_LOCALE=pt_BR` em `.env.example`.

**Motivo:** Loja voltada ao publico brasileiro.

**Consequencias:** Traducoes Laravel e textos de interface devem usar pt_BR; codigo permanece em ingles.

---

## 2026-08-12 — Roles de usuario via enum

**Contexto:** Fase 2 — separacao cliente/admin.

**Decisao:** Enum `UserRole` com valores `customer` e `admin`; coluna indexada em `users`.

**Motivo:** Tipagem clara, consultas eficientes e autorizacao explicita no backend.

**Consequencias:** Cadastro publico sempre cria `customer`; promocao admin apenas via `admin:promote`.

---

## 2026-08-12 — Promocao administrativa via CLI

**Contexto:** Requisito de seguranca — sem credenciais admin em seeders.

**Decisao:** Comando `php artisan admin:promote {email}` com validacao de e-mail verificado.

**Motivo:** Evita escalacao de privilegio via HTTP e mantem rastro operacional no terminal.

**Consequencias:** Primeiro admin: cadastrar-se pela loja, verificar e-mail, executar o comando.

---

## 2026-08-12 — Migracao de PostgreSQL para MySQL

**Contexto:** Decisao explicita do projeto na Fase 3.

**Decisao:** MySQL como banco principal em desenvolvimento e producao.

**Motivo:** Alinhamento com ambiente local do time (Herd/MySQL).

**Consequencias:** `.env.example` e documentacao atualizados; testes continuam com SQLite in-memory.

---

## 2026-08-12 — Catalogo base: categorias e marcas

**Contexto:** Fase 3 — CRUD administrativo.

**Decisao:** Tabelas `categories` (com hierarquia) e `brands`; soft deletes; slug unico gerado no backend.

**Motivo:** Base do catalogo antes de produtos na Fase 4.

**Consequencias:** Rotas `/admin/categories` e `/admin/brands`; exclusao de categoria bloqueada se houver filhos.

---

## 2026-08-12 — Catalogo: produtos, variacoes e estoque

**Contexto:** Fase 4 — CRUD administrativo de produtos.

**Decisao:** Tabelas `products`, `product_variants`, `product_images` e `stock_movements`; precos e estoque na variacao; imagens no produto; movimentacoes auditaveis.

**Motivo:** Modelo flexivel para SKUs distintos, precos por variacao e rastreio de estoque antes do checkout.

**Consequencias:** Rota `/admin/products`; upload em `storage/app/public/products`; executar `php artisan storage:link` no ambiente local; exclusao de categoria/marca bloqueada com produtos vinculados.

