# Entrega final — Tchê & Cultura

Projeto integrado · Grupo 5 · Engenharia de Software · PUCPR
Disciplina de Desenvolvimento Web

## Integrantes

Letícia Beatriz Silva de Morais · João Pedro Pires · Guilherme Sakagami · Eduardo Yonegura · Carlos Eduardo

## O que está nesta pasta

| Arquivo | O que é |
|---|---|
| `Video de apresentacao.mp4` | Demonstração do site com narração dos cinco integrantes e legendas (5min25) |
| `Banco de dados - tche_cultura (export completo).sql` | Export do banco já instalado, com dados, views, functions, trigger e procedures |
| `Legendas do video.srt` | Legendas separadas, caso queira exibir por fora do vídeo |
| `Transcricao do video.txt` | O que cada integrante falou, com o tempo no vídeo |

## Divisão da narração no vídeo

| Bloco | Tempo | Quem narra | Assunto |
|---|---|---|---|
| 1 | 0:00 – 1:24 | Letícia | Apresentação, mapa, acervo, pesquisa, quiz e acessibilidade |
| 2 | 1:24 – 2:28 | João Pedro | Cadastro, quatro perfis, favoritar, avaliar e Central de Ajuda |
| 3 | 2:28 – 3:33 | Guilherme | Perfil do estudante e as atividades (memória, quebra-cabeça e quiz) |
| 4 | 3:33 – 4:33 | Eduardo | Docente e pesquisadora: atividades, materiais e contribuições |
| 5 | 4:33 – 5:24 | Carlos | Painel do administrador: moderação, estatísticas, relatórios e atendimentos |

## Como rodar o projeto

O passo a passo completo está em `COMO-RODAR.md`, na raiz do projeto.

Resumo: copie a pasta `04 Desenvolvimento Web` para `C:\xampp\htdocs\tche`, ligue o Apache e o MySQL no XAMPP e abra `http://localhost/tche/`. Na primeira vez o site oferece um botão para instalar o banco sozinho.

Contas de teste (senha `Tche@2026`): `admin@email.com` (administrador), `ana@email.com` (estudante), `carlos@email.com` (docente), `marta@email.com` (pesquisadora), `joana@email.com` (público em geral).

## Observação sobre o banco

O script usa a colação `utf8mb4_unicode_ci`, compatível com o MySQL 8 e com o MariaDB que acompanha o XAMPP. As restrições `CHECK` exigem MySQL 8.0.16 ou MariaDB 10.4 em diante.
