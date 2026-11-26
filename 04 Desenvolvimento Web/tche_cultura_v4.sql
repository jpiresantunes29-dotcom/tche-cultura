-- =====================================================================================
-- PROJETO: Tchê & Cultura (Grupo 5 — PUCPR)
-- ARQUIVO: tche_cultura_v4.sql — banco alinhado aos Requisitos v3.2 e às Especificações
-- INTEGRANTES: Letícia (Consolidação), João (Banco e Views), Guilherme (Functions),
--              Eduardo (Cadastros Transacionais), Carlos (Relatórios e Revisão)
-- VERSÃO DO MYSQL: 8.0.16 ou superior (usa CHECK)
-- REFERÊNCIA: Requisitos v3.2 (RF-01 a RF-26, RNF-01 a RNF-05) e Especificações de
--             Caso de Uso (UC-01, UC-02, UC-03, UC-16, UC-23). Cada tabela indica os
--             requisitos que atende; a SEÇÃO 7 traz uma consulta por requisito.
-- SENHA DE TODOS OS USUÁRIOS DE TESTE: Tche@2026 (hash bcrypt, custo 10 — RNF-04)
-- =====================================================================================

DROP DATABASE IF EXISTS tche_cultura;
CREATE DATABASE tche_cultura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tche_cultura;

-- =====================================================================================
-- SEÇÃO 1: ESTRUTURA (30 tabelas)
-- Ação: Criação das tabelas do Modelo Lógico v4.
-- Por quê: Cada tabela atende a um ou mais requisitos da v3.2 (indicados acima dela).
-- Resultado: Banco pronto para receber os dados e os objetos programáveis.
-- =====================================================================================

-- ---------- Usuários e perfis (RF-22) ----------

-- RF-22: todo usuário autenticado é classificado em um perfil.
CREATE TABLE usuario (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE NOT NULL,
    classificacao VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_usuario_classificacao
        CHECK (classificacao IN ('Estudante', 'Docente', 'Pesquisador', 'Público em Geral', 'Administrador'))
);

-- RF-11 a RF-14: perfil Estudante.
CREATE TABLE estudante (
    usuario_id INT NOT NULL,
    instituicao VARCHAR(100),
    nivel_escolaridade VARCHAR(50),
    PRIMARY KEY (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- RF-15 a RF-18: perfil Docente.
CREATE TABLE docente (
    usuario_id INT NOT NULL,
    certificado_exp VARCHAR(255),
    instituicao_trabalho VARCHAR(100),
    PRIMARY KEY (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- RF-19 a RF-21: perfil Pesquisador (linha_pesquisa atende ao RF-21).
CREATE TABLE pesquisador (
    usuario_id INT NOT NULL,
    tempo_experiencia VARCHAR(50),
    instituicao_trabalho VARCHAR(100),
    linha_pesquisa VARCHAR(100) NOT NULL,
    PRIMARY KEY (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- RF-08 a RF-10: perfil Público em geral (sem dados próprios).
CREATE TABLE publico_geral (
    usuario_id INT NOT NULL,
    PRIMARY KEY (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- RF-22 a RF-26: Administrador (equipe que opera o site).
CREATE TABLE administrador (
    usuario_id INT NOT NULL,
    area_responsabilidade VARCHAR(100),
    data_inicio DATE,
    PRIMARY KEY (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- ---------- Mapa e conteúdo cultural (RF-01, RF-02, RF-17) ----------

-- RF-01: as 27 unidades federativas do Mapa do Brasil.
CREATE TABLE estado (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    sigla CHAR(2) NOT NULL UNIQUE,
    regiao VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_estado_regiao CHECK (regiao IN ('Norte', 'Nordeste', 'Centro-Oeste', 'Sudeste', 'Sul'))
);

-- RF-02 e RF-17: todo conteúdo pertence a um estado e a exatamente uma categoria
-- (generalização total e disjunta em 9 tipos). RF-21: texto técnico e referências.
CREATE TABLE conteudo_cultural (
    id INT NOT NULL AUTO_INCREMENT,
    tipo VARCHAR(30) NOT NULL,
    data_cadastro DATE NOT NULL,
    texto_simplificado TEXT,
    texto_tecnico TEXT,
    referencias TEXT,
    estado_id INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (estado_id) REFERENCES estado(id),
    CONSTRAINT ck_conteudo_tipo CHECK (tipo IN ('Culinária', 'Música', 'Literatura', 'Variação Linguística',
        'Ponto Turístico', 'Acontecimento Histórico', 'Incidência Nominal', 'Curiosidade', 'Artigo Acadêmico'))
);

-- RF-02: culinária (ingredientes = atributo multivalorado em tabela própria).
CREATE TABLE culinaria (
    conteudo_id INT NOT NULL,
    nome_prato VARCHAR(100) NOT NULL,
    receita TEXT,
    descricao VARCHAR(255),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

CREATE TABLE culinaria_ingrediente (
    culinaria_id INT NOT NULL,
    ingrediente VARCHAR(100) NOT NULL,
    PRIMARY KEY (culinaria_id, ingrediente),
    FOREIGN KEY (culinaria_id) REFERENCES culinaria(conteudo_id)
);

-- RF-02: música (letra exibida quando disponível).
CREATE TABLE musica (
    conteudo_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    artista VARCHAR(100),
    genero_musical VARCHAR(50),
    audio VARCHAR(255),
    letra TEXT,
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-02 e RF-03: literatura e autor (busca por obras).
CREATE TABLE autor (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (id)
);

CREATE TABLE literatura (
    conteudo_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    genero VARCHAR(50),
    trecho TEXT,
    autor_id INT NOT NULL,
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id),
    FOREIGN KEY (autor_id) REFERENCES autor(id)
);

-- RF-02 e RF-03: variações linguísticas (expressões regionais).
CREATE TABLE variacao_linguistica (
    conteudo_id INT NOT NULL,
    expressao VARCHAR(100) NOT NULL,
    significado VARCHAR(255),
    exemplo_uso VARCHAR(255),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-02: pontos turísticos (imagem exibida quando disponível).
CREATE TABLE ponto_turistico (
    conteudo_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    localizacao VARCHAR(255),
    historia TEXT,
    descricao VARCHAR(255),
    imagem VARCHAR(255),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-02: acontecimentos históricos.
CREATE TABLE acontecimento_historico (
    conteudo_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    periodo_historico VARCHAR(50),
    local VARCHAR(100),
    descricao VARCHAR(255),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-02 (N:M): um acontecimento pode abranger outros estados além do de origem.
CREATE TABLE acontecimento_estado (
    acontecimento_id INT NOT NULL,
    estado_id INT NOT NULL,
    PRIMARY KEY (acontecimento_id, estado_id),
    FOREIGN KEY (acontecimento_id) REFERENCES acontecimento_historico(conteudo_id),
    FOREIGN KEY (estado_id) REFERENCES estado(id)
);

-- RF-02 e RF-03: incidência nominal (termos).
CREATE TABLE incidencia_nominal (
    conteudo_id INT NOT NULL,
    termo VARCHAR(100) NOT NULL,
    significado VARCHAR(255),
    origem VARCHAR(100),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-02 e RF-03: curiosidades.
CREATE TABLE curiosidade (
    conteudo_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    curiosidade TEXT,
    categoria VARCHAR(50),
    descricao VARCHAR(255),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-20 e RF-21: artigos e trabalhos acadêmicos submetidos por pesquisadores.
CREATE TABLE artigo_academico (
    conteudo_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    resumo TEXT,
    area_pesquisa VARCHAR(100),
    arquivo VARCHAR(255),
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- ---------- Contribuição e moderação (RF-16, RF-19, RF-20, RF-23, RF-24) ----------

-- UC-16: situação 'rascunho' ou 'pendente' ao enviar; UC-23: 'aprovado' ou 'rejeitado'
-- (com motivo); RF-24: registra o administrador que editou durante a moderação.
CREATE TABLE contribuicao (
    conteudo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    admin_id INT,
    data_envio DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    data_revisao DATETIME,
    motivo_rejeicao VARCHAR(255),
    editor_id INT,
    data_edicao DATETIME,
    PRIMARY KEY (conteudo_id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (admin_id) REFERENCES administrador(usuario_id),
    FOREIGN KEY (editor_id) REFERENCES administrador(usuario_id),
    CONSTRAINT ck_contribuicao_status CHECK (status IN ('rascunho', 'pendente', 'aprovado', 'rejeitado')),
    CONSTRAINT ck_contribuicao_motivo CHECK (status <> 'rejeitado' OR motivo_rejeicao IS NOT NULL)
);

-- ---------- Interação do usuário (RF-03, RF-06 a RF-09, RF-25, RF-26) ----------

-- RF-06 (N:M): nota de 1 a 5 estrelas, uma por usuário e conteúdo.
CREATE TABLE avaliacao (
    usuario_id INT NOT NULL,
    conteudo_id INT NOT NULL,
    nota INT NOT NULL,
    data_avaliacao DATETIME NOT NULL,
    PRIMARY KEY (usuario_id, conteudo_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-07 e RF-26: feedback ou correção enviado junto da avaliação (por isso referencia
-- a avaliação) e analisado pelo administrador.
CREATE TABLE feedback (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    conteudo_id INT NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    texto TEXT NOT NULL,
    data_envio DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id, conteudo_id) REFERENCES avaliacao(usuario_id, conteudo_id),
    CONSTRAINT ck_feedback_tipo CHECK (tipo IN ('comentário', 'correção')),
    CONSTRAINT ck_feedback_status CHECK (status IN ('pendente', 'analisado'))
);

-- RF-09 (N:M): conteúdos, artigos e curiosidades salvos em "Favoritos".
CREATE TABLE favorito (
    usuario_id INT NOT NULL,
    conteudo_id INT NOT NULL,
    data_inclusao DATETIME NOT NULL,
    PRIMARY KEY (usuario_id, conteudo_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id)
);

-- RF-25, UC-02 e UC-03: registro de acessos e termos pesquisados. O usuário é
-- opcional porque o Usuário pode navegar sem login (Glossário de Atores).
CREATE TABLE acesso (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT,
    data_hora DATETIME NOT NULL,
    pagina_visitada VARCHAR(100) NOT NULL,
    conteudo_id INT,
    termo_pesquisado VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id),
    CONSTRAINT ck_acesso_pesquisa CHECK (pagina_visitada <> 'Pesquisa' OR termo_pesquisado IS NOT NULL)
);

-- RF-08: atendimento de ajuda por agente de IA ou por atendente humano. A resposta do
-- agente de IA é gravada na hora; a do atendente humano entra depois, pelo painel do
-- administrador (ck_atendimento_resposta garante que resposta e data andem juntas).
CREATE TABLE agente_ia (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    funcao VARCHAR(100),
    base_conhecimento TEXT,
    PRIMARY KEY (id)
);

CREATE TABLE atendimento (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    canal VARCHAR(20) NOT NULL,
    agente_id INT,
    data_hora DATETIME NOT NULL,
    topico VARCHAR(100),
    resposta TEXT,
    data_resposta DATETIME,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (agente_id) REFERENCES agente_ia(id),
    CONSTRAINT ck_atendimento_canal CHECK (
        (canal = 'agente de IA' AND agente_id IS NOT NULL) OR
        (canal = 'atendente humano' AND agente_id IS NULL)),
    CONSTRAINT ck_atendimento_resposta CHECK (
        (resposta IS NULL AND data_resposta IS NULL) OR
        (resposta IS NOT NULL AND data_resposta IS NOT NULL))
);

-- ---------- Apoio pedagógico (RF-05, RF-10 a RF-15, RF-18) ----------

-- RF-05, RF-11, RF-13 e RF-15: atividades interativas. O quiz inicial (RF-05) não é
-- ligado a um conteúdo; docente_id preenchido = criada por um docente (RF-15).
CREATE TABLE atividade_interativa (
    id INT NOT NULL AUTO_INCREMENT,
    conteudo_id INT,
    docente_id INT,
    tipo VARCHAR(30) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id),
    FOREIGN KEY (docente_id) REFERENCES docente(usuario_id),
    CONSTRAINT ck_atividade_tipo CHECK (tipo IN ('quiz inicial', 'quiz', 'jogo da memória', 'quebra-cabeça', 'dinâmica de aula')),
    CONSTRAINT ck_atividade_conteudo CHECK (tipo = 'quiz inicial' OR conteudo_id IS NOT NULL)
);

-- RF-11 e RF-13 (N:M): cada vez que um estudante realiza uma atividade.
CREATE TABLE tentativa (
    estudante_id INT NOT NULL,
    atividade_id INT NOT NULL,
    data_hora DATETIME NOT NULL,
    pontuacao INT NOT NULL,
    PRIMARY KEY (estudante_id, atividade_id, data_hora),
    FOREIGN KEY (estudante_id) REFERENCES estudante(usuario_id),
    FOREIGN KEY (atividade_id) REFERENCES atividade_interativa(id)
);

-- RF-10, RF-12, RF-14 e RF-18: materiais ligados a um conteúdo. docente_id preenchido
-- = anexado por um docente (RF-18).
CREATE TABLE recurso_complementar (
    id INT NOT NULL AUTO_INCREMENT,
    conteudo_id INT NOT NULL,
    docente_id INT,
    tipo VARCHAR(30) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    endereco VARCHAR(255),
    descricao VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (conteudo_id) REFERENCES conteudo_cultural(id),
    FOREIGN KEY (docente_id) REFERENCES docente(usuario_id),
    CONSTRAINT ck_recurso_tipo CHECK (tipo IN ('material pedagógico', 'plano de aula', 'vídeo-aula', 'link externo', 'mapa externo'))
);

-- =====================================================================================
-- SEÇÃO 2: DADOS DE TESTE
-- Ação: População de todas as tabelas (27 UFs, 14 conteúdos com as suas especializações,
--       contribuições em todas as situações, interações e apoio pedagógico).
-- Por quê: Permite testar joins, agregações, functions, procedures e as consultas da
--          SEÇÃO 7 (uma por requisito).
-- Resultado: Banco populado e coerente com as regras do Modelo Conceitual v4.
-- =====================================================================================

-- Senha de todos: Tche@2026 (bcrypt, custo 10). No PHP: password_verify('Tche@2026', $hash).
INSERT INTO usuario (nome, email, senha, telefone, data_nascimento, classificacao)
VALUES ('Ana Souza', 'ana@email.com', '$2y$10$z4dS65ffjrxGxC7x8twOzeJ30QgQKKuBzrQ/hoE0JMZh/knJdzdma', '(41) 99999-0001', '2008-03-10', 'Estudante'),
       ('Carlos Lima', 'carlos@email.com', '$2y$10$M37ZnKC.Rnzs/TjFVEIqr.HboUBa2s2fxIAbtkvVMNUOMmU94PyFa', '(41) 99999-0002', '1980-05-02', 'Docente'),
       ('Marta Reis', 'marta@email.com', '$2y$10$EO4DufMQp7O0EgA0rLIGzeeQZhLhjThHduw/GkjKSqV6ETev8J7X6', NULL, '1975-09-21', 'Pesquisador'),
       ('Admin Tchê', 'admin@email.com', '$2y$10$o10Z/zUJGzcPFaus3XAT4u9Sh5dydHmZIKJZkzFif8JO38BuGZH26', NULL, '1990-01-01', 'Administrador'),
       ('Joana Pereira', 'joana@email.com', '$2y$10$hoy2jl8vxIVEKHqx8tIz6e2K8A11m8aakO3k0yvyX2Hp1djUY9YBa', NULL, '1995-07-15', 'Público em Geral');

INSERT INTO estudante (usuario_id, instituicao, nivel_escolaridade)
VALUES (1, 'Colégio Estadual do Paraná', 'Ensino Médio');

INSERT INTO docente (usuario_id, certificado_exp, instituicao_trabalho)
VALUES (2, 'Licenciatura em História', 'UFPR');

INSERT INTO pesquisador (usuario_id, tempo_experiencia, instituicao_trabalho, linha_pesquisa)
VALUES (3, '10 anos', 'USP', 'História do Sul do Brasil');

INSERT INTO administrador (usuario_id, area_responsabilidade, data_inicio)
VALUES (4, 'Moderação', '2026-01-01');

INSERT INTO publico_geral (usuario_id)
VALUES (5);

-- As 4 primeiras UFs mantêm os IDs 1 a 4 usados nos testes.
INSERT INTO estado (nome, sigla, regiao)
VALUES ('Paraná', 'PR', 'Sul'), ('Rio Grande do Sul', 'RS', 'Sul'), ('Bahia', 'BA', 'Nordeste'),
       ('Santa Catarina', 'SC', 'Sul'), ('Acre', 'AC', 'Norte'), ('Alagoas', 'AL', 'Nordeste'),
       ('Amapá', 'AP', 'Norte'), ('Amazonas', 'AM', 'Norte'), ('Ceará', 'CE', 'Nordeste'),
       ('Distrito Federal', 'DF', 'Centro-Oeste'), ('Espírito Santo', 'ES', 'Sudeste'),
       ('Goiás', 'GO', 'Centro-Oeste'), ('Maranhão', 'MA', 'Nordeste'), ('Mato Grosso', 'MT', 'Centro-Oeste'),
       ('Mato Grosso do Sul', 'MS', 'Centro-Oeste'), ('Minas Gerais', 'MG', 'Sudeste'), ('Pará', 'PA', 'Norte'),
       ('Paraíba', 'PB', 'Nordeste'), ('Pernambuco', 'PE', 'Nordeste'), ('Piauí', 'PI', 'Nordeste'),
       ('Rio de Janeiro', 'RJ', 'Sudeste'), ('Rio Grande do Norte', 'RN', 'Nordeste'), ('Rondônia', 'RO', 'Norte'),
       ('Roraima', 'RR', 'Norte'), ('São Paulo', 'SP', 'Sudeste'), ('Sergipe', 'SE', 'Nordeste'),
       ('Tocantins', 'TO', 'Norte');

INSERT INTO conteudo_cultural (tipo, data_cadastro, texto_simplificado, texto_tecnico, referencias, estado_id)
VALUES ('Culinária', '2026-09-01', 'O barreado é um prato do litoral do Paraná.', 'Prato de cozimento lento de Morretes.', 'Livro de cozinha regional.', 1),
       ('Música', '2026-09-01', 'Música tradicional gaúcha.', NULL, NULL, 2),
       ('Literatura', '2026-09-01', 'Romance sobre o Rio Grande do Sul.', NULL, NULL, 2),
       ('Ponto Turístico', '2026-09-02', 'Museu de arte em Curitiba.', NULL, NULL, 1),
       ('Variação Linguística', '2026-09-02', 'Guri quer dizer menino.', NULL, NULL, 2),
       ('Incidência Nominal', '2026-09-02', 'Sobrenome de origem polonesa.', NULL, NULL, 1),
       ('Acontecimento Histórico', '2026-09-03', 'Conflito no sul do Brasil.', 'Conflito social de 1912 a 1916.', 'Fraga, N. Contestado.', 1),
       ('Acontecimento Histórico', '2026-09-03', 'Guerra no Rio Grande do Sul.', 'Conflito do período imperial.', 'Pesavento, S. A Revolução Farroupilha.', 2),
       ('Curiosidade', '2026-09-04', 'O chimarrão faz parte da cultura do Sul.', NULL, NULL, 1),
       ('Culinária', CURDATE(), 'O pinhão é típico do inverno no Paraná.', 'Prato de sementes de araucária.', 'Culinária Paranaense.', 1),
       ('Artigo Acadêmico', '2026-11-05', 'Estudo sobre a memória do Contestado.', 'Análise de fontes orais sobre a Guerra do Contestado.', 'Machado, P. Lideranças do Contestado.', 1),
       ('Curiosidade', '2026-11-10', 'Florianópolis tem mais de 40 praias.', NULL, NULL, 4),
       ('Música', '2026-10-20', 'Samba de roda do Recôncavo.', 'Patrimônio imaterial registrado pelo IPHAN.', 'IPHAN. Samba de Roda do Recôncavo Baiano.', 3),
       ('Culinária', '2026-10-10', 'O acarajé é um bolinho de feijão-fradinho.', 'Ofício das baianas de acarajé, patrimônio imaterial.', 'IPHAN. Ofício das Baianas de Acarajé.', 3);

-- Especializações: todo conteúdo é de exatamente um tipo (generalização total e disjunta).
INSERT INTO culinaria (conteudo_id, nome_prato, receita, descricao)
VALUES (1, 'Barreado', 'Cozinhar a carne por muitas horas.', 'Prato típico do Paraná'),
       (10, 'Pinhão cozido', 'Cozinhar o pinhão em água e sal.', 'Prato tradicional de inverno'),
       (14, 'Acarajé', 'Fritar a massa de feijão-fradinho no azeite de dendê.', 'Prato típico da Bahia');

INSERT INTO culinaria_ingrediente (culinaria_id, ingrediente)
VALUES (1, 'Carne bovina'), (1, 'Farinha de mandioca'), (10, 'Pinhão'), (10, 'Sal'),
       (14, 'Feijão-fradinho'), (14, 'Azeite de dendê');

-- A coluna audio guarda o arquivo (pasta arquivos/) ou o endereço do áudio da música. Fica
-- NULL no acervo de teste porque o projeto não distribui gravação de obra de terceiros; o
-- site só mostra o botão "Ouvir" quando o arquivo existe de verdade na pasta.
INSERT INTO musica (conteudo_id, nome, artista, genero_musical, audio, letra)
VALUES (2, 'Querência amada', 'Teixeirinha', 'Gaúcha', NULL, 'Letra cadastrada pela equipe.'),
       (13, 'Samba de roda', 'Mestres do Recôncavo', 'Samba de roda', NULL, NULL);

INSERT INTO autor (nome)
VALUES ('Érico Veríssimo');

INSERT INTO literatura (conteudo_id, titulo, genero, trecho, autor_id)
VALUES (3, 'O Tempo e o Vento', 'Romance', 'Trecho da obra...', 1);

INSERT INTO ponto_turistico (conteudo_id, nome, localizacao, historia, descricao, imagem)
VALUES (4, 'Museu Oscar Niemeyer', 'R. Mal. Hermes, 999 - Curitiba, PR', 'Inaugurado em 2002.', 'Museu de arte', 'museu.jpg');

INSERT INTO variacao_linguistica (conteudo_id, expressao, significado, exemplo_uso)
VALUES (5, 'Guri', 'Menino', 'Aquele guri é esperto.');

INSERT INTO incidencia_nominal (conteudo_id, termo, significado, origem)
VALUES (6, 'Kowalski', 'Sobrenome que significa ferreiro', 'Polônia');

INSERT INTO acontecimento_historico (conteudo_id, titulo, periodo_historico, local, descricao)
VALUES (7, 'Guerra do Contestado', 'República Velha', 'Paraná e Santa Catarina', 'Conflito pela terra'),
       (8, 'Revolução Farroupilha', 'Império', 'Rio Grande do Sul', 'Guerra regional');

-- N:M: o acontecimento abrange outros estados além do de origem.
INSERT INTO acontecimento_estado (acontecimento_id, estado_id)
VALUES (7, 4), (8, 4);

INSERT INTO curiosidade (conteudo_id, titulo, curiosidade, categoria, descricao)
VALUES (9, 'Origem do chimarrão', 'O chimarrão vem dos povos indígenas.', 'Culinária', 'Bebida típica'),
       (12, 'Praias de Florianópolis', 'A ilha tem mais de 40 praias.', 'Geografia', 'Curiosidade sobre SC');

INSERT INTO artigo_academico (conteudo_id, titulo, resumo, area_pesquisa, arquivo)
VALUES (11, 'Memória oral da Guerra do Contestado', 'Entrevistas com descendentes de caboclos.', 'História do Sul do Brasil', 'artigo-contestado.pdf');

-- Situações das contribuições (UC-16 e UC-23): aprovado, pendente, rascunho e rejeitado.
-- Conteúdos sem contribuição (1 a 6, 9 e 14) foram cadastrados pela equipe e já são públicos.
INSERT INTO contribuicao (conteudo_id, usuario_id, admin_id, data_envio, status, data_revisao, motivo_rejeicao, editor_id, data_edicao)
VALUES (7, 2, 4, '2026-09-03 10:00', 'aprovado', '2026-09-04 09:00', NULL, 4, '2026-09-04 08:50'),
       (8, 2, 4, '2026-09-03 11:00', 'aprovado', '2026-09-04 09:30', NULL, NULL, NULL),
       (10, 2, NULL, NOW(), 'pendente', NULL, NULL, NULL, NULL),
       (11, 3, NULL, '2026-11-05 14:00', 'pendente', NULL, NULL, NULL, NULL),
       (12, 2, NULL, '2026-11-10 20:00', 'rascunho', NULL, NULL, NULL, NULL),
       (13, 3, 4, '2026-10-20 10:00', 'rejeitado', '2026-10-21 09:00', 'Fonte da gravação não informada.', NULL, NULL);

INSERT INTO avaliacao (usuario_id, conteudo_id, nota, data_avaliacao)
VALUES (1, 7, 5, '2026-10-01 09:10'),
       (5, 7, 4, '2026-10-02 20:10'),
       (3, 2, 4, '2026-10-03 15:00'),
       (1, 9, 5, '2026-10-04 10:10'),
       (5, 14, 3, '2026-10-06 15:10');

INSERT INTO feedback (usuario_id, conteudo_id, tipo, texto, data_envio, status)
VALUES (1, 7, 'correção', 'O período está incorreto.', '2026-10-01 09:12', 'pendente'),
       (3, 2, 'comentário', 'Bom registro da música gaúcha.', '2026-10-03 15:02', 'analisado');

INSERT INTO favorito (usuario_id, conteudo_id, data_inclusao)
VALUES (1, 9, '2026-10-01 09:20'), (1, 7, '2026-10-01 09:21'), (5, 14, '2026-10-06 15:12');

INSERT INTO acesso (usuario_id, data_hora, pagina_visitada, conteudo_id, termo_pesquisado)
VALUES (1, '2026-10-01 09:00', 'Conteúdo', 7, NULL),
       (2, '2026-10-01 14:00', 'Conteúdo', 7, NULL),
       (3, '2026-10-02 19:00', 'Conteúdo', 9, NULL),
       (3, '2026-10-03 14:50', 'Conteúdo', 2, NULL),
       (5, '2026-10-02 20:00', 'Mapa', NULL, NULL),
       (5, '2026-10-02 20:05', 'Conteúdo', 7, NULL),
       (NULL, '2026-10-04 10:00', 'Pesquisa', NULL, 'chimarrão'),
       (1, '2026-10-04 10:05', 'Pesquisa', NULL, 'barreado'),
       (NULL, '2026-10-04 11:00', 'Pesquisa', NULL, 'chimarrão'),
       (NULL, '2026-10-06 15:00', 'Conteúdo', 14, NULL);

INSERT INTO agente_ia (nome, funcao, base_conhecimento)
VALUES ('Tchê Bot', 'Responder dúvidas sobre o conteúdo cultural', 'Conteúdos culturais dos estados brasileiros');

-- o canal de IA já grava a resposta; o canal humano fica sem resposta até um administrador responder
INSERT INTO atendimento (usuario_id, canal, agente_id, data_hora, topico, resposta, data_resposta)
VALUES (1, 'agente de IA', 1, '2026-10-05 11:00', 'Culinária do Paraná',
        'Olá! Sou o Tchê Bot. Encontrei no acervo: Barreado (Culinária Regional) — prato típico do litoral do Paraná.', '2026-10-05 11:00'),
       (5, 'atendente humano', NULL, '2026-10-06 16:00', 'Dúvida sobre o cadastro', NULL, NULL);

INSERT INTO atividade_interativa (conteudo_id, docente_id, tipo, titulo, descricao)
VALUES (4, NULL, 'jogo da memória', 'Memória dos monumentos do Paraná', 'Pares de monumentos e símbolos do PR'),
       (9, NULL, 'quiz', 'Quiz de curiosidades do Paraná', NULL),
       (NULL, NULL, 'quiz inicial', 'Quiz do Blue', 'Quiz de apresentação do Tchê & Cultura com o mascote Blue'),
       (7, 2, 'dinâmica de aula', 'Júri simulado: Guerra do Contestado', 'Atividade criada pelo docente para a aula'),
       (4, NULL, 'quebra-cabeça', 'Quebra-cabeça do Museu Oscar Niemeyer', 'Remonte a imagem do monumento em 9 peças');

INSERT INTO tentativa (estudante_id, atividade_id, data_hora, pontuacao)
VALUES (1, 1, '2026-10-05 10:00', 90),
       (1, 2, '2026-10-05 10:15', 80);

INSERT INTO recurso_complementar (conteudo_id, docente_id, tipo, titulo, endereco, descricao)
VALUES (7, NULL, 'vídeo-aula', 'O Contestado explicado', 'https://video.exemplo.com/contestado', NULL),
       (7, NULL, 'link externo', 'Saiba mais sobre o Contestado', 'https://exemplo.com/contestado', NULL),
       (7, 2, 'plano de aula', 'Plano de aula: Contestado', 'https://exemplo.com/plano.pdf', 'Anexado pelo docente'),
       (7, 2, 'material pedagógico', 'Linha do tempo do Contestado', 'https://exemplo.com/linha-do-tempo.pdf', 'Anexado pelo docente'),
       (4, NULL, 'mapa externo', 'Museu Oscar Niemeyer no Google Maps', 'https://maps.google.com/?q=Museu+Oscar+Niemeyer', NULL);

-- ---------- Acervo inicial do site (conteúdos 15 em diante, cadastrados pela equipe) ----------
-- Gerado por gerar_acervo.py. Cobre as 5 regiões e todas as categorias do RF-02.
INSERT INTO autor (nome) VALUES ('José de Alencar'), ('Graciliano Ramos'), ('João Guimarães Rosa'), ('Mário de Andrade'), ('Euclides da Cunha'), ('Domínio popular');
INSERT INTO conteudo_cultural (tipo, data_cadastro, texto_simplificado, texto_tecnico, referencias, estado_id) VALUES
       ('Literatura', '2026-02-10', 'Romance que conta a história de amor entre a indígena Iracema e o português Martim.', 'Romance indianista do Romantismo brasileiro, publicado em 1865.', 'Alencar, J. Iracema. 1865.', 9),
       ('Literatura', '2026-02-12', 'Uma família de retirantes atravessa o sertão fugindo da seca.', 'Romance regionalista da segunda fase do Modernismo, publicado em 1938.', 'Ramos, G. Vidas Secas. 1938.', 6),
       ('Literatura', '2026-03-05', 'O jagunço Riobaldo conta sua vida pelo sertão de Minas Gerais.', 'Romance de 1956 conhecido pela linguagem inventiva que recria a fala sertaneja.', 'Rosa, J. G. Grande Sertão: Veredas. 1956.', 16),
       ('Literatura', '2026-03-08', 'Macunaíma é o "herói sem nenhum caráter", que mistura lendas de todo o Brasil.', 'Rapsódia modernista de 1928 que reúne mitos indígenas, africanos e europeus.', 'Andrade, M. Macunaíma. 1928.', 25),
       ('Literatura', '2026-03-12', 'Livro que relata a Guerra de Canudos, no sertão da Bahia.', 'Obra de 1902 que mistura reportagem, geografia e história sobre Canudos.', 'Cunha, E. Os Sertões. 1902.', 3),
       ('Literatura', '2026-04-02', 'O boto-cor-de-rosa vira um moço elegante nas noites de festa nas margens dos rios.', 'Lenda amazônica de tradição oral ligada aos povos ribeirinhos.', 'Cascudo, L. C. Dicionário do Folclore Brasileiro.', 8),
       ('Literatura', '2026-04-05', 'A indígena Naiá se encantou pela Lua e virou a vitória-régia, a grande flor dos rios.', 'Lenda de origem tupi que explica o surgimento da vitória-régia.', 'Cascudo, L. C. Dicionário do Folclore Brasileiro.', 17),
       ('Literatura', '2026-04-08', 'A Comadre Fulozinha protege as matas e confunde quem caça sem respeito.', 'Entidade protetora da mata no folclore nordestino.', 'Cascudo, L. C. Dicionário do Folclore Brasileiro.', 19),
       ('Literatura', '2026-04-11', 'O Pé de Garrafa é um ser da mata que deixa pegadas redondas como fundo de garrafa.', 'Lenda do Centro-Oeste ligada aos caçadores e às matas do Cerrado.', 'Cascudo, L. C. Dicionário do Folclore Brasileiro.', 14),
       ('Literatura', '2026-04-14', 'O Saci-Pererê é um menino de uma perna só, de gorro vermelho, que adora pregar peças.', 'Personagem do folclore popularizado na literatura por Monteiro Lobato.', 'Cascudo, L. C. Dicionário do Folclore Brasileiro.', 25),
       ('Literatura', '2026-04-17', 'O Negrinho do Pastoreio ajuda quem perdeu algo a encontrar o que procura.', 'Lenda gaúcha registrada por João Simões Lopes Neto.', 'Lopes Neto, J. S. Lendas do Sul. 1913.', 2),
       ('Música', '2026-05-02', 'O carimbó é uma dança de roda com tambores, típica do Pará.', 'Reconhecido como Patrimônio Cultural Imaterial do Brasil pelo IPHAN em 2014.', 'IPHAN. Carimbó. 2014.', 17),
       ('Música', '2026-05-04', 'O frevo é o ritmo acelerado do Carnaval de Pernambuco, dançado com sombrinhas coloridas.', 'Inscrito pela UNESCO como Patrimônio Imaterial da Humanidade em 2012.', 'UNESCO. Frevo. 2012.', 19),
       ('Música', '2026-05-06', 'O samba nasceu nas rodas dos morros e quintais do Rio de Janeiro.', 'As matrizes do samba no Rio de Janeiro são patrimônio imaterial registrado pelo IPHAN em 2007.', 'IPHAN. Matrizes do Samba no Rio de Janeiro. 2007.', 21),
       ('Música', '2026-05-08', 'A moda de viola conta histórias do campo com a viola caipira.', 'Gênero da música caipira, base da música sertaneja do Centro-Oeste.', 'Nepomuceno, R. Música Caipira.', 12),
       ('Música', '2026-05-10', 'As toadas embalam a disputa dos bois Garantido e Caprichoso em Parintins.', 'Gênero ligado ao boi-bumbá amazonense.', 'Braga, S. Os bois-bumbás de Parintins.', 8),
       ('Culinária', '2026-06-02', 'O tacacá é um caldo quente servido na cuia, típico de Belém.', 'Preparo à base de tucupi e goma de mandioca, com jambu e camarão seco.', 'Lody, R. Brasil Bom de Boca.', 17),
       ('Culinária', '2026-06-04', 'O arroz com pequi é o sabor mais famoso do Cerrado goiano.', 'O pequi é fruto nativo do Cerrado; não se morde o caroço, que tem espinhos.', 'Lody, R. Brasil Bom de Boca.', 12),
       ('Culinária', '2026-06-06', 'O pão de queijo é o lanche mais conhecido de Minas Gerais.', 'Feito com polvilho, sem farinha de trigo.', 'Lody, R. Brasil Bom de Boca.', 16),
       ('Culinária', '2026-06-08', 'A moqueca capixaba é feita na panela de barro, sem leite de coco.', 'As panelas de barro de Goiabeiras são patrimônio imaterial registrado pelo IPHAN.', 'IPHAN. Ofício das Paneleiras de Goiabeiras. 2002.', 11),
       ('Culinária', '2026-06-10', 'O baião de dois junta arroz e feijão na mesma panela.', 'Prato do sertão cearense feito com feijão-de-corda e queijo coalho.', 'Lody, R. Brasil Bom de Boca.', 9),
       ('Culinária', '2026-06-12', 'O churrasco gaúcho é assado devagar no fogo de chão.', 'Tradição dos tropeiros e peões dos pampas.', 'Lody, R. Brasil Bom de Boca.', 2),
       ('Acontecimento Histórico', '2026-07-02', 'A Cabanagem foi uma revolta popular no Pará durante o período regencial.', 'Revolta de 1835 a 1840 que chegou a tomar o governo da província do Grão-Pará.', 'Ricci, M. Cabanagem. 2007.', 17),
       ('Acontecimento Histórico', '2026-07-04', 'Canudos foi uma comunidade do sertão baiano atacada pelo Exército.', 'Conflito de 1896 e 1897 entre o arraial liderado por Antônio Conselheiro e o governo republicano.', 'Cunha, E. Os Sertões. 1902.', 3),
       ('Acontecimento Histórico', '2026-07-06', 'A Inconfidência Mineira foi um movimento contra os impostos da Coroa portuguesa.', 'Conspiração de 1789 em Vila Rica, cujo principal condenado foi Tiradentes.', 'Maxwell, K. A Devassa da Devassa.', 16),
       ('Acontecimento Histórico', '2026-07-08', 'Brasília foi construída no Planalto Central e virou a capital em 1960.', 'Projeto urbanístico de Lúcio Costa e arquitetura de Oscar Niemeyer.', 'IPHAN. Conjunto Urbanístico de Brasília.', 10),
       ('Acontecimento Histórico', '2026-07-10', 'O Ceará libertou as pessoas escravizadas em 1884, antes da Lei Áurea.', 'Abolição provincial em 25 de março de 1884; o Ceará ficou conhecido como Terra da Luz.', 'Girão, R. A Abolição no Ceará.', 9),
       ('Ponto Turístico', '2026-08-02', 'O Teatro Amazonas é um teatro de cúpula colorida no centro de Manaus.', 'Inaugurado em 1896, no auge do ciclo da borracha.', 'IPHAN. Teatro Amazonas.', 8),
       ('Ponto Turístico', '2026-08-04', 'O Jalapão tem dunas alaranjadas, cachoeiras e fervedouros de água cristalina.', 'Parque estadual no leste do Tocantins.', 'Governo do Tocantins. Parque Estadual do Jalapão.', 27),
       ('Ponto Turístico', '2026-08-06', 'A Chapada dos Veadeiros tem cânions e cachoeiras no meio do Cerrado.', 'Parque nacional reconhecido como Patrimônio Natural da Humanidade pela UNESCO em 2001.', 'ICMBio. Parque Nacional da Chapada dos Veadeiros.', 12),
       ('Ponto Turístico', '2026-08-08', 'Fernando de Noronha é um arquipélago de praias de água muito clara.', 'Parque nacional marinho e Patrimônio Natural da Humanidade.', 'ICMBio. Parque Nacional Marinho de Fernando de Noronha.', 19),
       ('Ponto Turístico', '2026-08-10', 'O Cristo Redentor fica no alto do morro do Corcovado.', 'Monumento inaugurado em 1931.', 'Prefeitura do Rio de Janeiro. Cristo Redentor.', 21),
       ('Ponto Turístico', '2026-08-12', 'As Cataratas do Iguaçu formam centenas de quedas d''água na fronteira.', 'Parque Nacional do Iguaçu, Patrimônio Natural da Humanidade desde 1986.', 'ICMBio. Parque Nacional do Iguaçu.', 1),
       ('Ponto Turístico', '2026-08-14', 'O Ver-o-Peso é o grande mercado às margens da baía, em Belém.', 'Complexo de feira e mercado tombado pelo IPHAN.', 'IPHAN. Ver-o-Peso.', 17),
       ('Curiosidade', '2026-09-10', 'Em Parintins, os bois Garantido e Caprichoso disputam a festa todo mês de junho.', NULL, NULL, 8),
       ('Curiosidade', '2026-09-12', 'O Círio de Nazaré reúne multidões nas ruas de Belém em outubro.', NULL, NULL, 17),
       ('Curiosidade', '2026-09-14', 'Campina Grande faz um dos maiores São João do Brasil.', NULL, NULL, 18),
       ('Curiosidade', '2026-09-16', 'Nas Cavalhadas, cavaleiros encenam a batalha entre mouros e cristãos.', NULL, NULL, 12),
       ('Curiosidade', '2026-09-18', 'A Oktoberfest de Blumenau celebra a herança alemã de Santa Catarina.', NULL, NULL, 4),
       ('Curiosidade', '2026-09-20', 'O Bumba meu boi do Maranhão mistura dança, música e teatro.', NULL, NULL, 13),
       ('Curiosidade', '2026-09-22', 'O capim dourado do Jalapão vira bolsas, brincos e chapéus que brilham como ouro.', NULL, NULL, 27),
       ('Curiosidade', '2026-09-24', 'No Acre existem desenhos geométricos gigantes cavados no solo, os geoglifos.', NULL, NULL, 5),
       ('Variação Linguística', '2026-10-01', 'No Pará, "égua!" é uma expressão de espanto.', NULL, NULL, 17),
       ('Variação Linguística', '2026-10-02', '"Oxente" mostra surpresa no jeito baiano de falar.', NULL, NULL, 3),
       ('Variação Linguística', '2026-10-03', 'Em Minas, "uai" aparece em quase toda conversa.', NULL, NULL, 16),
       ('Variação Linguística', '2026-10-04', 'Para o mineiro, "trem" pode ser qualquer coisa.', NULL, NULL, 16),
       ('Variação Linguística', '2026-10-05', 'O gaúcho diz "bah" para quase tudo.', NULL, NULL, 2),
       ('Variação Linguística', '2026-10-06', 'Em Pernambuco, "arretado" pode ser elogio.', NULL, NULL, 19),
       ('Variação Linguística', '2026-10-07', 'O carioca chama o amigo de "mermão".', NULL, NULL, 21),
       ('Incidência Nominal', '2026-10-10', 'Schmidt é um sobrenome alemão comum em Santa Catarina.', NULL, NULL, 4),
       ('Incidência Nominal', '2026-10-11', 'Muitos lugares começam com "Ita", palavra tupi.', NULL, NULL, 3),
       ('Incidência Nominal', '2026-10-12', 'Guanabara é o nome tupi da baía do Rio de Janeiro.', NULL, NULL, 21),
       ('Incidência Nominal', '2026-10-13', 'Silva é o sobrenome mais comum do Brasil.', NULL, NULL, 25),
       ('Artigo Acadêmico', '2026-10-15', 'Estudo sobre como os tropeiros ajudaram a formar as cidades do Paraná.', 'Análise dos caminhos de tropas entre o Rio Grande do Sul e Sorocaba nos séculos XVIII e XIX.', 'Machado, B. P. Formação da estrutura agrária tradicional dos Campos Gerais.', 1);
INSERT INTO culinaria (conteudo_id, nome_prato, receita, descricao) VALUES
       (31, 'Tacacá', 'Servir o tucupi quente com goma, jambu e camarão.', 'Prato típico do Pará'),
       (32, 'Arroz com pequi', 'Refogar o pequi e cozinhar com o arroz.', 'Prato típico de Goiás'),
       (33, 'Pão de queijo', 'Misturar polvilho, queijo, ovos e leite e assar.', 'Prato típico de Minas Gerais'),
       (34, 'Moqueca capixaba', 'Cozinhar o peixe com tomate, cebola e coentro na panela de barro.', 'Prato típico do Espírito Santo'),
       (35, 'Baião de dois', 'Cozinhar arroz e feijão-de-corda juntos e finalizar com queijo coalho.', 'Prato típico do Ceará'),
       (36, 'Churrasco gaúcho', 'Assar a carne temperada com sal grosso.', 'Prato típico do Rio Grande do Sul');
INSERT INTO culinaria_ingrediente (culinaria_id, ingrediente) VALUES
       (31, 'Tucupi'),
       (31, 'Goma de mandioca'),
       (31, 'Jambu'),
       (31, 'Camarão seco'),
       (32, 'Arroz'),
       (32, 'Pequi'),
       (32, 'Alho'),
       (33, 'Polvilho'),
       (33, 'Queijo'),
       (33, 'Ovos'),
       (33, 'Leite'),
       (34, 'Peixe'),
       (34, 'Tomate'),
       (34, 'Coentro'),
       (34, 'Urucum'),
       (35, 'Arroz'),
       (35, 'Feijão-de-corda'),
       (35, 'Queijo coalho'),
       (36, 'Carne bovina'),
       (36, 'Sal grosso');
INSERT INTO musica (conteudo_id, nome, artista, genero_musical, audio, letra) VALUES
       (26, 'Carimbó', 'Mestres do carimbó paraense', 'Carimbó', NULL, NULL),
       (27, 'Frevo', 'Orquestras de frevo do Recife', 'Frevo', NULL, NULL),
       (28, 'Samba carioca', 'Escolas de samba do Rio', 'Samba', NULL, NULL),
       (29, 'Moda de viola', 'Duplas caipiras de Goiás', 'Música caipira', NULL, NULL),
       (30, 'Toada de boi-bumbá', 'Bois Garantido e Caprichoso', 'Toada', NULL, NULL);
INSERT INTO literatura (conteudo_id, titulo, genero, trecho, autor_id) VALUES
       (15, 'Iracema', 'Romance indianista', 'Narrativa sobre a formação do Ceará.', (SELECT id FROM autor WHERE nome = 'José de Alencar')),
       (16, 'Vidas Secas', 'Romance regionalista', 'A seca e a migração no sertão nordestino.', (SELECT id FROM autor WHERE nome = 'Graciliano Ramos')),
       (17, 'Grande Sertão: Veredas', 'Romance', 'O sertão como cenário e personagem.', (SELECT id FROM autor WHERE nome = 'João Guimarães Rosa')),
       (18, 'Macunaíma', 'Rapsódia modernista', 'Mistura de lendas e falares do Brasil.', (SELECT id FROM autor WHERE nome = 'Mário de Andrade')),
       (19, 'Os Sertões', 'Não ficção', 'A terra, o homem e a luta em Canudos.', (SELECT id FROM autor WHERE nome = 'Euclides da Cunha')),
       (20, 'Lenda do Boto', 'Lenda', 'O boto que vira gente nas festas juninas.', (SELECT id FROM autor WHERE nome = 'Domínio popular')),
       (21, 'Lenda da Vitória-Régia', 'Lenda', 'A estrela das águas da Amazônia.', (SELECT id FROM autor WHERE nome = 'Domínio popular')),
       (22, 'Comadre Fulozinha', 'Lenda', 'Guardiã das matas do Nordeste.', (SELECT id FROM autor WHERE nome = 'Domínio popular')),
       (23, 'Pé de Garrafa', 'Lenda', 'As pegadas misteriosas da mata.', (SELECT id FROM autor WHERE nome = 'Domínio popular')),
       (24, 'Saci-Pererê', 'Lenda', 'O travesso do gorro vermelho.', (SELECT id FROM autor WHERE nome = 'Domínio popular')),
       (25, 'Negrinho do Pastoreio', 'Lenda', 'A lenda dos pampas gaúchos.', (SELECT id FROM autor WHERE nome = 'Domínio popular'));
INSERT INTO variacao_linguistica (conteudo_id, expressao, significado, exemplo_uso) VALUES
       (57, 'Égua', 'Expressão de surpresa ou espanto', 'Égua, que chuva forte!'),
       (58, 'Oxente', 'Expressão de surpresa', 'Oxente, você por aqui?'),
       (59, 'Uai', 'Interjeição de surpresa ou de reforço', 'Uai, já vai embora?'),
       (60, 'Trem', 'Coisa, objeto', 'Me passa esse trem aí.'),
       (61, 'Bah', 'Expressão de espanto ou admiração', 'Bah, que frio!'),
       (62, 'Arretado', 'Muito bom, excelente (ou zangado, conforme o contexto)', 'Esse frevo é arretado!'),
       (63, 'Mermão', 'Meu irmão; forma de tratamento entre amigos', 'Fala, mermão!');
INSERT INTO ponto_turistico (conteudo_id, nome, localizacao, historia, descricao, imagem) VALUES
       (42, 'Teatro Amazonas', 'Largo de São Sebastião - Manaus, AM', 'Símbolo do ciclo da borracha.', 'Teatro histórico', NULL),
       (43, 'Jalapão', 'Leste do Tocantins', 'Região do capim dourado.', 'Parque natural', NULL),
       (44, 'Chapada dos Veadeiros', 'Alto Paraíso de Goiás, GO', 'Área protegida do Cerrado.', 'Parque nacional', NULL),
       (45, 'Fernando de Noronha', 'Arquipélago de Fernando de Noronha, PE', 'Área de proteção marinha.', 'Praias e vida marinha', NULL),
       (46, 'Cristo Redentor', 'Morro do Corcovado - Rio de Janeiro, RJ', 'Inaugurado em 1931.', 'Monumento', NULL),
       (47, 'Cataratas do Iguaçu', 'Foz do Iguaçu, PR', 'Parque nacional criado em 1939.', 'Quedas d''água', NULL),
       (48, 'Mercado Ver-o-Peso', 'Belém, PA', 'Mercado ativo desde o período colonial.', 'Mercado e feira', NULL);
INSERT INTO acontecimento_historico (conteudo_id, titulo, periodo_historico, local, descricao) VALUES
       (37, 'Cabanagem', 'Período Regencial', 'Belém e interior do Grão-Pará', 'Revolta popular de 1835 a 1840'),
       (38, 'Guerra de Canudos', 'República Velha', 'Sertão da Bahia', 'Conflito de 1896 a 1897'),
       (39, 'Inconfidência Mineira', 'Brasil Colônia', 'Vila Rica (Ouro Preto)', 'Movimento de 1789'),
       (40, 'Construção de Brasília', 'República', 'Planalto Central', 'Inaugurada em 21 de abril de 1960'),
       (41, 'Abolição no Ceará', 'Império', 'Província do Ceará', 'Abolição de 1884');
INSERT INTO acontecimento_estado (acontecimento_id, estado_id) VALUES
       (37, 8),
       (39, 21),
       (40, 12);
INSERT INTO incidencia_nominal (conteudo_id, termo, significado, origem) VALUES
       (64, 'Schmidt', 'Ferreiro, em alemão', 'Alemanha'),
       (65, 'Ita', 'Pedra, em tupi (como em Itaparica)', 'Tupi'),
       (66, 'Guanabara', 'Seio do mar, em tupi', 'Tupi'),
       (67, 'Silva', 'Selva, mata, em latim', 'Portugal');
INSERT INTO curiosidade (conteudo_id, titulo, curiosidade, categoria, descricao) VALUES
       (49, 'Festival de Parintins', 'O Garantido é vermelho e o Caprichoso é azul.', 'Festa', 'Festival folclórico'),
       (50, 'Círio de Nazaré', 'É uma das maiores procissões católicas do mundo.', 'Festa', 'Procissão em Belém'),
       (51, 'São João de Campina Grande', 'A festa dura o mês de junho inteiro.', 'Festa', 'Festa junina'),
       (52, 'Cavalhadas de Pirenópolis', 'Acontecem na Festa do Divino Espírito Santo.', 'Tradição', 'Encenação equestre'),
       (53, 'Oktoberfest de Blumenau', 'A festa começou em 1984.', 'Festa', 'Festa da imigração alemã'),
       (54, 'Bumba meu boi', 'Foi reconhecido pela UNESCO em 2019.', 'Tradição', 'Folguedo popular'),
       (55, 'Capim dourado', 'O artesanato é feito com fibra de buriti.', 'Tradição', 'Artesanato do Jalapão'),
       (56, 'Geoglifos do Acre', 'Foram feitos por povos antigos há centenas de anos.', 'Arqueologia', 'Sítios arqueológicos');
INSERT INTO artigo_academico (conteudo_id, titulo, resumo, area_pesquisa, arquivo) VALUES
       (68, 'Tropeirismo e a formação do Paraná', 'Rotas de tropas e surgimento de vilas.', 'História do Sul do Brasil', 'artigo-tropeirismo.pdf');
INSERT INTO contribuicao (conteudo_id, usuario_id, admin_id, data_envio, status, data_revisao, motivo_rejeicao, editor_id, data_edicao) VALUES
       (68, 3, 4, '2026-10-15 09:00', 'aprovado', '2026-10-15 18:00', NULL, NULL, NULL);
INSERT INTO recurso_complementar (conteudo_id, docente_id, tipo, titulo, endereco, descricao) VALUES
       (42, NULL, 'mapa externo', 'Teatro Amazonas no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Teatro+Amazonas', NULL),
       (43, NULL, 'mapa externo', 'Jalapão no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Jalapão', NULL),
       (44, NULL, 'mapa externo', 'Chapada dos Veadeiros no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Chapada+dos+Veadeiros', NULL),
       (45, NULL, 'mapa externo', 'Fernando de Noronha no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Fernando+de+Noronha', NULL),
       (46, NULL, 'mapa externo', 'Cristo Redentor no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Cristo+Redentor', NULL),
       (47, NULL, 'mapa externo', 'Cataratas do Iguaçu no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Cataratas+do+Iguaçu', NULL),
       (48, NULL, 'mapa externo', 'Mercado Ver-o-Peso no Google Maps', 'https://www.google.com/maps/search/?api=1&query=Mercado+Ver-o-Peso', NULL);
INSERT INTO atividade_interativa (conteudo_id, docente_id, tipo, titulo, descricao) VALUES
       (49, NULL, 'quiz', 'Quiz das festas do Norte', 'Perguntas sobre festas populares'),
       (42, NULL, 'jogo da memória', 'Memória dos monumentos do Norte', 'Pares de monumentos e cidades'),
       (40, NULL, 'quebra-cabeça', 'Quebra-cabeça do Plano Piloto', 'Monte o mapa de Brasília'),
       (24, 2, 'dinâmica de aula', 'Roda de lendas brasileiras', 'Cada grupo apresenta uma lenda de uma região');
-- Pontuação da estudante no quiz inicial (Quiz do Blue), para o ranking do site.
INSERT INTO tentativa (estudante_id, atividade_id, data_hora, pontuacao) VALUES (1, 3, '2026-10-05 09:30', 80);
-- =====================================================================================
-- SEÇÃO 3: VIEWS (João)
-- Ação: 4 views para consultas complexas (detalhes, movimento, tipos frequentes, perfil).
-- Por quê: Escondem os JOINs e agregações do front-end (PHP) e protegem dados pessoais.
-- Regra usada nas views 2 e 3: "publicado" = conteúdo da equipe (sem contribuição) ou
--       contribuição aprovada (UC-02: o site mostra só registros publicados).
-- =====================================================================================

-- View 1 — Finalidade: lista os conteúdos enviados por colaboradores (RF-16, RF-19, RF-20),
-- com estado, autor e situação na moderação (RF-23).
-- Parâmetros: nenhum.
-- Exemplo: SELECT codigo, autor, situacao FROM vw_conteudos_detalhados WHERE regiao = 'Sul' ORDER BY situacao;
DROP VIEW IF EXISTS vw_conteudos_detalhados;
CREATE VIEW vw_conteudos_detalhados
    (codigo, tipo, resumo, estado, regiao, cadastrado_em, dias_publicado, autor, perfil_autor, situacao) AS
SELECT c.id,
       c.tipo,
       COALESCE(SUBSTRING(c.texto_simplificado, 1, 35), 'Sem resumo'),
       CONCAT(e.nome, ' (', e.sigla, ')'),
       e.regiao,
       DATE_FORMAT(c.data_cadastro, '%d/%m/%Y'),
       DATEDIFF(CURDATE(), c.data_cadastro),
       u.nome,
       u.classificacao,
       CASE ct.status
           WHEN 'aprovado' THEN 'Aprovado'
           WHEN 'pendente' THEN 'Aguardando revisão'
           WHEN 'rascunho' THEN 'Rascunho'
           WHEN 'rejeitado' THEN 'Rejeitado'
       END
FROM usuario AS u
INNER JOIN contribuicao AS ct ON ct.usuario_id = u.id
INNER JOIN conteudo_cultural AS c ON c.id = ct.conteudo_id
INNER JOIN estado AS e ON e.id = c.estado_id;


-- View 2 — Finalidade: conteúdos publicados, acessos e média de avaliações por estado,
-- inclusive os estados sem movimento (RF-01, RF-25).
-- Parâmetros: nenhum.
-- Exemplo: SELECT estado, total_acessos FROM vw_movimento_por_estado ORDER BY total_acessos DESC;
DROP VIEW IF EXISTS vw_movimento_por_estado;
CREATE VIEW vw_movimento_por_estado AS
SELECT e.sigla AS sigla,
       e.nome AS estado,
       e.regiao AS regiao,
       COUNT(DISTINCT c.id) AS total_conteudos,
       COUNT(a.id) AS total_acessos,
       COALESCE((SELECT ROUND(AVG(av.nota), 1)
                 FROM avaliacao AS av
                 INNER JOIN conteudo_cultural AS c2 ON c2.id = av.conteudo_id
                 WHERE c2.estado_id = e.id), 0) AS media_avaliacoes,
       COALESCE(DATE_FORMAT(MAX(c.data_cadastro), '%d/%m/%Y'), 'Sem conteúdo') AS ultimo_cadastro,
       CASE
           WHEN COUNT(DISTINCT c.id) = 0 THEN 'Sem movimento'
           WHEN COUNT(a.id) = 0 THEN 'Sem acessos'
           WHEN COUNT(a.id) < 3 THEN 'Em crescimento'
           ELSE 'Movimentado'
       END AS nivel_movimento
FROM estado AS e
LEFT JOIN conteudo_cultural AS c ON c.estado_id = e.id
                               AND NOT EXISTS (SELECT 1 FROM contribuicao AS x
                                               WHERE x.conteudo_id = c.id AND x.status <> 'aprovado')
LEFT JOIN acesso AS a ON a.conteudo_id = c.id
                     AND a.pagina_visitada = 'Conteúdo'
GROUP BY e.id, e.sigla, e.nome, e.regiao;


-- View 3 — Finalidade: categorias publicadas 2 ou mais vezes nos últimos 12 meses (RF-17, RF-25).
-- Parâmetros: nenhum.
-- Exemplo: SELECT tipo, total_conteudos FROM vw_tipos_culturais_frequentes ORDER BY total_conteudos DESC;
DROP VIEW IF EXISTS vw_tipos_culturais_frequentes;
CREATE VIEW vw_tipos_culturais_frequentes
    (tipo, total_conteudos, qtd_estados, estados, primeiro_cadastro, ultimo_cadastro, frequencia) AS
SELECT UPPER(c.tipo),
       COUNT(c.id),
       COUNT(DISTINCT c.estado_id),
       GROUP_CONCAT(DISTINCT e.sigla ORDER BY e.sigla SEPARATOR ', '),
       DATE_FORMAT(MIN(c.data_cadastro), '%d/%m/%Y'),
       DATE_FORMAT(MAX(c.data_cadastro), '%d/%m/%Y'),
       CASE
           WHEN COUNT(c.id) >= 3 THEN 'Muito frequente'
           ELSE 'Frequente'
       END
FROM conteudo_cultural AS c
INNER JOIN estado AS e ON e.id = c.estado_id
WHERE c.data_cadastro BETWEEN DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND CURDATE()
  AND NOT EXISTS (SELECT 1 FROM contribuicao AS x WHERE x.conteudo_id = c.id AND x.status <> 'aprovado')
GROUP BY c.tipo
HAVING COUNT(c.id) >= 2
ORDER BY COUNT(c.id) DESC, c.tipo ASC;


-- View 4 — Finalidade: perfil público do usuário (RF-22), sem e-mail, senha, telefone e
-- data de nascimento (RNF-04). É a única coisa que o usuário visitante_tche pode ler.
-- Parâmetros: nenhum.
-- Exemplo: SELECT nome_exibicao, selo FROM vw_perfil_publico ORDER BY nome_exibicao;
DROP VIEW IF EXISTS vw_perfil_publico;
CREATE VIEW vw_perfil_publico
    (codigo, nome_exibicao, perfil, contribuicoes_aprovadas, avaliacoes_feitas, selo) AS
SELECT u.id,
       CONCAT(SUBSTRING_INDEX(u.nome, ' ', 1), ' ', LEFT(SUBSTRING_INDEX(u.nome, ' ', -1), 1), '.'),
       u.classificacao,
       COUNT(ct.conteudo_id),
       COALESCE((SELECT COUNT(av.nota) FROM avaliacao AS av WHERE av.usuario_id = u.id), 0),
       CASE
           WHEN COUNT(ct.conteudo_id) >= 2 THEN 'Colaborador destaque'
           WHEN COUNT(ct.conteudo_id) = 1 THEN 'Colaborador'
           ELSE 'Leitor'
       END
FROM usuario AS u
LEFT JOIN contribuicao AS ct ON ct.usuario_id = u.id
                            AND ct.status = 'aprovado'
                            AND ct.data_revisao IS NOT NULL
WHERE u.classificacao IN ('Estudante', 'Docente', 'Pesquisador', 'Público em Geral')
GROUP BY u.id, u.nome, u.classificacao;

-- View de segurança: o usuário visitante_tche só enxerga o perfil público.
DROP USER IF EXISTS 'visitante_tche'@'localhost';
CREATE USER 'visitante_tche'@'localhost' IDENTIFIED BY 'Visitante#2026';
GRANT SELECT ON tche_cultura.vw_perfil_publico TO 'visitante_tche'@'localhost';

-- =====================================================================================
-- SEÇÃO 4: FUNCTIONS (Guilherme)
-- Ação: fn_classificar_atualidade e fn_total_contribuicoes_usuario.
-- Por quê: Regras reutilizáveis que retornam um único valor e podem ser usadas em SELECTs.
-- Convenção: prefixo p_ em parâmetros e v_ em variáveis locais (evita conflito com colunas).
-- =====================================================================================

DELIMITER $$
DROP FUNCTION IF EXISTS fn_classificar_atualidade $$
CREATE FUNCTION fn_classificar_atualidade(p_data DATE)
RETURNS VARCHAR(30)
NOT DETERMINISTIC
NO SQL
BEGIN
    IF p_data IS NULL THEN
        RETURN 'Data não informada';
    ELSEIF p_data > CURDATE() THEN
        RETURN 'Data futura';
    ELSEIF DATEDIFF(CURDATE(), p_data) <= 365 THEN
        RETURN 'Atual';
    ELSE
        RETURN 'Desatualizado';
    END IF;
END $$
DELIMITER ;

DELIMITER $$
DROP FUNCTION IF EXISTS fn_total_contribuicoes_usuario $$
CREATE FUNCTION fn_total_contribuicoes_usuario(p_usuario_id INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*)
    INTO v_total
    FROM contribuicao
    WHERE usuario_id = p_usuario_id;
    RETURN v_total;
END $$
DELIMITER ;

-- =====================================================================================
-- SEÇÃO 5: TRIGGER E PROCEDURES (Eduardo e Carlos)
-- Ação: trigger de nota, procedure transacional de cadastro (UC-16), revisão e edição na
--       moderação (UC-23 / RF-24) e relatório por período (RF-25).
-- Por quê: Garantem as regras de negócio no próprio banco, com COMMIT/ROLLBACK e SIGNAL.
-- =====================================================================================

-- Trigger (RF-06): a nota deve ser de 1 a 5 estrelas.
DELIMITER $$
DROP TRIGGER IF EXISTS trg_valida_nota $$
CREATE TRIGGER trg_valida_nota BEFORE INSERT ON avaliacao FOR EACH ROW
BEGIN
    IF NEW.nota < 1 OR NEW.nota > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A nota deve ser de 1 a 5';
    END IF;
END $$
DELIMITER ;

-- Procedure de cadastro (Eduardo) — UC-16 (Docente) e RF-19/RF-20 (Pesquisador).
-- Grava, numa transação, o conteúdo, a linha da sua categoria (UC-17) e a contribuição.
-- p_situacao: 'pendente' (enviar para moderação) ou 'rascunho' (UC-16, fluxo A5).
-- p_autor: obrigatório só para Literatura (toda obra tem um autor).
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_registrar_contribuicao $$
CREATE PROCEDURE sp_registrar_contribuicao(
    IN p_usuario_id INT,
    IN p_estado_id INT,
    IN p_tipo_conteudo VARCHAR(30),
    IN p_titulo VARCHAR(150),
    IN p_texto_simplificado TEXT,
    IN p_texto_tecnico TEXT,
    IN p_referencias TEXT,
    IN p_autor VARCHAR(100),
    IN p_situacao VARCHAR(20),
    OUT p_novo_conteudo_id INT
)
BEGIN
    DECLARE v_usuario_existe INT DEFAULT 0;
    DECLARE v_estado_existe INT DEFAULT 0;
    DECLARE v_classificacao VARCHAR(20);
    DECLARE v_linha_pesquisa VARCHAR(100);
    DECLARE v_autor_id INT;
    -- RESIGNAL: desfaz a transação e repassa a mensagem original do erro.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT COUNT(*) INTO v_usuario_existe FROM usuario WHERE id = p_usuario_id;
    IF v_usuario_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: Usuário não encontrado.';
    END IF;
    SELECT classificacao INTO v_classificacao FROM usuario WHERE id = p_usuario_id;
    IF v_classificacao NOT IN ('Docente', 'Pesquisador') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: apenas Docentes e Pesquisadores podem enviar contribuições.';
    END IF;
    SELECT COUNT(*) INTO v_estado_existe FROM estado WHERE id = p_estado_id;
    IF v_estado_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: Estado não encontrado.';
    END IF;
    IF p_tipo_conteudo IS NULL OR p_tipo_conteudo = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: O tipo de conteúdo não pode estar vazio.';
    END IF;
    IF p_tipo_conteudo NOT IN ('Culinária', 'Música', 'Literatura', 'Variação Linguística', 'Ponto Turístico',
                               'Acontecimento Histórico', 'Incidência Nominal', 'Curiosidade', 'Artigo Acadêmico') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: categoria de conteúdo inválida.';
    END IF;
    IF p_titulo IS NULL OR p_titulo = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: Preencha os campos obrigatórios para continuar.';
    END IF;
    IF p_situacao IS NULL OR p_situacao NOT IN ('rascunho', 'pendente') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: a situação inicial deve ser rascunho ou pendente.';
    END IF;
    IF p_tipo_conteudo = 'Artigo Acadêmico' AND v_classificacao <> 'Pesquisador' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: apenas Pesquisadores submetem artigos acadêmicos.';
    END IF;
    IF v_classificacao = 'Pesquisador' AND (p_referencias IS NULL OR p_referencias = '') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: o Pesquisador deve informar as referências bibliográficas.';
    END IF;
    IF p_tipo_conteudo = 'Literatura' AND (p_autor IS NULL OR p_autor = '') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: informe o autor da obra literária.';
    END IF;

    START TRANSACTION;
        INSERT INTO conteudo_cultural (tipo, data_cadastro, texto_simplificado, texto_tecnico, referencias, estado_id)
        VALUES (p_tipo_conteudo, CURDATE(), p_texto_simplificado, p_texto_tecnico, p_referencias, p_estado_id);
        SET p_novo_conteudo_id = LAST_INSERT_ID();

        -- UC-17: a categoria classificada define a tabela da especialização.
        IF p_tipo_conteudo = 'Culinária' THEN
            INSERT INTO culinaria (conteudo_id, nome_prato) VALUES (p_novo_conteudo_id, p_titulo);
        ELSEIF p_tipo_conteudo = 'Música' THEN
            INSERT INTO musica (conteudo_id, nome) VALUES (p_novo_conteudo_id, p_titulo);
        ELSEIF p_tipo_conteudo = 'Literatura' THEN
            INSERT IGNORE INTO autor (nome) VALUES (p_autor);
            SELECT id INTO v_autor_id FROM autor WHERE nome = p_autor;
            INSERT INTO literatura (conteudo_id, titulo, autor_id) VALUES (p_novo_conteudo_id, p_titulo, v_autor_id);
        ELSEIF p_tipo_conteudo = 'Variação Linguística' THEN
            INSERT INTO variacao_linguistica (conteudo_id, expressao) VALUES (p_novo_conteudo_id, p_titulo);
        ELSEIF p_tipo_conteudo = 'Ponto Turístico' THEN
            INSERT INTO ponto_turistico (conteudo_id, nome) VALUES (p_novo_conteudo_id, p_titulo);
        ELSEIF p_tipo_conteudo = 'Acontecimento Histórico' THEN
            INSERT INTO acontecimento_historico (conteudo_id, titulo) VALUES (p_novo_conteudo_id, p_titulo);
        ELSEIF p_tipo_conteudo = 'Incidência Nominal' THEN
            INSERT INTO incidencia_nominal (conteudo_id, termo) VALUES (p_novo_conteudo_id, p_titulo);
        ELSEIF p_tipo_conteudo = 'Curiosidade' THEN
            INSERT INTO curiosidade (conteudo_id, titulo) VALUES (p_novo_conteudo_id, p_titulo);
        ELSE
            SELECT linha_pesquisa INTO v_linha_pesquisa FROM pesquisador WHERE usuario_id = p_usuario_id;
            INSERT INTO artigo_academico (conteudo_id, titulo, area_pesquisa)
            VALUES (p_novo_conteudo_id, p_titulo, v_linha_pesquisa);
        END IF;

        INSERT INTO contribuicao (conteudo_id, usuario_id, admin_id, data_envio, status)
        VALUES (p_novo_conteudo_id, p_usuario_id, NULL, NOW(), p_situacao);
    COMMIT;
END $$
DELIMITER ;

-- Procedure de revisão (Carlos) — UC-23 / RF-23: aprovar ou rejeitar um registro pendente.
-- A rejeição exige o motivo (fluxo A5.2). Se outro administrador já moderou, avisa (E6).
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_revisar_contribuicao $$
CREATE PROCEDURE sp_revisar_contribuicao(
    IN p_conteudo_id INT,
    IN p_admin_id INT,
    IN p_decisao VARCHAR(20),
    IN p_motivo VARCHAR(255)
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_admin INT;

    IF p_decisao IS NULL OR p_decisao NOT IN ('aprovado', 'rejeitado') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Decisão inválida. Use aprovado ou rejeitado.';
    END IF;
    IF p_decisao = 'rejeitado' AND (p_motivo IS NULL OR p_motivo = '') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Informe o motivo da rejeição.';
    END IF;
    SELECT usuario_id INTO v_admin FROM administrador WHERE usuario_id = p_admin_id;
    IF v_admin IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuário não possui permissão de administrador.';
    END IF;
    SELECT status INTO v_status FROM contribuicao WHERE conteudo_id = p_conteudo_id;
    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Contribuição não encontrada.';
    END IF;
    IF v_status = 'rascunho' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rascunhos não entram na fila de moderação.';
    END IF;

    UPDATE contribuicao
    SET status = p_decisao,
        admin_id = p_admin_id,
        data_revisao = NOW(),
        motivo_rejeicao = IF(p_decisao = 'rejeitado', p_motivo, NULL)
    WHERE conteudo_id = p_conteudo_id
      AND status = 'pendente';
    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este conteúdo já foi moderado por outro administrador.';
    END IF;
END $$
DELIMITER ;

-- Procedure de edição na moderação (Carlos) — RF-24 / UC-23 fluxo A5.1: o administrador
-- corrige o texto antes de aprovar, e o banco registra quem editou e quando.
-- Parâmetro NULL = mantém o valor atual.
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_editar_conteudo_moderacao $$
CREATE PROCEDURE sp_editar_conteudo_moderacao(
    IN p_conteudo_id INT,
    IN p_admin_id INT,
    IN p_texto_simplificado TEXT,
    IN p_texto_tecnico TEXT,
    IN p_referencias TEXT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_admin INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT usuario_id INTO v_admin FROM administrador WHERE usuario_id = p_admin_id;
    IF v_admin IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuário não possui permissão de administrador.';
    END IF;
    SELECT status INTO v_status FROM contribuicao WHERE conteudo_id = p_conteudo_id;
    IF v_status IS NULL OR v_status <> 'pendente' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Só é possível editar conteúdos pendentes de moderação.';
    END IF;

    START TRANSACTION;
        UPDATE conteudo_cultural
        SET texto_simplificado = COALESCE(p_texto_simplificado, texto_simplificado),
            texto_tecnico = COALESCE(p_texto_tecnico, texto_tecnico),
            referencias = COALESCE(p_referencias, referencias)
        WHERE id = p_conteudo_id;
        UPDATE contribuicao
        SET editor_id = p_admin_id,
            data_edicao = NOW()
        WHERE conteudo_id = p_conteudo_id;
    COMMIT;
END $$
DELIMITER ;

-- Procedure de relatório por período (Carlos) — RF-25.
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_relatorio_conteudos_periodo $$
CREATE PROCEDURE sp_relatorio_conteudos_periodo(
    IN p_data_inicio VARCHAR(10),
    IN p_data_fim VARCHAR(10)
)
BEGIN
    DECLARE v_inicio DATE;
    DECLARE v_fim DATE;
    DECLARE v_data_invalida TINYINT DEFAULT 0;
    -- No MySQL 8 (modo estrito), STR_TO_DATE com data inexistente gera o erro 1411
    -- em vez de retornar NULL; o handler transforma isso na mensagem amigável abaixo.
    DECLARE CONTINUE HANDLER FOR 1411 SET v_data_invalida = 1;

    SET v_inicio = STR_TO_DATE(p_data_inicio, '%Y-%m-%d');
    SET v_fim = STR_TO_DATE(p_data_fim, '%Y-%m-%d');
    IF v_data_invalida = 1 OR v_inicio IS NULL OR v_fim IS NULL
       OR DATE_FORMAT(v_inicio, '%Y-%m-%d') <> p_data_inicio
       OR DATE_FORMAT(v_fim, '%Y-%m-%d') <> p_data_fim THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data inválida. Use o formato AAAA-MM-DD.';
    END IF;
    IF v_inicio > v_fim THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A data inicial não pode ser maior que a data final.';
    END IF;

    SELECT c.id AS codigo,
           c.tipo AS tipo_conteudo,
           c.data_cadastro,
           e.nome AS estado
    FROM conteudo_cultural AS c
    INNER JOIN estado AS e ON e.id = c.estado_id
    WHERE c.data_cadastro BETWEEN v_inicio AND v_fim
    ORDER BY c.data_cadastro ASC, c.id ASC;
END $$
DELIMITER ;

-- =====================================================================================
-- SEÇÃO 6: TESTES DOS OBJETOS PROGRAMÁVEIS
-- Ação: Chamadas que demonstram Views, Functions e Procedures funcionando.
-- Por quê: Evidência de execução para o Relatório de Evidências.
-- =====================================================================================

-- Views (João)
SELECT codigo, tipo, autor, situacao FROM vw_conteudos_detalhados ORDER BY codigo;
SELECT estado, total_conteudos, total_acessos, media_avaliacoes, nivel_movimento
FROM vw_movimento_por_estado ORDER BY total_conteudos DESC, estado LIMIT 6;
SELECT tipo, total_conteudos, qtd_estados, estados, frequencia FROM vw_tipos_culturais_frequentes;
SELECT nome_exibicao, perfil, contribuicoes_aprovadas, avaliacoes_feitas, selo FROM vw_perfil_publico ORDER BY nome_exibicao;

-- Functions (Guilherme)
SELECT id, tipo, data_cadastro, fn_classificar_atualidade(data_cadastro) AS atualidade FROM conteudo_cultural ORDER BY id;
SELECT fn_classificar_atualidade(NULL) AS resultado_nulo,
       fn_classificar_atualidade('2024-01-01') AS resultado_antigo,
       fn_classificar_atualidade('2027-01-01') AS resultado_futuro;
SELECT u.id, u.nome, fn_total_contribuicoes_usuario(u.id) AS total_contribuicoes FROM usuario AS u;

-- Procedure de cadastro (Eduardo): o Docente envia uma curiosidade para moderação.
SET @id_gerado = 0;
CALL sp_registrar_contribuicao(2, 1, 'Curiosidade', 'Capital ecológica', 'Curitiba é a capital ecológica do Brasil.',
                               NULL, NULL, NULL, 'pendente', @id_gerado);
SELECT @id_gerado AS 'ID do Novo Conteúdo Retornado via OUT';
SELECT c.id, c.tipo, cu.titulo, ct.status
FROM conteudo_cultural AS c
INNER JOIN curiosidade AS cu ON cu.conteudo_id = c.id
INNER JOIN contribuicao AS ct ON ct.conteudo_id = c.id
WHERE c.id = @id_gerado;

-- Procedures de relatório e revisão (Carlos)
CALL sp_relatorio_conteudos_periodo('2026-09-01', '2026-09-30');
CALL sp_revisar_contribuicao(10, 4, 'aprovado', NULL);
SELECT conteudo_id, status, admin_id, data_revisao IS NOT NULL AS revisado FROM contribuicao WHERE conteudo_id = 10;

-- =====================================================================================
-- SEÇÃO 7: UMA CONSULTA POR REQUISITO (Requisitos v3.2)
-- Ação: Para cada RF da planilha, o comando SQL que o atende (ou a indicação de que ele
--       é só de interface). O texto de cada requisito é copiado da planilha.
-- Por quê: Rastreabilidade planilha → banco → código (aba RastreabilidadeBD da planilha).
-- =====================================================================================

-- RF-01: O Sistema deverá exibir o Mapa do Brasil com todos os estados e, ao selecionar um estado, apresentar os detalhes da sua região.
SELECT regiao, COUNT(*) AS estados, GROUP_CONCAT(sigla ORDER BY sigla SEPARATOR ', ') AS siglas
FROM estado GROUP BY regiao ORDER BY regiao;
SELECT sigla, estado, regiao, total_conteudos, nivel_movimento FROM vw_movimento_por_estado WHERE sigla = 'PR';

-- RF-02: O Sistema deverá disponibilizar, para cada estado, o conteúdo cultural nas categorias culinária, música, literatura, variações linguísticas, pontos turísticos, acontecimentos históricos, incidência nominal e curiosidades, exibindo a letra da música e a imagem do ponto turístico quando disponíveis.
SELECT c.id, c.tipo, c.texto_simplificado, m.letra, p.imagem
FROM conteudo_cultural AS c
LEFT JOIN musica AS m ON m.conteudo_id = c.id
LEFT JOIN ponto_turistico AS p ON p.conteudo_id = c.id
WHERE c.estado_id IN (1, 2)
  AND NOT EXISTS (SELECT 1 FROM contribuicao AS x WHERE x.conteudo_id = c.id AND x.status <> 'aprovado')
ORDER BY c.estado_id, c.tipo;

-- RF-03: O Sistema deverá permitir a busca de informações culturais por palavra-chave, abrangendo estados, nomes famosos, termos, obras, expressões regionais e curiosidades.
SET @termo = '%chimarrão%';
SELECT 'Estado' AS categoria, e.nome AS resultado, e.sigla AS uf FROM estado AS e WHERE e.nome LIKE @termo
UNION ALL SELECT 'Nome famoso', a.nome, e.sigla FROM autor AS a INNER JOIN literatura AS l ON l.autor_id = a.id
          INNER JOIN conteudo_cultural AS c ON c.id = l.conteudo_id INNER JOIN estado AS e ON e.id = c.estado_id WHERE a.nome LIKE @termo
UNION ALL SELECT 'Termo', i.termo, e.sigla FROM incidencia_nominal AS i INNER JOIN conteudo_cultural AS c ON c.id = i.conteudo_id
          INNER JOIN estado AS e ON e.id = c.estado_id WHERE i.termo LIKE @termo
UNION ALL SELECT 'Obra', l.titulo, e.sigla FROM literatura AS l INNER JOIN conteudo_cultural AS c ON c.id = l.conteudo_id
          INNER JOIN estado AS e ON e.id = c.estado_id WHERE l.titulo LIKE @termo
UNION ALL SELECT 'Expressão regional', v.expressao, e.sigla FROM variacao_linguistica AS v INNER JOIN conteudo_cultural AS c ON c.id = v.conteudo_id
          INNER JOIN estado AS e ON e.id = c.estado_id WHERE v.expressao LIKE @termo
UNION ALL SELECT 'Curiosidade', cu.titulo, e.sigla FROM curiosidade AS cu INNER JOIN conteudo_cultural AS c ON c.id = cu.conteudo_id
          INNER JOIN estado AS e ON e.id = c.estado_id WHERE (cu.titulo LIKE @termo OR cu.curiosidade LIKE @termo OR c.texto_simplificado LIKE @termo);
-- UC-03, passo 9: registrar o termo pesquisado.
INSERT INTO acesso (usuario_id, data_hora, pagina_visitada, conteudo_id, termo_pesquisado)
VALUES (NULL, NOW(), 'Pesquisa', NULL, 'chimarrão');

-- RF-04: O Sistema deverá disponibilizar um menu com as opções de pesquisa, ajuda, login e "explore mais".
-- Requisito de interface (HTML/CSS/JS): não há consulta ao banco.

-- RF-05: O Sistema deverá permitir que o usuário responda, a partir do menu, um quiz inicial de apresentação do site.
SELECT id, titulo, descricao FROM atividade_interativa WHERE tipo = 'quiz inicial';

-- RF-06: O Sistema deverá permitir que o usuário avalie os conteúdos e artigos com uma nota de 1 a 5 estrelas.
INSERT INTO avaliacao (usuario_id, conteudo_id, nota, data_avaliacao) VALUES (3, 9, 4, NOW());
SELECT conteudo_id, ROUND(AVG(nota), 1) AS media, COUNT(*) AS avaliacoes FROM avaliacao GROUP BY conteudo_id ORDER BY conteudo_id;

-- RF-07: O Sistema deverá permitir que o usuário, ao avaliar um conteúdo, envie opcionalmente um feedback ou correção para análise da equipe.
INSERT INTO feedback (usuario_id, conteudo_id, tipo, texto, data_envio, status)
VALUES (3, 9, 'comentário', 'Vale citar a erva-mate do Paraná.', NOW(), 'pendente');

-- RF-08: O Sistema deverá oferecer atendimento de ajuda por agente de IA ou por atendente humano.
SELECT a.data_hora, u.nome, a.canal, COALESCE(g.nome, 'Equipe de suporte') AS atendente, a.topico,
       COALESCE(a.resposta, '(aguardando resposta)') AS resposta, a.data_resposta
FROM atendimento AS a
INNER JOIN usuario AS u ON u.id = a.usuario_id
LEFT JOIN agente_ia AS g ON g.id = a.agente_id
ORDER BY a.data_hora;

-- RF-09: O Sistema deverá permitir que o usuário autenticado salve conteúdos, artigos e curiosidades na sua aba de "Favoritos".
SELECT f.data_inclusao, c.tipo, c.texto_simplificado
FROM favorito AS f INNER JOIN conteudo_cultural AS c ON c.id = f.conteudo_id
WHERE f.usuario_id = 1 ORDER BY f.data_inclusao DESC;

-- RF-10: O Sistema deverá integrar-se a plataformas externas de mapas (Google Maps e Google Earth) para localizar museus e centros culturais reais.
SELECT titulo, endereco FROM recurso_complementar WHERE tipo = 'mapa externo';

-- RF-11: O Sistema deverá disponibilizar ao estudante atividades sobre a cultura dos estados, de acordo com o conteúdo escolhido por ele.
SELECT a.id, a.tipo, a.titulo, t.pontuacao
FROM atividade_interativa AS a
LEFT JOIN tentativa AS t ON t.atividade_id = a.id AND t.estudante_id = 1
WHERE a.conteudo_id = 9;

-- RF-12: O Sistema deverá permitir que o estudante acesse os materiais pedagógicos publicados, incluindo os anexados pelo docente.
SELECT r.tipo, r.titulo, r.endereco, COALESCE(u.nome, 'Equipe Tchê & Cultura') AS anexado_por
FROM recurso_complementar AS r
LEFT JOIN usuario AS u ON u.id = r.docente_id
WHERE r.conteudo_id = 7 AND r.tipo IN ('material pedagógico', 'plano de aula');

-- RF-13: O Sistema deverá disponibilizar jogos interativos ligados ao material pedagógico acessado, incluindo quizzes, jogos de memória e quebra-cabeças com monumentos e símbolos estaduais.
SELECT a.tipo, a.titulo, e.sigla
FROM atividade_interativa AS a
INNER JOIN conteudo_cultural AS c ON c.id = a.conteudo_id
INNER JOIN estado AS e ON e.id = c.estado_id
WHERE a.tipo IN ('quiz', 'jogo da memória', 'quebra-cabeça');

-- RF-14: O Sistema deverá exibir, junto ao material pedagógico acessado, as vídeo-aulas recomendadas para aquele tema de estudo, quando houver.
SELECT titulo, endereco FROM recurso_complementar WHERE conteudo_id = 7 AND tipo = 'vídeo-aula';

-- RF-15: O Sistema deverá permitir que o docente crie atividades e dinâmicas sobre o conteúdo cultural, para uso em aula.
INSERT INTO atividade_interativa (conteudo_id, docente_id, tipo, titulo, descricao)
VALUES (8, 2, 'quebra-cabeça', 'Quebra-cabeça da bandeira farroupilha', 'Criado pelo docente');
SELECT a.titulo, a.tipo, u.nome AS docente
FROM atividade_interativa AS a INNER JOIN usuario AS u ON u.id = a.docente_id;

-- RF-16: O Sistema deverá permitir que o docente insira conteúdo cultural nos estados cadastrados.
CALL sp_registrar_contribuicao(2, 3, 'Ponto Turístico', 'Pelourinho', 'Centro histórico de Salvador.',
                               NULL, NULL, NULL, 'pendente', @id_rf16);
SELECT @id_rf16 AS conteudo_enviado_pelo_docente;

-- RF-17: O Sistema deverá exigir, a cada inserção de conteúdo pelo docente, a classificação da categoria à qual o conteúdo pertence.
-- Garantido pela coluna conteudo_cultural.tipo (NOT NULL + CHECK com as 9 categorias) e pela
-- sp_registrar_contribuicao, que grava a linha da categoria. Conferência:
SELECT tipo, COUNT(*) AS conteudos FROM conteudo_cultural GROUP BY tipo ORDER BY tipo;

-- RF-18: O Sistema deverá permitir que o docente anexe materiais pedagógicos ao conteúdo cultural.
INSERT INTO recurso_complementar (conteudo_id, docente_id, tipo, titulo, endereco, descricao)
VALUES (8, 2, 'material pedagógico', 'Mapa da Revolução Farroupilha', 'https://exemplo.com/farroupilha.pdf', 'Anexado pelo docente');

-- RF-19: O Sistema deverá permitir que o pesquisador insira informações culturais nos estados cadastrados, com indicação das referências bibliográficas utilizadas.
CALL sp_registrar_contribuicao(3, 2, 'Acontecimento Histórico', 'Guerra dos Farrapos: causas econômicas',
                               'Por que a guerra começou.', 'Disputa pelo imposto do charque.',
                               'Pesavento, S. A Revolução Farroupilha.', NULL, 'pendente', @id_rf19);

-- RF-20: O Sistema deverá permitir que o pesquisador submeta artigos e trabalhos acadêmicos para publicação no site.
SELECT c.id, ar.titulo, ar.area_pesquisa, ct.status
FROM artigo_academico AS ar
INNER JOIN conteudo_cultural AS c ON c.id = ar.conteudo_id
INNER JOIN contribuicao AS ct ON ct.conteudo_id = c.id;

-- RF-21: O Sistema deverá permitir que o pesquisador acesse o conteúdo voltado para a sua linha de pesquisa, com terminologia técnica e referências bibliográficas.
SELECT c.id, c.tipo, c.texto_tecnico, c.referencias
FROM pesquisador AS p
INNER JOIN artigo_academico AS ar ON ar.area_pesquisa = p.linha_pesquisa
INNER JOIN conteudo_cultural AS c ON c.id = ar.conteudo_id
WHERE p.usuario_id = 3
UNION
SELECT c.id, c.tipo, c.texto_tecnico, c.referencias
FROM conteudo_cultural AS c
WHERE c.tipo = 'Acontecimento Histórico' AND c.texto_tecnico IS NOT NULL;

-- RF-22: O Sistema deverá classificar o usuário autenticado como estudante, docente ou pesquisador e, na ausência dessa classificação, mantê-lo como público em geral.
SELECT u.id, u.nome, u.classificacao,
       CASE
           WHEN es.usuario_id IS NOT NULL THEN 'estudante'
           WHEN d.usuario_id IS NOT NULL THEN 'docente'
           WHEN p.usuario_id IS NOT NULL THEN 'pesquisador'
           WHEN ad.usuario_id IS NOT NULL THEN 'administrador'
           ELSE 'público em geral'
       END AS perfil_de_acesso
FROM usuario AS u
LEFT JOIN estudante AS es ON es.usuario_id = u.id
LEFT JOIN docente AS d ON d.usuario_id = u.id
LEFT JOIN pesquisador AS p ON p.usuario_id = u.id
LEFT JOIN administrador AS ad ON ad.usuario_id = u.id;

-- RF-23: O Sistema deverá disponibilizar ao administrador a fila de conteúdos e artigos submetidos, permitindo aprová-los ou rejeitá-los antes da publicação.
SELECT ct.conteudo_id, u.nome AS autor, c.tipo, ct.data_envio
FROM contribuicao AS ct
INNER JOIN usuario AS u ON u.id = ct.usuario_id
INNER JOIN conteudo_cultural AS c ON c.id = ct.conteudo_id
WHERE ct.status = 'pendente'
ORDER BY ct.data_envio;
CALL sp_revisar_contribuicao(@id_rf16, 4, 'rejeitado', 'Texto curto demais; detalhar a história do local.');

-- RF-24: O Sistema deverá permitir que o administrador edite o conteúdo submetido durante a moderação, antes de aprová-lo.
CALL sp_editar_conteudo_moderacao(11, 4, NULL, NULL, 'Machado, P. Lideranças do Contestado. Campinas: Unicamp, 2004.');
CALL sp_revisar_contribuicao(11, 4, 'aprovado', NULL);
SELECT conteudo_id, status, editor_id, data_edicao IS NOT NULL AS editado FROM contribuicao WHERE conteudo_id = 11;

-- RF-25: O Sistema deverá fornecer ao administrador relatórios e dados estatísticos de acesso, temas mais pesquisados e engajamento.
SELECT termo_pesquisado, COUNT(*) AS vezes FROM acesso
WHERE pagina_visitada = 'Pesquisa' GROUP BY termo_pesquisado ORDER BY vezes DESC;
SELECT c.tipo AS tema, COUNT(*) AS acessos FROM acesso AS a
INNER JOIN conteudo_cultural AS c ON c.id = a.conteudo_id GROUP BY c.tipo ORDER BY acessos DESC;
SET lc_time_names = 'pt_BR';
SELECT DAYNAME(data_hora) AS dia_da_semana, COUNT(*) AS acessos FROM acesso GROUP BY DAYNAME(data_hora) ORDER BY acessos DESC;
SELECT (SELECT COUNT(*) FROM avaliacao) AS avaliacoes, (SELECT COUNT(*) FROM favorito) AS favoritos,
       (SELECT COUNT(*) FROM feedback) AS feedbacks, (SELECT COUNT(*) FROM tentativa) AS atividades_realizadas;

-- RF-26: O Sistema deverá disponibilizar ao administrador os feedbacks e as correções enviados pelos usuários, para análise da equipe.
SELECT f.id, f.tipo, u.nome, c.tipo AS conteudo, f.texto, f.status
FROM feedback AS f
INNER JOIN usuario AS u ON u.id = f.usuario_id
INNER JOIN conteudo_cultural AS c ON c.id = f.conteudo_id
WHERE f.status = 'pendente' ORDER BY f.data_envio;

-- RNF-04: O Sistema deverá armazenar as senhas dos usuários de forma criptografada.
-- Medição da especificação: 100% das senhas em hash bcrypt com custo 10 ou mais.
SELECT COUNT(*) AS usuarios,
       SUM(senha REGEXP '^\\$2[aby]\\$(1[0-9]|[2-3][0-9])\\$.{53}$') AS senhas_bcrypt_custo_10_ou_mais
FROM usuario;

-- RNF-01, RNF-02, RNF-03 e RNF-05: requisitos de interface e desempenho (front-end e
-- testes com JMeter/WAVE); não há consulta ao banco.

-- =====================================================================================
-- CASOS-LIMITE (devem dar ERRO). Rode um por vez, depois do script inteiro, e tire o
-- print da mensagem para o Relatório de Evidências.
-- =====================================================================================
-- UPDATE vw_tipos_culturais_frequentes SET total_conteudos = 10 WHERE tipo = 'CULINÁRIA';   -- Erro 1288
-- INSERT INTO vw_tipos_culturais_frequentes (tipo, total_conteudos) VALUES ('MÚSICA', 1);   -- Erro 1471
-- CALL sp_registrar_contribuicao(999, 1, 'Curiosidade', 'x', 'x', NULL, NULL, NULL, 'pendente', @id);   -- Usuário não encontrado
-- CALL sp_registrar_contribuicao(1, 1, 'Curiosidade', 'x', 'x', NULL, NULL, NULL, 'pendente', @id);     -- Estudante não contribui
-- CALL sp_registrar_contribuicao(2, 999, 'Curiosidade', 'x', 'x', NULL, NULL, NULL, 'pendente', @id);   -- Estado não encontrado
-- CALL sp_registrar_contribuicao(2, 1, 'Dança', 'x', 'x', NULL, NULL, NULL, 'pendente', @id);           -- Categoria inválida
-- CALL sp_registrar_contribuicao(2, 1, 'Curiosidade', '', 'x', NULL, NULL, NULL, 'pendente', @id);      -- Campos obrigatórios
-- CALL sp_registrar_contribuicao(2, 1, 'Artigo Acadêmico', 'x', 'x', NULL, NULL, NULL, 'pendente', @id); -- Só pesquisador
-- CALL sp_registrar_contribuicao(3, 1, 'Curiosidade', 'x', 'x', NULL, NULL, NULL, 'pendente', @id);     -- Sem referências
-- CALL sp_registrar_contribuicao(2, 1, 'Literatura', 'x', 'x', NULL, NULL, NULL, 'pendente', @id);      -- Sem autor
-- CALL sp_revisar_contribuicao(10, 4, 'talvez', NULL);                     -- Decisão inválida
-- CALL sp_revisar_contribuicao(13, 4, 'rejeitado', NULL);                  -- Sem motivo
-- CALL sp_revisar_contribuicao(7, 4, 'aprovado', NULL);                    -- Já moderado (UC-23 E6)
-- CALL sp_revisar_contribuicao(12, 4, 'aprovado', NULL);                   -- Rascunho
-- CALL sp_revisar_contribuicao(10, 1, 'aprovado', NULL);                   -- Não é administrador
-- CALL sp_editar_conteudo_moderacao(7, 4, 'x', NULL, NULL);                -- Só edita pendente
-- CALL sp_relatorio_conteudos_periodo('2026-09-30', '2026-09-01');         -- Início maior que fim
-- CALL sp_relatorio_conteudos_periodo('2026-02-30', '2026-03-01');         -- Data inexistente
-- CALL sp_relatorio_conteudos_periodo('01/09/2026', '2026-10-01');         -- Formato errado
-- INSERT INTO avaliacao VALUES (2, 9, 7, NOW());                           -- Nota fora de 1 a 5 (trigger)
-- INSERT INTO feedback (usuario_id, conteudo_id, tipo, texto, data_envio, status)
--     VALUES (2, 1, 'correção', 'x', NOW(), 'pendente');                   -- Feedback sem avaliação (RF-07)
-- INSERT INTO atendimento (usuario_id, canal, agente_id, data_hora) VALUES (1, 'agente de IA', NULL, NOW());  -- Canal sem agente
