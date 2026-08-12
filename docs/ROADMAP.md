# Roadmap

## Fases

1. **Fundacao, documentacao e ambiente** — em andamento
2. Autenticacao, usuarios e autorizacao administrativa
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

### Pendente nesta fase

- Nenhum

### Decisoes

- Monolito modular Laravel (sem microservicos)
- Breeze como base de autenticacao (fase 2 expandira roles)
- SQLite in-memory apenas para testes automatizados
- PostgreSQL como banco principal em desenvolvimento e producao
- Interface em portugues do Brasil; codigo interno em ingles

### Testes relacionados

- `tests/Feature/ApplicationTest.php`

### Proxima fase recomendada

**Fase 2:** Autenticacao, usuarios e autorizacao administrativa
