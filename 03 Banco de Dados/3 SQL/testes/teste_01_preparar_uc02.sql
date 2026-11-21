-- Dados de TESTE do UC-02 (CT-CONT-002 e CT-CONT-004). NÃO fazem parte da base a ser publicada.
-- Todos os registros têm o prefixo "[TESTE]" e são removidos por teste_99_remover.sql.
USE tche_cultura;
SET NAMES utf8mb4;
SET @pr = (SELECT id FROM estado WHERE sigla = 'PR');

INSERT INTO conteudo_cultural (tipo, data_cadastro, texto_simplificado, estado_id)
VALUES ('Música', CURDATE(), 'Registro de teste: música do Paraná com a letra cadastrada.', @pr);
INSERT INTO musica (conteudo_id, nome, artista, genero_musical, letra)
VALUES (LAST_INSERT_ID(), '[TESTE] Cantiga com letra', 'Autor de teste', 'Folclórica',
        'Letra de teste, escrita só para verificar a exibição da letra no UC-02.');

INSERT INTO conteudo_cultural (tipo, data_cadastro, texto_simplificado, estado_id)
VALUES ('Música', CURDATE(), 'Registro de teste: música do Paraná sem letra cadastrada.', @pr);
INSERT INTO musica (conteudo_id, nome, artista, genero_musical, letra)
VALUES (LAST_INSERT_ID(), '[TESTE] Cantiga sem letra', 'Autor de teste', 'Folclórica', NULL);
