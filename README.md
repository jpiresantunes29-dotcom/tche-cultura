# Tchê & Cultura — Conheça o Brasil por dentro

<p align="center">
  <strong>Plataforma educacional interativa de difusão da diversidade cultural das cinco regiões do Brasil</strong>
</p>

## Sobre o Projeto

**Tchê & Cultura** é uma aplicação web completa que apresenta a riqueza cultural brasileira de forma interativa e educativa. O projeto abrange lendas, festas, pontos turísticos, expressões regionais, músicas típicas e artigos culturais dos 26 estados e do Distrito Federal, organizados pelas cinco grandes regiões do país.

A plataforma foi desenvolvida como projeto acadêmico para o curso de Engenharia de Software da PUCPR, aplicando na prática conceitos de desenvolvimento full-stack, modelagem de banco de dados relacional, engenharia de requisitos e acessibilidade web.

## Funcionalidades

- **Mapa Interativo** — Navegação visual pelas 5 regiões e 27 unidades federativas do Brasil, com GeoJSON renderizado via D3.js
- **Acervo Cultural** — Conteúdos organizados por categorias (lendas, festas, turismo, expressões, nomes e artigos), com filtro por estado e região
- **Pontos Turísticos** — 8 destinos icônicos com descrições detalhadas e fotografias reais
- **Músicas Regionais** — 7 gêneros musicais com letras completas e informações sobre origem e contexto cultural
- **Jogos Educativos** — Quiz cultural com 5 perguntas geradas dinamicamente, jogo da memória com pares conteúdo-estado e quebra-cabeça 3x3
- **Tchê Bot** — Chatbot integrado com busca semântica no acervo cultural, com fallback para atendimento humano
- **Sistema de Usuários** — Cadastro, autenticação, perfil, favoritos, avaliações com comentários e ranking por engajamento
- **Contribuição Colaborativa** — Usuários podem submeter novos conteúdos culturais, com fluxo de moderação e revisão administrativa
- **Painel Administrativo** — Gestão de contribuições, feedbacks, atendimentos, estatísticas de acesso e relatórios por período
- **Acessibilidade** — Alto contraste, ajuste de tamanho de fonte, navegação completa por teclado, skip links e atributos ARIA (WCAG)

## Arquitetura

A aplicação segue uma arquitetura monolítica em arquivo único (`index.php`), onde o mesmo arquivo serve tanto a API REST (quando acessado com `?api=acao`) quanto a interface completa do site (acesso direto). O backend em PHP processa as requisições e o frontend em JavaScript consome a API de forma assíncrona.

### Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| **Backend** | PHP 8 — arquivo único com 31 endpoints de API REST |
| **Banco de Dados** | MySQL 8 — PDO, procedures, triggers, views, bcrypt (cost 10) |
| **Frontend** | HTML5, JavaScript vanilla (ES6+), Tailwind CSS |
| **Mapa** | D3.js com GeoJSON do Brasil (regiões e UFs) |
| **Autenticação** | Sessões PHP com senhas hasheadas via bcrypt |

## Como Executar

### Requisitos

- PHP 8.0+ com extensões `pdo_mysql` e `mysqli`
- MySQL 8.0.16+
- Servidor web (Apache ou Nginx)
- Recomendado: **XAMPP**, **WAMP** ou equivalente

### Instalação Local (XAMPP)

1. Clone o repositório ou extraia o ZIP
2. Copie a pasta `04 Desenvolvimento Web/` para `C:\xampp\htdocs\tche\`
3. Inicie o XAMPP (Apache + MySQL)
4. Importe o banco de dados pelo phpMyAdmin (`http://localhost/phpmyadmin`):
   - Primeiro importe `tche_cultura_v4.sql` (schema, dados iniciais, procedures, triggers e views)
   - Depois importe `atualizar_conteudo.sql` (imagens dos pontos turísticos e letras das músicas)
5. Acesse `http://localhost/tche/`

> **Alternativa:** ao acessar o site pela primeira vez sem o banco instalado, a própria interface oferece o botão "Instalar banco de dados", que executa o schema automaticamente.

### Usuários de Teste

| E-mail | Senha | Papel |
|--------|-------|-------|
| admin@email.com | Tche@2026 | Administrador |
| ana@email.com | Tche@2026 | Usuário comum |
| carlos@email.com | Tche@2026 | Usuário comum |
| marta@email.com | Tche@2026 | Usuário comum |
| joana@email.com | Tche@2026 | Usuário comum |

### Versão Estática (GitHub Pages)

A pasta `docs/` contém uma versão estática do site que funciona sem backend (sem banco de dados). Os conteúdos culturais estão embutidos diretamente no HTML.

Acesse em: **https://jpiresantunes29-dotcom.github.io/tche-cultura/**

## Estrutura do Repositório

```
tche-cultura/
├── 01 Gestao do Projeto/              # Escopo e planejamento do projeto
├── 02 Engenharia de Requisitos/        # Casos de uso, requisitos funcionais e diagrama
├── 03 Banco de Dados/                  # Modelagem e scripts SQL
│   ├── 1 Diagramas/                    # Modelos conceitual e lógico (draw.io e PNG)
│   ├── 2 Documentos/                   # Análise funcional e documentação do banco
│   ├── 3 SQL/                          # Schema principal e scripts de teste
│   └── 4 Entrega Views Functions/      # Relatório de evidências e views documentadas
├── 04 Desenvolvimento Web/             # Código-fonte da aplicação
│   ├── index.php                       # Aplicação completa (API + frontend em arquivo único)
│   ├── tche_cultura_v4.sql             # Schema do banco com dados iniciais
│   ├── atualizar_conteudo.sql          # Conteúdo real (imagens e letras)
│   ├── tailwind.css                    # Estilos compilados do Tailwind CSS
│   ├── mapa_brasil.json                # GeoJSON do mapa interativo do Brasil
│   ├── imagens/                        # Fotografias dos pontos turísticos
│   └── arquivos/                       # PDFs e artigos do blog cultural
├── 05 Conferencia e Ferramentas/       # Configuração do Tailwind e ferramentas de build
├── docs/                               # Versão estática para GitHub Pages
└── README.md
```

## Banco de Dados

O banco `tche_cultura` utiliza MySQL 8 e inclui:

- **14 tabelas** — usuários, estados, conteúdos culturais, pontos turísticos, músicas, atividades interativas, tentativas, favoritos, avaliações, feedbacks, atendimentos, contribuições, recursos complementares e acessos
- **Stored Procedures** — `sp_registrar_contribuicao`, `sp_revisar_contribuicao`, `sp_editar_conteudo_moderacao`, `sp_relatorio_conteudos_periodo`
- **Triggers** — validação de notas de avaliação e controle de integridade
- **Views** — `vw_tipos_culturais_frequentes` para estatísticas do painel administrativo
- **Índices** — otimização de consultas frequentes por estado, tipo e status

## API

A API REST responde em JSON via `index.php?api=acao`. Principais endpoints:

| Endpoint | Descrição |
|----------|-----------|
| `status` | Verifica conexão com o banco |
| `instalar` | Cria o schema automaticamente |
| `estados` | Lista estados com contagem de conteúdos |
| `mapa` | GeoJSON para renderização do mapa |
| `conteudos` | Lista do acervo com filtros por tipo, estado e região |
| `pesquisar` | Busca textual no acervo cultural |
| `cadastro` / `login` / `logout` | Autenticação de usuários |
| `favoritar` / `avaliar` | Interações do usuário com conteúdos |
| `quiz` / `atividade` / `atividade_resultado` | Jogos educativos |
| `ajuda` | Central de ajuda (Tchê Bot + atendimento humano) |
| `contribuir` | Submissão de conteúdo colaborativo |
| `admin_*` | Endpoints do painel administrativo |

## Licença

Projeto acadêmico desenvolvido para fins educacionais.

---

<p align="center">
  <em>Feito para valorizar e difundir a cultura brasileira</em>
</p>
