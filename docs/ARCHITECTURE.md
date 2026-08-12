# Arquitetura

## Visao geral

Monolito modular Laravel com frontend Inertia (Vue 3 + TypeScript). Uma unica aplicacao serve loja publica, area do cliente e painel administrativo (`/admin`).

## Camadas

| Camada | Responsabilidade |
|--------|------------------|
| Controllers | Orquestracao HTTP; controllers pequenos |
| Form Requests | Validacao de entrada |
| Policies | Autorizacao no backend |
| Middleware | Controle de acesso (cliente vs admin) |
| Models (Eloquent) | Persistencia, relacionamentos, casts, scopes |
| Actions / Services | Regras de negocio complexas apenas quando necessario |
| API Resources | Respostas de API quando aplicavel |

## O que evitamos

- Microservicos nesta etapa
- Repository Pattern quando Eloquent bastar
- DTOs e interfaces sem beneficio claro
- Duplicar regras de negocio no frontend

## Regras de negocio criticas

- Valores monetarios em **centavos** (inteiros), nunca `float`
- Backend e fonte de verdade para precos, estoque, descontos e permissoes
- Operacoes financeiras, checkout e estoque usam **transacoes**
- Paginacao em listagens que possam crescer
- Eager loading para evitar N+1

## Frontend

- Vue 3 + TypeScript + Inertia.js + Tailwind CSS 4
- Componentes reutilizaveis; composables apenas quando necessario
- Interface em portugues do Brasil
- Layout responsivo com estados de carregamento, vazio e erro

## Seguranca (diretrizes)

- CSRF, validacao rigorosa, rate limiting em auth
- Policies para operacoes sensiveis
- Segredos somente em variaveis de ambiente
- Sem credenciais administrativas em seeders versionados

## Integracoes futuras

- **Pagamentos:** abstracao simples; Mercado Pago como primeira integracao
- **Frete:** abstracao simples; provedores externos em fases dedicadas

## Estrutura de rotas (planejada)

```
/              Loja publica (vitrine)
/products/*    Pagina do produto
/categories/*  Listagem por categoria
/cart          Carrinho
/wishlist      Lista de desejos (auth)
/dashboard     Area autenticada (cliente)
/admin/*       Painel administrativo (cupons, promocoes, catalogo)
```
