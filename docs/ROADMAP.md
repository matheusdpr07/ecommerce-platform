# Roadmap

## Fases

1. **Fundacao, documentacao e ambiente** — concluida
2. **Autenticacao, usuarios e autorizacao administrativa** — concluida
3. Categorias e marcas
4. Produtos, variacoes, imagens e estoque
5. Loja publica, busca, filtros e pagina do produto
6. Carrinho e lista de desejos
7. Cupons e promocoes
8. Enderecos, frete e checkout
9. Pedidos e controle transacional de estoque
10. Mercado Pago, Pix, webhooks e reembolsos
11. Painel administrativo completo
12. Avaliacoes, conteudo e notificacoes
13. SEO, acessibilidade e desempenho
14. Seguranca, testes, CI e preparacao para producao

---

## Fase 1 — Fundacao, documentacao e ambiente

**Status:** concluida

### Concluido

- Laravel 12 instalado na raiz do repositorio
- Laravel Breeze com Inertia, Vue 3 e TypeScript
- Tailwind CSS 4 via `@tailwindcss/vite`
- Laravel Sanctum instalado
- Pest configurado para testes
- Laravel Pint disponivel
- PostgreSQL configurado em `.env.example`
- Locale padrao `pt_BR` em `.env.example`
- Documentacao inicial (`docs/`, `.cursor/rules/`)
- Testes basicos de boot e pagina inicial

### Proxima fase recomendada

**Fase 2:** Autenticacao, usuarios e autorizacao administrativa

---

## Fase 2 — Autenticacao, usuarios e autorizacao administrativa

**Status:** concluida

### Concluido

- Enum `UserRole` (`customer`, `admin`)
- Coluna `role` em `users` com indice
- Cadastro sempre cria clientes; `role` fora do mass assignment
- Verificacao de e-mail obrigatoria (`MustVerifyEmail`)
- Middleware `admin` e rotas `/admin`
- Policy `UserPolicy` com controle de acesso administrativo
- Comando `admin:promote` para promover administradores com seguranca
- Rate limiting em login e cadastro
- Interface de auth e area logada em portugues do Brasil
- Pagina inicial do painel administrativo (`/admin`)
- Link de administracao visivel apenas para admins

### Pendente nesta fase

- Nenhum

### Decisoes

- Roles via enum string no banco; promocao admin somente via CLI
- Middleware + Policy em camadas (defesa em profundidade)
- Area do cliente em `/dashboard`; admin em `/admin`

### Testes relacionados

- `tests/Feature/Auth/UserRoleTest.php`
- `tests/Feature/Admin/AdminAccessTest.php`
- `tests/Feature/Auth/RegistrationTest.php` (assertiva de role)

### Proxima fase recomendada

**Fase 3:** Categorias e marcas
