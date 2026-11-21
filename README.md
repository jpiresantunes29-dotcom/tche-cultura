# Tchê & Cultura — Conheça o Brasil por dentro

<p align="center">
  <strong>Plataforma educacional interativa sobre a diversidade cultural das cinco regiões do Brasil</strong>
</p>

## Sobre o Projeto

**Tchê & Cultura** é um site que apresenta a riqueza cultural brasileira de forma interativa e educativa. O projeto reúne lendas, festas, pontos turísticos, expressões regionais, músicas típicas e artigos culturais dos 26 estados e do Distrito Federal, organizados pelas cinco grandes regiões do país.

A plataforma foi desenvolvida como projeto acadêmico para o curso de Engenharia de Software da PUCPR.

## Funcionalidades

- **Mapa Interativo** — Navegação visual pelas 5 regiões e 27 unidades federativas do Brasil
- **Acervo Cultural** — Conteúdos organizados por categorias (lendas, festas, turismo, expressões, nomes e artigos), com filtro por estado e região
- **Pontos Turísticos** — 8 destinos com descrições detalhadas e fotografias
- **Músicas Regionais** — 7 gêneros musicais com letras completas e informações sobre origem
- **Jogos Educativos** — Quiz cultural, jogo da memória e quebra-cabeça
- **Tchê Bot** — Chatbot que busca informações dentro do acervo cultural do site
- **Sistema de Usuários** — Cadastro, login, perfil, favoritos e avaliações com comentários
- **Contribuição Colaborativa** — Usuários podem enviar novos conteúdos culturais para revisão
- **Painel Administrativo** — Gestão de contribuições, feedbacks, atendimentos e estatísticas
- **Acessibilidade** — Alto contraste, ajuste de fonte, navegação por teclado

## Tecnologias Utilizadas

- **Backend:** PHP 8
- **Banco de Dados:** MySQL 8
- **Frontend:** HTML5, JavaScript, Tailwind CSS
- **Mapa:** D3.js

## Como Executar

### Requisitos

- XAMPP, WAMP ou servidor equivalente com PHP e MySQL

### Instalação

1. Clone o repositório ou extraia o ZIP
2. Copie a pasta `04 Desenvolvimento Web/` para a pasta do servidor (ex: `htdocs/tche/`)
3. Inicie o servidor (Apache + MySQL)
4. Importe o banco de dados pelo phpMyAdmin:
   - Primeiro importe `tche_cultura_v4.sql`
   - Depois importe `atualizar_conteudo.sql`
5. Acesse pelo navegador (ex: `http://localhost/tche/`)

> **Dica:** ao acessar o site pela primeira vez sem o banco instalado, a própria interface oferece um botão para instalar o banco automaticamente.

### Usuários de Teste

| E-mail | Senha | Papel |
|--------|-------|-------|
| admin@email.com | Tche@2026 | Administrador |
| ana@email.com | Tche@2026 | Usuário comum |
| carlos@email.com | Tche@2026 | Usuário comum |
| marta@email.com | Tche@2026 | Usuário comum |
| joana@email.com | Tche@2026 | Usuário comum |

### Versão Online (GitHub Pages)

Uma versão simplificada do site, sem banco de dados, está disponível online:

**https://jpiresantunes29-dotcom.github.io/tche-cultura/**

## Estrutura do Repositório

```
tche-cultura/
├── 01 Gestao do Projeto/              # Planejamento do projeto
├── 02 Engenharia de Requisitos/        # Casos de uso e requisitos
├── 03 Banco de Dados/                  # Modelagem e scripts do banco
│   ├── 1 Diagramas/                    # Modelos conceitual e lógico
│   ├── 2 Documentos/                   # Documentação do banco
│   ├── 3 SQL/                          # Scripts SQL e testes
│   └── 4 Entrega Views Functions/      # Relatório de evidências
├── 04 Desenvolvimento Web/             # Código-fonte do site
│   ├── index.php                       # Arquivo principal do site
│   ├── tche_cultura_v4.sql             # Banco de dados
│   ├── atualizar_conteudo.sql          # Conteúdos (imagens e letras)
│   ├── tailwind.css                    # Estilos visuais
│   ├── mapa_brasil.json                # Dados do mapa do Brasil
│   ├── imagens/                        # Fotos dos pontos turísticos
│   └── arquivos/                       # Artigos em PDF
├── 05 Conferencia e Ferramentas/       # Ferramentas de apoio
├── docs/                               # Versão online (GitHub Pages)
└── README.md
```

## Licença

Projeto acadêmico desenvolvido para fins educacionais.

---

<p align="center">
  <em>Feito para valorizar e difundir a cultura brasileira</em>
</p>
