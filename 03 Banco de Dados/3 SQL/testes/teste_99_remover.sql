-- Remove todos os registros de teste criados por teste_01 e teste_02 (e as buscas/acessos gerados nos testes).
USE tche_cultura;
DELETE FROM acesso WHERE conteudo_id IN (SELECT conteudo_id FROM (
    SELECT conteudo_id FROM musica WHERE nome LIKE '[TESTE]%'
    UNION SELECT conteudo_id FROM culinaria WHERE nome_prato LIKE '[TESTE]%') t);
DELETE FROM musica WHERE nome LIKE '[TESTE]%';
DELETE FROM culinaria WHERE nome_prato LIKE '[TESTE]%';
DELETE FROM conteudo_cultural
 WHERE texto_simplificado LIKE 'Registro de teste%'
   AND id NOT IN (SELECT conteudo_id FROM musica UNION SELECT conteudo_id FROM culinaria
                  UNION SELECT conteudo_id FROM literatura UNION SELECT conteudo_id FROM ponto_turistico);
