# Configuracao do ambiente

## Requisitos

- PHP 8.4+
- Composer 2.x
- Node.js 20+ e npm
- MySQL 8+
- Herd (recomendado no Windows/macOS) ou equivalente

## Instalacao

```bash
git clone <repo-url> ecommerce-platform
cd ecommerce-platform
composer install
cp .env.example .env
php artisan key:generate
```

## Banco de dados (MySQL)

1. Crie o banco `ecommerce_platform` no MySQL.
2. Configure credenciais no `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_platform
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

3. Execute as migrations:

```bash
php artisan migrate
php artisan storage:link
```

> Nunca commite o arquivo `.env`. Credenciais ficam apenas localmente.
> O `storage:link` e necessario para exibir imagens de produtos no painel admin.

## Herd

Com Herd, o site pode ficar disponivel em `http://ecommerce-platform.test`. Ajuste `APP_URL` no `.env` conforme o dominio local.

## Frontend

```bash
npm install
npm run dev    # desenvolvimento com HMR
npm run build  # build de producao
```

## Desenvolvimento integrado

```bash
composer dev
```

Inicia servidor PHP, fila, logs (Pail) e Vite simultaneamente.

## Testes e qualidade

```bash
php artisan test           # Pest
./vendor/bin/pint          # formatacao PHP
npm run build              # verificar build TypeScript/Vite
```

Testes usam SQLite in-memory (configurado em `phpunit.xml`); nao e necessario MySQL para rodar a suite.

## Primeiro administrador

Nao existem credenciais administrativas padrao. Para promover um usuario existente:

```bash
php artisan admin:promote usuario@example.com
```

O usuario deve estar cadastrado previamente. Por padrao, o e-mail precisa estar verificado. Use `--force` apenas em ambiente local se necessario.

## Problemas comuns

| Problema | Solucao |
|----------|---------|
| Erro de conexao MySQL | Verifique servico, host, porta e credenciais no `.env` |
| `npm run build` falha | Execute `npm install` e verifique Node 20+ |
| Permissao em `storage/` ou `bootstrap/cache/` | `php artisan storage:link` e permissões de escrita |
