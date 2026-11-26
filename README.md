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

- **Backend:** PHP 8 com PDO (`declare(strict_types=1)`), em arquivo único (`index.php`)
- **Banco de Dados:** MySQL 8 ou MariaDB 10.4+ — 30 tabelas, 4 views, 2 functions, 1 trigger e 4 procedures
- **Frontend:** HTML5 e JavaScript puro, sem framework
- **Estilo:** Tailwind CSS (compilado em `tailwind.css`)
- **Mapa:** SVG montado pelo próprio JavaScript a partir de `mapa_brasil.json` — sem biblioteca de gráficos

## Como Executar

> **Quer o passo a passo em linguagem simples?** Veja o [COMO-RODAR.md](COMO-RODAR.md).

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

> **Dica:** ao acessar o site pela primeira vez sem o banco instalado, a própria interface oferece um botão para instalar o banco automaticamente. Ele já executa os dois scripts na ordem certa.

> **Sobre o banco:** o script usa a colação `utf8mb4_unicode_ci`, que funciona tanto no MySQL 8 quanto no MariaDB que vem com o XAMPP. As restrições `CHECK` exigem MySQL 8.0.16 ou MariaDB 10.4 em diante.

### Usuários de Teste

Todas usam a senha `Tche@2026`. Cada perfil enxerga recursos diferentes.

| E-mail | Perfil | O que esse perfil pode fazer |
|--------|--------|------------------------------|
| admin@email.com | Administrador | Painel completo: moderação, estatísticas, relatórios, feedbacks e atendimentos |
| ana@email.com | Estudante | Fazer as atividades e guardar a pontuação no perfil |
| carlos@email.com | Docente | Ver o texto técnico, criar atividades, anexar materiais e contribuir |
| marta@email.com | Pesquisadora | Contribuir, incluindo artigos acadêmicos com referências |
| joana@email.com | Público em geral | Favoritar e avaliar conteúdos |

### Versão Online (GitHub Pages)

Uma versão simplificada do site, sem banco de dados, está disponível online:

**https://jpiresantunes29-dotcom.github.io/tche-cultura/**

## Vídeo de Apresentação

Demonstração completa do site, com narração dos cinco integrantes do grupo e legendas (5min24):

**[`entrega/Video de apresentacao.mp4`](entrega/)**

O vídeo percorre o site inteiro: mapa e acervo como visitante, cadastro e os quatro perfis, as atividades do estudante, as contribuições de docente e pesquisadora, e o painel do administrador aprovando uma contribuição que passa a aparecer no mapa.

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
├── entrega/                            # Vídeo de apresentação e export do banco
├── docs/                               # Versão online (GitHub Pages)
├── COMO-RODAR.md                       # Passo a passo em linguagem simples
└── README.md
```

## Licença

Projeto acadêmico desenvolvido para fins educacionais.

---

<p align="center">
  <em>Feito para valorizar e difundir a cultura brasileira</em>
</p>
