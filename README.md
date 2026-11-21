# 🎵 Tchê & Cultura — Conheça o Brasil por dentro

<p align="center">
  <strong>Plataforma de difusão da diversidade cultural das cinco regiões do Brasil</strong>
</p>

## 📋 Sobre o Projeto

**Tchê & Cultura** é uma plataforma educacional interativa desenvolvida como projeto acadêmico do curso de Engenharia de Software da PUCPR (Grupo 5). A plataforma apresenta a riqueza cultural brasileira através de um mapa interativo, pontos turísticos, músicas regionais, jogos educativos e um chatbot cultural.

### ✨ Funcionalidades

- 🗺️ **Mapa Interativo** — Navegue pelas 5 regiões do Brasil
- 🏛️ **Pontos Turísticos** — 8 destinos icônicos com descrições e imagens
- 🎶 **Músicas Regionais** — 7 gêneros musicais com letras completas
- 🧩 **Jogos Educativos** — Quiz cultural e quebra-cabeça interativo
- 🤖 **Tchê Bot** — Chatbot com base de conhecimento cultural
- 👤 **Sistema de Usuários** — Cadastro, login, ranking e favoritos
- ♿ **Acessibilidade** — Alto contraste, ajuste de fonte, navegação por teclado

## 🚀 Como Executar

### Requisitos
- **XAMPP** (ou equivalente) com PHP 8+ e MySQL 8+
- Navegador moderno

### Instalação Local (XAMPP)

1. **Copie a pasta** `04 Desenvolvimento Web/` para `C:\xampp\htdocs\tche\`
2. **Inicie o XAMPP** (Apache + MySQL)
3. **Importe o banco de dados:**
   - Abra o phpMyAdmin (`http://localhost/phpmyadmin`)
   - Importe o arquivo `tche_cultura_v4.sql`
   - Depois importe `atualizar_conteudo.sql` (imagens e letras)
4. **Acesse** `http://localhost/tche/`

### Usuários de Teste

| E-mail | Senha | Papel |
|--------|-------|-------|
| admin@email.com | Tche@2026 | Administrador |
| ana@email.com | Tche@2026 | Usuário |
| carlos@email.com | Tche@2026 | Usuário |
| marta@email.com | Tche@2026 | Usuário |
| joana@email.com | Tche@2026 | Usuário |

### Versão Estática (GitHub Pages)

A pasta `docs/` contém uma versão estática do site que funciona sem backend.
Acesse em: **https://jpiresantunes29-dotcom.github.io/tche-cultura/**

## 📁 Estrutura do Projeto

```
├── 01 Briefing e Backlog/      # Documentação inicial e product backlog
├── 02 Wireframes/               # Protótipos e wireframes do projeto
├── 03 Modelagem de Dados/       # Diagrama ER e modelo do banco
├── 04 Desenvolvimento Web/      # Código-fonte do site
│   ├── index.php                # Aplicação completa (PHP 8 + API)
│   ├── tche_cultura_v4.sql      # Schema e dados iniciais
│   ├── atualizar_conteudo.sql   # Imagens e letras das músicas
│   ├── tailwind.css             # Estilos compilados
│   ├── mapa_brasil.json         # GeoJSON do mapa interativo
│   ├── imagens/                 # Imagens dos pontos turísticos
│   └── arquivos/                # PDFs e artigos do blog
├── 05 Relatório Final/          # Relatório e apresentação
└── docs/                        # Versão estática (GitHub Pages)
```

## 🛠️ Tecnologias

- **Backend:** PHP 8 (arquivo único, 31 endpoints de API)
- **Banco de Dados:** MySQL 8 (PDO, bcrypt cost 10)
- **Frontend:** HTML5, Tailwind CSS, JavaScript vanilla
- **Mapa:** D3.js com GeoJSON do Brasil
- **Autenticação:** JWT-like com bcrypt

## 👥 Grupo 5 — PUCPR

Projeto desenvolvido para a disciplina de Engenharia de Software.

---

<p align="center">
  <em>Feito com ❤️ para valorizar a cultura brasileira</em>
</p>
