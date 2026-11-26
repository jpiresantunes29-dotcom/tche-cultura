-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: tche_cultura
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.5

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `tche_cultura`
--

/*!40000 DROP DATABASE IF EXISTS `tche_cultura`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `tche_cultura` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `tche_cultura`;

--
-- Table structure for table `acesso`
--

DROP TABLE IF EXISTS `acesso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acesso` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `data_hora` datetime NOT NULL,
  `pagina_visitada` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conteudo_id` int DEFAULT NULL,
  `termo_pesquisado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `conteudo_id` (`conteudo_id`),
  CONSTRAINT `acesso_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `acesso_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`),
  CONSTRAINT `ck_acesso_pesquisa` CHECK (((`pagina_visitada` <> _utf8mb4'Pesquisa') or (`termo_pesquisado` is not null)))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acesso`
--

LOCK TABLES `acesso` WRITE;
/*!40000 ALTER TABLE `acesso` DISABLE KEYS */;
INSERT INTO `acesso` VALUES (1,1,'2026-10-01 09:00:00','Conteúdo',7,NULL),(2,2,'2026-10-01 14:00:00','Conteúdo',7,NULL),(3,3,'2026-10-02 19:00:00','Conteúdo',9,NULL),(4,3,'2026-10-03 14:50:00','Conteúdo',2,NULL),(5,5,'2026-10-02 20:00:00','Mapa',NULL,NULL),(6,5,'2026-10-02 20:05:00','Conteúdo',7,NULL),(7,NULL,'2026-10-04 10:00:00','Pesquisa',NULL,'chimarrão'),(8,1,'2026-10-04 10:05:00','Pesquisa',NULL,'barreado'),(9,NULL,'2026-10-04 11:00:00','Pesquisa',NULL,'chimarrão'),(10,NULL,'2026-10-06 15:00:00','Conteúdo',14,NULL);
/*!40000 ALTER TABLE `acesso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acontecimento_estado`
--

DROP TABLE IF EXISTS `acontecimento_estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acontecimento_estado` (
  `acontecimento_id` int NOT NULL,
  `estado_id` int NOT NULL,
  PRIMARY KEY (`acontecimento_id`,`estado_id`),
  KEY `estado_id` (`estado_id`),
  CONSTRAINT `acontecimento_estado_ibfk_1` FOREIGN KEY (`acontecimento_id`) REFERENCES `acontecimento_historico` (`conteudo_id`),
  CONSTRAINT `acontecimento_estado_ibfk_2` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acontecimento_estado`
--

LOCK TABLES `acontecimento_estado` WRITE;
/*!40000 ALTER TABLE `acontecimento_estado` DISABLE KEYS */;
INSERT INTO `acontecimento_estado` VALUES (7,4),(8,4),(37,8),(40,12),(39,21);
/*!40000 ALTER TABLE `acontecimento_estado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acontecimento_historico`
--

DROP TABLE IF EXISTS `acontecimento_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acontecimento_historico` (
  `conteudo_id` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo_historico` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `acontecimento_historico_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acontecimento_historico`
--

LOCK TABLES `acontecimento_historico` WRITE;
/*!40000 ALTER TABLE `acontecimento_historico` DISABLE KEYS */;
INSERT INTO `acontecimento_historico` VALUES (7,'Guerra do Contestado','República Velha','Paraná e Santa Catarina','Conflito pela terra'),(8,'Revolução Farroupilha','Império','Rio Grande do Sul','Guerra regional'),(37,'Cabanagem','Período Regencial','Belém e interior do Grão-Pará','Revolta popular de 1835 a 1840'),(38,'Guerra de Canudos','República Velha','Sertão da Bahia','Conflito de 1896 a 1897'),(39,'Inconfidência Mineira','Brasil Colônia','Vila Rica (Ouro Preto)','Movimento de 1789'),(40,'Construção de Brasília','República','Planalto Central','Inaugurada em 21 de abril de 1960'),(41,'Abolição no Ceará','Império','Província do Ceará','Abolição de 1884');
/*!40000 ALTER TABLE `acontecimento_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `administrador`
--

DROP TABLE IF EXISTS `administrador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `administrador` (
  `usuario_id` int NOT NULL,
  `area_responsabilidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `administrador_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administrador`
--

LOCK TABLES `administrador` WRITE;
/*!40000 ALTER TABLE `administrador` DISABLE KEYS */;
INSERT INTO `administrador` VALUES (4,'Moderação','2026-01-01');
/*!40000 ALTER TABLE `administrador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agente_ia`
--

DROP TABLE IF EXISTS `agente_ia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agente_ia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `funcao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_conhecimento` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agente_ia`
--

LOCK TABLES `agente_ia` WRITE;
/*!40000 ALTER TABLE `agente_ia` DISABLE KEYS */;
INSERT INTO `agente_ia` VALUES (1,'Tchê Bot','Responder dúvidas sobre o conteúdo cultural','Conteúdos culturais dos estados brasileiros');
/*!40000 ALTER TABLE `agente_ia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artigo_academico`
--

DROP TABLE IF EXISTS `artigo_academico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artigo_academico` (
  `conteudo_id` int NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resumo` text COLLATE utf8mb4_unicode_ci,
  `area_pesquisa` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `artigo_academico_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artigo_academico`
--

LOCK TABLES `artigo_academico` WRITE;
/*!40000 ALTER TABLE `artigo_academico` DISABLE KEYS */;
INSERT INTO `artigo_academico` VALUES (11,'Memória oral da Guerra do Contestado','Entrevistas com descendentes de caboclos.','História do Sul do Brasil','artigo-contestado.pdf'),(68,'Tropeirismo e a formação do Paraná','Rotas de tropas e surgimento de vilas.','História do Sul do Brasil','artigo-tropeirismo.pdf');
/*!40000 ALTER TABLE `artigo_academico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atendimento`
--

DROP TABLE IF EXISTS `atendimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `canal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agente_id` int DEFAULT NULL,
  `data_hora` datetime NOT NULL,
  `topico` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resposta` text COLLATE utf8mb4_unicode_ci,
  `data_resposta` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `agente_id` (`agente_id`),
  CONSTRAINT `atendimento_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `atendimento_ibfk_2` FOREIGN KEY (`agente_id`) REFERENCES `agente_ia` (`id`),
  CONSTRAINT `ck_atendimento_canal` CHECK ((((`canal` = _utf8mb4'agente de IA') and (`agente_id` is not null)) or ((`canal` = _utf8mb4'atendente humano') and (`agente_id` is null)))),
  CONSTRAINT `ck_atendimento_resposta` CHECK ((((`resposta` is null) and (`data_resposta` is null)) or ((`resposta` is not null) and (`data_resposta` is not null))))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atendimento`
--

LOCK TABLES `atendimento` WRITE;
/*!40000 ALTER TABLE `atendimento` DISABLE KEYS */;
INSERT INTO `atendimento` VALUES (1,1,'agente de IA',1,'2026-10-05 11:00:00','Culinária do Paraná','Olá! Sou o Tchê Bot. Encontrei no acervo: Barreado (Culinária Regional) — prato típico do litoral do Paraná.','2026-10-05 11:00:00'),(2,5,'atendente humano',NULL,'2026-10-06 16:00:00','Dúvida sobre o cadastro',NULL,NULL);
/*!40000 ALTER TABLE `atendimento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atividade_interativa`
--

DROP TABLE IF EXISTS `atividade_interativa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atividade_interativa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conteudo_id` int DEFAULT NULL,
  `docente_id` int DEFAULT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conteudo_id` (`conteudo_id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `atividade_interativa_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`),
  CONSTRAINT `atividade_interativa_ibfk_2` FOREIGN KEY (`docente_id`) REFERENCES `docente` (`usuario_id`),
  CONSTRAINT `ck_atividade_conteudo` CHECK (((`tipo` = _utf8mb4'quiz inicial') or (`conteudo_id` is not null))),
  CONSTRAINT `ck_atividade_tipo` CHECK ((`tipo` in (_utf8mb4'quiz inicial',_utf8mb4'quiz',_utf8mb4'jogo da memória',_utf8mb4'quebra-cabeça',_utf8mb4'dinâmica de aula')))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atividade_interativa`
--

LOCK TABLES `atividade_interativa` WRITE;
/*!40000 ALTER TABLE `atividade_interativa` DISABLE KEYS */;
INSERT INTO `atividade_interativa` VALUES (1,4,NULL,'jogo da memória','Memória dos monumentos do Paraná','Pares de monumentos e símbolos do PR'),(2,9,NULL,'quiz','Quiz de curiosidades do Paraná',NULL),(3,NULL,NULL,'quiz inicial','Quiz do Blue','Quiz de apresentação do Tchê & Cultura com o mascote Blue'),(4,7,2,'dinâmica de aula','Júri simulado: Guerra do Contestado','Atividade criada pelo docente para a aula'),(5,4,NULL,'quebra-cabeça','Quebra-cabeça do Museu Oscar Niemeyer','Remonte a imagem do monumento em 9 peças'),(6,49,NULL,'quiz','Quiz das festas do Norte','Perguntas sobre festas populares'),(7,42,NULL,'jogo da memória','Memória dos monumentos do Norte','Pares de monumentos e cidades'),(8,40,NULL,'quebra-cabeça','Quebra-cabeça do Plano Piloto','Monte o mapa de Brasília'),(9,24,2,'dinâmica de aula','Roda de lendas brasileiras','Cada grupo apresenta uma lenda de uma região');
/*!40000 ALTER TABLE `atividade_interativa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autor`
--

DROP TABLE IF EXISTS `autor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `autor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autor`
--

LOCK TABLES `autor` WRITE;
/*!40000 ALTER TABLE `autor` DISABLE KEYS */;
INSERT INTO `autor` VALUES (7,'Domínio popular'),(1,'Érico Veríssimo'),(6,'Euclides da Cunha'),(3,'Graciliano Ramos'),(4,'João Guimarães Rosa'),(2,'José de Alencar'),(5,'Mário de Andrade');
/*!40000 ALTER TABLE `autor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avaliacao`
--

DROP TABLE IF EXISTS `avaliacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avaliacao` (
  `usuario_id` int NOT NULL,
  `conteudo_id` int NOT NULL,
  `nota` int NOT NULL,
  `data_avaliacao` datetime NOT NULL,
  PRIMARY KEY (`usuario_id`,`conteudo_id`),
  KEY `conteudo_id` (`conteudo_id`),
  CONSTRAINT `avaliacao_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `avaliacao_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avaliacao`
--

LOCK TABLES `avaliacao` WRITE;
/*!40000 ALTER TABLE `avaliacao` DISABLE KEYS */;
INSERT INTO `avaliacao` VALUES (1,7,5,'2026-10-01 09:10:00'),(1,9,5,'2026-10-04 10:10:00'),(3,2,4,'2026-10-03 15:00:00'),(5,7,4,'2026-10-02 20:10:00'),(5,14,3,'2026-10-06 15:10:00');
/*!40000 ALTER TABLE `avaliacao` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_valida_nota` BEFORE INSERT ON `avaliacao` FOR EACH ROW BEGIN
    IF NEW.nota < 1 OR NEW.nota > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A nota deve ser de 1 a 5';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `conteudo_cultural`
--

DROP TABLE IF EXISTS `conteudo_cultural`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conteudo_cultural` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_cadastro` date NOT NULL,
  `texto_simplificado` text COLLATE utf8mb4_unicode_ci,
  `texto_tecnico` text COLLATE utf8mb4_unicode_ci,
  `referencias` text COLLATE utf8mb4_unicode_ci,
  `estado_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `estado_id` (`estado_id`),
  CONSTRAINT `conteudo_cultural_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`),
  CONSTRAINT `ck_conteudo_tipo` CHECK ((`tipo` in (_utf8mb4'Culinária',_utf8mb4'Música',_utf8mb4'Literatura',_utf8mb4'Variação Linguística',_utf8mb4'Ponto Turístico',_utf8mb4'Acontecimento Histórico',_utf8mb4'Incidência Nominal',_utf8mb4'Curiosidade',_utf8mb4'Artigo Acadêmico')))
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conteudo_cultural`
--

LOCK TABLES `conteudo_cultural` WRITE;
/*!40000 ALTER TABLE `conteudo_cultural` DISABLE KEYS */;
INSERT INTO `conteudo_cultural` VALUES (1,'Culinária','2026-09-01','O barreado é um prato do litoral do Paraná.','Prato de cozimento lento de Morretes.','Livro de cozinha regional.',1),(2,'Música','2026-09-01','Música tradicional gaúcha.',NULL,NULL,2),(3,'Literatura','2026-09-01','Romance sobre o Rio Grande do Sul.',NULL,NULL,2),(4,'Ponto Turístico','2026-09-02','Museu de arte em Curitiba.',NULL,NULL,1),(5,'Variação Linguística','2026-09-02','Guri quer dizer menino.',NULL,NULL,2),(6,'Incidência Nominal','2026-09-02','Sobrenome de origem polonesa.',NULL,NULL,1),(7,'Acontecimento Histórico','2026-09-03','Conflito no sul do Brasil.','Conflito social de 1912 a 1916.','Fraga, N. Contestado.',1),(8,'Acontecimento Histórico','2026-09-03','Guerra no Rio Grande do Sul.','Conflito do período imperial.','Pesavento, S. A Revolução Farroupilha.',2),(9,'Curiosidade','2026-09-04','O chimarrão faz parte da cultura do Sul.',NULL,NULL,1),(10,'Culinária','2026-11-26','O pinhão é típico do inverno no Paraná.','Prato de sementes de araucária.','Culinária Paranaense.',1),(11,'Artigo Acadêmico','2026-11-05','Estudo sobre a memória do Contestado.','Análise de fontes orais sobre a Guerra do Contestado.','Machado, P. Lideranças do Contestado.',1),(12,'Curiosidade','2026-11-10','Florianópolis tem mais de 40 praias.',NULL,NULL,4),(13,'Música','2026-10-20','Samba de roda do Recôncavo.','Patrimônio imaterial registrado pelo IPHAN.','IPHAN. Samba de Roda do Recôncavo Baiano.',3),(14,'Culinária','2026-10-10','O acarajé é um bolinho de feijão-fradinho.','Ofício das baianas de acarajé, patrimônio imaterial.','IPHAN. Ofício das Baianas de Acarajé.',3),(15,'Literatura','2026-02-10','Romance que conta a história de amor entre a indígena Iracema e o português Martim.','Romance indianista do Romantismo brasileiro, publicado em 1865.','Alencar, J. Iracema. 1865.',9),(16,'Literatura','2026-02-12','Uma família de retirantes atravessa o sertão fugindo da seca.','Romance regionalista da segunda fase do Modernismo, publicado em 1938.','Ramos, G. Vidas Secas. 1938.',6),(17,'Literatura','2026-03-05','O jagunço Riobaldo conta sua vida pelo sertão de Minas Gerais.','Romance de 1956 conhecido pela linguagem inventiva que recria a fala sertaneja.','Rosa, J. G. Grande Sertão: Veredas. 1956.',16),(18,'Literatura','2026-03-08','Macunaíma é o \"herói sem nenhum caráter\", que mistura lendas de todo o Brasil.','Rapsódia modernista de 1928 que reúne mitos indígenas, africanos e europeus.','Andrade, M. Macunaíma. 1928.',25),(19,'Literatura','2026-03-12','Livro que relata a Guerra de Canudos, no sertão da Bahia.','Obra de 1902 que mistura reportagem, geografia e história sobre Canudos.','Cunha, E. Os Sertões. 1902.',3),(20,'Literatura','2026-04-02','O boto-cor-de-rosa vira um moço elegante nas noites de festa nas margens dos rios.','Lenda amazônica de tradição oral ligada aos povos ribeirinhos.','Cascudo, L. C. Dicionário do Folclore Brasileiro.',8),(21,'Literatura','2026-04-05','A indígena Naiá se encantou pela Lua e virou a vitória-régia, a grande flor dos rios.','Lenda de origem tupi que explica o surgimento da vitória-régia.','Cascudo, L. C. Dicionário do Folclore Brasileiro.',17),(22,'Literatura','2026-04-08','A Comadre Fulozinha protege as matas e confunde quem caça sem respeito.','Entidade protetora da mata no folclore nordestino.','Cascudo, L. C. Dicionário do Folclore Brasileiro.',19),(23,'Literatura','2026-04-11','O Pé de Garrafa é um ser da mata que deixa pegadas redondas como fundo de garrafa.','Lenda do Centro-Oeste ligada aos caçadores e às matas do Cerrado.','Cascudo, L. C. Dicionário do Folclore Brasileiro.',14),(24,'Literatura','2026-04-14','O Saci-Pererê é um menino de uma perna só, de gorro vermelho, que adora pregar peças.','Personagem do folclore popularizado na literatura por Monteiro Lobato.','Cascudo, L. C. Dicionário do Folclore Brasileiro.',25),(25,'Literatura','2026-04-17','O Negrinho do Pastoreio ajuda quem perdeu algo a encontrar o que procura.','Lenda gaúcha registrada por João Simões Lopes Neto.','Lopes Neto, J. S. Lendas do Sul. 1913.',2),(26,'Música','2026-05-02','O carimbó é uma dança de roda com tambores, típica do Pará.','Reconhecido como Patrimônio Cultural Imaterial do Brasil pelo IPHAN em 2014.','IPHAN. Carimbó. 2014.',17),(27,'Música','2026-05-04','O frevo é o ritmo acelerado do Carnaval de Pernambuco, dançado com sombrinhas coloridas.','Inscrito pela UNESCO como Patrimônio Imaterial da Humanidade em 2012.','UNESCO. Frevo. 2012.',19),(28,'Música','2026-05-06','O samba nasceu nas rodas dos morros e quintais do Rio de Janeiro.','As matrizes do samba no Rio de Janeiro são patrimônio imaterial registrado pelo IPHAN em 2007.','IPHAN. Matrizes do Samba no Rio de Janeiro. 2007.',21),(29,'Música','2026-05-08','A moda de viola conta histórias do campo com a viola caipira.','Gênero da música caipira, base da música sertaneja do Centro-Oeste.','Nepomuceno, R. Música Caipira.',12),(30,'Música','2026-05-10','As toadas embalam a disputa dos bois Garantido e Caprichoso em Parintins.','Gênero ligado ao boi-bumbá amazonense.','Braga, S. Os bois-bumbás de Parintins.',8),(31,'Culinária','2026-06-02','O tacacá é um caldo quente servido na cuia, típico de Belém.','Preparo à base de tucupi e goma de mandioca, com jambu e camarão seco.','Lody, R. Brasil Bom de Boca.',17),(32,'Culinária','2026-06-04','O arroz com pequi é o sabor mais famoso do Cerrado goiano.','O pequi é fruto nativo do Cerrado; não se morde o caroço, que tem espinhos.','Lody, R. Brasil Bom de Boca.',12),(33,'Culinária','2026-06-06','O pão de queijo é o lanche mais conhecido de Minas Gerais.','Feito com polvilho, sem farinha de trigo.','Lody, R. Brasil Bom de Boca.',16),(34,'Culinária','2026-06-08','A moqueca capixaba é feita na panela de barro, sem leite de coco.','As panelas de barro de Goiabeiras são patrimônio imaterial registrado pelo IPHAN.','IPHAN. Ofício das Paneleiras de Goiabeiras. 2002.',11),(35,'Culinária','2026-06-10','O baião de dois junta arroz e feijão na mesma panela.','Prato do sertão cearense feito com feijão-de-corda e queijo coalho.','Lody, R. Brasil Bom de Boca.',9),(36,'Culinária','2026-06-12','O churrasco gaúcho é assado devagar no fogo de chão.','Tradição dos tropeiros e peões dos pampas.','Lody, R. Brasil Bom de Boca.',2),(37,'Acontecimento Histórico','2026-07-02','A Cabanagem foi uma revolta popular no Pará durante o período regencial.','Revolta de 1835 a 1840 que chegou a tomar o governo da província do Grão-Pará.','Ricci, M. Cabanagem. 2007.',17),(38,'Acontecimento Histórico','2026-07-04','Canudos foi uma comunidade do sertão baiano atacada pelo Exército.','Conflito de 1896 e 1897 entre o arraial liderado por Antônio Conselheiro e o governo republicano.','Cunha, E. Os Sertões. 1902.',3),(39,'Acontecimento Histórico','2026-07-06','A Inconfidência Mineira foi um movimento contra os impostos da Coroa portuguesa.','Conspiração de 1789 em Vila Rica, cujo principal condenado foi Tiradentes.','Maxwell, K. A Devassa da Devassa.',16),(40,'Acontecimento Histórico','2026-07-08','Brasília foi construída no Planalto Central e virou a capital em 1960.','Projeto urbanístico de Lúcio Costa e arquitetura de Oscar Niemeyer.','IPHAN. Conjunto Urbanístico de Brasília.',10),(41,'Acontecimento Histórico','2026-07-10','O Ceará libertou as pessoas escravizadas em 1884, antes da Lei Áurea.','Abolição provincial em 25 de março de 1884; o Ceará ficou conhecido como Terra da Luz.','Girão, R. A Abolição no Ceará.',9),(42,'Ponto Turístico','2026-08-02','O Teatro Amazonas é um teatro de cúpula colorida no centro de Manaus.','Inaugurado em 1896, no auge do ciclo da borracha.','IPHAN. Teatro Amazonas.',8),(43,'Ponto Turístico','2026-08-04','O Jalapão tem dunas alaranjadas, cachoeiras e fervedouros de água cristalina.','Parque estadual no leste do Tocantins.','Governo do Tocantins. Parque Estadual do Jalapão.',27),(44,'Ponto Turístico','2026-08-06','A Chapada dos Veadeiros tem cânions e cachoeiras no meio do Cerrado.','Parque nacional reconhecido como Patrimônio Natural da Humanidade pela UNESCO em 2001.','ICMBio. Parque Nacional da Chapada dos Veadeiros.',12),(45,'Ponto Turístico','2026-08-08','Fernando de Noronha é um arquipélago de praias de água muito clara.','Parque nacional marinho e Patrimônio Natural da Humanidade.','ICMBio. Parque Nacional Marinho de Fernando de Noronha.',19),(46,'Ponto Turístico','2026-08-10','O Cristo Redentor fica no alto do morro do Corcovado.','Monumento inaugurado em 1931.','Prefeitura do Rio de Janeiro. Cristo Redentor.',21),(47,'Ponto Turístico','2026-08-12','As Cataratas do Iguaçu formam centenas de quedas d\'água na fronteira.','Parque Nacional do Iguaçu, Patrimônio Natural da Humanidade desde 1986.','ICMBio. Parque Nacional do Iguaçu.',1),(48,'Ponto Turístico','2026-08-14','O Ver-o-Peso é o grande mercado às margens da baía, em Belém.','Complexo de feira e mercado tombado pelo IPHAN.','IPHAN. Ver-o-Peso.',17),(49,'Curiosidade','2026-09-10','Em Parintins, os bois Garantido e Caprichoso disputam a festa todo mês de junho.',NULL,NULL,8),(50,'Curiosidade','2026-09-12','O Círio de Nazaré reúne multidões nas ruas de Belém em outubro.',NULL,NULL,17),(51,'Curiosidade','2026-09-14','Campina Grande faz um dos maiores São João do Brasil.',NULL,NULL,18),(52,'Curiosidade','2026-09-16','Nas Cavalhadas, cavaleiros encenam a batalha entre mouros e cristãos.',NULL,NULL,12),(53,'Curiosidade','2026-09-18','A Oktoberfest de Blumenau celebra a herança alemã de Santa Catarina.',NULL,NULL,4),(54,'Curiosidade','2026-09-20','O Bumba meu boi do Maranhão mistura dança, música e teatro.',NULL,NULL,13),(55,'Curiosidade','2026-09-22','O capim dourado do Jalapão vira bolsas, brincos e chapéus que brilham como ouro.',NULL,NULL,27),(56,'Curiosidade','2026-09-24','No Acre existem desenhos geométricos gigantes cavados no solo, os geoglifos.',NULL,NULL,5),(57,'Variação Linguística','2026-10-01','No Pará, \"égua!\" é uma expressão de espanto.',NULL,NULL,17),(58,'Variação Linguística','2026-10-02','\"Oxente\" mostra surpresa no jeito baiano de falar.',NULL,NULL,3),(59,'Variação Linguística','2026-10-03','Em Minas, \"uai\" aparece em quase toda conversa.',NULL,NULL,16),(60,'Variação Linguística','2026-10-04','Para o mineiro, \"trem\" pode ser qualquer coisa.',NULL,NULL,16),(61,'Variação Linguística','2026-10-05','O gaúcho diz \"bah\" para quase tudo.',NULL,NULL,2),(62,'Variação Linguística','2026-10-06','Em Pernambuco, \"arretado\" pode ser elogio.',NULL,NULL,19),(63,'Variação Linguística','2026-10-07','O carioca chama o amigo de \"mermão\".',NULL,NULL,21),(64,'Incidência Nominal','2026-10-10','Schmidt é um sobrenome alemão comum em Santa Catarina.',NULL,NULL,4),(65,'Incidência Nominal','2026-10-11','Muitos lugares começam com \"Ita\", palavra tupi.',NULL,NULL,3),(66,'Incidência Nominal','2026-10-12','Guanabara é o nome tupi da baía do Rio de Janeiro.',NULL,NULL,21),(67,'Incidência Nominal','2026-10-13','Silva é o sobrenome mais comum do Brasil.',NULL,NULL,25),(68,'Artigo Acadêmico','2026-10-15','Estudo sobre como os tropeiros ajudaram a formar as cidades do Paraná.','Análise dos caminhos de tropas entre o Rio Grande do Sul e Sorocaba nos séculos XVIII e XIX.','Machado, B. P. Formação da estrutura agrária tradicional dos Campos Gerais.',1);
/*!40000 ALTER TABLE `conteudo_cultural` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contribuicao`
--

DROP TABLE IF EXISTS `contribuicao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contribuicao` (
  `conteudo_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `data_envio` datetime NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_revisao` datetime DEFAULT NULL,
  `motivo_rejeicao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editor_id` int DEFAULT NULL,
  `data_edicao` datetime DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `admin_id` (`admin_id`),
  KEY `editor_id` (`editor_id`),
  CONSTRAINT `contribuicao_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`),
  CONSTRAINT `contribuicao_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `contribuicao_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `administrador` (`usuario_id`),
  CONSTRAINT `contribuicao_ibfk_4` FOREIGN KEY (`editor_id`) REFERENCES `administrador` (`usuario_id`),
  CONSTRAINT `ck_contribuicao_motivo` CHECK (((`status` <> _utf8mb4'rejeitado') or (`motivo_rejeicao` is not null))),
  CONSTRAINT `ck_contribuicao_status` CHECK ((`status` in (_utf8mb4'rascunho',_utf8mb4'pendente',_utf8mb4'aprovado',_utf8mb4'rejeitado')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribuicao`
--

LOCK TABLES `contribuicao` WRITE;
/*!40000 ALTER TABLE `contribuicao` DISABLE KEYS */;
INSERT INTO `contribuicao` VALUES (7,2,4,'2026-09-03 10:00:00','aprovado','2026-09-04 09:00:00',NULL,4,'2026-09-04 08:50:00'),(8,2,4,'2026-09-03 11:00:00','aprovado','2026-09-04 09:30:00',NULL,NULL,NULL),(10,2,NULL,'2026-11-26 18:56:06','pendente',NULL,NULL,NULL,NULL),(11,3,NULL,'2026-11-05 14:00:00','pendente',NULL,NULL,NULL,NULL),(12,2,NULL,'2026-11-10 20:00:00','rascunho',NULL,NULL,NULL,NULL),(13,3,4,'2026-10-20 10:00:00','rejeitado','2026-10-21 09:00:00','Fonte da gravação não informada.',NULL,NULL),(68,3,4,'2026-10-15 09:00:00','aprovado','2026-10-15 18:00:00',NULL,NULL,NULL);
/*!40000 ALTER TABLE `contribuicao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `culinaria`
--

DROP TABLE IF EXISTS `culinaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `culinaria` (
  `conteudo_id` int NOT NULL,
  `nome_prato` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receita` text COLLATE utf8mb4_unicode_ci,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `culinaria_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `culinaria`
--

LOCK TABLES `culinaria` WRITE;
/*!40000 ALTER TABLE `culinaria` DISABLE KEYS */;
INSERT INTO `culinaria` VALUES (1,'Barreado','Cozinhar a carne por muitas horas.','Prato típico do Paraná'),(10,'Pinhão cozido','Cozinhar o pinhão em água e sal.','Prato tradicional de inverno'),(14,'Acarajé','Fritar a massa de feijão-fradinho no azeite de dendê.','Prato típico da Bahia'),(31,'Tacacá','Servir o tucupi quente com goma, jambu e camarão.','Prato típico do Pará'),(32,'Arroz com pequi','Refogar o pequi e cozinhar com o arroz.','Prato típico de Goiás'),(33,'Pão de queijo','Misturar polvilho, queijo, ovos e leite e assar.','Prato típico de Minas Gerais'),(34,'Moqueca capixaba','Cozinhar o peixe com tomate, cebola e coentro na panela de barro.','Prato típico do Espírito Santo'),(35,'Baião de dois','Cozinhar arroz e feijão-de-corda juntos e finalizar com queijo coalho.','Prato típico do Ceará'),(36,'Churrasco gaúcho','Assar a carne temperada com sal grosso.','Prato típico do Rio Grande do Sul');
/*!40000 ALTER TABLE `culinaria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `culinaria_ingrediente`
--

DROP TABLE IF EXISTS `culinaria_ingrediente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `culinaria_ingrediente` (
  `culinaria_id` int NOT NULL,
  `ingrediente` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`culinaria_id`,`ingrediente`),
  CONSTRAINT `culinaria_ingrediente_ibfk_1` FOREIGN KEY (`culinaria_id`) REFERENCES `culinaria` (`conteudo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `culinaria_ingrediente`
--

LOCK TABLES `culinaria_ingrediente` WRITE;
/*!40000 ALTER TABLE `culinaria_ingrediente` DISABLE KEYS */;
INSERT INTO `culinaria_ingrediente` VALUES (1,'Carne bovina'),(1,'Farinha de mandioca'),(10,'Pinhão'),(10,'Sal'),(14,'Azeite de dendê'),(14,'Feijão-fradinho'),(31,'Camarão seco'),(31,'Goma de mandioca'),(31,'Jambu'),(31,'Tucupi'),(32,'Alho'),(32,'Arroz'),(32,'Pequi'),(33,'Leite'),(33,'Ovos'),(33,'Polvilho'),(33,'Queijo'),(34,'Coentro'),(34,'Peixe'),(34,'Tomate'),(34,'Urucum'),(35,'Arroz'),(35,'Feijão-de-corda'),(35,'Queijo coalho'),(36,'Carne bovina'),(36,'Sal grosso');
/*!40000 ALTER TABLE `culinaria_ingrediente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curiosidade`
--

DROP TABLE IF EXISTS `curiosidade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curiosidade` (
  `conteudo_id` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curiosidade` text COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `curiosidade_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curiosidade`
--

LOCK TABLES `curiosidade` WRITE;
/*!40000 ALTER TABLE `curiosidade` DISABLE KEYS */;
INSERT INTO `curiosidade` VALUES (9,'Origem do chimarrão','O chimarrão vem dos povos indígenas.','Culinária','Bebida típica'),(12,'Praias de Florianópolis','A ilha tem mais de 40 praias.','Geografia','Curiosidade sobre SC'),(49,'Festival de Parintins','O Garantido é vermelho e o Caprichoso é azul.','Festa','Festival folclórico'),(50,'Círio de Nazaré','É uma das maiores procissões católicas do mundo.','Festa','Procissão em Belém'),(51,'São João de Campina Grande','A festa dura o mês de junho inteiro.','Festa','Festa junina'),(52,'Cavalhadas de Pirenópolis','Acontecem na Festa do Divino Espírito Santo.','Tradição','Encenação equestre'),(53,'Oktoberfest de Blumenau','A festa começou em 1984.','Festa','Festa da imigração alemã'),(54,'Bumba meu boi','Foi reconhecido pela UNESCO em 2019.','Tradição','Folguedo popular'),(55,'Capim dourado','O artesanato é feito com fibra de buriti.','Tradição','Artesanato do Jalapão'),(56,'Geoglifos do Acre','Foram feitos por povos antigos há centenas de anos.','Arqueologia','Sítios arqueológicos');
/*!40000 ALTER TABLE `curiosidade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente`
--

DROP TABLE IF EXISTS `docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docente` (
  `usuario_id` int NOT NULL,
  `certificado_exp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instituicao_trabalho` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `docente_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente`
--

LOCK TABLES `docente` WRITE;
/*!40000 ALTER TABLE `docente` DISABLE KEYS */;
INSERT INTO `docente` VALUES (2,'Licenciatura em História','UFPR');
/*!40000 ALTER TABLE `docente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado`
--

DROP TABLE IF EXISTS `estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regiao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sigla` (`sigla`),
  CONSTRAINT `ck_estado_regiao` CHECK ((`regiao` in (_utf8mb4'Norte',_utf8mb4'Nordeste',_utf8mb4'Centro-Oeste',_utf8mb4'Sudeste',_utf8mb4'Sul')))
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado`
--

LOCK TABLES `estado` WRITE;
/*!40000 ALTER TABLE `estado` DISABLE KEYS */;
INSERT INTO `estado` VALUES (1,'Paraná','PR','Sul'),(2,'Rio Grande do Sul','RS','Sul'),(3,'Bahia','BA','Nordeste'),(4,'Santa Catarina','SC','Sul'),(5,'Acre','AC','Norte'),(6,'Alagoas','AL','Nordeste'),(7,'Amapá','AP','Norte'),(8,'Amazonas','AM','Norte'),(9,'Ceará','CE','Nordeste'),(10,'Distrito Federal','DF','Centro-Oeste'),(11,'Espírito Santo','ES','Sudeste'),(12,'Goiás','GO','Centro-Oeste'),(13,'Maranhão','MA','Nordeste'),(14,'Mato Grosso','MT','Centro-Oeste'),(15,'Mato Grosso do Sul','MS','Centro-Oeste'),(16,'Minas Gerais','MG','Sudeste'),(17,'Pará','PA','Norte'),(18,'Paraíba','PB','Nordeste'),(19,'Pernambuco','PE','Nordeste'),(20,'Piauí','PI','Nordeste'),(21,'Rio de Janeiro','RJ','Sudeste'),(22,'Rio Grande do Norte','RN','Nordeste'),(23,'Rondônia','RO','Norte'),(24,'Roraima','RR','Norte'),(25,'São Paulo','SP','Sudeste'),(26,'Sergipe','SE','Nordeste'),(27,'Tocantins','TO','Norte');
/*!40000 ALTER TABLE `estado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudante`
--

DROP TABLE IF EXISTS `estudante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudante` (
  `usuario_id` int NOT NULL,
  `instituicao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nivel_escolaridade` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `estudante_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudante`
--

LOCK TABLES `estudante` WRITE;
/*!40000 ALTER TABLE `estudante` DISABLE KEYS */;
INSERT INTO `estudante` VALUES (1,'Colégio Estadual do Paraná','Ensino Médio');
/*!40000 ALTER TABLE `estudante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorito`
--

DROP TABLE IF EXISTS `favorito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorito` (
  `usuario_id` int NOT NULL,
  `conteudo_id` int NOT NULL,
  `data_inclusao` datetime NOT NULL,
  PRIMARY KEY (`usuario_id`,`conteudo_id`),
  KEY `conteudo_id` (`conteudo_id`),
  CONSTRAINT `favorito_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `favorito_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorito`
--

LOCK TABLES `favorito` WRITE;
/*!40000 ALTER TABLE `favorito` DISABLE KEYS */;
INSERT INTO `favorito` VALUES (1,7,'2026-10-01 09:21:00'),(1,9,'2026-10-01 09:20:00'),(5,14,'2026-10-06 15:12:00');
/*!40000 ALTER TABLE `favorito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `conteudo_id` int NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_envio` datetime NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`,`conteudo_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`usuario_id`, `conteudo_id`) REFERENCES `avaliacao` (`usuario_id`, `conteudo_id`),
  CONSTRAINT `ck_feedback_status` CHECK ((`status` in (_utf8mb4'pendente',_utf8mb4'analisado'))),
  CONSTRAINT `ck_feedback_tipo` CHECK ((`tipo` in (_utf8mb4'comentário',_utf8mb4'correção')))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,1,7,'correção','O período está incorreto.','2026-10-01 09:12:00','pendente'),(2,3,2,'comentário','Bom registro da música gaúcha.','2026-10-03 15:02:00','analisado');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incidencia_nominal`
--

DROP TABLE IF EXISTS `incidencia_nominal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incidencia_nominal` (
  `conteudo_id` int NOT NULL,
  `termo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `significado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `incidencia_nominal_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incidencia_nominal`
--

LOCK TABLES `incidencia_nominal` WRITE;
/*!40000 ALTER TABLE `incidencia_nominal` DISABLE KEYS */;
INSERT INTO `incidencia_nominal` VALUES (6,'Kowalski','Sobrenome que significa ferreiro','Polônia'),(64,'Schmidt','Ferreiro, em alemão','Alemanha'),(65,'Ita','Pedra, em tupi (como em Itaparica)','Tupi'),(66,'Guanabara','Seio do mar, em tupi','Tupi'),(67,'Silva','Selva, mata, em latim','Portugal');
/*!40000 ALTER TABLE `incidencia_nominal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `literatura`
--

DROP TABLE IF EXISTS `literatura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `literatura` (
  `conteudo_id` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trecho` text COLLATE utf8mb4_unicode_ci,
  `autor_id` int NOT NULL,
  PRIMARY KEY (`conteudo_id`),
  KEY `autor_id` (`autor_id`),
  CONSTRAINT `literatura_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`),
  CONSTRAINT `literatura_ibfk_2` FOREIGN KEY (`autor_id`) REFERENCES `autor` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `literatura`
--

LOCK TABLES `literatura` WRITE;
/*!40000 ALTER TABLE `literatura` DISABLE KEYS */;
INSERT INTO `literatura` VALUES (3,'O Tempo e o Vento','Romance','Trecho da obra...',1),(15,'Iracema','Romance indianista','Narrativa sobre a formação do Ceará.',2),(16,'Vidas Secas','Romance regionalista','A seca e a migração no sertão nordestino.',3),(17,'Grande Sertão: Veredas','Romance','O sertão como cenário e personagem.',4),(18,'Macunaíma','Rapsódia modernista','Mistura de lendas e falares do Brasil.',5),(19,'Os Sertões','Não ficção','A terra, o homem e a luta em Canudos.',6),(20,'Lenda do Boto','Lenda','O boto que vira gente nas festas juninas.',7),(21,'Lenda da Vitória-Régia','Lenda','A estrela das águas da Amazônia.',7),(22,'Comadre Fulozinha','Lenda','Guardiã das matas do Nordeste.',7),(23,'Pé de Garrafa','Lenda','As pegadas misteriosas da mata.',7),(24,'Saci-Pererê','Lenda','O travesso do gorro vermelho.',7),(25,'Negrinho do Pastoreio','Lenda','A lenda dos pampas gaúchos.',7);
/*!40000 ALTER TABLE `literatura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `musica`
--

DROP TABLE IF EXISTS `musica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `musica` (
  `conteudo_id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `artista` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero_musical` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `letra` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `musica_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `musica`
--

LOCK TABLES `musica` WRITE;
/*!40000 ALTER TABLE `musica` DISABLE KEYS */;
INSERT INTO `musica` VALUES (2,'Querência amada','Teixeirinha','Gaúcha',NULL,'Querência amada, meu rincon querido\nQue eu trago guardado dentro do meu peito\nMeu rancho de palha coberto de estrelas\nMeu pago adorado que me viu nascer\n\nQuerência amada, terra dos meus sonhos\nDas coxilhas verdes, das noites de lua\nDo chimarrão quente, do churrasco e mate\nDa china morena que eu amei demais\n\nEu sou peão de estância, campeiro e tropeiro\nCom o laço no braço e a bota no pé\nMontado a cavalo, cortando a campanha\nSou gaúcho forte, sou filho do chão\n\nQuerência amada, quando eu me for embora\nVou levar saudade deste meu rincão\nDa terra querida que me deu a vida\nMeu pago adorado, minha tradição'),(13,'Samba de roda','Mestres do Recôncavo','Samba de roda',NULL,'Ô de casa, ô de fora\nVenha ver o samba de roda\nQue a gente baiana adora\nBatendo palma e cantando agora\n\nÉ no Recôncavo baiano\nQue o samba tem sua raiz\nA roda gira bonita\nE o povo todo é feliz\n\nPisa na barra da saia\nMas não me pise no pé\nQue a roda tá formada\nE o samba vai até amanhecer\n\nChama a vizinha, chama o compadre\nVem todo mundo sambar\nQue o samba de roda é bonito\nE veio pra nos alegrar\n\nA viola toca bonito\nO pandeiro faz o repique\nO atabaque marca o ritmo\nE o canto sobe com axé'),(26,'Carimbó','Mestres do carimbó paraense','Carimbó',NULL,'Ê, carimbó, ê carimbó\nVem dançar o carimbó\nQue é do Pará, que é do Pará\nO ritmo que veio de lá\n\nCurimbó batendo forte\nNo terreiro de chão batido\nA morena gira a saia\nNum requebro colorido\n\nÊ, carimbó, ê carimbó\nA festa não pode parar\nO maracá vai sacudindo\nE a madrugada vai chegar\n\nNa beira do rio Guamá\nO pescador canta esta canção\nDe noite a lua alumia\nO carimbó do meu sertão\n\nSaia rodada no ar\nPés descalços no salão\nO carimbó é a nossa festa\nÉ a alma da nossa nação'),(27,'Frevo','Orquestras de frevo do Recife','Frevo',NULL,'Olinda, ó linda cidade\nDe morros e ladeiras\nNo carnaval a multidão\nDesce as ruas inteiras\n\nÉ frevo, é frevo, é frevo\nO passo que é só nosso\nCom a sombrinha colorida\nNo ritmo do alvoroço\n\nRecife, capital do frevo\nBerço da folia\nO bloco passa na rua\nEspalhando alegria\n\nLevanta o braço, gira o corpo\nPula sem parar\nO frevo é pernambucano\nE veio pra ficar\n\nA orquestra toca forte\nMetal e percussão\nO frevo é patrimônio\nDa nossa grande nação'),(28,'Samba carioca','Escolas de samba do Rio','Samba',NULL,'Na Sapucaí, o samba é rei\nA bateria faz tremer o chão\nPassistas brilham sob as luzes\nNum desfile de emoção\n\nSalgueiro, Mangueira, Portela\nBeija-Flor e Imperatriz\nCada escola tem sua história\nQue o samba sempre rediz\n\nCidade maravilhosa\nDe morros e de mar\nO samba nasce no morro\nE desce pra desfilar\n\nTamborim marca a cadência\nSurdo dá o coração\nCavaquinho e pandeiro juntos\nTocam nesta canção\n\nNo Rio o samba é cultura\nÉ tradição, é raiz\nDe Noel a Cartola e Paulinho\nO samba nos faz feliz'),(29,'Moda de viola','Duplas caipiras de Goiás','Música caipira',NULL,'Lá no sertão de Goiás\nOnde o sol bate mais forte\nO violeiro canta a vida\nSua sina e sua sorte\n\nModa de viola é história\nContada em versos e rimas\nDe amores e despedidas\nDe vales, rios e minas\n\nÔ de casa, ô de rancho\nVem ouvir minha toada\nDa porteira pro terreiro\nNa poeira da estrada\n\nA viola tem dez cordas\nQue choram quando eu dedilho\nCada nota é uma saudade\nDo meu sertão e meu filho\n\nCaipira de chapéu de palha\nPé no chão e coração\nCanta a moda com a alma\nÉ tradição do sertão'),(30,'Toada de boi-bumbá','Bois Garantido e Caprichoso','Toada',NULL,'No meio da floresta\nO boi-bumbá vai dançar\nGarantido é vermelho\nCaprichoso é azul-luar\n\nA toada ecoa forte\nNo festival de Parintins\nÉ a festa do Amazonas\nDos começos até os fins\n\nÔ boi, ô boi, ô boi-bumbá\nA arena inteira vai cantar\nCunhã-poranga tão bonita\nFaz o povo se encantar\n\nPajé conta sua lenda\nDa cobra-grande e do boto\nRituais da mata virgem\nNo batuque mais devoto\n\nParintins, ilha encantada\nDe lendas e tradição\nO boi-bumbá é a nossa festa\nÉ orgulho da nação');
/*!40000 ALTER TABLE `musica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesquisador`
--

DROP TABLE IF EXISTS `pesquisador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pesquisador` (
  `usuario_id` int NOT NULL,
  `tempo_experiencia` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instituicao_trabalho` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linha_pesquisa` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `pesquisador_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesquisador`
--

LOCK TABLES `pesquisador` WRITE;
/*!40000 ALTER TABLE `pesquisador` DISABLE KEYS */;
INSERT INTO `pesquisador` VALUES (3,'10 anos','USP','História do Sul do Brasil');
/*!40000 ALTER TABLE `pesquisador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ponto_turistico`
--

DROP TABLE IF EXISTS `ponto_turistico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ponto_turistico` (
  `conteudo_id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localizacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `historia` text COLLATE utf8mb4_unicode_ci,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `ponto_turistico_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ponto_turistico`
--

LOCK TABLES `ponto_turistico` WRITE;
/*!40000 ALTER TABLE `ponto_turistico` DISABLE KEYS */;
INSERT INTO `ponto_turistico` VALUES (4,'Museu Oscar Niemeyer','R. Mal. Hermes, 999 - Curitiba, PR','Inaugurado em 2002.','Museu de arte','museu.jpg'),(42,'Teatro Amazonas','Largo de São Sebastião - Manaus, AM','Símbolo do ciclo da borracha.','Teatro histórico','teatro_amazonas.jpg'),(43,'Jalapão','Leste do Tocantins','Região do capim dourado.','Parque natural','jalapao.jpg'),(44,'Chapada dos Veadeiros','Alto Paraíso de Goiás, GO','Área protegida do Cerrado.','Parque nacional','chapada_veadeiros.jpg'),(45,'Fernando de Noronha','Arquipélago de Fernando de Noronha, PE','Área de proteção marinha.','Praias e vida marinha','fernando_noronha.jpg'),(46,'Cristo Redentor','Morro do Corcovado - Rio de Janeiro, RJ','Inaugurado em 1931.','Monumento','cristo_redentor.jpg'),(47,'Cataratas do Iguaçu','Foz do Iguaçu, PR','Parque nacional criado em 1939.','Quedas d\'água','cataratas_iguacu.jpg'),(48,'Mercado Ver-o-Peso','Belém, PA','Mercado ativo desde o período colonial.','Mercado e feira','mercado_vero_peso.jpg');
/*!40000 ALTER TABLE `ponto_turistico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publico_geral`
--

DROP TABLE IF EXISTS `publico_geral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publico_geral` (
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `publico_geral_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publico_geral`
--

LOCK TABLES `publico_geral` WRITE;
/*!40000 ALTER TABLE `publico_geral` DISABLE KEYS */;
INSERT INTO `publico_geral` VALUES (5);
/*!40000 ALTER TABLE `publico_geral` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurso_complementar`
--

DROP TABLE IF EXISTS `recurso_complementar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurso_complementar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conteudo_id` int NOT NULL,
  `docente_id` int DEFAULT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conteudo_id` (`conteudo_id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `recurso_complementar_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`),
  CONSTRAINT `recurso_complementar_ibfk_2` FOREIGN KEY (`docente_id`) REFERENCES `docente` (`usuario_id`),
  CONSTRAINT `ck_recurso_tipo` CHECK ((`tipo` in (_utf8mb4'material pedagógico',_utf8mb4'plano de aula',_utf8mb4'vídeo-aula',_utf8mb4'link externo',_utf8mb4'mapa externo')))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurso_complementar`
--

LOCK TABLES `recurso_complementar` WRITE;
/*!40000 ALTER TABLE `recurso_complementar` DISABLE KEYS */;
INSERT INTO `recurso_complementar` VALUES (1,7,NULL,'vídeo-aula','O Contestado explicado','https://video.exemplo.com/contestado',NULL),(2,7,NULL,'link externo','Saiba mais sobre o Contestado','https://exemplo.com/contestado',NULL),(3,7,2,'plano de aula','Plano de aula: Contestado','https://exemplo.com/plano.pdf','Anexado pelo docente'),(4,7,2,'material pedagógico','Linha do tempo do Contestado','https://exemplo.com/linha-do-tempo.pdf','Anexado pelo docente'),(5,4,NULL,'mapa externo','Museu Oscar Niemeyer no Google Maps','https://maps.google.com/?q=Museu+Oscar+Niemeyer',NULL),(6,42,NULL,'mapa externo','Teatro Amazonas no Google Maps','https://www.google.com/maps/search/?api=1&query=Teatro+Amazonas',NULL),(7,43,NULL,'mapa externo','Jalapão no Google Maps','https://www.google.com/maps/search/?api=1&query=Jalapão',NULL),(8,44,NULL,'mapa externo','Chapada dos Veadeiros no Google Maps','https://www.google.com/maps/search/?api=1&query=Chapada+dos+Veadeiros',NULL),(9,45,NULL,'mapa externo','Fernando de Noronha no Google Maps','https://www.google.com/maps/search/?api=1&query=Fernando+de+Noronha',NULL),(10,46,NULL,'mapa externo','Cristo Redentor no Google Maps','https://www.google.com/maps/search/?api=1&query=Cristo+Redentor',NULL),(11,47,NULL,'mapa externo','Cataratas do Iguaçu no Google Maps','https://www.google.com/maps/search/?api=1&query=Cataratas+do+Iguaçu',NULL),(12,48,NULL,'mapa externo','Mercado Ver-o-Peso no Google Maps','https://www.google.com/maps/search/?api=1&query=Mercado+Ver-o-Peso',NULL);
/*!40000 ALTER TABLE `recurso_complementar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tentativa`
--

DROP TABLE IF EXISTS `tentativa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tentativa` (
  `estudante_id` int NOT NULL,
  `atividade_id` int NOT NULL,
  `data_hora` datetime NOT NULL,
  `pontuacao` int NOT NULL,
  PRIMARY KEY (`estudante_id`,`atividade_id`,`data_hora`),
  KEY `atividade_id` (`atividade_id`),
  CONSTRAINT `tentativa_ibfk_1` FOREIGN KEY (`estudante_id`) REFERENCES `estudante` (`usuario_id`),
  CONSTRAINT `tentativa_ibfk_2` FOREIGN KEY (`atividade_id`) REFERENCES `atividade_interativa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tentativa`
--

LOCK TABLES `tentativa` WRITE;
/*!40000 ALTER TABLE `tentativa` DISABLE KEYS */;
INSERT INTO `tentativa` VALUES (1,1,'2026-10-05 10:00:00',90),(1,2,'2026-10-05 10:15:00',80),(1,3,'2026-10-05 09:30:00',80);
/*!40000 ALTER TABLE `tentativa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date NOT NULL,
  `classificacao` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  CONSTRAINT `ck_usuario_classificacao` CHECK ((`classificacao` in (_utf8mb4'Estudante',_utf8mb4'Docente',_utf8mb4'Pesquisador',_utf8mb4'Público em Geral',_utf8mb4'Administrador')))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Ana Souza','ana@email.com','$2y$10$z4dS65ffjrxGxC7x8twOzeJ30QgQKKuBzrQ/hoE0JMZh/knJdzdma','(41) 99999-0001','2008-03-10','Estudante'),(2,'Carlos Lima','carlos@email.com','$2y$10$M37ZnKC.Rnzs/TjFVEIqr.HboUBa2s2fxIAbtkvVMNUOMmU94PyFa','(41) 99999-0002','1980-05-02','Docente'),(3,'Marta Reis','marta@email.com','$2y$10$EO4DufMQp7O0EgA0rLIGzeeQZhLhjThHduw/GkjKSqV6ETev8J7X6',NULL,'1975-09-21','Pesquisador'),(4,'Admin Tchê','admin@email.com','$2y$10$o10Z/zUJGzcPFaus3XAT4u9Sh5dydHmZIKJZkzFif8JO38BuGZH26',NULL,'1990-01-01','Administrador'),(5,'Joana Pereira','joana@email.com','$2y$10$hoy2jl8vxIVEKHqx8tIz6e2K8A11m8aakO3k0yvyX2Hp1djUY9YBa',NULL,'1995-07-15','Público em Geral');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variacao_linguistica`
--

DROP TABLE IF EXISTS `variacao_linguistica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variacao_linguistica` (
  `conteudo_id` int NOT NULL,
  `expressao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `significado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exemplo_uso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`conteudo_id`),
  CONSTRAINT `variacao_linguistica_ibfk_1` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudo_cultural` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variacao_linguistica`
--

LOCK TABLES `variacao_linguistica` WRITE;
/*!40000 ALTER TABLE `variacao_linguistica` DISABLE KEYS */;
INSERT INTO `variacao_linguistica` VALUES (5,'Guri','Menino','Aquele guri é esperto.'),(57,'Égua','Expressão de surpresa ou espanto','Égua, que chuva forte!'),(58,'Oxente','Expressão de surpresa','Oxente, você por aqui?'),(59,'Uai','Interjeição de surpresa ou de reforço','Uai, já vai embora?'),(60,'Trem','Coisa, objeto','Me passa esse trem aí.'),(61,'Bah','Expressão de espanto ou admiração','Bah, que frio!'),(62,'Arretado','Muito bom, excelente (ou zangado, conforme o contexto)','Esse frevo é arretado!'),(63,'Mermão','Meu irmão; forma de tratamento entre amigos','Fala, mermão!');
/*!40000 ALTER TABLE `variacao_linguistica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `vw_conteudos_detalhados`
--

DROP TABLE IF EXISTS `vw_conteudos_detalhados`;
/*!50001 DROP VIEW IF EXISTS `vw_conteudos_detalhados`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_conteudos_detalhados` AS SELECT 
 1 AS `codigo`,
 1 AS `tipo`,
 1 AS `resumo`,
 1 AS `estado`,
 1 AS `regiao`,
 1 AS `cadastrado_em`,
 1 AS `dias_publicado`,
 1 AS `autor`,
 1 AS `perfil_autor`,
 1 AS `situacao`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_movimento_por_estado`
--

DROP TABLE IF EXISTS `vw_movimento_por_estado`;
/*!50001 DROP VIEW IF EXISTS `vw_movimento_por_estado`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_movimento_por_estado` AS SELECT 
 1 AS `sigla`,
 1 AS `estado`,
 1 AS `regiao`,
 1 AS `total_conteudos`,
 1 AS `total_acessos`,
 1 AS `media_avaliacoes`,
 1 AS `ultimo_cadastro`,
 1 AS `nivel_movimento`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_perfil_publico`
--

DROP TABLE IF EXISTS `vw_perfil_publico`;
/*!50001 DROP VIEW IF EXISTS `vw_perfil_publico`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_perfil_publico` AS SELECT 
 1 AS `codigo`,
 1 AS `nome_exibicao`,
 1 AS `perfil`,
 1 AS `contribuicoes_aprovadas`,
 1 AS `avaliacoes_feitas`,
 1 AS `selo`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_tipos_culturais_frequentes`
--

DROP TABLE IF EXISTS `vw_tipos_culturais_frequentes`;
/*!50001 DROP VIEW IF EXISTS `vw_tipos_culturais_frequentes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_tipos_culturais_frequentes` AS SELECT 
 1 AS `tipo`,
 1 AS `total_conteudos`,
 1 AS `qtd_estados`,
 1 AS `estados`,
 1 AS `primeiro_cadastro`,
 1 AS `ultimo_cadastro`,
 1 AS `frequencia`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'tche_cultura'
--

--
-- Dumping routines for database 'tche_cultura'
--
/*!50003 DROP FUNCTION IF EXISTS `fn_classificar_atualidade` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_classificar_atualidade`(p_data DATE) RETURNS varchar(30) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_total_contribuicoes_usuario` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_total_contribuicoes_usuario`(p_usuario_id INT) RETURNS int
    READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*)
    INTO v_total
    FROM contribuicao
    WHERE usuario_id = p_usuario_id;
    RETURN v_total;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_editar_conteudo_moderacao` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_editar_conteudo_moderacao`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_contribuicao` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_contribuicao`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_relatorio_conteudos_periodo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_relatorio_conteudos_periodo`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_revisar_contribuicao` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_revisar_contribuicao`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Current Database: `tche_cultura`
--

USE `tche_cultura`;

--
-- Final view structure for view `vw_conteudos_detalhados`
--

/*!50001 DROP VIEW IF EXISTS `vw_conteudos_detalhados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_conteudos_detalhados` (`codigo`,`tipo`,`resumo`,`estado`,`regiao`,`cadastrado_em`,`dias_publicado`,`autor`,`perfil_autor`,`situacao`) AS select `c`.`id` AS `id`,`c`.`tipo` AS `tipo`,coalesce(substr(`c`.`texto_simplificado`,1,35),'Sem resumo') AS `COALESCE(SUBSTRING(c.texto_simplificado, 1, 35), 'Sem resumo')`,concat(`e`.`nome`,' (',`e`.`sigla`,')') AS `CONCAT(e.nome, ' (', e.sigla, ')')`,`e`.`regiao` AS `regiao`,date_format(`c`.`data_cadastro`,'%d/%m/%Y') AS `DATE_FORMAT(c.data_cadastro, '%d/%m/%Y')`,(to_days(curdate()) - to_days(`c`.`data_cadastro`)) AS `DATEDIFF(CURDATE(), c.data_cadastro)`,`u`.`nome` AS `nome`,`u`.`classificacao` AS `classificacao`,(case `ct`.`status` when 'aprovado' then 'Aprovado' when 'pendente' then 'Aguardando revisão' when 'rascunho' then 'Rascunho' when 'rejeitado' then 'Rejeitado' end) AS `Name_exp_10` from (((`usuario` `u` join `contribuicao` `ct` on((`ct`.`usuario_id` = `u`.`id`))) join `conteudo_cultural` `c` on((`c`.`id` = `ct`.`conteudo_id`))) join `estado` `e` on((`e`.`id` = `c`.`estado_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_movimento_por_estado`
--

/*!50001 DROP VIEW IF EXISTS `vw_movimento_por_estado`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_movimento_por_estado` AS select `e`.`sigla` AS `sigla`,`e`.`nome` AS `estado`,`e`.`regiao` AS `regiao`,count(distinct `c`.`id`) AS `total_conteudos`,count(`a`.`id`) AS `total_acessos`,coalesce((select round(avg(`av`.`nota`),1) from (`avaliacao` `av` join `conteudo_cultural` `c2` on((`c2`.`id` = `av`.`conteudo_id`))) where (`c2`.`estado_id` = `e`.`id`)),0) AS `media_avaliacoes`,coalesce(date_format(max(`c`.`data_cadastro`),'%d/%m/%Y'),'Sem conteúdo') AS `ultimo_cadastro`,(case when (count(distinct `c`.`id`) = 0) then 'Sem movimento' when (count(`a`.`id`) = 0) then 'Sem acessos' when (count(`a`.`id`) < 3) then 'Em crescimento' else 'Movimentado' end) AS `nivel_movimento` from ((`estado` `e` left join `conteudo_cultural` `c` on(((`c`.`estado_id` = `e`.`id`) and exists(select 1 from `contribuicao` `x` where ((`x`.`conteudo_id` = `c`.`id`) and (`x`.`status` <> 'aprovado'))) is false))) left join `acesso` `a` on(((`a`.`conteudo_id` = `c`.`id`) and (`a`.`pagina_visitada` = 'Conteúdo')))) group by `e`.`id`,`e`.`sigla`,`e`.`nome`,`e`.`regiao` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_perfil_publico`
--

/*!50001 DROP VIEW IF EXISTS `vw_perfil_publico`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_perfil_publico` (`codigo`,`nome_exibicao`,`perfil`,`contribuicoes_aprovadas`,`avaliacoes_feitas`,`selo`) AS select `u`.`id` AS `id`,concat(substring_index(`u`.`nome`,' ',1),' ',left(substring_index(`u`.`nome`,' ',-(1)),1),'.') AS `Name_exp_2`,`u`.`classificacao` AS `classificacao`,count(`ct`.`conteudo_id`) AS `COUNT(ct.conteudo_id)`,coalesce((select count(`av`.`nota`) from `avaliacao` `av` where (`av`.`usuario_id` = `u`.`id`)),0) AS `Name_exp_5`,(case when (count(`ct`.`conteudo_id`) >= 2) then 'Colaborador destaque' when (count(`ct`.`conteudo_id`) = 1) then 'Colaborador' else 'Leitor' end) AS `Name_exp_6` from (`usuario` `u` left join `contribuicao` `ct` on(((`ct`.`usuario_id` = `u`.`id`) and (`ct`.`status` = 'aprovado') and (`ct`.`data_revisao` is not null)))) where (`u`.`classificacao` in ('Estudante','Docente','Pesquisador','Público em Geral')) group by `u`.`id`,`u`.`nome`,`u`.`classificacao` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_tipos_culturais_frequentes`
--

/*!50001 DROP VIEW IF EXISTS `vw_tipos_culturais_frequentes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_tipos_culturais_frequentes` (`tipo`,`total_conteudos`,`qtd_estados`,`estados`,`primeiro_cadastro`,`ultimo_cadastro`,`frequencia`) AS select upper(`c`.`tipo`) AS `UPPER(c.tipo)`,count(`c`.`id`) AS `COUNT(c.id)`,count(distinct `c`.`estado_id`) AS `COUNT(DISTINCT c.estado_id)`,group_concat(distinct `e`.`sigla` order by `e`.`sigla` ASC separator ', ') AS `GROUP_CONCAT(DISTINCT e.sigla ORDER BY e.sigla SEPARATOR ', ')`,date_format(min(`c`.`data_cadastro`),'%d/%m/%Y') AS `DATE_FORMAT(MIN(c.data_cadastro), '%d/%m/%Y')`,date_format(max(`c`.`data_cadastro`),'%d/%m/%Y') AS `DATE_FORMAT(MAX(c.data_cadastro), '%d/%m/%Y')`,(case when (count(`c`.`id`) >= 3) then 'Muito frequente' else 'Frequente' end) AS `Name_exp_7` from (`conteudo_cultural` `c` join `estado` `e` on((`e`.`id` = `c`.`estado_id`))) where ((`c`.`data_cadastro` between (curdate() - interval 12 month) and curdate()) and exists(select 1 from `contribuicao` `x` where ((`x`.`conteudo_id` = `c`.`id`) and (`x`.`status` <> 'aprovado'))) is false) group by `c`.`tipo` having (count(`c`.`id`) >= 2) order by count(`c`.`id`) desc,`c`.`tipo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
