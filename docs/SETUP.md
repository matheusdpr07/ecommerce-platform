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

## Identidade da loja

A identidade exibida na vitrine pode ser ajustada sem alterar os componentes Vue:

```env
STORE_NAME="Aurea"
STORE_EYEBROW="Curadoria contemporanea"
STORE_TAGLINE="Escolhas que transformam o cotidiano."
STORE_SUPPORT_EMAIL="hello@example.com"
```

Esses valores definem a assinatura da marca, a mensagem principal e o contato mostrado ao cliente. Banners e campanhas sao administrados em `/admin/banners`.

## Mercado Pago e Pix

Configure as credenciais da aplicacao Mercado Pago somente no `.env` local:

```env
MERCADO_PAGO_ACCESS_TOKEN=seu_access_token
MERCADO_PAGO_WEBHOOK_SECRET=seu_webhook_secret
MERCADO_PAGO_BASE_URL=https://api.mercadopago.com
MERCADO_PAGO_SANDBOX=false
MERCADO_PAGO_SANDBOX_PAYER_EMAIL=
MERCADO_PAGO_PIX_EXPIRATION=PT30M
MERCADO_PAGO_WEBHOOK_TOLERANCE_SECONDS=300
```

- `MERCADO_PAGO_ACCESS_TOKEN` autentica criacao, consulta e reembolso de orders.
- `MERCADO_PAGO_WEBHOOK_SECRET` valida a assinatura HMAC das notificacoes.
- `MERCADO_PAGO_SANDBOX` ativa o comprador de teste sem alterar o e-mail local do cliente.
- `MERCADO_PAGO_SANDBOX_PAYER_EMAIL` recebe o e-mail `@testuser.com` de uma conta de teste do tipo Comprador.
- `MERCADO_PAGO_PIX_EXPIRATION` usa o formato de duracao ISO 8601.
- `MERCADO_PAGO_WEBHOOK_TOLERANCE_SECONDS` limita a idade aceita da assinatura.

No painel do Mercado Pago, habilite notificacoes do topico **Order** para a URL publica HTTPS:

```text
https://seu-dominio.com/webhooks/mercado-pago
```

Em desenvolvimento, use um tunnel HTTPS apontando para o dominio local. A criacao local do pedido continua funcionando sem credenciais, mas gerar o Pix exige um access token valido.

Para testar a Checkout API Orders sem cobranca real, use o Access Token `APP_USR` da conta de teste Vendedor e configure:

```env
MERCADO_PAGO_SANDBOX=true
MERCADO_PAGO_SANDBOX_PAYER_EMAIL=email_da_conta_comprador@testuser.com
```

Crie a conta Comprador em **Suas integracoes > Testes > Contas de teste**. Antes de publicar, altere `MERCADO_PAGO_SANDBOX=false`; em producao, o e-mail real do cliente volta a ser enviado.

> Nunca registre ou commite access tokens e secrets. O endpoint de webhook e publico, mas rejeita assinaturas ausentes, invalidas ou expiradas.

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

A fila deve permanecer ativa para enviar os e-mails transacionais de pedidos, pagamentos, entrega e moderacao de avaliacoes. Em producao, configure um worker para a conexao definida em `QUEUE_CONNECTION`.

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
| Pix nao e gerado | Verifique access token, URL base e logs da aplicacao |
| Webhook retorna `401` | Confirme o secret, o topico Order e a URL configurada no Mercado Pago |
| HTML abre, mas a interface demora ou nao aparece | Confirme se o servidor indicado em `public/hot` esta respondendo; reinicie `npm run dev` ou remova o arquivo obsoleto depois de executar `npm run build` |
| `npm run build` falha | Execute `npm install` e verifique Node 20+ |
| Permissao em `storage/` ou `bootstrap/cache/` | `php artisan storage:link` e permissões de escrita |
