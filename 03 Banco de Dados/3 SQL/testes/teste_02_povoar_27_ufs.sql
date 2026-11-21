-- Dados de TESTE do RNF-03 (desempenho): completa as 27 UFs com um registro de culinária cada,
-- nas UFs que ainda não têm conteúdo publicado. Prefixo "[TESTE]"; removidos por teste_99_remover.sql.
USE tche_cultura;
SET NAMES utf8mb4;

INSERT INTO conteudo_cultural (tipo, data_cadastro, texto_simplificado, estado_id)
SELECT 'Culinária', CURDATE(), CONCAT('Registro de teste de desempenho para ', e.nome, '.'), e.id
FROM estado e
WHERE NOT EXISTS (SELECT 1 FROM conteudo_cultural c WHERE c.estado_id = e.id);

INSERT INTO culinaria (conteudo_id, nome_prato, descricao)
SELECT c.id, CONCAT('[TESTE] Prato de ', e.nome), 'Registro de teste'
FROM conteudo_cultural c
JOIN estado e ON e.id = c.estado_id
WHERE c.texto_simplificado LIKE 'Registro de teste de desempenho para %'
  AND NOT EXISTS (SELECT 1 FROM culinaria x WHERE x.conteudo_id = c.id);
