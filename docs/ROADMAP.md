# Roadmap

## Fases

1. **Fundacao, documentacao e ambiente** — concluida
2. **Autenticacao, usuarios e autorizacao administrativa** — concluida
3. **Categorias e marcas** — concluida
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

## Fase 3 — Categorias e marcas

**Status:** concluida

### Concluido

- Migracao do banco principal para **MySQL** (`.env.example`)
- Tabelas `categories` e `brands` com slug, status, SEO basico e soft deletes
- Hierarquia de categorias via `parent_id`
- CRUD administrativo em `/admin/categories` e `/admin/brands`
- Paginacao, busca e filtro por status nas listagens
- Policies `CategoryPolicy` e `BrandPolicy` (somente admin)
- Form Requests com validacao e mensagens em portugues
- Layout administrativo com navegacao dedicada
- Protecao contra exclusao de categoria com subcategorias

### Pendente nesta fase

- Nenhum

### Decisoes

- MySQL substitui PostgreSQL por decisao do projeto (documentado em `DECISIONS.md`)
- Slug gerado automaticamente quando omitido no formulario
- Soft deletes em categorias e marcas

### Testes relacionados

- `tests/Feature/Admin/CategoryManagementTest.php`
- `tests/Feature/Admin/BrandManagementTest.php`

### Proxima fase recomendada

**Fase 4:** Produtos, variacoes, imagens e estoque
