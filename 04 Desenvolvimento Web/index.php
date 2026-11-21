<?php
/* =====================================================================================
 * PROJETO: Tchê & Cultura — aplicação web em ARQUIVO ÚNICO (versão 4.2, 14/11/2026)
 * INTEGRANTES: Letícia Beatriz Silva de Morais, João Pedro Pires, Guilherme Sakagami,
 *              Eduardo Yonegura e Carlos Eduardo
 *
 * Este arquivo contém tudo: a API em PHP (PDO + MySQL), o HTML, o CSS e o JavaScript.
 *   - Acesso normal (index.php)            -> devolve a página do site
 *   - Acesso com ?api=nome_da_acao         -> devolve JSON (usado pelo JavaScript da página)
 *
 * Banco: tche_cultura_v4.sql (na mesma pasta). Se o banco ainda não existir, a própria
 *        página oferece o botão "Instalar banco de dados" (executa as seções 1 a 5 do script).
 * Requisitos: PHP 8.0+ com pdo_mysql e mysqli, MySQL 8.0.16+.
 * Referência: Requisitos v3.2 — cada ação da API indica o RF que atende.
 *
 * MAPA DO ARQUIVO (procure pelos banners "PARTE N"):
 *   PARTE 1  Configuração e valores permitidos
 *   PARTE 2  Conexão, resposta JSON e erros
 *   PARTE 3  Validação de entrada
 *   PARTE 4  Sessão e permissões
 *   PARTE 5  Consulta-base do acervo
 *   PARTE 6  Instalação do banco
 *   PARTE 7  Geradores das atividades (quiz, jogo da memória e quebra-cabeça)
 *   PARTE 8  Upload de materiais do docente
 *   PARTE 9  Ações da API (api2 — switch com todas as ações)
 *   PARTE 10 HTML e CSS da página
 *   PARTE 11 JavaScript: navegação, mapa, acervo e pesquisa
 *   PARTE 12 JavaScript: conta, contribuição, painel admin e atividades
 * ===================================================================================== */

declare(strict_types=1);

/* =====================================================================================
 * PARTE 1 — CONFIGURAÇÃO E VALORES PERMITIDOS
 * ===================================================================================== */

// Ajuste aqui se o seu MySQL tiver outra senha/porta. No XAMPP o padrão é root sem senha.
define('DB_HOST', getenv('TCHE_DB_HOST') ?: 'localhost');
define('DB_PORT', (int)(getenv('TCHE_DB_PORT') ?: 3306));
define('DB_NAME', 'tche_cultura');
define('DB_USER', getenv('TCHE_DB_USER') ?: 'root');
define('DB_PASS', getenv('TCHE_DB_PASS') !== false ? (string)getenv('TCHE_DB_PASS') : '');
define('DB_SOCKET', getenv('TCHE_DB_SOCKET') ?: '');
define('SQL_ARQUIVO', __DIR__ . '/tche_cultura_v4.sql');

// Valores permitidos — iguais às restrições CHECK do banco v4
const CATEGORIAS = ['Culinária', 'Música', 'Literatura', 'Variação Linguística', 'Ponto Turístico',
    'Acontecimento Histórico', 'Incidência Nominal', 'Curiosidade', 'Artigo Acadêmico'];
const TIPOS_ATIVIDADE = ['quiz', 'jogo da memória', 'quebra-cabeça', 'dinâmica de aula'];
const TIPOS_RECURSO = ['material pedagógico', 'plano de aula', 'vídeo-aula', 'link externo', 'mapa externo'];

// Publicado = cadastrado pela equipe (sem contribuição) ou contribuição aprovada (UC-02)
const SQL_PUBLICADO = "(ct.conteudo_id IS NULL OR ct.status = 'aprovado')";

// Upload de materiais (RF-18): extensão aceita => tipos de conteúdo que o arquivo pode ter de verdade
const UPLOAD_PASTA = 'arquivos/materiais';
const UPLOAD_MAX_BYTES = 10485760; // 10 MB
const UPLOAD_TIPOS = [
    'pdf'  => ['application/pdf'],
    'txt'  => ['text/plain'],
    'png'  => ['image/png'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'webp' => ['image/webp'],
    'mp3'  => ['audio/mpeg'],
    'mp4'  => ['video/mp4'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'odt'  => ['application/vnd.oasis.opendocument.text', 'application/zip'],
    'odp'  => ['application/vnd.oasis.opendocument.presentation', 'application/zip'],
];

session_start();

if (isset($_GET['api'])) {
    api((string)$_GET['api']);
    exit;
}

/* =====================================================================================
 * PARTE 2 — CONEXÃO, RESPOSTA JSON E ERROS
 * ===================================================================================== */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = DB_SOCKET !== ''
            ? 'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=utf8mb4'
            : 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET lc_time_names = 'pt_BR'");
    }
    return $pdo;
}

function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
}

class ErroApi extends Exception
{
}

/** Aceita JSON (fetch comum) e formulário/multipart (envio de arquivo). */
function corpo(): array
{
    $json = json_decode((string)file_get_contents('php://input'), true);
    return is_array($json) ? $json : $_POST;
}

/** Converte erros do MySQL em mensagens legíveis. 1644 = SIGNAL das procedures e do trigger. */
function mensagem_pdo(PDOException $e): string
{
    $info = $e->errorInfo ?? [];
    $codigo = (int)($info[1] ?? 0);
    if ($codigo === 1644) {
        $msg = preg_replace('/^Erro:\s*/u', '', (string)$info[2]);
        return mb_strtoupper(mb_substr($msg, 0, 1)) . mb_substr($msg, 1);
    }
    if ($codigo === 1062) return 'Registro duplicado.';
    if ($codigo === 1452) return 'Registro relacionado não encontrado.';
    if ($codigo === 3819) return 'Valor não permitido pelas regras do banco.';
    return 'Erro no banco de dados: ' . $e->getMessage();
}

/* =====================================================================================
 * PARTE 3 — VALIDAÇÃO DE ENTRADA
 * ===================================================================================== */

function texto(array $d, string $campo, int $max, bool $obrigatorio = true): ?string
{
    $rotulos = ['nome' => 'Nome', 'email' => 'E-mail', 'data_nascimento' => 'Data de nascimento',
        'telefone' => 'Telefone', 'instituicao' => 'Instituição', 'nivel' => 'Ano/Período',
        'tempo' => 'Tempo na área', 'certificado' => 'Formação/Certificado', 'linha' => 'Linha de pesquisa',
        'titulo' => 'Título', 'texto' => 'Texto', 'texto_tecnico' => 'Texto técnico', 'referencias' => 'Referências',
        'autor' => 'Autor', 'comentario' => 'Comentário', 'pergunta' => 'Mensagem', 'motivo' => 'Motivo',
        'endereco' => 'Endereço', 'descricao' => 'Descrição', 'termo' => 'Termo', 'resposta' => 'Resposta'];
    $rotulo = $rotulos[$campo] ?? $campo;
    $v = trim((string)($d[$campo] ?? ''));
    if ($v === '') {
        if ($obrigatorio) throw new ErroApi("Preencha o campo \"$rotulo\".");
        return null;
    }
    if (mb_strlen($v) > $max) throw new ErroApi("O campo \"$rotulo\" aceita no máximo $max caracteres.");
    return $v;
}

/* =====================================================================================
 * PARTE 4 — SESSÃO E PERMISSÕES
 * ===================================================================================== */

function usuario_sessao(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function exigir_login(): array
{
    $u = usuario_sessao();
    if (!$u) throw new ErroApi('Faça login para continuar.');
    return $u;
}

function exigir_admin(): array
{
    $u = exigir_login();
    if (empty($u['admin'])) throw new ErroApi('Apenas administradores podem acessar esta área.');
    return $u;
}

function exigir_docente(): array
{
    $u = exigir_login();
    if (empty($u['docente'])) throw new ErroApi('Apenas docentes podem fazer isso.');
    return $u;
}

/** Perfil do usuário pela tabela de especialização (RF-22). */
function carregar_usuario(int $id): ?array
{
    $st = db()->prepare(
        "SELECT u.id, u.nome, u.email, u.telefone, u.data_nascimento, u.classificacao,
                EXISTS(SELECT 1 FROM administrador a WHERE a.usuario_id = u.id) AS admin,
                EXISTS(SELECT 1 FROM estudante e WHERE e.usuario_id = u.id) AS estudante,
                EXISTS(SELECT 1 FROM docente d WHERE d.usuario_id = u.id) AS docente,
                EXISTS(SELECT 1 FROM pesquisador p WHERE p.usuario_id = u.id) AS pesquisador
         FROM usuario u WHERE u.id = ?"
    );
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) return null;
    $u['id'] = (int)$u['id'];
    foreach (['admin', 'estudante', 'docente', 'pesquisador'] as $k) $u[$k] = (bool)$u[$k];
    $u['tecnico'] = $u['docente'] || $u['pesquisador'] || $u['admin']; // vê o texto técnico (RF-21)
    return $u;
}

/* =====================================================================================
 * PARTE 5 — CONSULTA-BASE DO ACERVO
 * ===================================================================================== */

/** Conteúdo + estado + título da categoria + situação na moderação. */
function sql_acervo(): string
{
    return "SELECT c.id, c.tipo, c.data_cadastro, c.texto_simplificado, c.texto_tecnico, c.referencias,
                   c.estado_id, e.nome AS estado, e.sigla, e.regiao,
                   COALESCE(cu.nome_prato, m.nome, l.titulo, v.expressao, p.nome, ah.titulo, i.termo, cr.titulo, ar.titulo) AS titulo,
                   COALESCE(l.genero, cr.categoria) AS subcategoria,
                   CASE WHEN ct.conteudo_id IS NULL THEN 'oficial' ELSE ct.status END AS situacao,
                   ct.usuario_id AS contribuidor_id
            FROM conteudo_cultural c
            INNER JOIN estado e ON e.id = c.estado_id
            LEFT JOIN culinaria cu ON cu.conteudo_id = c.id
            LEFT JOIN musica m ON m.conteudo_id = c.id
            LEFT JOIN literatura l ON l.conteudo_id = c.id
            LEFT JOIN variacao_linguistica v ON v.conteudo_id = c.id
            LEFT JOIN ponto_turistico p ON p.conteudo_id = c.id
            LEFT JOIN acontecimento_historico ah ON ah.conteudo_id = c.id
            LEFT JOIN incidencia_nominal i ON i.conteudo_id = c.id
            LEFT JOIN curiosidade cr ON cr.conteudo_id = c.id
            LEFT JOIN artigo_academico ar ON ar.conteudo_id = c.id
            LEFT JOIN contribuicao ct ON ct.conteudo_id = c.id";
}

/** Só a equipe e o próprio autor veem contribuição em revisão (UC-02). */
function conteudo_visivel(int $id): array
{
    $st = db()->prepare(sql_acervo() . " WHERE c.id = ?");
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) throw new ErroApi('Conteúdo não encontrado.');
    $u = usuario_sessao();
    $visivel = in_array($c['situacao'], ['oficial', 'aprovado'], true)
        || ($u && ((int)$c['contribuidor_id'] === $u['id'] || $u['admin']));
    if (!$visivel) throw new ErroApi('Este conteúdo ainda está em revisão.');
    return $c;
}

/* =====================================================================================
 * PARTE 6 — INSTALAÇÃO DO BANCO
 * ===================================================================================== */

/** Executa as seções 1 a 5 do tche_cultura_v4.sql (estrutura, dados e objetos). */
function instalar_banco(): void
{
    if (!is_file(SQL_ARQUIVO)) throw new ErroApi('Arquivo tche_cultura_v4.sql não encontrado na pasta do site.');
    $sql = (string)file_get_contents(SQL_ARQUIVO);
    $corte = strpos($sql, '-- SEÇÃO 6');           // os testes das seções 6 e 7 não entram no site
    if ($corte !== false) $sql = substr($sql, 0, $corte);
    $sql = preg_replace('/^\s*DELIMITER\s+\S+\s*$/mi', '', $sql);
    $sql = str_replace('$$', ';', $sql);

    mysqli_report(MYSQLI_REPORT_OFF);
    $m = DB_SOCKET !== '' ? @new mysqli(null, DB_USER, DB_PASS, '', 0, DB_SOCKET) : @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($m->connect_errno) throw new ErroApi('Não foi possível conectar ao MySQL: ' . $m->connect_error);
    $m->set_charset('utf8mb4');
    if (!$m->multi_query($sql)) throw new ErroApi('Erro ao instalar: ' . $m->error);
    do {
        if ($r = $m->store_result()) $r->free();
    } while ($m->more_results() && $m->next_result());
    if ($m->errno) throw new ErroApi('Erro ao instalar: ' . $m->error);
    $m->close();
}

/* =====================================================================================
 * PARTE 7 — GERADORES DAS ATIVIDADES (RF-11 e RF-13)
 * Tudo é sorteado no servidor a partir do acervo; o gabarito fica na sessão, nunca no
 * navegador, para o estudante não conseguir ver a resposta pelo código da página.
 * ===================================================================================== */

/** Conteúdos publicados sorteados, com a região do conteúdo-base na frente. */
function acervo_sorteado(int $limite, ?int $estadoBase): array
{
    $pdo = db();
    $sql = "SELECT * FROM (" . sql_acervo() . " WHERE " . SQL_PUBLICADO . ") a ";
    if ($estadoBase) {
        $st = $pdo->prepare($sql . " ORDER BY (a.regiao = (SELECT regiao FROM estado WHERE id = ?)) DESC, RAND() LIMIT $limite");
        $st->execute([$estadoBase]);
        return $st->fetchAll();
    }
    return $pdo->query($sql . " ORDER BY RAND() LIMIT $limite")->fetchAll();
}

/** QUIZ: perguntas "De qual estado é X?" com 4 alternativas. */
function gerar_perguntas(int $n, ?int $estadoBase): array
{
    $lista = acervo_sorteado(40, $estadoBase);
    $estados = db()->query("SELECT id, nome FROM estado")->fetchAll();
    $perguntas = [];
    foreach ($lista as $c) {
        if (count($perguntas) >= $n) break;
        $erradas = array_values(array_filter($estados, fn($e) => (int)$e['id'] !== (int)$c['estado_id']));
        shuffle($erradas);
        $opcoes = array_merge([$c['estado']], array_column(array_slice($erradas, 0, 3), 'nome'));
        shuffle($opcoes);
        $perguntas[] = ['pergunta' => "De qual estado é: {$c['titulo']} ({$c['tipo']})?",
            'opcoes' => $opcoes, 'correta' => array_search($c['estado'], $opcoes, true),
            'explicacao' => "{$c['titulo']} é de {$c['estado']} ({$c['sigla']}): {$c['texto_simplificado']}"];
    }
    return $perguntas;
}

/**
 * JOGO DA MEMÓRIA: cada par liga um conteúdo ao seu estado. Os estados precisam ser
 * diferentes entre si, senão existiriam duas cartas iguais e o par ficaria ambíguo.
 */
function gerar_pares_memoria(int $n, ?int $estadoBase): array
{
    $pares = [];
    $usados = [];
    foreach (acervo_sorteado(80, $estadoBase) as $c) {
        if (count($pares) >= $n) break;
        if (isset($usados[$c['estado_id']])) continue;
        $usados[$c['estado_id']] = true;
        $pares[] = ['chave' => 'par' . count($pares), 'conteudo_id' => (int)$c['id'],
            'titulo' => $c['titulo'], 'categoria' => $c['tipo'],
            'estado' => $c['estado'], 'sigla' => $c['sigla'], 'regiao' => $c['regiao'],
            'dica' => $c['texto_simplificado']];
    }
    return $pares;
}

/** QUEBRA-CABEÇA: mosaico grade x grade embaralhado (nunca já montado). */
function gerar_quebracabeca(int $grade): array
{
    $certo = range(0, $grade * $grade - 1);
    $ordem = $certo;
    do {
        shuffle($ordem);
    } while ($ordem === $certo);
    return ['grade' => $grade, 'ordem_inicial' => $ordem];
}

/** Pontuação 0–100 das atividades, conforme a regra do CHECK ck_tentativa_pontuacao. */
function pontuacao_valida(int|float $p): int
{
    return max(0, min(100, (int)round($p)));
}

/* =====================================================================================
 * PARTE 8 — UPLOAD DE MATERIAIS DO DOCENTE (RF-18)
 * ===================================================================================== */

/**
 * Grava o arquivo enviado em arquivos/materiais e devolve o caminho que vai para o banco.
 * Devolve null quando nenhum arquivo foi escolhido (o docente pode anexar só um link).
 */
function salvar_upload(string $campo): ?string
{
    $f = $_FILES[$campo] ?? null;
    if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (int)$f['size'] > UPLOAD_MAX_BYTES) {
        throw new ErroApi('O arquivo passou do limite de 10 MB.');
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string)$f['tmp_name'])) {
        throw new ErroApi('Falha no envio do arquivo. Tente novamente.');
    }

    $ext = strtolower((string)pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!isset(UPLOAD_TIPOS[$ext])) {
        throw new ErroApi('Tipo de arquivo não aceito. Use: ' . implode(', ', array_keys(UPLOAD_TIPOS)) . '.');
    }
    // confere o conteúdo real do arquivo, porque a extensão do nome pode estar trocada
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$f['tmp_name']);
    if (!in_array($mime, UPLOAD_TIPOS[$ext], true)) {
        throw new ErroApi("O conteúdo do arquivo não é um .$ext de verdade (detectado: $mime).");
    }

    $pasta = __DIR__ . '/' . UPLOAD_PASTA;
    if (!is_dir($pasta) && !@mkdir($pasta, 0775, true)) {
        throw new ErroApi('Não foi possível criar a pasta ' . UPLOAD_PASTA . '. Dê permissão de escrita a ela.');
    }
    $base = trim((string)preg_replace('/[^a-z0-9]+/', '-', mb_strtolower((string)pathinfo((string)$f['name'], PATHINFO_FILENAME))), '-');
    $nome = mb_substr($base ?: 'material', 0, 40) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file((string)$f['tmp_name'], $pasta . '/' . $nome)) {
        throw new ErroApi('Não foi possível gravar o arquivo enviado na pasta ' . UPLOAD_PASTA . '.');
    }
    return UPLOAD_PASTA . '/' . $nome;
}

/** true quando o arquivo citado no banco existe na pasta do site (evita link quebrado). */
function arquivo_existe(?string $caminho, string $pastaPadrao): bool
{
    if ($caminho === null || $caminho === '') return false;
    if (preg_match('#^https?://#i', $caminho)) return true;
    $rel = str_contains($caminho, '/') ? $caminho : $pastaPadrao . '/' . $caminho;
    if (str_contains($rel, '..')) return false;
    return is_file(__DIR__ . '/' . $rel);
}
/* =====================================================================================
 * PARTE 9 — AÇÕES DA API
 * Uma única porta de entrada: index.php?api=<ação>. Tudo responde JSON.
 * Grupos de ações, na ordem em que aparecem no switch:
 *   infraestrutura ....... status, instalar
 *   leitura pública ...... estados, conteudos, conteudo, pesquisar, estatisticas
 *   autenticação ......... cadastro, login, logout, perfil
 *   interações ........... favoritar, avaliar, quiz
 *   atividades ........... atividade, atividade_resultado
 *   ajuda (RF-08) ........ ajuda, minhas_mensagens
 *   contribuição ......... contribuir, enviar_rascunho, criar_atividade, anexar_recurso
 *   administração ........ admin_*
 * ===================================================================================== */

function api(string $acao): void
{
    try {
        $u = usuario_sessao();
        $uid = $u['id'] ?? 0;

        switch ($acao) {

            // ---------------------------------------------------------- infraestrutura
            case 'status':
                try {
                    db()->query('SELECT 1 FROM usuario LIMIT 1');
                    if ($u) $_SESSION['usuario'] = $u = carregar_usuario($u['id']);
                    responder(['ok' => true, 'banco' => true, 'usuario' => $u]);
                } catch (PDOException $e) {
                    $cod = (int)($e->errorInfo[1] ?? 0);
                    $semBanco = $cod === 1049 || $cod === 1146 || str_contains($e->getMessage(), '1049');
                    responder(['ok' => true, 'banco' => false,
                        'motivo' => $semBanco ? 'nao_instalado' : 'conexao',
                        'sql_encontrado' => is_file(SQL_ARQUIVO),
                        'erro' => $e->getMessage()]);
                }
                return;

            case 'instalar':
                try {
                    db()->query('SELECT 1 FROM usuario LIMIT 1');
                    throw new ErroApi('O banco de dados já está instalado.');
                } catch (PDOException $e) {
                    // banco ausente: segue para a instalação
                }
                instalar_banco();
                responder(['ok' => true]);
                return;

            // ---------------------------------------------------------- leitura pública
            case 'estados': // RF-01
                responder(['ok' => true, 'estados' => db()->query(
                    "SELECT id, nome, sigla, regiao FROM estado ORDER BY nome")->fetchAll()]);
                return;

            case 'mapa': // RF-01: desenho das 27 unidades federativas (E2 do UC-01 quando indisponível)
                $arq = __DIR__ . '/mapa_brasil.json';
                $mapa = is_file($arq) ? json_decode((string)file_get_contents($arq), true) : null;
                if (!$mapa || empty($mapa['locations'])) throw new ErroApi('Mapa indisponível.');
                responder(['ok' => true, 'mapa' => $mapa]);
                return;

            case 'conteudos': // RF-02: só o conteúdo publicado aparece no site
                $st = db()->prepare(
                    "SELECT a.id, a.tipo, a.titulo, a.subcategoria, a.texto_simplificado AS texto, a.data_cadastro,
                            a.estado_id, a.estado, a.sigla, a.regiao, a.situacao,
                            (SELECT ROUND(AVG(av.nota), 1) FROM avaliacao av WHERE av.conteudo_id = a.id) AS media,
                            (SELECT COUNT(*) FROM avaliacao av WHERE av.conteudo_id = a.id) AS total_avaliacoes,
                            (SELECT COUNT(*) FROM favorito f WHERE f.conteudo_id = a.id AND f.usuario_id = :u) AS favorito
                     FROM (" . sql_acervo() . " WHERE " . SQL_PUBLICADO . ") a
                     ORDER BY a.id");
                $st->execute([':u' => $uid]);
                $lista = $st->fetchAll();
                foreach ($lista as &$c) {
                    $c['id'] = (int)$c['id'];
                    $c['estado_id'] = (int)$c['estado_id'];
                    $c['total_avaliacoes'] = (int)$c['total_avaliacoes'];
                    $c['favorito'] = (int)$c['favorito'] > 0;
                    $c['media'] = $c['media'] !== null ? (float)$c['media'] : null;
                }
                responder(['ok' => true, 'conteudos' => $lista]);
                return;

            case 'conteudo': // RF-02, RF-10, RF-12, RF-14, RF-21
                $id = (int)($_GET['id'] ?? 0);
                $c = conteudo_visivel($id);
                $pdo = db();
                $st = $pdo->prepare(
                    "SELECT au.nome AS autor, l.genero, l.trecho, m.artista, m.genero_musical, m.letra, m.audio,
                            cu.receita, ah.periodo_historico, ah.local, p.localizacao, p.historia, p.imagem,
                            v.significado AS significado_expressao, v.exemplo_uso,
                            i.significado AS significado_termo, i.origem, cr.curiosidade, cr.categoria,
                            ar.resumo, ar.area_pesquisa, ar.arquivo
                     FROM conteudo_cultural c
                     LEFT JOIN literatura l ON l.conteudo_id = c.id
                     LEFT JOIN autor au ON au.id = l.autor_id
                     LEFT JOIN musica m ON m.conteudo_id = c.id
                     LEFT JOIN culinaria cu ON cu.conteudo_id = c.id
                     LEFT JOIN acontecimento_historico ah ON ah.conteudo_id = c.id
                     LEFT JOIN ponto_turistico p ON p.conteudo_id = c.id
                     LEFT JOIN variacao_linguistica v ON v.conteudo_id = c.id
                     LEFT JOIN incidencia_nominal i ON i.conteudo_id = c.id
                     LEFT JOIN curiosidade cr ON cr.conteudo_id = c.id
                     LEFT JOIN artigo_academico ar ON ar.conteudo_id = c.id
                     WHERE c.id = ?");
                $st->execute([$id]);
                $ficha = array_filter($st->fetch() ?: [], fn($x) => $x !== null && $x !== '');

                $st = $pdo->prepare("SELECT ingrediente FROM culinaria_ingrediente WHERE culinaria_id = ? ORDER BY ingrediente");
                $st->execute([$id]);
                $ingredientes = $st->fetchAll(PDO::FETCH_COLUMN);

                $st = $pdo->prepare("SELECT e.nome, e.sigla FROM acontecimento_estado ae INNER JOIN estado e ON e.id = ae.estado_id WHERE ae.acontecimento_id = ? ORDER BY e.nome");
                $st->execute([$id]);
                $estadosRel = $st->fetchAll();

                $st = $pdo->prepare("SELECT r.tipo, r.titulo, r.endereco, r.descricao, u.nome AS docente
                                     FROM recurso_complementar r LEFT JOIN usuario u ON u.id = r.docente_id
                                     WHERE r.conteudo_id = ? ORDER BY r.tipo, r.id");
                $st->execute([$id]);
                $recursos = $st->fetchAll();

                $st = $pdo->prepare("SELECT a.id, a.tipo, a.titulo, a.descricao, u.nome AS docente
                                     FROM atividade_interativa a LEFT JOIN usuario u ON u.id = a.docente_id
                                     WHERE a.conteudo_id = ? ORDER BY a.id");
                $st->execute([$id]);
                $atividades = $st->fetchAll();

                // vw_perfil_publico (João): nome abreviado, sem e-mail nem senha
                $st = $pdo->prepare(
                    "SELECT a.usuario_id, COALESCE(p.nome_exibicao, 'Equipe Tchê & Cultura') AS nome,
                            COALESCE(p.perfil, 'Administrador') AS classificacao, a.nota, a.data_avaliacao
                     FROM avaliacao a LEFT JOIN vw_perfil_publico p ON p.codigo = a.usuario_id
                     WHERE a.conteudo_id = ? ORDER BY a.data_avaliacao DESC");
                $st->execute([$id]);
                $avaliacoes = $st->fetchAll();

                $st = $pdo->prepare("SELECT fn_classificar_atualidade(?) AS atualidade,
                        (SELECT COUNT(*) FROM favorito WHERE usuario_id = ? AND conteudo_id = ?) AS favorito");
                $st->execute([$c['data_cadastro'], $uid, $id]);
                $extra = $st->fetch();

                // UC-02, passo 6: registra o acesso (o usuário é opcional)
                $pdo->prepare("INSERT INTO acesso (usuario_id, data_hora, pagina_visitada, conteudo_id) VALUES (?, NOW(), 'Conteúdo', ?)")
                    ->execute([$uid ?: null, $id]);

                // RF-21: texto técnico e referências só para docente, pesquisador e administrador
                if (!($u['tecnico'] ?? false)) { $c['texto_tecnico'] = null; $c['referencias'] = null; }

                responder(['ok' => true, 'conteudo' => $c + [
                    'id' => (int)$c['id'],
                    'atualidade' => $extra['atualidade'],
                    'favorito' => (int)$extra['favorito'] > 0,
                    // avisa o navegador se a foto, o PDF e o áudio citados no banco estão de fato na pasta
                    'imagem_disponivel' => arquivo_existe($ficha['imagem'] ?? null, 'imagens'),
                    'arquivo_disponivel' => arquivo_existe($ficha['arquivo'] ?? null, 'arquivos'),
                    'audio_disponivel' => arquivo_existe($ficha['audio'] ?? null, 'arquivos'),
                    'ficha' => $ficha,
                    'ingredientes' => $ingredientes,
                    'estados_relacionados' => $estadosRel,
                    'recursos' => $recursos,
                    'atividades' => $atividades,
                    'avaliacoes' => $avaliacoes,
                ]]);
                return;

            case 'pesquisar': // RF-03 / UC-03
                $termo = trim((string)($_GET['termo'] ?? ''));
                if (mb_strlen($termo) < 2) throw new ErroApi('Digite pelo menos 2 letras para pesquisar.');
                if (mb_strlen($termo) > 100) throw new ErroApi('Termo muito longo.');
                $pdo = db();
                $like = '%' . $termo . '%';
                $st = $pdo->prepare(
                    "SELECT a.id, a.tipo, a.titulo, a.texto_simplificado AS texto, a.estado, a.sigla, a.regiao
                     FROM (" . sql_acervo() . " WHERE " . SQL_PUBLICADO . ") a
                     LEFT JOIN literatura l ON l.conteudo_id = a.id
                     LEFT JOIN autor au ON au.id = l.autor_id
                     WHERE a.titulo LIKE :t1 OR a.texto_simplificado LIKE :t2 OR a.estado LIKE :t3
                        OR a.sigla = :t4 OR au.nome LIKE :t5
                     ORDER BY (a.titulo LIKE :t6) DESC, (a.estado LIKE :t7) DESC, a.titulo
                     LIMIT 40");
                $st->execute([':t1' => $like, ':t2' => $like, ':t3' => $like, ':t4' => $termo, ':t5' => $like, ':t6' => $like, ':t7' => $like]);
                $resultados = $st->fetchAll();
                $sugestoes = [];
                if (!$resultados) { // UC-03, E3.2: até cinco termos semelhantes
                    $st = $pdo->prepare("SELECT DISTINCT a.titulo FROM (" . sql_acervo() . " WHERE " . SQL_PUBLICADO . ") a
                                         WHERE a.titulo LIKE ? OR a.titulo LIKE ? OR SOUNDEX(a.titulo) = SOUNDEX(?)
                                         ORDER BY a.titulo LIMIT 5");
                    $st->execute(['%' . mb_substr($termo, 0, 4) . '%', '%' . mb_substr($termo, -4) . '%', $termo]);
                    $sugestoes = $st->fetchAll(PDO::FETCH_COLUMN);
                }
                // UC-03, passo 9: registra o termo pesquisado (RF-25)
                $pdo->prepare("INSERT INTO acesso (usuario_id, data_hora, pagina_visitada, termo_pesquisado) VALUES (?, NOW(), 'Pesquisa', ?)")
                    ->execute([$uid ?: null, mb_substr($termo, 0, 100)]);
                responder(['ok' => true, 'resultados' => $resultados, 'sugestoes' => $sugestoes]);
                return;

            case 'estatisticas': // seção "Acervo em Números" (views do João)
                $pdo = db();
                responder(['ok' => true,
                    'tipos' => $pdo->query("SELECT c.tipo, COUNT(*) AS quantidade FROM conteudo_cultural c
                                            LEFT JOIN contribuicao ct ON ct.conteudo_id = c.id
                                            WHERE " . SQL_PUBLICADO . " GROUP BY c.tipo ORDER BY quantidade DESC, c.tipo")->fetchAll(),
                    'estados' => $pdo->query("SELECT estado, sigla, regiao, total_conteudos FROM vw_movimento_por_estado
                                              ORDER BY total_conteudos DESC, estado LIMIT 6")->fetchAll(),
                    'ranking' => $pdo->query("SELECT p.nome_exibicao AS nome, MAX(t.pontuacao) AS melhor_pontuacao, COUNT(*) AS tentativas
                                              FROM tentativa t
                                              INNER JOIN atividade_interativa a ON a.id = t.atividade_id AND a.tipo = 'quiz inicial'
                                              INNER JOIN vw_perfil_publico p ON p.codigo = t.estudante_id
                                              GROUP BY p.codigo, p.nome_exibicao ORDER BY melhor_pontuacao DESC, tentativas ASC LIMIT 5")->fetchAll(),
                    'totais' => $pdo->query("SELECT
                            (SELECT COUNT(*) FROM conteudo_cultural c LEFT JOIN contribuicao ct ON ct.conteudo_id = c.id WHERE " . SQL_PUBLICADO . ") AS conteudos,
                            (SELECT COUNT(*) FROM vw_movimento_por_estado WHERE total_conteudos > 0) AS estados,
                            (SELECT COUNT(*) FROM usuario) AS usuarios,
                            (SELECT COUNT(*) FROM avaliacao) AS avaliacoes")->fetch(),
                ]);
                return;

            // ---------------------------------------------------------- autenticação (RF-22, RNF-04)
            case 'cadastro':
                $d = corpo();
                $nome = texto($d, 'nome', 100);
                $email = mb_strtolower(texto($d, 'email', 100));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ErroApi('Informe um e-mail válido.');
                $senha = (string)($d['senha'] ?? '');
                if (mb_strlen($senha) < 6) throw new ErroApi('A senha precisa ter pelo menos 6 caracteres.');
                $nasc = texto($d, 'data_nascimento', 10);
                $dt = DateTime::createFromFormat('!Y-m-d', $nasc);
                if (!$dt || $dt->format('Y-m-d') !== $nasc || $dt > new DateTime() || (int)$dt->format('Y') < 1900)
                    throw new ErroApi('Informe uma data de nascimento válida.');
                $telefone = texto($d, 'telefone', 20, false);

                $perfis = ['estudante' => 'Estudante', 'pesquisador' => 'Pesquisador',
                    'docente' => 'Docente', 'publico' => 'Público em Geral'];
                $perfil = (string)($d['perfil'] ?? '');
                if (!isset($perfis[$perfil])) throw new ErroApi('Escolha um perfil válido.');

                $pdo = db();
                $pdo->beginTransaction(); // usuário + especialização gravados juntos (ou nenhum)
                try {
                    // RNF-04: bcrypt com custo 10
                    $pdo->prepare("INSERT INTO usuario (nome, email, senha, telefone, data_nascimento, classificacao) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$nome, $email, password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]), $telefone, $nasc, $perfis[$perfil]]);
                    $novo = (int)$pdo->lastInsertId();
                    if ($perfil === 'estudante') {
                        $pdo->prepare("INSERT INTO estudante (usuario_id, instituicao, nivel_escolaridade) VALUES (?, ?, ?)")
                            ->execute([$novo, texto($d, 'instituicao', 100), texto($d, 'nivel', 50)]);
                    } elseif ($perfil === 'pesquisador') {
                        $pdo->prepare("INSERT INTO pesquisador (usuario_id, tempo_experiencia, instituicao_trabalho, linha_pesquisa) VALUES (?, ?, ?, ?)")
                            ->execute([$novo, texto($d, 'tempo', 50), texto($d, 'instituicao', 100), texto($d, 'linha', 100)]);
                    } elseif ($perfil === 'docente') {
                        $pdo->prepare("INSERT INTO docente (usuario_id, certificado_exp, instituicao_trabalho) VALUES (?, ?, ?)")
                            ->execute([$novo, texto($d, 'certificado', 255), texto($d, 'instituicao', 100)]);
                    } else {
                        $pdo->prepare("INSERT INTO publico_geral (usuario_id) VALUES (?)")->execute([$novo]);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    if ($e instanceof PDOException && (int)($e->errorInfo[1] ?? 0) === 1062)
                        throw new ErroApi('Este e-mail já está cadastrado. Use a opção Entrar.');
                    throw $e;
                }
                session_regenerate_id(true);
                $_SESSION['usuario'] = carregar_usuario($novo);
                responder(['ok' => true, 'usuario' => $_SESSION['usuario']]);
                return;

            case 'login':
                $d = corpo();
                $st = db()->prepare("SELECT id, senha FROM usuario WHERE email = ?");
                $st->execute([mb_strtolower(trim((string)($d['email'] ?? '')))]);
                $linha = $st->fetch();
                if (!$linha || !password_verify((string)($d['senha'] ?? ''), $linha['senha']))
                    throw new ErroApi('E-mail ou senha incorretos.');
                session_regenerate_id(true);
                $_SESSION['usuario'] = carregar_usuario((int)$linha['id']);
                responder(['ok' => true, 'usuario' => $_SESSION['usuario']]);
                return;

            case 'logout':
                $_SESSION = [];
                session_destroy();
                responder(['ok' => true]);
                return;

            case 'perfil':
                $u = exigir_login();
                $pdo = db();
                $st = $pdo->prepare("SELECT fn_total_contribuicoes_usuario(?)"); // function do Guilherme
                $st->execute([$uid]);
                $total = (int)$st->fetchColumn();

                $st = $pdo->prepare("SELECT a.id, a.titulo, a.tipo, a.estado, f.data_inclusao
                                     FROM favorito f INNER JOIN (" . sql_acervo() . ") a ON a.id = f.conteudo_id
                                     WHERE f.usuario_id = ? ORDER BY f.data_inclusao DESC");
                $st->execute([$uid]);
                $favoritos = $st->fetchAll();

                $st = $pdo->prepare("SELECT a.id, a.titulo, a.tipo, a.estado, ct.status, ct.data_envio, ct.motivo_rejeicao,
                                            ct.data_edicao IS NOT NULL AS editado
                                     FROM contribuicao ct INNER JOIN (" . sql_acervo() . ") a ON a.id = ct.conteudo_id
                                     WHERE ct.usuario_id = ? ORDER BY ct.data_envio DESC");
                $st->execute([$uid]);
                $contribuicoes = $st->fetchAll();

                $st = $pdo->prepare("SELECT a.titulo AS atividade, a.tipo, t.pontuacao, t.data_hora
                                     FROM tentativa t INNER JOIN atividade_interativa a ON a.id = t.atividade_id
                                     WHERE t.estudante_id = ? ORDER BY t.data_hora DESC LIMIT 10");
                $st->execute([$uid]);
                $tentativas = $st->fetchAll();

                $st = $pdo->prepare("SELECT a.id, a.tipo, a.titulo, x.titulo AS conteudo, x.id AS conteudo_id
                                     FROM atividade_interativa a LEFT JOIN (" . sql_acervo() . ") x ON x.id = a.conteudo_id
                                     WHERE a.docente_id = ? ORDER BY a.id DESC");
                $st->execute([$uid]);
                $minhasAtividades = $st->fetchAll();

                $detalhe = null;
                foreach ([
                    'estudante' => "SELECT instituicao, nivel_escolaridade FROM estudante WHERE usuario_id = ?",
                    'docente' => "SELECT instituicao_trabalho, certificado_exp FROM docente WHERE usuario_id = ?",
                    'pesquisador' => "SELECT instituicao_trabalho, tempo_experiencia, linha_pesquisa FROM pesquisador WHERE usuario_id = ?",
                    'administrador' => "SELECT area_responsabilidade, data_inicio FROM administrador WHERE usuario_id = ?",
                ] as $sql) {
                    $st = $pdo->prepare($sql);
                    $st->execute([$uid]);
                    if ($r = $st->fetch()) { $detalhe = $r; break; }
                }

                // RF-21: conteúdo da linha de pesquisa do pesquisador
                $linhaPesquisa = [];
                if ($u['pesquisador']) {
                    // palavras da linha de pesquisa (ex.: "História do Sul do Brasil") comparadas
                    // com a região, o tipo, a área do artigo e o título de cada conteúdo publicado
                    $linha = (string)($detalhe['linha_pesquisa'] ?? '');
                    preg_match_all('/[\p{L}-]{3,}/u', $linha, $m);
                    $ignorar = ['brasil', 'das', 'dos', 'para', 'com'];
                    $palavras = array_values(array_filter(array_unique(array_map('mb_strtolower', $m[0])),
                        fn($p) => !in_array($p, $ignorar, true)));
                    if ($palavras) {
                        $pontos = [];
                        $params = [];
                        foreach ($palavras as $p) {
                            $pontos[] = "(a.regiao LIKE ?) * 2 + (a.tipo LIKE ?) + (COALESCE(ar.area_pesquisa, '') LIKE ?) * 2 + (a.titulo LIKE ?)";
                            array_push($params, "%$p%", "%$p%", "%$p%", "%$p%");
                        }
                        $st = $pdo->prepare("SELECT a.id, a.tipo, a.titulo, a.estado, (" . implode(' + ', $pontos) . ") AS relevancia
                                             FROM (" . sql_acervo() . " WHERE " . SQL_PUBLICADO . ") a
                                             LEFT JOIN artigo_academico ar ON ar.conteudo_id = a.id
                                             HAVING relevancia > 0 AND (a.tipo <> 'Curiosidade' OR relevancia > 2)
                                             ORDER BY relevancia DESC, a.tipo, a.titulo LIMIT 12");
                        $st->execute($params);
                        $linhaPesquisa = $st->fetchAll();
                    }
                }

                // RF-08: atendimentos do usuário, com a resposta da IA ou do atendente humano
                $st = $pdo->prepare("SELECT a.id, a.canal, a.data_hora, a.topico, a.resposta, a.data_resposta, g.nome AS agente
                                     FROM atendimento a LEFT JOIN agente_ia g ON g.id = a.agente_id
                                     WHERE a.usuario_id = ? ORDER BY a.data_hora DESC LIMIT 10");
                $st->execute([$uid]);
                $mensagens = $st->fetchAll();

                responder(['ok' => true, 'usuario' => $u, 'detalhe' => $detalhe, 'total_contribuicoes' => $total,
                    'favoritos' => $favoritos, 'contribuicoes' => $contribuicoes, 'tentativas' => $tentativas,
                    'minhas_atividades' => $minhasAtividades, 'linha_pesquisa' => $linhaPesquisa,
                    'mensagens' => $mensagens]);
                return;

            // ---------------------------------------------------------- interações do usuário
            case 'favoritar': // RF-09
                exigir_login();
                $id = (int)(corpo()['conteudo_id'] ?? 0);
                conteudo_visivel($id);
                $pdo = db();
                $st = $pdo->prepare("DELETE FROM favorito WHERE usuario_id = ? AND conteudo_id = ?");
                $st->execute([$uid, $id]);
                $favorito = false;
                if ($st->rowCount() === 0) {
                    $pdo->prepare("INSERT INTO favorito (usuario_id, conteudo_id, data_inclusao) VALUES (?, ?, NOW())")->execute([$uid, $id]);
                    $favorito = true;
                }
                responder(['ok' => true, 'favorito' => $favorito]);
                return;

            case 'avaliar': // RF-06 (nota) + RF-07 (feedback ou correção opcional, para a equipe)
                exigir_login();
                $d = corpo();
                $id = (int)($d['conteudo_id'] ?? 0);
                conteudo_visivel($id);
                $nota = (int)($d['nota'] ?? 0);
                if ($nota < 1) throw new ErroApi('Escolha de 1 a 5 estrelas.');
                $comentario = texto($d, 'comentario', 2000, false);
                $tipoFb = (string)($d['tipo_feedback'] ?? 'comentário');
                if ($comentario !== null && !in_array($tipoFb, ['comentário', 'correção'], true)) throw new ErroApi('Tipo de feedback inválido.');
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $st = $pdo->prepare("SELECT COUNT(*) FROM avaliacao WHERE usuario_id = ? AND conteudo_id = ?");
                    $st->execute([$uid, $id]);
                    if ((int)$st->fetchColumn() > 0) {
                        if ($nota > 5) throw new ErroApi('A nota deve ser de 1 a 5');
                        $pdo->prepare("UPDATE avaliacao SET nota = ?, data_avaliacao = NOW() WHERE usuario_id = ? AND conteudo_id = ?")
                            ->execute([$nota, $uid, $id]);
                    } else { // a nota de 1 a 5 é validada pelo trigger trg_valida_nota
                        $pdo->prepare("INSERT INTO avaliacao (usuario_id, conteudo_id, nota, data_avaliacao) VALUES (?, ?, ?, NOW())")
                            ->execute([$uid, $id, $nota]);
                    }
                    if ($comentario !== null) {
                        $pdo->prepare("INSERT INTO feedback (usuario_id, conteudo_id, tipo, texto, data_envio, status) VALUES (?, ?, ?, ?, NOW(), 'pendente')")
                            ->execute([$uid, $id, $tipoFb, $comentario]);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
                }
                responder(['ok' => true, 'feedback' => $comentario !== null]);
                return;

            case 'quiz': // RF-05: Quiz do Blue (quiz inicial); a pontuação vai de 0 a 100
                $acertos = max(0, min(5, (int)(corpo()['acertos'] ?? 0)));
                $pdo = db();
                $aid = (int)$pdo->query("SELECT id FROM atividade_interativa WHERE tipo = 'quiz inicial' ORDER BY id LIMIT 1")->fetchColumn();
                $salvo = false;
                if ($u && $u['estudante'] && $aid) {
                    $pdo->prepare("INSERT IGNORE INTO tentativa (estudante_id, atividade_id, data_hora, pontuacao) VALUES (?, ?, NOW(), ?)")
                        ->execute([$uid, $aid, $acertos * 20]);
                    $salvo = true;
                }
                responder(['ok' => true, 'salvo' => $salvo]);
                return;

            /* ------------------------------------------------------------------------------
             * ATIVIDADES INTERATIVAS (RF-11 e RF-13)
             * Três tipos jogáveis: quiz, jogo da memória e quebra-cabeça. O conteúdo das
             * três é montado aqui, no servidor, e o gabarito fica na sessão: o navegador
             * recebe só o que precisa desenhar na tela.
             * ------------------------------------------------------------------------------ */
            case 'atividade':
                $id = (int)($_GET['id'] ?? 0);
                $st = db()->prepare("SELECT id, tipo, titulo, descricao, conteudo_id FROM atividade_interativa WHERE id = ?");
                $st->execute([$id]);
                $a = $st->fetch();
                if (!$a) throw new ErroApi('Atividade não encontrada.');
                if ($a['tipo'] === 'dinâmica de aula') throw new ErroApi('Esta é uma dinâmica para o docente aplicar em sala.');
                $a['id'] = (int)$a['id'];
                $a['conteudo_id'] = $a['conteudo_id'] !== null ? (int)$a['conteudo_id'] : null;

                $estadoBase = null;
                if ($a['conteudo_id']) {
                    $st = db()->prepare("SELECT estado_id FROM conteudo_cultural WHERE id = ?");
                    $st->execute([$a['conteudo_id']]);
                    $estadoBase = (int)$st->fetchColumn() ?: null;
                }

                if ($a['tipo'] === 'jogo da memória') {
                    $pares = gerar_pares_memoria(6, $estadoBase);
                    if (count($pares) < 4) throw new ErroApi('O acervo ainda não tem conteúdo suficiente para o jogo da memória.');
                    // duas cartas por par: a do conteúdo e a do estado dele
                    $cartas = [];
                    foreach ($pares as $p) {
                        $cartas[] = ['carta' => $p['chave'] . 'a', 'par' => $p['chave'], 'lado' => 'conteudo',
                            'face' => $p['titulo'], 'rodape' => $p['categoria']];
                        $cartas[] = ['carta' => $p['chave'] . 'b', 'par' => $p['chave'], 'lado' => 'estado',
                            'face' => $p['estado'], 'rodape' => $p['sigla'] . ' · ' . $p['regiao']];
                    }
                    shuffle($cartas);
                    $_SESSION['atividade'] = ['id' => $a['id'], 'tipo' => $a['tipo'],
                        'chaves' => array_column($pares, 'chave'), 'dicas' => array_column($pares, 'dica', 'chave'),
                        'pares' => array_column($pares, 'titulo', 'chave'), 'estados' => array_column($pares, 'estado', 'chave')];
                    responder(['ok' => true, 'atividade' => $a, 'cartas' => $cartas, 'total_pares' => count($pares)]);
                    return;
                }

                if ($a['tipo'] === 'quebra-cabeça') {
                    $qc = gerar_quebracabeca(3);
                    // usa a foto do conteúdo quando ela existe na pasta; se não, o site desenha a arte
                    $st = db()->prepare("SELECT imagem FROM ponto_turistico WHERE conteudo_id = ?");
                    $st->execute([$a['conteudo_id']]);
                    $img = (string)$st->fetchColumn();
                    $_SESSION['atividade'] = ['id' => $a['id'], 'tipo' => $a['tipo'], 'grade' => $qc['grade']];
                    responder(['ok' => true, 'atividade' => $a,
                        'imagem' => arquivo_existe($img, 'imagens') ? (str_contains($img, '/') ? $img : 'imagens/' . $img) : null] + $qc);
                    return;
                }

                $perguntas = gerar_perguntas(5, $estadoBase);
                $_SESSION['atividade'] = ['id' => $a['id'], 'tipo' => 'quiz',
                    'gabarito' => array_column($perguntas, 'correta'), 'explicacoes' => array_column($perguntas, 'explicacao')];
                foreach ($perguntas as &$p) unset($p['correta'], $p['explicacao']);
                responder(['ok' => true, 'atividade' => $a, 'perguntas' => $perguntas]);
                return;

            case 'atividade_resultado':
                $d = corpo();
                $s = $_SESSION['atividade'] ?? null;
                if (!$s || (int)($d['atividade_id'] ?? 0) !== $s['id']) throw new ErroApi('Abra a atividade novamente.');
                $tipo = $s['tipo'];
                $detalhe = [];

                if ($tipo === 'jogo da memória') {
                    // o navegador manda os pares que achou; o servidor confere se são os pares sorteados
                    $achados = array_values(array_unique(array_map('strval', (array)($d['pares'] ?? []))));
                    $validos = array_values(array_intersect($achados, $s['chaves']));
                    $erros = max(0, min(60, (int)($d['erros'] ?? 0)));
                    $completo = count($validos) === count($s['chaves']);
                    // terminar o jogo vale 100 e cada erro desconta 5, com piso de 40 pontos
                    $pont = $completo ? pontuacao_valida(max(40, 100 - 5 * $erros))
                        : pontuacao_valida(count($validos) * 50 / max(1, count($s['chaves'])));
                    $detalhe = ['pares_certos' => count($validos), 'total_pares' => count($s['chaves']),
                        'erros' => $erros, 'completo' => $completo,
                        'dicas' => array_map(fn($k) => ['titulo' => $s['pares'][$k], 'estado' => $s['estados'][$k], 'dica' => $s['dicas'][$k]], $s['chaves'])];
                } elseif ($tipo === 'quebra-cabeça') {
                    $n = $s['grade'] * $s['grade'];
                    $ordem = array_map('intval', (array)($d['ordem'] ?? []));
                    if (count($ordem) !== $n || array_diff(range(0, $n - 1), $ordem)) throw new ErroApi('Montagem inválida: refaça o quebra-cabeça.');
                    $certas = 0;
                    foreach ($ordem as $pos => $peca) if ($pos === $peca) $certas++;
                    $mov = max(0, min(999, (int)($d['movimentos'] ?? 0)));
                    $completo = $certas === $n;
                    // montar tudo vale 100, com desconto leve a partir de 12 trocas; piso de 60
                    $pont = $completo ? pontuacao_valida(max(60, 100 - 2 * max(0, $mov - 12)))
                        : pontuacao_valida(min(55, $certas * 100 / $n));
                    $detalhe = ['pecas_certas' => $certas, 'total_pecas' => $n, 'movimentos' => $mov, 'completo' => $completo];
                } else {
                    $resp = array_map('intval', (array)($d['respostas'] ?? []));
                    $acertos = 0;
                    foreach ($s['gabarito'] as $i => $g) if (($resp[$i] ?? -1) === $g) $acertos++;
                    $pont = pontuacao_valida($acertos * 100 / max(1, count($s['gabarito'])));
                    $detalhe = ['acertos' => $acertos, 'total' => count($s['gabarito']),
                        'gabarito' => $s['gabarito'], 'explicacoes' => $s['explicacoes']];
                }

                $salvo = false;
                if ($u && $u['estudante']) {
                    db()->prepare("INSERT IGNORE INTO tentativa (estudante_id, atividade_id, data_hora, pontuacao) VALUES (?, ?, NOW(), ?)")
                        ->execute([$uid, $s['id'], $pont]);
                    $salvo = true;
                }
                unset($_SESSION['atividade']);
                responder(['ok' => true, 'tipo' => $tipo, 'pontuacao' => $pont, 'salvo' => $salvo] + $detalhe);
                return;

            /* ------------------------------------------------------------------------------
             * CENTRAL DE AJUDA (RF-08)
             * Canal de IA: o Tchê Bot procura a pergunta no acervo e responde na hora.
             * Canal humano: a mensagem fica pendente e um administrador responde pelo
             * painel; a resposta aparece em "Minhas mensagens", no perfil do usuário.
             * ------------------------------------------------------------------------------ */
            case 'ajuda':
                exigir_login();
                $d = corpo();
                $canal = (string)($d['canal'] ?? '');
                if (!in_array($canal, ['agente de IA', 'atendente humano'], true)) throw new ErroApi('Escolha como quer ser atendido.');
                $pergunta = texto($d, 'pergunta', 500);
                $pdo = db();
                $resposta = null;
                $relacionados = [];
                $agente = null;
                if ($canal === 'agente de IA') {
                    $agente = $pdo->query("SELECT id, nome FROM agente_ia ORDER BY id LIMIT 1")->fetch();
                    if (!$agente) throw new ErroApi('O agente de IA não está disponível. Escolha o atendente humano.');
                    // busca no acervo as palavras da pergunta (4 letras ou mais)
                    preg_match_all('/[\p{L}]{4,}/u', mb_strtolower($pergunta), $m);
                    $comuns = ['quero', 'saber', 'sobre', 'como', 'qual', 'quais', 'onde', 'quando', 'porque', 'para',
                        'pelo', 'pela', 'você', 'vocês', 'isso', 'esse', 'essa', 'este', 'esta', 'tem', 'existe', 'fala',
                        'conte', 'contar', 'gostaria', 'pode', 'poderia', 'ajuda', 'ajudar', 'brasil', 'cultura'];
                    $palavras = array_slice(array_values(array_diff(array_unique($m[0]), $comuns)), 0, 6);
                    if ($palavras) {
                        // título vale 3 pontos, estado 2 e texto 1
                        $cond = implode(' + ', array_fill(0, count($palavras), "(a.titulo LIKE ?) * 3 + (a.estado LIKE ?) * 2 + (a.texto_simplificado LIKE ?)"));
                        $params = [];
                        foreach ($palavras as $p) array_push($params, "%$p%", "%$p%", "%$p%");
                        $st = $pdo->prepare("SELECT a.id, a.tipo, a.titulo, a.texto_simplificado AS texto, a.estado, a.sigla, ($cond) AS pontos
                                             FROM (" . sql_acervo() . " WHERE " . SQL_PUBLICADO . ") a
                                             HAVING pontos > 0 ORDER BY pontos DESC, a.id LIMIT 3");
                        $st->execute($params);
                        $relacionados = $st->fetchAll();
                    }
                    $resposta = $relacionados
                        ? "Olá! Sou o {$agente['nome']}. Encontrei no acervo: {$relacionados[0]['titulo']} ({$relacionados[0]['estado']}) — {$relacionados[0]['texto']}"
                        : "Olá! Sou o {$agente['nome']}. Não encontrei esse assunto no acervo. Tente outras palavras ou fale com um atendente humano.";
                }
                // a resposta da IA já é gravada; a do atendente humano entra depois (ck_atendimento_resposta)
                $pdo->prepare("INSERT INTO atendimento (usuario_id, canal, agente_id, data_hora, topico, resposta, data_resposta)
                               VALUES (?, ?, ?, NOW(), ?, ?, " . ($resposta === null ? 'NULL' : 'NOW()') . ")")
                    ->execute([$uid, $canal, $agente['id'] ?? null, mb_substr($pergunta, 0, 100), $resposta]);
                responder(['ok' => true, 'atendimento_id' => (int)$pdo->lastInsertId(),
                    'aguardando' => $resposta === null, 'relacionados' => $relacionados,
                    'resposta' => $resposta ?? 'Sua mensagem foi registrada e está na fila do atendimento humano. A resposta aparece aqui e em "Minhas mensagens", no seu perfil.']);
                return;

            case 'minhas_mensagens': // RF-08: histórico de atendimentos do próprio usuário
                exigir_login();
                $st = db()->prepare(
                    "SELECT a.id, a.canal, a.data_hora, a.topico, a.resposta, a.data_resposta, g.nome AS agente
                     FROM atendimento a LEFT JOIN agente_ia g ON g.id = a.agente_id
                     WHERE a.usuario_id = ? ORDER BY a.data_hora DESC LIMIT 20");
                $st->execute([$uid]);
                responder(['ok' => true, 'mensagens' => $st->fetchAll()]);
                return;

            // ---------------------------------------------------------- contribuição (RF-16 a RF-20)
            case 'contribuir':
                exigir_login();
                $d = corpo();
                $tipo = (string)($d['tipo'] ?? '');
                if (!in_array($tipo, CATEGORIAS, true)) throw new ErroApi('Escolha uma categoria válida.'); // UC-17
                $situacao = ($d['situacao'] ?? '') === 'rascunho' ? 'rascunho' : 'pendente';
                $pdo = db();
                // Procedure transacional do Eduardo: grava o conteúdo, a categoria e a contribuição
                $st = $pdo->prepare("CALL sp_registrar_contribuicao(?, ?, ?, ?, ?, ?, ?, ?, ?, @novo_id)");
                $st->execute([$uid, (int)($d['estado_id'] ?? 0), $tipo, texto($d, 'titulo', 100),
                    texto($d, 'texto', 2000), texto($d, 'texto_tecnico', 4000, false), texto($d, 'referencias', 1000, false),
                    texto($d, 'autor', 100, false), $situacao]);
                $st->closeCursor();
                $novo = (int)$pdo->query("SELECT @novo_id")->fetchColumn();
                responder(['ok' => true, 'conteudo_id' => $novo, 'situacao' => $situacao]);
                return;

            case 'enviar_rascunho': // UC-16: o rascunho salvo pode ser enviado depois para moderação
                exigir_login();
                $st = db()->prepare("UPDATE contribuicao SET status = 'pendente', data_envio = NOW()
                                     WHERE conteudo_id = ? AND usuario_id = ? AND status = 'rascunho'");
                $st->execute([(int)(corpo()['conteudo_id'] ?? 0), $uid]);
                if ($st->rowCount() === 0) throw new ErroApi('Rascunho não encontrado.');
                responder(['ok' => true]);
                return;

            case 'criar_atividade': // RF-15
                exigir_docente();
                $d = corpo();
                $id = (int)($d['conteudo_id'] ?? 0);
                conteudo_visivel($id);
                $tipo = (string)($d['tipo'] ?? '');
                if (!in_array($tipo, TIPOS_ATIVIDADE, true)) throw new ErroApi('Escolha o tipo da atividade.');
                db()->prepare("INSERT INTO atividade_interativa (conteudo_id, docente_id, tipo, titulo, descricao) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $uid, $tipo, texto($d, 'titulo', 150), texto($d, 'descricao', 255, false)]);
                responder(['ok' => true]);
                return;

            /**
             * RF-18 (e RF-10, RF-12 e RF-14 pelos tipos): o docente anexa material ao conteúdo
             * enviando um arquivo (fica em arquivos/materiais) ou informando um link externo.
             */
            case 'anexar_recurso':
                exigir_docente();
                $d = corpo();
                $id = (int)($d['conteudo_id'] ?? 0);
                conteudo_visivel($id);
                $tipo = (string)($d['tipo'] ?? '');
                if (!in_array($tipo, TIPOS_RECURSO, true)) throw new ErroApi('Escolha o tipo do material.');

                $enviado = salvar_upload('arquivo');
                $link = texto($d, 'endereco', 255, false);
                if ($enviado === null && $link === null) throw new ErroApi('Escolha um arquivo para enviar ou informe um link.');
                if ($enviado !== null && $link !== null) throw new ErroApi('Envie o arquivo ou informe o link, não os dois.');
                if ($link !== null && !preg_match('#^https?://#i', $link)) throw new ErroApi('O endereço deve começar com http:// ou https://');

                $endereco = $enviado ?? $link;
                try {
                    db()->prepare("INSERT INTO recurso_complementar (conteudo_id, docente_id, tipo, titulo, endereco, descricao) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$id, $uid, $tipo, texto($d, 'titulo', 150), $endereco, texto($d, 'descricao', 255, false)]);
                } catch (Throwable $e) {
                    if ($enviado !== null) @unlink(__DIR__ . '/' . $enviado); // não deixa arquivo órfão na pasta
                    throw $e;
                }
                responder(['ok' => true, 'arquivo' => $enviado]);
                return;

            // ---------------------------------------------------------- administração (RF-23 a RF-26)
            case 'admin_contribuicoes':
                exigir_admin();
                responder(['ok' => true, 'contribuicoes' => db()->query(
                    "SELECT a.id AS conteudo_id, a.titulo, a.tipo, a.estado, a.texto_simplificado, a.texto_tecnico, a.referencias,
                            ct.status, ct.data_envio, ct.motivo_rejeicao, u.nome AS contribuidor, u.classificacao,
                            r.nome AS revisado_por, ed.nome AS editado_por, ct.data_edicao
                     FROM contribuicao ct
                     INNER JOIN (" . sql_acervo() . ") a ON a.id = ct.conteudo_id
                     INNER JOIN usuario u ON u.id = ct.usuario_id
                     LEFT JOIN usuario r ON r.id = ct.admin_id
                     LEFT JOIN usuario ed ON ed.id = ct.editor_id
                     WHERE ct.status <> 'rascunho'
                     ORDER BY (ct.status = 'pendente') DESC, ct.data_envio DESC")->fetchAll()]);
                return;

            case 'admin_revisar': // procedure do Carlos (aprovar ou rejeitar com motivo)
                exigir_admin();
                $d = corpo();
                $st = db()->prepare("CALL sp_revisar_contribuicao(?, ?, ?, ?)");
                $st->execute([(int)($d['conteudo_id'] ?? 0), $uid, (string)($d['decisao'] ?? ''), texto($d, 'motivo', 255, false)]);
                $st->closeCursor();
                responder(['ok' => true]);
                return;

            case 'admin_editar': // RF-24: procedure de edição na moderação
                exigir_admin();
                $d = corpo();
                $st = db()->prepare("CALL sp_editar_conteudo_moderacao(?, ?, ?, ?, ?)");
                $st->execute([(int)($d['conteudo_id'] ?? 0), $uid, texto($d, 'texto', 2000, false),
                    texto($d, 'texto_tecnico', 4000, false), texto($d, 'referencias', 1000, false)]);
                $st->closeCursor();
                responder(['ok' => true]);
                return;

            case 'admin_relatorio': // RF-25: procedure do Carlos
                exigir_admin();
                $st = db()->prepare("CALL sp_relatorio_conteudos_periodo(?, ?)");
                $st->execute([(string)($_GET['inicio'] ?? ''), (string)($_GET['fim'] ?? '')]);
                $linhas = $st->fetchAll();
                $st->closeCursor();
                responder(['ok' => true, 'linhas' => $linhas]);
                return;

            case 'admin_estatisticas': // RF-25
                exigir_admin();
                $pdo = db();
                responder(['ok' => true,
                    'termos' => $pdo->query("SELECT termo_pesquisado AS termo, COUNT(*) AS vezes FROM acesso
                                             WHERE pagina_visitada = 'Pesquisa' GROUP BY termo_pesquisado ORDER BY vezes DESC, termo LIMIT 10")->fetchAll(),
                    'temas' => $pdo->query("SELECT c.tipo AS tema, COUNT(*) AS acessos FROM acesso a
                                            INNER JOIN conteudo_cultural c ON c.id = a.conteudo_id
                                            GROUP BY c.tipo ORDER BY acessos DESC")->fetchAll(),
                    'dias' => $pdo->query("SELECT DAYNAME(data_hora) AS dia, COUNT(*) AS acessos FROM acesso
                                           GROUP BY DAYOFWEEK(data_hora), DAYNAME(data_hora) ORDER BY DAYOFWEEK(data_hora)")->fetchAll(),
                    'frequentes' => $pdo->query("SELECT tipo, total_conteudos, estados, frequencia FROM vw_tipos_culturais_frequentes")->fetchAll(),
                    'engajamento' => $pdo->query("SELECT (SELECT COUNT(*) FROM acesso) AS acessos, (SELECT COUNT(*) FROM avaliacao) AS avaliacoes,
                                                  (SELECT COUNT(*) FROM favorito) AS favoritos, (SELECT COUNT(*) FROM feedback) AS feedbacks,
                                                  (SELECT COUNT(*) FROM tentativa) AS atividades, (SELECT COUNT(*) FROM atendimento) AS atendimentos")->fetch(),
                ]);
                return;

            case 'admin_feedbacks': // RF-26
                exigir_admin();
                responder(['ok' => true, 'feedbacks' => db()->query(
                    "SELECT f.id, u.nome, f.tipo, f.texto, f.data_envio, f.status, a.titulo, a.id AS conteudo_id, av.nota
                     FROM feedback f
                     INNER JOIN usuario u ON u.id = f.usuario_id
                     INNER JOIN avaliacao av ON av.usuario_id = f.usuario_id AND av.conteudo_id = f.conteudo_id
                     INNER JOIN (" . sql_acervo() . ") a ON a.id = f.conteudo_id
                     ORDER BY (f.status = 'pendente') DESC, f.data_envio DESC")->fetchAll()]);
                return;

            case 'admin_feedback_analisado':
                exigir_admin();
                db()->prepare("UPDATE feedback SET status = 'analisado' WHERE id = ?")->execute([(int)(corpo()['id'] ?? 0)]);
                responder(['ok' => true]);
                return;

            case 'admin_atendimentos': // RF-08 visto pela equipe: fila do atendimento humano primeiro
                exigir_admin();
                responder(['ok' => true, 'atendimentos' => db()->query(
                    "SELECT a.id, a.data_hora, u.nome, u.email, a.canal, COALESCE(g.nome, 'Equipe') AS atendente,
                            a.topico, a.resposta, a.data_resposta
                     FROM atendimento a INNER JOIN usuario u ON u.id = a.usuario_id LEFT JOIN agente_ia g ON g.id = a.agente_id
                     ORDER BY (a.resposta IS NULL) DESC, a.data_hora DESC LIMIT 50")->fetchAll()]);
                return;

            case 'admin_responder': // RF-08: o atendente humano responde a mensagem pendente
                exigir_admin();
                $d = corpo();
                $st = db()->prepare("UPDATE atendimento SET resposta = ?, data_resposta = NOW()
                                     WHERE id = ? AND canal = 'atendente humano' AND resposta IS NULL");
                $st->execute([texto($d, 'resposta', 2000), (int)($d['id'] ?? 0)]);
                if ($st->rowCount() === 0) throw new ErroApi('Esta mensagem não existe ou já foi respondida.');
                responder(['ok' => true]);
                return;

            default:
                responder(['ok' => false, 'erro' => 'Ação desconhecida.'], 404);
                return;
        }
    } catch (ErroApi $e) {
        responder(['ok' => false, 'erro' => $e->getMessage()], 400);
    } catch (PDOException $e) {
        responder(['ok' => false, 'erro' => mensagem_pdo($e)], 400);
    } catch (Throwable $e) {
        responder(['ok' => false, 'erro' => 'Erro interno: ' . $e->getMessage()], 500);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tchê & Cultura - Conheça o Brasil por dentro</title>
    <meta name="description" content="Plataforma de difusão da diversidade cultural das cinco regiões do Brasil.">

    <!-- =====================================================================================
         PARTE 10 — HTML E CSS DA PÁGINA
         Ordem do arquivo: Tailwind · estilos próprios · barra de acessibilidade · cabeçalho ·
         seção 1 mapa · seção 2 experiências · seções 3 a 8 temáticas · seção 9 números ·
         rodapé · modal · avisos rápidos.
         ===================================================================================== -->

    <!-- ============================== TAILWIND CSS ==============================
         O site usa o tailwind.css compilado (pasta do site), então funciona sem internet.
         Se esse arquivo não estiver na pasta, a função abaixo carrega o Tailwind pelo CDN. -->
    <script>
        const TEMA_TAILWIND = {
            theme: {
                extend: {
                    colors: {
                        cream: '#F4EFE6',
                        creamCard: '#FDFBF7',
                        terracotta: '#B8491C',
                        terracottaDark: '#963A13',
                        darkGreen: '#0B261A',
                        darkGreenCard: '#123324',
                        accentGold: '#E6A123',
                        mapNorte: '#229A88',
                        mapNordeste: '#F06548',
                        mapCentro: '#F3C151',
                        mapSudeste: '#297BB3',
                        mapSul: '#781FB6'
                    },
                    fontFamily: {
                        display: ['"Space Grotesk"', 'Verdana', 'sans-serif'],
                        serif: ['"Instrument Serif"', 'Georgia', 'serif'],
                        sans: ['"Plus Jakarta Sans"', 'Segoe UI', 'sans-serif']
                    }
                }
            }
        };
        function tailwindDeReserva() {
            const s = document.createElement('script');
            s.src = 'https://cdn.tailwindcss.com';
            s.onload = () => { window.tailwind.config = TEMA_TAILWIND; };
            document.head.appendChild(s);
        }
    </script>
    <link rel="stylesheet" href="tailwind.css" onerror="tailwindDeReserva()">

    <!-- Google Fonts (opcional: sem internet o site usa as fontes do sistema) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- ============================== ESTILOS PERSONALIZADOS ============================== -->
    <style>
        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }

        /* MAPA DO BRASIL */
        .map-path { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; }
        .map-path:hover, .map-path:focus { filter: brightness(1.15) drop-shadow(0 8px 12px rgba(0, 0, 0, 0.2)); transform: translateY(-2px); outline: none; }
        .map-path.active { filter: brightness(1.2) drop-shadow(0 10px 15px rgba(0, 0, 0, 0.3)); stroke: #FFFFFF; stroke-width: 3px; }
        .map-path:focus-visible { stroke: #0B261A; stroke-width: 4px; }
        .uf-path { cursor: pointer; stroke: #F4EFE6; stroke-width: 0.8; transition: filter 0.2s; }
        .uf-path:hover, .uf-path:focus { filter: brightness(1.18) drop-shadow(0 3px 4px rgba(0, 0, 0, 0.35)); stroke: #FFFFFF; stroke-width: 1.8; outline: none; }
        .uf-path.active { stroke: #0B261A; stroke-width: 2.2; }
        .uf-path:focus-visible { stroke: #0B261A; stroke-width: 3; }
        .map-label { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 15px; fill: #FFFFFF; pointer-events: none; user-select: none; }

        /* NAVEGAÇÃO */
        .nav-btn { padding: 8px 12px; border-radius: 12px; font-size: 13.5px; font-weight: 600; color: #0B261A; transition: all 0.2s ease; display: inline-block; }
        .nav-btn:hover { background-color: rgba(11, 38, 26, 0.08); }
        .icon-btn { width: 40px; height: 40px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(11, 38, 26, 0.2); color: #0B261A; font-weight: 700; transition: background 0.2s; }
        .icon-btn:hover { background: rgba(11, 38, 26, 0.06); }

        /* PILLS (EXPERIÊNCIAS) */
        .cat-pill { padding: 10px 22px; border-radius: 9999px; font-size: 14px; font-weight: 600; background-color: #0B261A; color: #F4EFE6; transition: all 0.2s ease; border: 1px solid rgba(244, 239, 230, 0.1); }
        .cat-pill:hover { border-color: #E6A123; }
        .cat-pill.active { background-color: #E6A123; color: #0B261A; border-color: #E6A123; }

        /* FORMULÁRIOS */
        .campo { width: 100%; padding: 0.85rem; border-radius: 0.75rem; background: #FDFBF7; border: 1px solid rgba(11, 38, 26, 0.2); color: #0B261A; font-size: 0.8rem; }
        .campo:focus { outline: none; border-color: #B8491C; box-shadow: 0 0 0 3px rgba(184, 73, 28, 0.15); }
        .rotulo { display: block; font-size: 0.75rem; font-weight: 700; color: #0B261A; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem; }
        .dica { font-size: 0.7rem; color: rgba(11, 38, 26, 0.75); margin-top: 0.25rem; }
        .btn-primario { width: 100%; padding: 1rem; border-radius: 0.75rem; background: #B8491C; color: #fff; font-weight: 700; font-size: 0.875rem; transition: background 0.2s; }
        .btn-primario:hover { background: #B8491C; }
        .btn-primario:disabled { opacity: 0.6; cursor: wait; }
        .btn-sec { padding: 0.6rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(11, 38, 26, 0.2); font-weight: 700; font-size: 0.75rem; color: #0B261A; transition: background 0.2s; }
        .btn-sec:hover { background: rgba(11, 38, 26, 0.05); }

        /* CARDS DO ACERVO */
        .card-conteudo { cursor: pointer; position: relative; transition: transform 0.2s, box-shadow 0.2s; }
        .card-conteudo:hover, .card-conteudo:focus-visible { transform: translateY(-3px); }
        .card-conteudo:focus-visible { outline: 3px solid #E6A123; outline-offset: 2px; }
        .btn-fav { position: absolute; top: 14px; right: 14px; width: 32px; height: 32px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 15px; transition: transform 0.2s; }
        .btn-fav:hover { transform: scale(1.15); }
        .estrela { font-size: 1.6rem; line-height: 1; color: rgba(11, 38, 26, 0.2); transition: color 0.15s, transform 0.15s; }
        .estrela.on { color: #E6A123; }
        .estrela:hover { transform: scale(1.15); }
        .aba { padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 700; border: 1px solid rgba(11, 38, 26, 0.15); }
        .aba.active { background: #0B261A; color: #F4EFE6; border-color: #0B261A; }
        .selo { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.25rem 0.6rem; border-radius: 9999px; }
        .bloco { padding: 1rem; border-radius: 1rem; background: #FDFBF7; border: 1px solid rgba(11, 38, 26, 0.1); }
        details > summary { cursor: pointer; list-style: none; }
        details > summary::-webkit-details-marker { display: none; }

        /* ACESSIBILIDADE (RNF-01 / RNF-02) */
        .skip-link { position: absolute; left: -9999px; top: 8px; z-index: 100; background: #0B261A; color: #F4EFE6; padding: 10px 16px; border-radius: 10px; font-weight: 700; }
        .skip-link:focus { left: 8px; }
        :focus-visible { outline: 3px solid #B8491C; outline-offset: 2px; }
        html.alto-contraste body { background: #000 !important; color: #fff !important; }
        html.alto-contraste .bg-cream, html.alto-contraste .bg-creamCard, html.alto-contraste .bg-darkGreen,
        html.alto-contraste .bg-darkGreenCard, html.alto-contraste .bloco, html.alto-contraste .campo,
        html.alto-contraste header { background: #000 !important; color: #fff !important; border-color: #fff !important; }
        html.alto-contraste [class*="text-darkGreen"], html.alto-contraste [class*="text-cream"] { color: #fff !important; }
        html.alto-contraste .text-terracotta, html.alto-contraste .text-accentGold { color: #FFE14D !important; }
        html.alto-contraste .nav-btn, html.alto-contraste .icon-btn, html.alto-contraste .btn-sec { color: #fff !important; border-color: #fff !important; }
        html.alto-contraste .bg-terracotta, html.alto-contraste .btn-primario, html.alto-contraste .bg-accentGold { background: #FFE14D !important; color: #000 !important; }
        html.alto-contraste .border, html.alto-contraste [class*="border-"] { border-color: #fff !important; }
        html.alto-contraste .cat-pill.active, html.alto-contraste .aba.active { background: #FFE14D !important; color: #000 !important; }
        html.alto-contraste #modal-card { background: #000 !important; border: 2px solid #fff; }

        /* JOGO DA MEMÓRIA (RF-11) — carta com frente escura e verso virando em 3D */
        .memo-grade { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        @media (min-width: 640px) { .memo-grade { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .memo-carta { position: relative; height: 108px; perspective: 900px; }
        .memo-carta > button { position: absolute; inset: 0; transform-style: preserve-3d; transition: transform 0.35s ease; border-radius: 14px; }
        .memo-carta.virada > button, .memo-carta.certa > button { transform: rotateY(180deg); }
        .memo-carta.certa { opacity: 0.85; }
        .memo-face { position: absolute; inset: 0; border-radius: 14px; padding: 8px; backface-visibility: hidden;
            display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .memo-frente { background: #0B261A; color: #F4EFE6; border: 1px solid rgba(230, 161, 35, 0.45); font-size: 26px; }
        .memo-tras { background: #FDFBF7; color: #0B261A; border: 1px solid rgba(11, 38, 26, 0.15); transform: rotateY(180deg); }
        .memo-carta.certa .memo-tras { background: #ECFDF5; border-color: #86EFAC; }
        .memo-titulo { font-size: 11.5px; font-weight: 700; line-height: 1.25; }
        .memo-rodape { font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.6; margin-top: 4px; }

        /* QUEBRA-CABEÇA (RF-13) — mosaico 3x3; clicar em duas peças troca as duas de lugar */
        /* width: 100% é necessário: só com max-width e margem automática o mosaico encolhe.
           A proporção (aspect-ratio) é ajustada pelo JS conforme a imagem, para não esticar. */
        .qc-grade { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-template-rows: repeat(3, minmax(0, 1fr));
            gap: 6px; width: 100%; max-width: 380px; aspect-ratio: 1 / 1; margin: 0 auto; }
        .qc-peca { position: relative; width: 100%; height: 100%; border-radius: 10px; border: 2px solid transparent;
            background-size: 300% 300%; background-repeat: no-repeat; transition: transform 0.15s, border-color 0.2s; }
        .qc-peca.sel { outline: 4px solid #B8491C; outline-offset: 2px; transform: scale(0.96); }
        .qc-peca.ok { border-color: #22C55E; }
        .qc-num { position: absolute; bottom: 3px; right: 6px; font-size: 10px; font-weight: 700; color: #fff; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9); }

        /* LEITOR DE ARTIGO (RF-21) */
        .artigo-corpo p { line-height: 1.8; margin-bottom: 0.9rem; }

        #toast { transition: opacity 0.3s, transform 0.3s; }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; scroll-behavior: auto !important; } }
    </style>
</head>
<body class="bg-cream text-darkGreen font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-terracotta selection:text-white">
    <a href="#conteudo-principal" class="skip-link">Pular para o conteúdo</a>

    <!-- BARRA DE ACESSIBILIDADE -->
    <div class="bg-darkGreen text-cream text-[11px]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-9 flex items-center justify-end gap-3" role="toolbar" aria-label="Acessibilidade">
            <span class="hidden sm:inline opacity-70 font-bold uppercase tracking-wider">Acessibilidade:</span>
            <button onclick="ajustarFonte(-1)" class="font-bold px-2 py-1 rounded hover:bg-cream/10" aria-label="A−: diminuir o tamanho do texto">A−</button>
            <button onclick="ajustarFonte(0)" class="font-bold px-2 py-1 rounded hover:bg-cream/10" aria-label="A: tamanho normal do texto">A</button>
            <button onclick="ajustarFonte(1)" class="font-bold px-2 py-1 rounded hover:bg-cream/10" aria-label="A+: aumentar o tamanho do texto">A+</button>
            <button onclick="alternarContraste()" id="btn-contraste" class="font-bold px-2 py-1 rounded hover:bg-cream/10" aria-pressed="false">◐ Alto contraste</button>
        </div>
    </div>

    <!-- CABEÇALHO FIXO (RF-04: menu com pesquisa, ajuda, login e "explore mais") -->
    <header class="sticky top-0 z-40 bg-cream/95 backdrop-blur-md border-b border-darkGreen/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-3">

            <a href="#mapa" class="flex items-center gap-3 cursor-pointer shrink-0" aria-label="Tchê & Cultura — início">
                <div class="w-11 h-11 bg-gradient-to-br from-[#0B62C4] to-[#1A81FF] rounded-2xl flex items-center justify-center shadow-md border border-white/60 transform transition hover:scale-105">
                    <svg viewBox="0 0 100 100" class="w-9 h-9" aria-hidden="true">
                        <path d="M 15 50 C 5 25, 25 10, 45 35 C 55 20, 85 10, 80 40 C 95 45, 90 70, 70 70 C 55 90, 25 80, 15 50 Z" fill="#0B62C4"/>
                        <path d="M 25 45 C 10 20, 35 15, 50 38 C 35 45, 25 65, 25 45 Z" fill="#1A81FF"/>
                        <circle cx="58" cy="32" r="7" fill="#FFD700"/>
                        <circle cx="58" cy="32" r="4" fill="#FFFFFF"/>
                        <circle cx="59" cy="32" r="2.5" fill="#000000"/>
                        <path d="M 64 33 C 78 30, 84 42, 70 54 C 62 48, 62 38, 64 33 Z" fill="#111111"/>
                    </svg>
                </div>
                <div class="hidden sm:flex items-baseline">
                    <span class="text-2xl font-display font-bold tracking-tight text-darkGreen">Tchê</span>
                    <span class="text-2xl font-serif italic font-normal text-terracotta ml-1.5">& Cultura</span>
                </div>
            </a>

            <nav class="hidden xl:flex items-center gap-0.5" aria-label="Seções do site">
                <a href="#mapa" class="nav-btn">Mapa</a>
                <a href="#lendas" class="nav-btn">Lendas</a>
                <a href="#festas" class="nav-btn">Festas</a>
                <a href="#turismo" class="nav-btn">Turismo</a>
                <a href="#expressoes" class="nav-btn">Expressões</a>
                <a href="#nomes" class="nav-btn">Nomes</a>
                <a href="#artigos" class="nav-btn">Artigos</a>
                <a href="#numeros" class="nav-btn">Números</a>
            </nav>

            <div class="flex items-center gap-2">
                <button onclick="openPesquisa()" class="icon-btn" aria-label="Pesquisar no acervo" title="Pesquisar">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </button>
                <button onclick="openAjuda()" class="icon-btn" aria-label="Ajuda" title="Ajuda">?</button>
                <!-- Cadastro, login, perfil, admin e quiz — preenchidos pelo JavaScript -->
                <div id="header-acoes" class="flex items-center gap-2"></div>
                <button onclick="openMenu()" class="icon-btn xl:hidden" aria-label="Abrir menu">☰</button>
            </div>
        </div>
    </header>

    <main id="conteudo-principal" class="flex-grow" tabindex="-1">

        <!-- SEÇÃO 1: MAPA (RF-01) -->
        <section id="mapa" class="py-16 px-4 sm:px-6 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">

                <div class="lg:col-span-4 flex flex-col justify-between py-2">
                    <div>
                        <span class="text-xs font-bold text-terracotta tracking-widest uppercase block mb-4">◆ BRASIL DE MUITOS BRASIS</span>
                        <h1 class="text-5xl sm:text-6xl font-display font-bold text-darkGreen leading-[1.02] tracking-tight mb-6">
                            Conheça o<br>Brasil<br>
                            <span class="font-serif italic font-normal text-terracotta text-6xl sm:text-7xl block mt-1">por dentro.</span>
                        </h1>
                        <p class="text-darkGreen/80 text-sm leading-relaxed font-normal max-w-sm mb-8">
                            Uma viagem interativa pelas histórias, sons, sabores e palavras que formam a nossa cultura nacional.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <button class="w-full text-left p-5 rounded-2xl bg-creamCard border border-darkGreen/10 flex items-center justify-between hover:border-terracotta transition shadow-sm group" onclick="scrollToMap()">
                            <div>
                                <span class="text-sm font-serif italic text-terracotta font-bold block mb-1">01</span>
                                <span class="text-sm font-bold text-darkGreen block">Escolha um estado no mapa</span>
                                <span class="text-xs text-darkGreen/75 block mt-0.5">Ou use Tab e Enter no teclado</span>
                            </div>
                            <span class="text-xl text-terracotta group-hover:translate-x-1 transition-transform" aria-hidden="true">→</span>
                        </button>
                        <button class="w-full text-left p-5 rounded-2xl bg-creamCard border border-darkGreen/10 flex items-center justify-between hover:border-terracotta transition shadow-sm group" onclick="openPesquisa()">
                            <div>
                                <span class="text-sm font-serif italic text-terracotta font-bold block mb-1">02</span>
                                <span class="text-sm font-bold text-darkGreen block">Pesquise um tema, estado ou autor</span>
                                <span class="text-xs text-darkGreen/75 block mt-0.5">Ex.: chimarrão, Bahia, cordel</span>
                            </div>
                            <span class="text-xl text-terracotta group-hover:translate-x-1 transition-transform" aria-hidden="true">→</span>
                        </button>
                    </div>
                </div>

                <div id="mapa-svg" class="lg:col-span-5 relative flex flex-col items-center justify-center p-6 bg-creamCard/60 rounded-3xl border border-darkGreen/5 shadow-inner min-h-[460px] overflow-hidden">
                    <div class="w-full max-w-md relative z-10 p-2">
                        <p id="mapa-rotulo" class="h-8 mb-1 text-center font-display font-bold text-lg text-darkGreen" aria-hidden="true"></p>
                        <div id="mapa-container">
                            <p class="py-24 text-center text-sm text-darkGreen/75" role="status">Carregando o mapa...</p>
                        </div>
                        <p id="mapa-aviso" role="alert" class="hidden mt-3 text-xs font-bold text-amber-900 bg-amber-50 border border-amber-200 rounded-xl p-3 text-center"></p>
                        <div class="flex flex-col items-center gap-2 mt-3">
                            <button id="btn-lista-estados" type="button" onclick="abrirListaEstados()" class="px-4 py-2 rounded-full border border-darkGreen/20 text-xs font-bold uppercase tracking-wider text-darkGreen hover:bg-darkGreen/5 transition">Lista de estados</button>
                            <p class="text-center text-[10px] font-bold text-terracotta uppercase tracking-widest">✦ CLIQUE NOS ESTADOS PARA DETALHES</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 bg-creamCard p-8 rounded-3xl border-l-4 border-terracotta shadow-lg flex flex-col justify-between" aria-live="polite">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <span id="uf-regiao" class="text-[11px] font-bold tracking-widest uppercase text-terracotta flex items-center gap-1">◆ BRASIL</span>
                            <span id="region-num" class="text-3xl font-serif italic text-terracotta font-bold" aria-hidden="true">—</span>
                        </div>
                        <h2 id="region-title" class="text-4xl font-display font-bold text-darkGreen mb-4">Brasil</h2>
                        <p id="region-desc" class="text-sm text-darkGreen/80 leading-relaxed font-normal mb-6"></p>
                        <div class="border-t border-darkGreen/10 pt-6 mb-6">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-darkGreen/70 block mb-2">DESTAQUE CULTURAL:</span>
                            <p id="region-highlight" class="font-serif italic text-2xl text-terracotta font-normal"></p>
                        </div>
                        <p id="region-count" class="text-[11px] font-bold uppercase tracking-wider text-darkGreen/70 mb-6"></p>
                    </div>
                    <button id="btn-explorar" onclick="explorarEstadoAtual()" class="w-full py-4 px-6 rounded-xl bg-terracotta hover:bg-terracottaDark text-white font-bold text-sm shadow-md transition flex items-center justify-between group">
                        <span>Explorar Estado</span>
                        <span class="group-hover:translate-x-1 transition-transform" aria-hidden="true">→</span>
                    </button>
                </div>

            </div>
        </section>

        <!-- SEÇÃO 2: EXPERIÊNCIAS -->
        <section id="experiencias" class="bg-darkGreen text-cream py-16 px-4 sm:px-6">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-end mb-10">
                    <div class="lg:col-span-8">
                        <span class="text-xs font-bold text-accentGold tracking-widest uppercase block mb-3">◆ PERCURSOS NACIONAIS</span>
                        <h2 class="text-4xl sm:text-5xl font-display font-bold leading-[1.05]">
                            Explore o Brasil<br>
                            <span class="font-serif italic font-normal text-accentGold text-4xl sm:text-6xl block mt-1">por aquilo que te move.</span>
                        </h2>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-cream/70 text-sm leading-relaxed font-normal">
                            Escolha um tema e veja o destaque mais recente de cada uma das cinco regiões.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mb-10" role="tablist" aria-label="Temas">
                    <button onclick="filterCategory('literatura')" class="cat-pill active" id="pill-literatura" role="tab">Literatura</button>
                    <button onclick="filterCategory('musica')" class="cat-pill" id="pill-musica" role="tab">Música</button>
                    <button onclick="filterCategory('historia')" class="cat-pill" id="pill-historia" role="tab">História</button>
                    <button onclick="filterCategory('culinaria')" class="cat-pill" id="pill-culinaria" role="tab">Culinária</button>
                </div>

                <div id="category-cards-grid" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4" aria-live="polite"></div>
            </div>
        </section>

        <!-- SEÇÕES 3 A 8: geradas a partir da mesma estrutura (ver SECOES no JavaScript) -->
        <section id="lendas" class="py-16 px-4 sm:px-6 max-w-7xl mx-auto" data-secao="lendas" data-escura="0"
                 data-rotulo="◆ LITERATURA ORAL & MITOS" data-titulo="Lendas Folclóricas"></section>

        <section id="festas" class="bg-darkGreen text-cream py-16 px-4 sm:px-6" data-secao="festas" data-escura="1"
                 data-rotulo="◆ EXPLORE MAIS: FESTAS, TRADIÇÕES & DESCOBERTAS" data-titulo="Curiosidades do Brasil"></section>

        <section id="turismo" class="py-16 px-4 sm:px-6 max-w-7xl mx-auto" data-secao="turismo" data-escura="0"
                 data-rotulo="◆ PATRIMÔNIO, PARQUES & PAISAGENS" data-titulo="Pontos Turísticos"></section>

        <section id="expressoes" class="bg-darkGreen text-cream py-16 px-4 sm:px-6" data-secao="expressoes" data-escura="1"
                 data-rotulo="◆ COMO SE FALA POR AÍ" data-titulo="Variações Linguísticas"></section>

        <section id="nomes" class="py-16 px-4 sm:px-6 max-w-7xl mx-auto" data-secao="nomes" data-escura="0"
                 data-rotulo="◆ NOMES DE LUGARES & SUAS ORIGENS" data-titulo="Incidência Nominal"></section>

        <section id="artigos" class="bg-darkGreen text-cream py-16 px-4 sm:px-6" data-secao="artigos" data-escura="1"
                 data-rotulo="◆ PRODUÇÃO ACADÊMICA" data-titulo="Artigos Acadêmicos"></section>

        <!-- SEÇÃO 9: ACERVO EM NÚMEROS (views do banco) -->
        <section id="numeros" class="py-16 px-4 sm:px-6 max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 pb-4 border-b border-darkGreen/10 gap-4">
                <div>
                    <span class="text-xs font-bold text-terracotta uppercase tracking-widest block mb-1">◆ DIRETO DO BANCO DE DADOS</span>
                    <h2 class="text-4xl font-display font-bold text-darkGreen">Acervo em Números</h2>
                </div>
                <button onclick="openContribuir()" class="px-5 py-3 rounded-xl bg-terracotta text-white font-bold text-xs uppercase tracking-wider hover:bg-terracottaDark transition self-start md:self-auto">
                    + Contribuir com o acervo
                </button>
            </div>
            <div id="numeros-totais" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6"></div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-cream">
                <div class="bg-darkGreen p-6 rounded-2xl">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-accentGold mb-4">Conteúdos por tipo</h3>
                    <div id="numeros-tipos" class="space-y-2.5"></div>
                </div>
                <div class="bg-darkGreen p-6 rounded-2xl">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-accentGold mb-4">Estados com mais conteúdo</h3>
                    <div id="numeros-estados" class="space-y-2.5"></div>
                </div>
                <div class="bg-darkGreen p-6 rounded-2xl">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-accentGold mb-4">Ranking do Quiz do Blue</h3>
                    <div id="numeros-ranking" class="space-y-2.5"></div>
                    <button onclick="openQuiz()" class="mt-5 w-full py-3 rounded-xl bg-accentGold text-darkGreen font-bold text-xs uppercase tracking-wider hover:bg-accentGold/90 transition">Jogar o Quiz do Blue</button>
                </div>
            </div>
        </section>
    </main>

    <!-- RODAPÉ -->
    <footer class="bg-darkGreen text-cream py-10 border-t border-cream/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <span class="text-2xl font-display font-bold text-cream">Tchê</span>
                <span class="text-2xl font-serif italic text-accentGold">& Cultura</span>
            </div>
            <p class="text-xs text-cream/60 text-center md:text-left">
                Plataforma de difusão da diversidade e do acervo cultural das 5 regiões brasileiras.
                <button onclick="openAjuda()" class="underline text-accentGold ml-1">Fale com a equipe</button>
            </p>
            <div class="text-xs text-cream/70">© 2026 Tchê & Cultura. Mascote Oficial: Blue.</div>
        </div>
    </footer>

    <!-- MODAL -->
    <div id="modal-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden" onclick="if(event.target===this) closeModal()">
        <div id="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-titulo" class="bg-cream rounded-3xl max-w-4xl w-full max-h-[85vh] overflow-y-auto p-6 sm:p-8 shadow-2xl relative">
            <button onclick="closeModal()" aria-label="Fechar" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-darkGreen/10 text-darkGreen font-bold hover:bg-darkGreen/20 flex items-center justify-center transition z-10">✕</button>
            <div id="modal-content"></div>
        </div>
    </div>

    <!-- AVISOS RÁPIDOS -->
    <div id="toast" role="status" aria-live="polite" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-xl bg-darkGreen text-cream text-sm font-bold shadow-2xl opacity-0 pointer-events-none translate-y-4 max-w-[90vw] text-center"></div>

    <!-- =====================================================================================
         PARTE 11 — JAVASCRIPT: NAVEGAÇÃO, MAPA, ACERVO E PESQUISA
         Seções: configuração · utilitários · acessibilidade · inicialização · cabeçalho ·
         mapa e regiões · percursos · seções temáticas · detalhe do conteúdo · leitor de
         artigo · área do docente · pesquisa · central de ajuda · números · modal.
         ===================================================================================== -->
    <script>
    // ---------------------------------------------------------------- CONFIGURAÇÃO
    const API = location.pathname + '?api=';

    // Textos de apresentação das regiões (conteúdo editorial fixo da interface)
    const REGIOES = {
        norte: { id: "01", title: "Norte", desc: "Onde o rio é estrada. Sete estados cobrindo a maior floresta tropical do planeta, marcados por fortes tradições indígenas e ribeirinhas.", highlight: "Festival de Parintins & Círio de Nazaré" },
        nordeste: { id: "02", title: "Nordeste", desc: "Berço da colonização do Brasil. Maior faixa litorânea do país, unindo versos de cordel, festas de São João e pluralidade rítmica.", highlight: "São João & Carnaval de Rua" },
        "centro-oeste": { id: "03", title: "Centro-Oeste", desc: "Coração do Brasil. Encontro do Cerrado com o Pantanal, a riqueza do agronegócio e o modernismo da capital Brasília.", highlight: "Cavalhadas & Pantanal" },
        sudeste: { id: "04", title: "Sudeste", desc: "Polo econômico e cultural. Das cidades históricas barrocas de Minas ao samba do Rio e à multiculturalidade industrial de São Paulo.", highlight: "Samba, Barroco & Café" },
        sul: { id: "05", title: "Sul", desc: "Tradição dos pampas tropeiros combinada à rica arquitetura e gastronomia herdadas da imigração europeia.", highlight: "Churrasco, Tropeirismo & Oktoberfest" }
    };
    const NOMES_REGIOES = Object.values(REGIOES).map(r => r.title);

    // As 9 categorias do banco v4 (coluna conteudo_cultural.tipo — Requisitos v3.2)
    const TIPOS = ['Culinária', 'Música', 'Literatura', 'Variação Linguística', 'Ponto Turístico',
        'Acontecimento Histórico', 'Incidência Nominal', 'Curiosidade', 'Artigo Acadêmico'];

    // Seções do site -> filtro sobre o acervo publicado
    const SECOES = {
        lendas: c => c.tipo === 'Literatura' && c.subcategoria === 'Lenda',
        festas: c => c.tipo === 'Curiosidade',
        turismo: c => c.tipo === 'Ponto Turístico',
        expressoes: c => c.tipo === 'Variação Linguística',
        nomes: c => c.tipo === 'Incidência Nominal',
        artigos: c => c.tipo === 'Artigo Acadêmico'
    };
    // Pills de "Explore o Brasil": o destaque mais recente de cada região
    const PERCURSOS = {
        literatura: c => c.tipo === 'Literatura' && c.subcategoria !== 'Lenda',
        musica: c => c.tipo === 'Música',
        historia: c => c.tipo === 'Acontecimento Histórico',
        culinaria: c => c.tipo === 'Culinária'
    };
    const TIPOS_ATIVIDADE = ['quiz', 'jogo da memória', 'quebra-cabeça', 'dinâmica de aula'];
    const TIPOS_RECURSO = ['material pedagógico', 'plano de aula', 'vídeo-aula', 'link externo', 'mapa externo'];

    let app = { usuario: null, conteudos: [], estados: [], filtros: {}, categoria: 'literatura' };
    let ufAtual = null;          // sigla do estado selecionado no mapa (UC-01)
    let conteudoAberto = null;   // último conteúdo aberto no modal (usado pelo leitor de artigo)

    // ---------------------------------------------------------------- UTILITÁRIOS
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }
    /** Só deixa passar link externo (http/https) e arquivo da própria pasta do site. */
    function urlSegura(u) {
        const s = String(u || '');
        if (/^https?:\/\//i.test(s)) return esc(s);
        if (/^(arquivos|imagens)\/[A-Za-z0-9._\/-]+$/.test(s) && !s.includes('..')) return esc(s);
        return '#';
    }
    /** Nome gravado no banco -> caminho dentro do site (o banco pode ter só o nome do arquivo). */
    const caminhoArquivo = (nome, pasta) => urlSegura(String(nome).includes('/') ? nome : pasta + '/' + nome);

    async function api(acao, dados = null, params = '') {
        // FormData = envio de arquivo (o navegador monta o cabeçalho multipart sozinho)
        let opcoes = {};
        if (dados instanceof FormData) opcoes = { method: 'POST', body: dados };
        else if (dados) opcoes = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dados) };
        const resp = await fetch(API + acao + params, { credentials: 'same-origin', ...opcoes });
        let json;
        try { json = await resp.json(); } catch (e) { throw new Error('Resposta inválida do servidor. O PHP está rodando?'); }
        if (!json.ok) throw new Error(json.erro || 'Erro desconhecido.');
        return json;
    }

    function toast(msg, erro = false) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.toggle('bg-darkGreen', !erro);
        t.classList.toggle('bg-red-700', erro);
        t.style.opacity = '1'; t.style.transform = 'translate(-50%, 0)';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translate(-50%, 1rem)'; }, 3200);
    }

    function dataBR(d) { if (!d) return ''; const [a, m, r] = String(d).slice(0, 10).split('-'); return `${r}/${m}/${a}`; }
    function idade(nasc) {
        const n = new Date(nasc + 'T00:00:00'), h = new Date();
        let i = h.getFullYear() - n.getFullYear();
        if (h.getMonth() < n.getMonth() || (h.getMonth() === n.getMonth() && h.getDate() < n.getDate())) i--;
        return i;
    }
    function estrelas(media) {
        if (media === null || media === undefined) return '';
        return `★ ${Number(media).toFixed(1).replace('.', ',')}`;
    }
    function erroInline(id, msg) {
        const el = document.getElementById(id);
        if (el) { el.textContent = msg; el.classList.remove('hidden'); }
    }
    const boxErro = id => `<p id="${id}" role="alert" class="hidden text-xs font-bold text-red-700 bg-red-50 border border-red-200 rounded-xl p-3"></p>`;
    const cabecalhoModal = (rotulo, titulo, sub = '') => `
        <div class="text-center mb-6 border-b border-darkGreen/10 pb-4">
            <span class="text-xs font-bold text-terracotta uppercase tracking-widest block mb-1">${rotulo}</span>
            <h2 class="text-3xl font-display font-bold text-darkGreen">${titulo}</h2>
            ${sub ? `<p class="text-xs text-darkGreen/70 mt-1">${sub}</p>` : ''}
        </div>`;
    const carregando = txt => `<div class="py-16 text-center text-sm text-darkGreen/75" role="status">${txt}</div>`;
    const COR_STATUS = { aprovado: 'bg-green-100 text-green-800', rejeitado: 'bg-red-100 text-red-800', pendente: 'bg-amber-100 text-amber-800', rascunho: 'bg-darkGreen/10 text-darkGreen', analisado: 'bg-green-100 text-green-800' };
    const val = id => (document.getElementById(id)?.value ?? '').trim();

    // ---------------------------------------------------------------- ACESSIBILIDADE (RNF-01 / RNF-02)
    const ESCALAS = [100, 112.5, 125, 137.5];
    let escala = 0;
    function guardar(chave, valor) { try { localStorage.setItem(chave, valor); } catch (e) { /* sem armazenamento */ } }
    function ler(chave) { try { return localStorage.getItem(chave); } catch (e) { return null; } }
    function ajustarFonte(passo) {
        escala = passo === 0 ? 0 : Math.max(0, Math.min(ESCALAS.length - 1, escala + passo));
        document.documentElement.style.fontSize = ESCALAS[escala] + '%';
        guardar('tche_fonte', escala);
    }
    function alternarContraste(forcar) {
        const ligado = forcar !== undefined ? forcar : !document.documentElement.classList.contains('alto-contraste');
        document.documentElement.classList.toggle('alto-contraste', ligado);
        document.getElementById('btn-contraste').setAttribute('aria-pressed', String(ligado));
        guardar('tche_contraste', ligado ? '1' : '0');
    }

    // ---------------------------------------------------------------- INICIALIZAÇÃO
    window.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        escala = Math.min(ESCALAS.length - 1, Number(ler('tche_fonte')) || 0);
        document.documentElement.style.fontSize = ESCALAS[escala] + '%';
        if (ler('tche_contraste') === '1') alternarContraste(true);
        montarSecoes();
        atualizarPainelUF();
        renderHeader();
        try {
            const st = await api('status');
            if (!st.banco) { mostrarInstalacao(st); return; }
            app.usuario = st.usuario;
            renderHeader();
            await carregarAcervo();
        } catch (e) {
            toast(e.message, true);
        }
    }

    async function carregarAcervo() {
        const [est, cont] = await Promise.all([api('estados'), api('conteudos')]);
        app.estados = est.estados.map(e => ({ ...e, id: Number(e.id) }));
        app.conteudos = cont.conteudos;
        atualizarPainelUF();
        carregarMapa();
        rerenderTudo();
        carregarNumeros();
    }

    function mostrarInstalacao(st) {
        const naoInstalado = st.motivo === 'nao_instalado';
        openModal(`
            <div class="text-center py-4">
                <span class="text-xs font-bold text-terracotta uppercase tracking-widest block mb-2">CONFIGURAÇÃO INICIAL</span>
                <h2 class="text-3xl font-display font-bold text-darkGreen mb-3">${naoInstalado ? 'Banco de dados não instalado' : 'Sem conexão com o MySQL'}</h2>
                ${naoInstalado ? `
                    <p class="text-sm text-darkGreen/80 max-w-md mx-auto mb-6">O MySQL está funcionando, mas o banco <strong>tche_cultura</strong> ainda não existe.
                    ${st.sql_encontrado ? 'Clique abaixo para criá-lo a partir do arquivo <strong>tche_cultura_v4.sql</strong>.' : 'Coloque o arquivo <strong>tche_cultura_v4.sql</strong> na mesma pasta do index.php.'}</p>
                    ${st.sql_encontrado ? `<button id="btn-instalar" onclick="instalarBanco()" class="btn-primario max-w-xs">Instalar banco de dados</button>` : ''}
                ` : `
                    <p class="text-sm text-darkGreen/80 max-w-md mx-auto mb-4">Verifique se o MySQL está ligado (no XAMPP: botão <strong>Start</strong> do MySQL) e se usuário/senha no topo do <strong>index.php</strong> estão corretos.</p>
                    <p class="text-[11px] text-red-700 bg-red-50 border border-red-200 rounded-xl p-3 max-w-md mx-auto break-words">${esc(st.erro)}</p>
                `}
                <div class="mt-4">${boxErro('erro-instalar')}</div>
            </div>`);
    }

    async function instalarBanco() {
        const b = document.getElementById('btn-instalar');
        b.disabled = true; b.textContent = 'Instalando...';
        try {
            await api('instalar', {});
            closeModal();
            toast('Banco instalado com sucesso!');
            await carregarAcervo();
        } catch (e) {
            erroInline('erro-instalar', e.message);
            b.disabled = false; b.textContent = 'Tentar novamente';
        }
    }

    // ---------------------------------------------------------------- CABEÇALHO
    const podeContribuir = () => app.usuario && (app.usuario.docente || app.usuario.pesquisador);

    function renderHeader() {
        const u = app.usuario;
        const el = document.getElementById('header-acoes');
        const quiz = `<button onclick="openQuiz()" class="hidden sm:inline-flex whitespace-nowrap px-4 py-2.5 rounded-full bg-accentGold text-darkGreen font-bold text-xs uppercase tracking-wider shadow-sm hover:bg-accentGold/90 transition">Quiz do Blue</button>`;
        if (!u) {
            el.innerHTML = `
                <button onclick="openLogin()" class="whitespace-nowrap px-3 sm:px-4 py-2.5 rounded-full border border-darkGreen/20 text-darkGreen font-bold text-xs uppercase tracking-wider hover:bg-darkGreen/5 transition">Entrar</button>
                <button onclick="openRegistrationModal()" class="hidden sm:inline-flex whitespace-nowrap px-4 py-2.5 rounded-full bg-terracotta text-white font-bold text-xs uppercase tracking-wider shadow-sm hover:bg-terracottaDark transition">Cadastre-se</button>
                ${quiz}`;
        } else {
            const primeiro = esc(u.nome.split(' ')[0]);
            el.innerHTML = `
                ${u.admin ? `<button onclick="openAdmin()" class="hidden md:inline-flex xl:hidden 2xl:inline-flex whitespace-nowrap px-4 py-2.5 rounded-full bg-darkGreen text-cream font-bold text-xs uppercase tracking-wider hover:bg-darkGreenCard transition">Painel Admin</button>` : ''}
                <button onclick="openPerfil()" class="flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-full bg-creamCard border border-darkGreen/15 hover:border-terracotta transition">
                    <span class="w-7 h-7 rounded-full bg-terracotta text-white text-xs font-bold flex items-center justify-center" aria-hidden="true">${primeiro.charAt(0)}</span>
                    <span class="text-xs font-bold text-darkGreen max-w-[80px] truncate"><span class="sr-only">Meu perfil: </span>${primeiro}</span>
                </button>
                ${quiz}`;
        }
    }

    function openMenu() {
        const links = [['mapa', 'Mapa'], ['experiencias', 'Explore o Brasil'], ['lendas', 'Lendas'], ['festas', 'Curiosidades'], ['turismo', 'Turismo'],
            ['expressoes', 'Expressões'], ['nomes', 'Nomes'], ['artigos', 'Artigos'], ['numeros', 'Números']];
        const u = app.usuario;
        openModal(`
            <div class="max-w-md mx-auto py-2">
                ${cabecalhoModal('NAVEGAÇÃO', 'Menu')}
                <div class="grid grid-cols-2 gap-2 mb-4">${links.map(([id, r]) => `<a href="#${id}" onclick="closeModal()" class="bloco text-sm font-bold hover:border-terracotta">${r}</a>`).join('')}</div>
                <div class="grid gap-2">
                    <button onclick="openPesquisa()" class="btn-sec text-left">🔍 Pesquisar</button>
                    <button onclick="openQuiz()" class="btn-sec text-left">🐦 Quiz do Blue</button>
                    <button onclick="openAjuda()" class="btn-sec text-left">❓ Ajuda</button>
                    ${!u ? `<button onclick="openRegistrationModal()" class="btn-sec text-left">✍️ Cadastre-se</button>` : ''}
                    ${u && u.admin ? `<button onclick="openAdmin()" class="btn-sec text-left">⚙️ Painel Admin</button>` : ''}
                </div>
            </div>`);
    }

    // ---------------------------------------------------------------- MAPA DO BRASIL (RF-01 / UC-01)
    const ROTULO_UF = e => `${e.nome} (${e.sigla})`;
    const estadoPorSigla = sigla => app.estados.find(e => e.sigla === sigla);
    const publicadosDoEstado = estadoId => app.conteudos.filter(c => c.estado_id === estadoId);

    function scrollToMap() {
        document.getElementById('mapa-svg').scrollIntoView({ behavior: 'smooth', block: 'center' });
        (document.querySelector('#mapa-container .uf-path') || document.getElementById('btn-lista-estados'))?.focus({ preventScroll: true });
    }

    /** Passos 2 a 4 do fluxo principal: desenha o mapa; se falhar, aplica a exceção E2. */
    async function carregarMapa() {
        const box = document.getElementById('mapa-container');
        try {
            const m = (await api('mapa')).mapa;
            if (!Array.isArray(m.locations) || !m.locations.length) throw new Error('Mapa vazio.');
            const rotulos = Object.fromEntries(app.estados.map(e => [e.sigla.toLowerCase(), e]));
            box.innerHTML = `<svg viewBox="${esc(m.viewBox)}" class="w-full h-auto drop-shadow-lg" role="group" aria-label="Mapa do Brasil com as 27 unidades federativas">
                ${m.locations.map(l => {
                    const e = rotulos[l.id];
                    return `<path class="uf-path" data-sigla="${esc(l.id.toUpperCase())}" tabindex="0" role="button" aria-pressed="false"
                        aria-label="${esc(e ? ROTULO_UF(e) : l.nome)}" fill="${COR_REGIAO[e?.regiao] || '#999'}" d="${esc(l.path)}"/>`;
                }).join('')}</svg>`;
            box.querySelectorAll('.uf-path').forEach(p => {
                const e = estadoPorSigla(p.dataset.sigla);
                const mostrar = () => { document.getElementById('mapa-rotulo').textContent = e ? ROTULO_UF(e) : ''; };
                const limpar = () => { document.getElementById('mapa-rotulo').textContent = ufAtual ? ROTULO_UF(estadoPorSigla(ufAtual)) : ''; };
                p.addEventListener('pointerenter', mostrar);
                p.addEventListener('pointerleave', limpar);
                p.addEventListener('focus', mostrar);
                p.addEventListener('blur', limpar);
                p.addEventListener('click', () => selecionarUF(p.dataset.sigla));
                p.addEventListener('keydown', ev => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); selecionarUF(p.dataset.sigla); } });
            });
            marcarUFAtiva();
        } catch (e) {
            // E2.1 e E2.2: mensagem e relação das unidades federativas em texto
            box.innerHTML = `<div class="py-4">
                <p role="alert" class="text-sm font-bold text-red-700 bg-red-50 border border-red-200 rounded-xl p-3 text-center mb-3">Não foi possível carregar o mapa.</p>
                <ul class="grid grid-cols-2 gap-1.5 text-xs">${app.estados.map(s => `<li><button type="button" onclick="selecionarUF('${esc(s.sigla)}')" class="w-full text-left px-3 py-2 rounded-lg bg-creamCard border border-darkGreen/10 hover:border-terracotta">${esc(ROTULO_UF(s))}</button></li>`).join('')}</ul>
            </div>`;
        }
    }

    function marcarUFAtiva() {
        document.querySelectorAll('.uf-path').forEach(el => {
            const ativo = el.dataset.sigla === ufAtual;
            el.classList.toggle('active', ativo);
            el.setAttribute('aria-pressed', String(ativo));
        });
    }

    /** Painel lateral: dados do estado selecionado e da região a que pertence. */
    function atualizarPainelUF() {
        const e = ufAtual ? estadoPorSigla(ufAtual) : null;
        const regiao = e ? Object.values(REGIOES).find(r => r.title === e.regiao) : null;
        document.getElementById('uf-regiao').textContent = e ? `◆ REGIÃO ${e.regiao.toUpperCase()}` : '◆ BRASIL';
        document.getElementById('region-num').textContent = regiao ? regiao.id : '—';
        document.getElementById('region-title').textContent = e ? e.nome : 'Brasil';
        document.getElementById('region-desc').textContent = regiao ? regiao.desc
            : 'Passe o cursor sobre um estado para ver o nome e selecione-o para abrir a página da região.';
        document.getElementById('region-highlight').textContent = regiao ? regiao.highlight : 'Brasil de muitos Brasis';
        document.getElementById('region-count').textContent = !app.estados.length ? ''
            : e ? `${publicadosDoEstado(e.id).length} conteúdo(s) publicado(s) em ${e.sigla}`
                : `${app.estados.length} estados · ${app.conteudos.length} conteúdos no acervo`;
        document.getElementById('btn-explorar').disabled = !e;
        marcarUFAtiva();
    }

    /** Passos 5 a 7 do fluxo principal; E5 quando o estado não tem conteúdo publicado. */
    function selecionarUF(sigla) {
        const e = estadoPorSigla(sigla);
        const aviso = document.getElementById('mapa-aviso');
        if (!e) { toast('O acervo ainda está carregando...', true); return; }
        closeModal();
        if (!publicadosDoEstado(e.id).length) {
            ufAtual = null;
            atualizarPainelUF();
            document.getElementById('mapa-rotulo').textContent = '';
            aviso.textContent = 'Esta região ainda não possui conteúdo publicado.';
            aviso.classList.remove('hidden');
            return;
        }
        aviso.classList.add('hidden');
        ufAtual = sigla;
        atualizarPainelUF();
        document.getElementById('mapa-rotulo').textContent = ROTULO_UF(e);
        abrirEstado(sigla);
    }

    function explorarEstadoAtual() { if (ufAtual) abrirEstado(ufAtual); }

    /** Fluxo alternativo A5: lista de estados em ordem alfabética. */
    function abrirListaEstados() {
        const lista = [...app.estados].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
        if (!lista.length) { toast('O acervo ainda está carregando...', true); return; }
        openModal(`<div>
            ${cabecalhoModal('A5 · LISTA DE ESTADOS', 'Escolha um estado', `${lista.length} unidades federativas em ordem alfabética`)}
            <ul id="lista-estados" class="grid sm:grid-cols-2 gap-2 max-h-[55vh] overflow-y-auto pr-2">
                ${lista.map(s => `<li><button type="button" onclick="selecionarUF('${esc(s.sigla)}')" class="w-full text-left bloco hover:border-terracotta transition"><span class="font-bold">${esc(ROTULO_UF(s))}</span> <span class="text-[10px] uppercase tracking-wider text-darkGreen/70">· ${esc(s.regiao)}</span></button></li>`).join('')}
            </ul></div>`);
    }

    // ---------------------------------------------------------------- CONTEÚDO POR ESTADO (RF-02 / UC-02)
    // As oito categorias do RF-02; "Artigo Acadêmico" tem seção própria no site.
    const CATEGORIAS_UF = [['Culinária', 'Culinária'], ['Música', 'Música'], ['Literatura', 'Literatura'],
        ['Variação Linguística', 'Variações linguísticas'], ['Ponto Turístico', 'Pontos turísticos'],
        ['Acontecimento Histórico', 'Acontecimentos históricos'], ['Incidência Nominal', 'Incidência nominal'], ['Curiosidade', 'Curiosidades']];
    const rotuloCategoria = tipo => (CATEGORIAS_UF.find(c => c[0] === tipo) || [tipo, tipo])[1];

    const botaoCategoria = (e, tipo, rotulo, n) => `
        <button type="button" onclick="abrirCategoria('${esc(e.sigla)}', '${esc(tipo)}')" class="text-left p-4 rounded-2xl bg-creamCard border border-darkGreen/10 hover:border-terracotta transition shadow-sm">
            <span class="block font-display font-bold text-lg text-darkGreen">${esc(rotulo)}</span>
            <span class="text-[11px] font-bold uppercase tracking-wider text-darkGreen/70">${n} registro(s)</span>
        </button>`;

    /** Página da região (UC-01, passo 7): detalhes do estado e suas categorias. */
    function abrirEstado(sigla) {
        const e = estadoPorSigla(sigla);
        const regiao = Object.values(REGIOES).find(r => r.title === e.regiao);
        const doEstado = publicadosDoEstado(e.id);
        openModal(`<div>
            <div class="border-b border-darkGreen/10 pb-4 mb-4 pr-12">
                <span class="text-xs font-bold text-terracotta uppercase tracking-wider">REGIÃO ${esc(e.regiao.toUpperCase())}</span>
                <h2 id="titulo-estado" class="text-3xl font-display font-bold text-darkGreen">${esc(ROTULO_UF(e))}</h2>
            </div>
            <p class="text-sm text-darkGreen/80 mb-2 leading-relaxed">${esc(regiao?.desc || '')}</p>
            <p class="text-xs font-bold text-darkGreen/75 mb-5">${doEstado.length} conteúdo(s) publicado(s) neste estado. Escolha uma categoria:</p>
            <div id="categorias-estado" class="grid sm:grid-cols-2 gap-3">
                ${CATEGORIAS_UF.map(([tipo, rot]) => botaoCategoria(e, tipo, rot, doEstado.filter(c => c.tipo === tipo).length)).join('')}
            </div></div>`);
    }

    const botaoRegistro = c => `
        <button type="button" onclick="abrirConteudo(${c.id})" class="w-full text-left bloco hover:border-terracotta transition">
            <span class="block font-display font-bold text-lg">${esc(c.titulo)}</span>
            <span class="block text-xs text-darkGreen/70 line-clamp-2">${esc(c.texto)}</span>
        </button>`;

    /** Passos 1 a 3 do fluxo principal e exceção E1. */
    function abrirCategoria(sigla, tipo) {
        const e = estadoPorSigla(sigla);
        const doEstado = publicadosDoEstado(e.id);
        const itens = doEstado.filter(c => c.tipo === tipo);
        const voltar = `<button type="button" onclick="abrirEstado('${esc(sigla)}')" class="text-xs font-bold text-terracotta hover:underline mb-3">← Voltar para ${esc(ROTULO_UF(e))}</button>`;
        if (!itens.length) {
            const outras = CATEGORIAS_UF.filter(([t]) => t !== tipo && doEstado.some(c => c.tipo === t));
            openModal(`<div>${voltar}
                ${cabecalhoModal(esc(ROTULO_UF(e)), esc(rotuloCategoria(tipo)))}
                <p role="alert" id="aviso-categoria" class="text-sm font-bold text-amber-900 bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">Não há conteúdo publicado nesta categoria para o estado selecionado.</p>
                <p class="text-xs font-bold text-darkGreen/75 mb-2">Categorias com conteúdo publicado:</p>
                <div class="grid sm:grid-cols-2 gap-3">${outras.map(([t, rot]) => botaoCategoria(e, t, rot, doEstado.filter(c => c.tipo === t).length)).join('')}</div>
            </div>`);
            return;
        }
        openModal(`<div>${voltar}
            ${cabecalhoModal(esc(ROTULO_UF(e)), esc(rotuloCategoria(tipo)), `${itens.length} registro(s) publicado(s)`)}
            <div id="lista-registros" class="space-y-2 max-h-[50vh] overflow-y-auto pr-2">${itens.map(botaoRegistro).join('')}</div>
            <button type="button" onclick="abrirCategoriaTodos('${esc(sigla)}', '${esc(tipo)}')" class="mt-4 text-xs font-bold px-4 py-2 rounded-full border border-darkGreen/20 hover:border-terracotta">Ver em todos os estados</button>
        </div>`);
    }

    /** Fluxo alternativo A1: a categoria em todas as unidades federativas, agrupada por estado. */
    function abrirCategoriaTodos(sigla, tipo) {
        const itens = app.conteudos.filter(c => c.tipo === tipo);
        const grupos = [...new Set(itens.map(c => c.estado))].sort((a, b) => a.localeCompare(b, 'pt-BR'));
        openModal(`<div>
            <button type="button" onclick="abrirCategoria('${esc(sigla)}', '${esc(tipo)}')" class="text-xs font-bold text-terracotta hover:underline mb-3">← Voltar para ${esc(ROTULO_UF(estadoPorSigla(sigla)))}</button>
            ${cabecalhoModal('TODOS OS ESTADOS', esc(rotuloCategoria(tipo)), `${itens.length} registro(s) em ${grupos.length} unidade(s) federativa(s)`)}
            <div id="lista-todos" class="space-y-4 max-h-[55vh] overflow-y-auto pr-2">
                ${grupos.map(g => `<section><h3 class="text-sm font-bold text-terracotta uppercase tracking-wider mb-1">${esc(g)}</h3>
                    <div class="space-y-2">${itens.filter(c => c.estado === g).map(botaoRegistro).join('')}</div></section>`).join('')
                    || '<p class="text-sm text-darkGreen/70">Nenhum registro publicado nesta categoria.</p>'}
            </div></div>`);
    }

    // ---------------------------------------------------------------- PERCURSOS (PILLS)
    function filterCategory(cat) {
        app.categoria = cat;
        document.querySelectorAll('.cat-pill').forEach(btn => { btn.classList.remove('active'); btn.setAttribute('aria-selected', 'false'); });
        const activeBtn = document.getElementById(`pill-${cat}`);
        if (activeBtn) { activeBtn.classList.add('active'); activeBtn.setAttribute('aria-selected', 'true'); }

        const container = document.getElementById('category-cards-grid');
        if (!app.conteudos.length) { container.innerHTML = cardsCarregando(5, true); return; }

        const filtro = PERCURSOS[cat];
        const items = NOMES_REGIOES.map(reg => {
            const lista = app.conteudos.filter(c => filtro(c) && c.regiao === reg)
                .sort((a, b) => String(b.data_cadastro).localeCompare(String(a.data_cadastro)) || b.id - a.id);
            return lista.length ? { reg, c: lista[0] } : null;
        }).filter(Boolean);

        container.innerHTML = items.map(it => `
            <article class="card-conteudo relative bg-darkGreenCard p-5 rounded-2xl border border-cream/10 hover:border-accentGold/40 focus-within:border-accentGold flex flex-col justify-between shadow-md">
                <div>
                    <span class="text-[10px] font-bold text-accentGold uppercase tracking-wider block mb-1.5">${esc(it.reg.toUpperCase())} · ${esc(it.c.sigla)}</span>
                    <h3 class="text-lg font-display font-bold text-cream mb-2"><button type="button" onclick="abrirConteudo(${it.c.id})" class="text-left after:absolute after:inset-0">${esc(it.c.titulo)}</button></h3>
                    <p class="text-xs text-cream/80 leading-relaxed font-normal line-clamp-4">${esc(it.c.texto)}</p>
                </div>
                <span class="text-[10px] font-bold text-cream/70 uppercase tracking-wider mt-4" aria-hidden="true">Ver detalhes →</span>
            </article>`).join('') || `<p class="col-span-full text-center text-cream/60 text-sm py-6">Nenhum conteúdo publicado neste tema.</p>`;
    }

    // ---------------------------------------------------------------- SEÇÕES TEMÁTICAS
    function montarSecoes() {
        document.querySelectorAll('section[data-secao]').forEach(sec => {
            const chave = sec.dataset.secao, escura = sec.dataset.escura === '1';
            const opcoes = ['<option value="todas">Todas as Regiões</option>', ...NOMES_REGIOES.map(r => `<option value="${r}">${r}</option>`)].join('');
            const cabecalho = `
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-4 border-b ${escura ? 'border-cream/10' : 'border-darkGreen/10'} gap-4">
                    <div>
                        <span class="text-xs font-bold ${escura ? 'text-accentGold' : 'text-terracotta'} uppercase tracking-widest block mb-1">${sec.dataset.rotulo}</span>
                        <h2 class="text-4xl font-display font-bold ${escura ? 'text-cream' : 'text-darkGreen'}">${sec.dataset.titulo}</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <label for="filtro-${chave}" class="text-xs font-bold ${escura ? 'text-cream/70' : 'text-darkGreen/75'}">Filtrar Região:</label>
                        <select id="filtro-${chave}" onchange="renderSpecificSection('${chave}', this.value)" class="${escura ? 'bg-darkGreenCard border-cream/20 text-cream focus:border-accentGold' : 'bg-creamCard border-darkGreen/20 text-darkGreen focus:border-terracotta'} border font-bold text-xs rounded-xl px-4 py-2 focus:outline-none">${opcoes}</select>
                    </div>
                </div>
                <div id="grid-${chave}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" aria-live="polite">${cardsCarregando(3, escura)}</div>`;
            sec.innerHTML = escura ? `<div class="max-w-7xl mx-auto">${cabecalho}</div>` : cabecalho;
        });
    }

    function cardsCarregando(n, escura) {
        return Array.from({ length: n }, () => `<div class="h-36 rounded-2xl animate-pulse ${escura ? 'bg-darkGreenCard' : 'bg-creamCard border border-darkGreen/10'}"></div>`).join('');
    }

    function renderSpecificSection(sectionKey, regionFilter) {
        app.filtros[sectionKey] = regionFilter;
        const container = document.getElementById(`grid-${sectionKey}`);
        if (!container || !app.conteudos.length) return;

        let items = app.conteudos.filter(SECOES[sectionKey]);
        if (regionFilter !== 'todas') items = items.filter(c => c.regiao === regionFilter);

        const isDarkSection = container.closest('section').dataset.escura === '1';
        if (items.length === 0) {
            container.innerHTML = `<p class="col-span-full text-center py-8 ${isDarkSection ? 'text-cream/60' : 'text-darkGreen/75'} text-sm">Nenhum item publicado para esta região nesta categoria.</p>`;
            return;
        }

        const cardBgClass = isDarkSection ? 'bg-darkGreenCard border-cream/10 text-cream' : 'bg-creamCard border-darkGreen/10 text-darkGreen';
        const tagColorClass = isDarkSection ? 'text-accentGold' : 'text-terracotta';
        const favBg = isDarkSection ? 'bg-cream/10' : 'bg-darkGreen/5';

        container.innerHTML = items.map(it => `
            <article class="card-conteudo relative ${cardBgClass} p-6 rounded-2xl border shadow-sm hover:shadow-md focus-within:shadow-md">
                <button onclick="favoritar(${it.id})" class="btn-fav z-10 ${favBg}" aria-label="${it.favorito ? 'Remover dos favoritos' : 'Favoritar'}" title="${it.favorito ? 'Remover dos favoritos' : 'Favoritar'}">${it.favorito ? '❤️' : '🤍'}</button>
                <span class="text-[10px] font-bold ${tagColorClass} block mb-1 uppercase tracking-widest pr-10">${esc(it.estado)} · ${esc(it.sigla)}${it.subcategoria && sectionKey === 'festas' ? ' · ' + esc(it.subcategoria) : ''}</span>
                <h3 class="text-xl font-display font-bold mb-2 pr-8"><button type="button" onclick="abrirConteudo(${it.id})" class="text-left after:absolute after:inset-0">${esc(it.titulo)}</button></h3>
                <p class="text-xs opacity-80 leading-relaxed line-clamp-4">${esc(it.texto)}</p>
                <div class="flex items-center justify-between mt-4 text-[11px] font-bold">
                    <span>${it.total_avaliacoes ? `<span class="text-accentGold">${estrelas(it.media)}</span> (${it.total_avaliacoes})` : 'Sem avaliações'}</span>
                    <span aria-hidden="true">Ver detalhes →</span>
                </div>
            </article>`).join('');
    }

    function rerenderTudo() {
        filterCategory(app.categoria);
        Object.keys(SECOES).forEach(s => renderSpecificSection(s, app.filtros[s] || 'todas'));
    }

    // ---------------------------------------------------------------- DETALHE DO CONTEÚDO (RF-02, RF-06, RF-07, RF-09 a RF-15, RF-18, RF-21)
    const ROTULOS_FICHA = {
        autor: 'Autor', genero: 'Gênero', artista: 'Artista', genero_musical: 'Gênero musical',
        periodo_historico: 'Período', local: 'Local', localizacao: 'Localização',
        significado_expressao: 'Significado', exemplo_uso: 'Exemplo de uso', significado_termo: 'Significado',
        origem: 'Origem', categoria: 'Categoria', area_pesquisa: 'Área de pesquisa',
        trecho: 'Trecho', letra: 'Letra', receita: 'Receita', historia: 'História', curiosidade: 'Curiosidade', resumo: 'Resumo'
    };
    const CAMPOS_LONGOS = ['trecho', 'letra', 'receita', 'historia', 'curiosidade', 'resumo'];
    const ICONE_RECURSO = { 'material pedagógico': '📄', 'plano de aula': '🗂️', 'vídeo-aula': '🎬', 'link externo': '🔗', 'mapa externo': '🗺️' };
    const COR_REGIAO = { 'Norte': '#229A88', 'Nordeste': '#F06548', 'Centro-Oeste': '#F3C151', 'Sudeste': '#297BB3', 'Sul': '#781FB6' };
    const ICONE_TIPO = {
        'Culinária': '🍲', 'Música': '🎶', 'Literatura': '📖', 'Variação Linguística': '💬', 'Ponto Turístico': '🏛️',
        'Acontecimento Histórico': '⏳', 'Incidência Nominal': '🔤', 'Curiosidade': '🎉', 'Artigo Acadêmico': '📚'
    };

    /**
     * Arte do conteúdo desenhada pelo próprio site (SVG em data URI), usada como capa
     * quando o conteúdo não tem foto e como imagem do quebra-cabeça. O desenho é sorteado
     * a partir do id, então o mesmo conteúdo sempre gera a mesma arte, sem baixar nada.
     */
    function arteConteudo(c) {
        const cor = COR_REGIAO[c.regiao] || '#0B261A';
        const semente = (Number(c.id) || 1) * 2654435761 % 4294967296;
        const num = i => ((semente >> (i * 3)) % 1000) / 1000;
        const circulos = [0, 1, 2, 3, 4].map(i => {
            const cx = 30 + num(i) * 240, cy = 30 + num(i + 5) * 240;
            const r = 30 + num(i + 2) * 70;
            return `<circle cx="${cx.toFixed(0)}" cy="${cy.toFixed(0)}" r="${r.toFixed(0)}" fill="#F4EFE6" opacity="${(0.08 + num(i + 1) * 0.16).toFixed(2)}"/>`;
        }).join('');
        const faixas = [0, 1, 2].map(i => {
            const y = 40 + i * 95 + num(i + 7) * 30;
            return `<path d="M0 ${y.toFixed(0)} Q 150 ${(y - 50 + num(i) * 100).toFixed(0)} 300 ${y.toFixed(0)}" stroke="#E6A123" stroke-width="${(2 + num(i + 3) * 5).toFixed(1)}" fill="none" opacity="0.5"/>`;
        }).join('');
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="300" height="300">`
            + `<rect width="300" height="300" fill="${cor}"/>${circulos}${faixas}`
            + `<text x="150" y="168" font-size="96" text-anchor="middle" opacity="0.95">${ICONE_TIPO[c.tipo] || '✦'}</text>`
            + `<text x="150" y="250" font-size="20" font-family="Verdana,sans-serif" font-weight="bold" fill="#F4EFE6" text-anchor="middle">${esc(String(c.sigla || ''))}</text></svg>`;
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }

    async function abrirConteudo(id) {
        openModal(carregando('Carregando conteúdo...'));
        let c;
        try { c = (await api('conteudo', null, '&id=' + id)).conteudo; }
        catch (e) { openModal(`<p class="py-10 text-center text-sm text-red-700">${esc(e.message)}</p>`); return; }

        conteudoAberto = c; // o leitor de artigo reaproveita o conteúdo já carregado
        const u = app.usuario;
        const minha = u ? c.avaliacoes.find(a => Number(a.usuario_id) === u.id) : null;
        const media = c.avaliacoes.length ? c.avaliacoes.reduce((s, a) => s + Number(a.nota), 0) / c.avaliacoes.length : null;
        const f = c.ficha;
        const curtos = Object.entries(f).filter(([k]) => ROTULOS_FICHA[k] && !CAMPOS_LONGOS.includes(k));
        const longos = Object.entries(f).filter(([k]) => CAMPOS_LONGOS.includes(k));
        const corAtual = { 'Atual': 'bg-green-100 text-green-800', 'Desatualizado': 'bg-amber-100 text-amber-800' }[c.atualidade] || 'bg-darkGreen/10 text-darkGreen';
        const mapa = c.tipo === 'Ponto Turístico' && (f.localizacao || c.titulo)
            ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(`${c.titulo}, ${f.localizacao || c.estado}`) : null;

        openModal(`
            <div>
                <div class="border-b border-darkGreen/10 pb-4 mb-5 pr-12">
                    <span class="text-xs font-bold text-terracotta uppercase tracking-wider">${esc(c.tipo)}${c.subcategoria ? ' · ' + esc(c.subcategoria) : ''} · ${esc(c.estado)} (${esc(c.sigla)}) · ${esc(c.regiao)}</span>
                    <h2 class="text-3xl font-display font-bold text-darkGreen mt-1">${esc(c.titulo)}</h2>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="selo ${corAtual}" title="Calculado por fn_classificar_atualidade">${esc(c.atualidade)}</span>
                        <span class="selo bg-darkGreen/5">Cadastrado em ${dataBR(c.data_cadastro)}</span>
                        ${c.situacao !== 'oficial' ? `<span class="selo bg-terracotta/10 text-terracotta">Contribuição ${esc(c.situacao)}</span>` : ''}
                        <button onclick="favoritar(${c.id}, true)" class="ml-auto text-xs font-bold px-3 py-1.5 rounded-full border border-darkGreen/15 hover:border-terracotta transition">${c.favorito ? '❤️ Favoritado' : '🤍 Favoritar'}</button>
                    </div>
                </div>

                ${c.imagem_disponivel
                    ? `<img src="${caminhoArquivo(f.imagem, 'imagens')}" alt="${esc(c.titulo)}" class="w-full max-h-72 object-cover rounded-2xl mb-4 border border-darkGreen/10">`
                    : `<img src="${arteConteudo(c)}" alt="Ilustração gerada pelo site para ${esc(c.titulo)}" class="w-full h-40 object-cover rounded-2xl mb-4 border border-darkGreen/10">`}
                <p class="text-sm text-darkGreen/90 leading-relaxed mb-4">${esc(c.texto_simplificado)}</p>

                ${c.texto_tecnico ? `<div class="p-4 rounded-xl bg-creamCard border-l-4 border-terracotta mb-4"><span class="text-[10px] font-bold uppercase tracking-widest text-terracotta block mb-1">Texto técnico · visível para docentes e pesquisadores</span><p class="text-xs text-darkGreen/80 leading-relaxed">${esc(c.texto_tecnico)}</p></div>` : ''}

                ${curtos.length || c.ingredientes.length || c.estados_relacionados.length ? `
                <div class="grid sm:grid-cols-2 gap-2 mb-3 text-xs">
                    ${curtos.map(([k, v]) => `<p class="p-3 rounded-xl bg-creamCard border border-darkGreen/10"><strong>${ROTULOS_FICHA[k]}:</strong> ${esc(v)}</p>`).join('')}
                    ${c.ingredientes.length ? `<p class="p-3 rounded-xl bg-creamCard border border-darkGreen/10"><strong>Ingredientes:</strong> ${c.ingredientes.map(esc).join(', ')}</p>` : ''}
                    ${c.estados_relacionados.length ? `<p class="p-3 rounded-xl bg-creamCard border border-darkGreen/10"><strong>Estados envolvidos:</strong> ${c.estados_relacionados.map(e => esc(e.nome)).join(', ')}</p>` : ''}
                </div>` : ''}
                ${longos.map(([k, v]) => `<div class="p-3 rounded-xl bg-creamCard border border-darkGreen/10 text-xs mb-2"><strong class="block mb-1">${ROTULOS_FICHA[k]}:</strong><p class="whitespace-pre-line text-darkGreen/85 leading-relaxed">${esc(v)}</p></div>`).join('')}

                <div class="flex flex-wrap gap-2 my-4">
                    <button type="button" id="btn-audiodescricao" onclick="lerConteudo()" aria-pressed="false" class="btn-sec">🔊 Ouvir descrição</button>
                    ${mapa ? `<a href="${mapa}" target="_blank" rel="noopener" class="btn-sec">🗺️ Ver no Google Maps</a>` : ''}
                    ${c.audio_disponivel ? `<a href="${caminhoArquivo(f.audio, 'arquivos')}" target="_blank" rel="noopener" class="btn-sec">🎧 Ouvir</a>` : ''}
                    ${c.tipo === 'Artigo Acadêmico' ? `<button onclick="abrirArtigo()" class="btn-sec">📄 Ler artigo completo</button>` : ''}
                    ${c.arquivo_disponivel ? `<a href="${caminhoArquivo(f.arquivo, 'arquivos')}" target="_blank" rel="noopener" class="btn-sec">⬇️ Baixar o PDF</a>` : ''}
                </div>

                ${c.referencias ? `<p class="text-[11px] text-darkGreen/75 mb-4"><strong>Referências:</strong> ${esc(c.referencias)}</p>` : ''}

                ${c.recursos.length ? `<div class="mb-5"><h3 class="text-[11px] font-bold uppercase tracking-widest text-terracotta mb-2">Materiais e links complementares</h3>
                    <div class="grid sm:grid-cols-2 gap-2">${c.recursos.map(r => `
                        <a href="${urlSegura(r.endereco)}" target="_blank" rel="noopener" class="bloco text-xs hover:border-terracotta block">
                            <span class="font-bold">${ICONE_RECURSO[r.tipo] || '↗'} ${esc(r.titulo)}</span>
                            <span class="block text-darkGreen/75 mt-0.5">${esc(r.tipo)}${r.docente ? ' · por ' + esc(r.docente) : ''}</span>
                            ${r.descricao ? `<span class="block text-darkGreen/70 mt-1">${esc(r.descricao)}</span>` : ''}
                        </a>`).join('')}</div></div>` : ''}

                ${c.atividades.length ? `<div class="mb-5"><h3 class="text-[11px] font-bold uppercase tracking-widest text-terracotta mb-2">Atividades interativas</h3>
                    <div class="space-y-2">${c.atividades.map(a => `
                        <div class="bloco text-xs flex flex-wrap items-center justify-between gap-2">
                            <div><strong>${esc(a.titulo)}</strong> <span class="text-darkGreen/70">· ${esc(a.tipo)}${a.docente ? ' · ' + esc(a.docente) : ''}</span>
                                ${a.descricao ? `<p class="text-darkGreen/70 mt-0.5">${esc(a.descricao)}</p>` : ''}</div>
                            ${a.tipo !== 'dinâmica de aula' ? `<button onclick="abrirAtividade(${a.id}, ${c.id})" class="px-4 py-2 rounded-lg bg-accentGold text-darkGreen font-bold">▶ Fazer atividade</button>` : '<span class="selo bg-darkGreen/5">Para sala de aula</span>'}
                        </div>`).join('')}</div></div>` : ''}

                ${u && u.docente ? painelDocente(c.id) : ''}

                <div class="border-t border-darkGreen/10 pt-5 mt-2">
                    <div class="flex items-baseline justify-between mb-3">
                        <h3 class="text-lg font-display font-bold">Avaliações</h3>
                        <span class="text-sm font-bold text-accentGold">${media !== null ? `${estrelas(media)} · ${c.avaliacoes.length} avaliação(ões)` : ''}</span>
                    </div>
                    ${u ? `
                        <form onsubmit="enviarAvaliacao(event, ${c.id})" class="p-4 rounded-2xl bg-creamCard border border-darkGreen/10 mb-4 space-y-3">
                            <div class="flex items-center gap-1" id="estrelas-form" role="radiogroup" aria-label="Nota de 1 a 5">
                                ${[1, 2, 3, 4, 5].map(n => `<button type="button" class="estrela ${minha && n <= minha.nota ? 'on' : ''}" onclick="marcarNota(${n})" aria-label="${n} estrela${n > 1 ? 's' : ''}">★</button>`).join('')}
                                <span class="text-xs text-darkGreen/75 ml-2">${minha ? 'Edite sua nota' : 'Sua nota'}</span>
                            </div>
                            <input type="hidden" id="nota" value="${minha ? minha.nota : 0}">
                            <details>
                                <summary class="text-xs font-bold text-terracotta">+ Enviar um comentário ou correção para a equipe (opcional)</summary>
                                <div class="grid sm:grid-cols-4 gap-2 mt-2">
                                    <select id="tipo-feedback" class="campo sm:col-span-1" aria-label="Tipo de feedback"><option value="comentário">Comentário</option><option value="correção">Correção</option></select>
                                    <textarea id="comentario" maxlength="2000" rows="2" class="campo sm:col-span-3" placeholder="Viu algum erro ou quer sugerir algo? Só a equipe lê."></textarea>
                                </div>
                            </details>
                            ${boxErro('erro-avaliacao')}
                            <button class="px-5 py-2.5 rounded-xl bg-terracotta hover:bg-terracottaDark text-white font-bold text-xs transition">${minha ? 'Atualizar avaliação' : 'Enviar avaliação'}</button>
                        </form>` : `
                        <p class="text-xs text-darkGreen/70 mb-4 p-4 rounded-2xl bg-creamCard border border-darkGreen/10">
                            <button onclick="openLogin()" class="font-bold text-terracotta underline">Entre</button> para avaliar, favoritar e enviar correções.</p>`}
                    <div class="grid sm:grid-cols-2 gap-2">
                        ${c.avaliacoes.length ? c.avaliacoes.map(a => `
                            <div class="p-3 rounded-xl border border-darkGreen/10 bg-white/50 text-xs flex justify-between">
                                <span><strong>${esc(a.nome)}</strong> <span class="text-darkGreen/70">· ${esc(a.classificacao)}</span></span>
                                <span class="text-accentGold font-bold" aria-label="${a.nota} de 5">${'★'.repeat(a.nota)}${'☆'.repeat(5 - a.nota)}</span>
                            </div>`).join('') : '<p class="text-xs text-darkGreen/70 italic">Ainda não há avaliações. Seja o primeiro!</p>'}
                    </div>
                </div>
            </div>`);
    }

    // ---------------------------------------------------------------- AUDIODESCRIÇÃO (RNF-01)
    /** Narra o texto do conteúdo aberto e descreve a imagem exibida (síntese de voz do navegador, pt-BR). */
    function pararLeitura() {
        if ('speechSynthesis' in window) window.speechSynthesis.cancel();
        const b = document.getElementById('btn-audiodescricao');
        if (b) { b.setAttribute('aria-pressed', 'false'); b.textContent = '🔊 Ouvir descrição'; }
    }

    function lerConteudo() {
        const b = document.getElementById('btn-audiodescricao');
        if (!('speechSynthesis' in window) || !conteudoAberto) { toast('Seu navegador não oferece leitura em voz alta.', true); return; }
        if (b.getAttribute('aria-pressed') === 'true') { pararLeitura(); return; }
        const img = document.querySelector('#modal-content img');
        const partes = [conteudoAberto.titulo, conteudoAberto.texto_simplificado,
            img ? 'Imagem: ' + (img.getAttribute('alt') || '') : ''].filter(Boolean);
        const fala = new SpeechSynthesisUtterance(partes.map(t => t.replace(/[.\s]+$/, '')).join('. ') + '.');
        fala.lang = 'pt-BR';
        fala.onend = pararLeitura;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(fala);
        b.setAttribute('aria-pressed', 'true');
        b.textContent = '⏹ Parar leitura';
    }

    // ---------------------------------------------------------------- LEITOR DE ARTIGO (RF-02, RF-21)
    /**
     * Mostra o artigo acadêmico inteiro dentro do site, montado com o que está no banco
     * (resumo, texto, texto técnico e referências). O PDF, quando existe na pasta, é só
     * um download a mais — o artigo pode ser lido sem ele.
     */
    function abrirArtigo() {
        const c = conteudoAberto;
        if (!c) return;
        const f = c.ficha;
        const paragrafos = t => String(t || '').split(/\n{2,}|\r\n\r\n/).filter(p => p.trim())
            .map(p => `<p>${esc(p.trim())}</p>`).join('');
        openModal(`
            <div class="py-2">
                <div class="border-b border-darkGreen/10 pb-4 mb-5 pr-12">
                    <span class="text-xs font-bold text-terracotta uppercase tracking-widest block">Artigo acadêmico${f.area_pesquisa ? ' · ' + esc(f.area_pesquisa) : ''}</span>
                    <h2 class="text-3xl font-display font-bold text-darkGreen mt-1">${esc(c.titulo)}</h2>
                    <p class="text-xs text-darkGreen/75 mt-2">${esc(c.estado)} (${esc(c.sigla)}) · ${esc(c.regiao)} · publicado em ${dataBR(c.data_cadastro)}</p>
                </div>
                <div class="artigo-corpo text-sm text-darkGreen/90 max-h-[55vh] overflow-y-auto pr-2">
                    ${f.resumo ? `<div class="p-4 rounded-xl bg-creamCard border-l-4 border-accentGold mb-5">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-terracotta block mb-1">Resumo</span>
                        <p class="text-xs leading-relaxed">${esc(f.resumo)}</p></div>` : ''}
                    ${paragrafos(c.texto_simplificado)}
                    ${c.texto_tecnico ? `<h3 class="text-base font-display font-bold text-darkGreen mt-6 mb-2">Desenvolvimento técnico</h3>${paragrafos(c.texto_tecnico)}`
                        : `<p class="text-[11px] italic text-darkGreen/75 mt-4">O desenvolvimento técnico deste artigo é liberado para docentes, pesquisadores e administradores (RF-21).</p>`}
                    ${c.referencias ? `<h3 class="text-base font-display font-bold text-darkGreen mt-6 mb-2">Referências</h3>
                        <p class="text-xs text-darkGreen/75 whitespace-pre-line">${esc(c.referencias)}</p>` : ''}
                </div>
                <div class="grid sm:grid-cols-2 gap-3 mt-5">
                    ${c.arquivo_disponivel ? `<a href="${caminhoArquivo(f.arquivo, 'arquivos')}" target="_blank" rel="noopener" class="btn-sec text-center py-3.5">⬇️ Baixar o PDF</a>` : ''}
                    <button onclick="abrirConteudo(${c.id})" class="btn-sec py-3.5">← Voltar à ficha do conteúdo</button>
                </div>
            </div>`);
    }

    // ---------------------------------------------------------------- ÁREA DO DOCENTE (RF-15, RF-18)
    function painelDocente(id) {
        return `
            <details class="mb-5 bloco">
                <summary class="text-xs font-bold text-terracotta uppercase tracking-widest">✏️ Área do docente: criar atividade ou anexar material</summary>
                <div class="grid md:grid-cols-2 gap-4 mt-4">
                    <form onsubmit="criarAtividade(event, ${id})" class="space-y-2">
                        <p class="text-xs font-bold">Nova atividade (RF-15)</p>
                        <select id="at-tipo" class="campo" aria-label="Tipo da atividade">${TIPOS_ATIVIDADE.map(t => `<option>${t}</option>`).join('')}</select>
                        <input id="at-titulo" class="campo" maxlength="150" required placeholder="Título da atividade" aria-label="Título da atividade">
                        <textarea id="at-desc" class="campo" maxlength="255" rows="2" placeholder="Descrição / instruções (opcional)" aria-label="Descrição"></textarea>
                        ${boxErro('erro-atividade')}
                        <button class="px-4 py-2 rounded-lg bg-darkGreen text-cream text-xs font-bold">Criar atividade</button>
                    </form>
                    <form onsubmit="anexarRecurso(event, ${id})" class="space-y-2">
                        <p class="text-xs font-bold">Anexar material ou link (RF-18)</p>
                        <select id="rc-tipo" class="campo" aria-label="Tipo do material">${TIPOS_RECURSO.map(t => `<option>${t}</option>`).join('')}</select>
                        <input id="rc-titulo" class="campo" maxlength="150" required placeholder="Título" aria-label="Título do material">
                        <input id="rc-desc" class="campo" maxlength="255" placeholder="Descrição (opcional)" aria-label="Descrição do material">
                        <div class="rounded-xl border border-dashed border-darkGreen/25 p-3 space-y-2 bg-cream/60">
                            <label class="text-[11px] font-bold block" for="rc-arquivo">Enviar arquivo do computador</label>
                            <input type="file" id="rc-arquivo" class="w-full text-[11px]" accept=".pdf,.txt,.png,.jpg,.jpeg,.webp,.mp3,.mp4,.docx,.pptx,.odt,.odp"
                                   aria-describedby="rc-arquivo-dica" onchange="marcarEnvio()">
                            <p id="rc-arquivo-dica" class="text-[10px] text-darkGreen/75">PDF, Word, PowerPoint, imagem, áudio ou vídeo — até 10 MB.</p>
                            <p class="text-[10px] font-bold text-darkGreen/70 text-center">— ou —</p>
                            <input id="rc-endereco" class="campo" maxlength="255" placeholder="https://... (link externo)" aria-label="Endereço (link externo)" onchange="marcarEnvio()">
                        </div>
                        ${boxErro('erro-recurso')}
                        <button id="rc-botao" class="px-4 py-2 rounded-lg bg-darkGreen text-cream text-xs font-bold">Anexar</button>
                    </form>
                </div>
            </details>`;
    }

    /** Arquivo e link são alternativas: escolher um desabilita o outro campo. */
    function marcarEnvio() {
        const arq = document.getElementById('rc-arquivo');
        const link = document.getElementById('rc-endereco');
        if (!arq || !link) return;
        link.disabled = arq.files.length > 0;
        arq.disabled = link.value.trim() !== '';
    }

    async function criarAtividade(ev, id) {
        ev.preventDefault();
        try {
            await api('criar_atividade', { conteudo_id: id, tipo: val('at-tipo'), titulo: val('at-titulo'), descricao: val('at-desc') });
            toast('Atividade criada!');
            abrirConteudo(id);
        } catch (e) { erroInline('erro-atividade', e.message); }
    }

    async function anexarRecurso(ev, id) {
        ev.preventDefault();
        const arq = document.getElementById('rc-arquivo');
        const botao = document.getElementById('rc-botao');
        const dados = new FormData();
        dados.append('conteudo_id', id);
        dados.append('tipo', val('rc-tipo'));
        dados.append('titulo', val('rc-titulo'));
        dados.append('descricao', val('rc-desc'));
        dados.append('endereco', val('rc-endereco'));
        if (arq && arq.files[0]) dados.append('arquivo', arq.files[0]);
        try {
            if (botao) { botao.disabled = true; botao.textContent = 'Enviando...'; }
            const r = await api('anexar_recurso', dados);
            toast(r.arquivo ? 'Arquivo enviado e anexado!' : 'Material anexado!');
            abrirConteudo(id);
        } catch (e) {
            if (botao) { botao.disabled = false; botao.textContent = 'Anexar'; }
            erroInline('erro-recurso', e.message);
        }
    }

    function marcarNota(n) {
        document.getElementById('nota').value = n;
        document.querySelectorAll('#estrelas-form .estrela').forEach((b, i) => b.classList.toggle('on', i < n));
    }

    async function enviarAvaliacao(ev, id) {
        ev.preventDefault();
        const nota = Number(document.getElementById('nota').value);
        try {
            const r = await api('avaliar', { conteudo_id: id, nota, comentario: val('comentario'), tipo_feedback: val('tipo-feedback') || 'comentário' });
            toast(r.feedback ? 'Avaliação salva e feedback enviado à equipe!' : 'Avaliação salva!');
            atualizarConteudoLocal();
            abrirConteudo(id);
        } catch (e) { erroInline('erro-avaliacao', e.message); }
    }

    async function favoritar(id, reabrir = false) {
        if (!app.usuario) { toast('Entre na sua conta para favoritar.', true); openLogin(); return; }
        try {
            const r = await api('favoritar', { conteudo_id: id });
            const c = app.conteudos.find(x => x.id === id);
            if (c) c.favorito = r.favorito;
            rerenderTudo();
            toast(r.favorito ? 'Adicionado aos favoritos ❤️' : 'Removido dos favoritos');
            if (reabrir) abrirConteudo(id);
        } catch (e) { toast(e.message, true); }
    }

    async function atualizarConteudoLocal() {
        try { app.conteudos = (await api('conteudos')).conteudos; rerenderTudo(); atualizarPainelUF(); carregarNumeros(); } catch (e) { /* silencioso */ }
    }

    // ---------------------------------------------------------------- PESQUISA (RF-03 / UC-03)
    function openPesquisa(termo = '') {
        openModal(`
            <div class="py-2">
                ${cabecalhoModal('ACERVO', 'Pesquisar', 'Busque por tema, título, estado, sigla ou autor.')}
                <form onsubmit="pesquisar(event)" class="flex gap-2 mb-5" role="search">
                    <label for="busca" class="sr-only">Termo de pesquisa</label>
                    <input id="busca" type="search" class="campo" maxlength="100" placeholder="Ex.: chimarrão, Bahia, cordel" value="${esc(termo)}" autocomplete="off">
                    <button class="px-5 rounded-xl bg-terracotta text-white font-bold text-sm hover:bg-terracottaDark">Buscar</button>
                </form>
                ${boxErro('erro-busca')}
                <div id="resultado-busca" aria-live="polite"></div>
            </div>`);
        setTimeout(() => document.getElementById('busca')?.focus(), 50);
        if (termo) pesquisar(null);
    }

    async function pesquisar(ev) {
        if (ev) ev.preventDefault();
        const termo = val('busca');
        const box = document.getElementById('resultado-busca');
        const campo = document.getElementById('busca');
        document.getElementById('erro-busca').classList.add('hidden');
        if (termo.length < 2) { // campo em branco: não consulta e devolve o foco ao campo
            box.innerHTML = '';
            erroInline('erro-busca', 'Digite pelo menos 2 letras para pesquisar.');
            campo.focus();
            return;
        }
        box.innerHTML = carregando('Pesquisando...');
        // UC-03, E4: consulta acima de 2 s (RNF-03) mantém o indicador de carregamento visível
        const lento = setTimeout(() => {
            box.innerHTML = `<div id="indicador-lento" role="status" class="py-10 text-center text-sm text-darkGreen/70">
                <span class="inline-block w-6 h-6 mb-2 rounded-full border-2 border-terracotta border-t-transparent animate-spin" aria-hidden="true"></span>
                <p>A consulta está demorando mais que o esperado. Aguarde um instante...</p></div>`;
        }, 2000);
        try {
            const r = await api('pesquisar', null, '&termo=' + encodeURIComponent(termo));
            clearTimeout(lento);
            if (!r.resultados.length) {
                box.innerHTML = `<div class="text-center py-6">
                    <p class="text-sm font-bold mb-2">Nenhum resultado para "${esc(termo)}".</p>
                    ${r.sugestoes.length ? `<p class="text-xs text-darkGreen/70 mb-3">Você quis dizer:</p>
                        <div class="flex flex-wrap justify-center gap-2">${r.sugestoes.map(s => `<button onclick="openPesquisa(this.dataset.t)" data-t="${esc(s)}" class="px-3 py-1.5 rounded-full bg-creamCard border border-darkGreen/15 text-xs font-bold hover:border-terracotta">${esc(s)}</button>`).join('')}</div>`
                        : '<p class="text-xs text-darkGreen/70">Tente outra palavra ou explore pelo mapa.</p>'}</div>`;
                return;
            }
            box.innerHTML = `<p class="text-xs font-bold text-darkGreen/75 mb-3">${r.resultados.length} resultado(s)</p>
                <div class="space-y-2">${r.resultados.map(c => `
                    <button onclick="abrirConteudo(${c.id})" class="w-full text-left bloco hover:border-terracotta transition">
                        <span class="text-[10px] font-bold text-terracotta uppercase tracking-wider">${esc(c.tipo)} · ${esc(c.estado)} (${esc(c.sigla)})</span>
                        <span class="block font-display font-bold text-lg">${esc(c.titulo)}</span>
                        <span class="block text-xs text-darkGreen/70 line-clamp-2">${esc(c.texto)}</span>
                    </button>`).join('')}</div>`;
        } catch (e) { clearTimeout(lento); box.innerHTML = ''; erroInline('erro-busca', e.message); }
    }

    /* ------------------------------------------------------------------------------------
     * CENTRAL DE AJUDA (RF-08)
     * Dois canais: o Tchê Bot responde na hora pelo acervo; no canal humano a mensagem
     * fica pendente até um administrador responder pelo painel. A conversa é montada com
     * o histórico de atendimentos do usuário, por isso a resposta da equipe aparece aqui.
     * ------------------------------------------------------------------------------------ */
    let conversaAjuda = [];

    async function openAjuda() {
        if (!app.usuario) {
            openModal(`<div class="max-w-md mx-auto py-2 text-center">
                ${cabecalhoModal('PRECISA DE AJUDA?', 'Central de Ajuda')}
                <p class="text-sm text-darkGreen/80 mb-4">Você pode tirar dúvidas com o <strong>Tchê Bot</strong> (agente de IA) ou deixar uma mensagem para a <strong>equipe</strong>. Para isso, entre na sua conta.</p>
                <p class="text-xs text-darkGreen/75 mb-5">Dica: muita coisa você encontra direto na <button onclick="openPesquisa()" class="underline font-bold text-terracotta">pesquisa</button>.</p>
                <button onclick="openLogin()" class="btn-primario max-w-xs">Entrar</button>
            </div>`);
            return;
        }
        openModal(carregando('Abrindo a central de ajuda...'));
        await carregarConversa();
        openModal(`
            <div class="py-2 max-w-2xl mx-auto">
                ${cabecalhoModal('PRECISA DE AJUDA?', 'Central de Ajuda', 'Escolha com quem quer falar.')}
                <div id="ajuda-conversa" class="space-y-3 mb-4 max-h-[38vh] overflow-y-auto" aria-live="polite">${renderConversa()}</div>
                <form onsubmit="enviarAjuda(event)" class="space-y-3">
                    <fieldset class="grid sm:grid-cols-2 gap-2">
                        <legend class="rotulo">Atendimento:</legend>
                        <label class="bloco flex items-start gap-2 cursor-pointer text-xs"><input type="radio" name="canal" value="agente de IA" checked class="mt-0.5">
                            <span><strong>🤖 Tchê Bot</strong><br><span class="text-darkGreen/75">Resposta na hora, a partir do acervo</span></span></label>
                        <label class="bloco flex items-start gap-2 cursor-pointer text-xs"><input type="radio" name="canal" value="atendente humano" class="mt-0.5">
                            <span><strong>🙋 Atendente da equipe</strong><br><span class="text-darkGreen/75">A resposta aparece nesta conversa</span></span></label>
                    </fieldset>
                    <label class="rotulo" for="ajuda-pergunta">Sua mensagem:</label>
                    <textarea id="ajuda-pergunta" required maxlength="500" rows="3" class="campo" placeholder="Ex.: O que é o barreado?"></textarea>
                    ${boxErro('erro-ajuda')}
                    <div class="grid sm:grid-cols-[1fr_auto] gap-2 items-center">
                        <button id="btn-ajuda" class="btn-primario">Enviar</button>
                        <button type="button" onclick="atualizarConversa()" class="btn-sec">↻ Ver se já responderam</button>
                    </div>
                </form>
            </div>`);
    }

    /** Monta a conversa a partir dos atendimentos já registrados no banco. */
    async function carregarConversa() {
        try {
            const r = await api('minhas_mensagens');
            conversaAjuda = [];
            r.mensagens.slice().reverse().forEach(m => {
                conversaAjuda.push({ de: 'eu', texto: m.topico });
                conversaAjuda.push({
                    de: 'atendente', texto: m.resposta, pendente: !m.resposta,
                    autor: m.canal === 'agente de IA' ? (m.agente || 'Tchê Bot') : 'Equipe Tchê & Cultura',
                    data: m.data_resposta
                });
            });
        } catch (e) {
            conversaAjuda = [];
        }
    }

    function renderConversa() {
        if (!conversaAjuda.length) return '<p class="text-xs italic text-darkGreen/75 text-center py-4">Nenhuma mensagem ainda. Escreva sua dúvida abaixo.</p>';
        return conversaAjuda.map(m => m.de === 'eu'
            ? `<div class="ml-auto max-w-[85%] p-3 rounded-2xl rounded-br-sm bg-terracotta text-white text-xs">${esc(m.texto)}</div>`
            : `<div class="max-w-[85%] p-3 rounded-2xl rounded-bl-sm bg-creamCard border border-darkGreen/10 text-xs">
                ${m.autor ? `<span class="block text-[10px] font-bold uppercase tracking-wider text-terracotta mb-1">${esc(m.autor)}${m.data ? ' · ' + dataBR(m.data) : ''}</span>` : ''}
                ${m.pendente
                    ? '<span class="italic text-darkGreen/75">⏳ Na fila do atendimento humano. A resposta aparece aqui assim que a equipe responder.</span>'
                    : esc(m.texto)}
                ${m.relacionados?.length ? `<div class="flex flex-wrap gap-1.5 mt-2">${m.relacionados.map(c => `<button onclick="abrirConteudo(${c.id})" class="px-2.5 py-1 rounded-full bg-cream border border-darkGreen/15 font-bold hover:border-terracotta">${esc(c.titulo)} · ${esc(c.sigla)}</button>`).join('')}</div>` : ''}</div>`).join('');
    }

    async function atualizarConversa() {
        await carregarConversa();
        const conv = document.getElementById('ajuda-conversa');
        if (conv) { conv.innerHTML = renderConversa(); conv.scrollTop = conv.scrollHeight; }
    }

    async function enviarAjuda(ev) {
        ev.preventDefault();
        const canal = document.querySelector('input[name="canal"]:checked').value;
        const pergunta = val('ajuda-pergunta');
        const btn = document.getElementById('btn-ajuda');
        btn.disabled = true;
        try {
            const r = await api('ajuda', { canal, pergunta });
            conversaAjuda.push({ de: 'eu', texto: pergunta });
            conversaAjuda.push({
                de: 'atendente', texto: r.resposta, pendente: r.aguardando, relacionados: r.relacionados,
                autor: canal === 'agente de IA' ? 'Tchê Bot' : 'Equipe Tchê & Cultura'
            });
            const conv = document.getElementById('ajuda-conversa');
            conv.innerHTML = renderConversa();
            conv.scrollTop = conv.scrollHeight;
            document.getElementById('ajuda-pergunta').value = '';
        } catch (e) { erroInline('erro-ajuda', e.message); }
        btn.disabled = false;
    }

    // ---------------------------------------------------------------- NÚMEROS (VIEWS)
    async function carregarNumeros() {
        try {
            const n = await api('estatisticas');
            const t = n.totais;
            document.getElementById('numeros-totais').innerHTML = [
                [t.conteudos, 'conteúdos publicados'], [t.estados, 'estados com conteúdo'], [t.usuarios, 'usuários'], [t.avaliacoes, 'avaliações']
            ].map(([v, r]) => `
                <div class="bg-creamCard p-5 rounded-2xl border border-darkGreen/10">
                    <span class="text-4xl font-serif italic text-terracotta font-bold block">${esc(v)}</span>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-darkGreen/75">${r}</span>
                </div>`).join('');

            const max = Math.max(...n.tipos.map(x => Number(x.quantidade)), 1);
            document.getElementById('numeros-tipos').innerHTML = n.tipos.map(x => barra(x.tipo, x.quantidade, max)).join('');
            const maxE = Math.max(...n.estados.map(x => Number(x.total_conteudos)), 1);
            document.getElementById('numeros-estados').innerHTML = n.estados.map(x => barra(`${x.estado} (${x.sigla})`, x.total_conteudos, maxE)).join('');
            document.getElementById('numeros-ranking').innerHTML = rankingHtml(n.ranking, true);
            return n;
        } catch (e) { return null; }
    }

    function barra(rotulo, valor, max) {
        return `<div>
            <div class="flex justify-between text-xs mb-1"><span class="text-cream/85">${esc(rotulo)}</span><span class="font-bold text-accentGold">${esc(valor)}</span></div>
            <div class="h-1.5 rounded-full bg-cream/10 overflow-hidden"><div class="h-full bg-accentGold rounded-full" style="width:${(Number(valor) / max) * 100}%"></div></div>
        </div>`;
    }

    function rankingHtml(ranking, escuro) {
        if (!ranking.length) return `<p class="text-xs ${escuro ? 'text-cream/60' : 'text-darkGreen/75'} italic">Ninguém jogou ainda. Seja o primeiro!</p>`;
        return ranking.map((r, i) => `
            <div class="flex items-center justify-between text-xs ${escuro ? 'text-cream/85' : 'text-darkGreen'}">
                <span><strong class="${escuro ? 'text-accentGold' : 'text-terracotta'} mr-2">${i + 1}º</strong>${esc(r.nome)}</span>
                <span class="font-bold">${esc(r.melhor_pontuacao)} pts <span class="font-normal opacity-60">· ${esc(r.tentativas)}x</span></span>
            </div>`).join('');
    }

    // ---------------------------------------------------------------- MODAL
    let focoAntes = null;
    function openModal(htmlContent) {
        const fundo = document.getElementById('modal-backdrop');
        if (fundo.classList.contains('hidden')) focoAntes = document.activeElement;
        document.getElementById('modal-content').innerHTML = htmlContent;
        const titulo = document.querySelector('#modal-content h2');
        document.querySelectorAll('#modal-titulo').forEach(e => e.removeAttribute('id'));
        if (titulo) titulo.id = 'modal-titulo';
        fundo.classList.remove('hidden');
        const card = document.getElementById('modal-card');
        card.scrollTop = 0;
        document.body.style.overflow = 'hidden';
        if (!card.contains(document.activeElement)) card.querySelector('button')?.focus({ preventScroll: true });
    }

    function closeModal() {
        pararLeitura();
        document.getElementById('modal-backdrop').classList.add('hidden');
        document.body.style.overflow = '';
        if (focoAntes && document.body.contains(focoAntes)) focoAntes.focus({ preventScroll: true });
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
        if (e.key === 'Tab' && !document.getElementById('modal-backdrop').classList.contains('hidden')) { // mantém o foco no modal
            const f = [...document.querySelectorAll('#modal-card button, #modal-card a[href], #modal-card input, #modal-card select, #modal-card textarea, #modal-card summary')].filter(x => !x.disabled && x.offsetParent !== null);
            if (!f.length) return;
            if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
            else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
        }
    });

    /* =====================================================================================
     * PARTE 12 — JAVASCRIPT: CONTA, CONTRIBUIÇÃO, PAINEL ADMIN E ATIVIDADES
     * Seções: login · cadastro em 3 etapas · perfil · contribuição · painel do administrador ·
     * atividades (quiz, jogo da memória e quebra-cabeça) · Quiz do Blue.
     * ===================================================================================== */

    // ---------------------------------------------------------------- LOGIN (RF-22)
    function openLogin() {
        openModal(`
            <div class="py-2 max-w-md mx-auto">
                ${cabecalhoModal('BEM-VINDO DE VOLTA', 'Entrar')}
                <form onsubmit="fazerLogin(event)" class="space-y-4">
                    <div><label class="rotulo" for="login-email">E-mail:</label><input type="email" id="login-email" required class="campo" placeholder="seu@email.com" autocomplete="email"></div>
                    <div><label class="rotulo" for="login-senha">Senha:</label><input type="password" id="login-senha" required class="campo" placeholder="Sua senha" autocomplete="current-password"></div>
                    ${boxErro('erro-login')}
                    <button type="submit" class="btn-primario">Entrar →</button>
                </form>
                <p class="text-xs text-center text-darkGreen/70 mt-4">Não tem conta? <button onclick="openRegistrationModal()" class="font-bold text-terracotta underline">Cadastre-se</button></p>
                <p class="text-[11px] text-center text-darkGreen/70 mt-4 p-3 rounded-xl bg-creamCard border border-darkGreen/10 leading-relaxed">
                    Contas de teste (senha <strong>Tche@2026</strong>): ana@email.com (estudante), carlos@email.com (docente),
                    marta@email.com (pesquisadora), joana@email.com (público geral), admin@email.com (administrador)</p>
            </div>`);
        setTimeout(() => document.getElementById('login-email')?.focus(), 50);
    }

    async function fazerLogin(ev) {
        ev.preventDefault();
        try {
            const r = await api('login', { email: val('login-email'), senha: document.getElementById('login-senha').value });
            app.usuario = r.usuario;
            conversaAjuda = [];
            renderHeader();
            closeModal();
            toast(`Olá, ${r.usuario.nome.split(' ')[0]}!`);
            atualizarConteudoLocal();
        } catch (e) { erroInline('erro-login', e.message); }
    }

    async function sair() {
        await api('logout', {});
        app.usuario = null;
        conversaAjuda = [];
        renderHeader();
        closeModal();
        toast('Você saiu da sua conta.');
        atualizarConteudoLocal();
    }

    // ---------------------------------------------------------------- CADASTRO EM 3 ETAPAS (RF-22, RNF-04)
    let registrationData = {};

    function openRegistrationModal() {
        const r = registrationData;
        const hoje = new Date().toISOString().slice(0, 10);
        openModal(`
            <div class="py-2 max-w-lg mx-auto">
                ${cabecalhoModal('JUNTE-SE AO TCHÊ & CULTURA · ETAPA 1 DE 3', 'Cadastro de Usuário')}
                <form onsubmit="handleRegistrationStep1(event)" class="space-y-4">
                    <div><label class="rotulo" for="reg-name">Seu Nome Completo:</label>
                        <input type="text" id="reg-name" required maxlength="100" placeholder="Digite seu nome..." class="campo" value="${esc(r.nome)}" autocomplete="name"></div>
                    <div><label class="rotulo" for="reg-email">E-mail:</label>
                        <input type="email" id="reg-email" required maxlength="100" placeholder="seu@email.com" class="campo" value="${esc(r.email)}" autocomplete="email"></div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="rotulo" for="reg-pass">Senha (mín. 6):</label>
                            <input type="password" id="reg-pass" required minlength="6" placeholder="Crie uma senha" class="campo" autocomplete="new-password"></div>
                        <div><label class="rotulo" for="reg-birth">Data de nascimento:</label>
                            <input type="date" id="reg-birth" required min="1900-01-01" max="${hoje}" class="campo" value="${esc(r.data_nascimento)}"></div>
                    </div>
                    <div><label class="rotulo" for="reg-phone">Telefone (opcional):</label>
                        <input type="tel" id="reg-phone" maxlength="20" placeholder="(41) 99999-9999" class="campo" value="${esc(r.telefone)}" autocomplete="tel"></div>
                    <p class="dica">Sua senha é guardada criptografada (bcrypt). Nome completo, e-mail e telefone não aparecem para outros usuários.</p>
                    ${boxErro('erro-cadastro')}
                    <button type="submit" class="btn-primario">Avançar para Perfil →</button>
                </form>
                <p class="text-xs text-center text-darkGreen/70 mt-4">Já tem conta? <button onclick="openLogin()" class="font-bold text-terracotta underline">Entrar</button></p>
            </div>`);
        if (r.erro) { erroInline('erro-cadastro', r.erro); r.erro = null; }
    }

    function handleRegistrationStep1(event) {
        event.preventDefault();
        registrationData = {
            nome: val('reg-name'), email: val('reg-email'), senha: document.getElementById('reg-pass').value,
            data_nascimento: val('reg-birth'), telefone: val('reg-phone')
        };
        renderRegistrationStep2();
    }

    function renderRegistrationStep2() {
        const opcao = (role, txt, sub) => `
            <button onclick="selectUserRole('${role}')" class="w-full text-left p-4 rounded-xl bg-creamCard hover:border-terracotta border border-darkGreen/10 text-xs text-darkGreen transition flex items-center justify-between group">
                <span><strong>${txt}</strong><span class="block text-darkGreen/75 mt-0.5">${sub}</span></span><span class="group-hover:translate-x-1 transition text-terracotta" aria-hidden="true">→</span>
            </button>`;
        openModal(`
            <div class="py-2 max-w-lg mx-auto">
                ${cabecalhoModal('ETAPA 2 DE 3', 'Qual é o seu perfil?', 'Escolha apenas uma das opções abaixo:')}
                <div class="space-y-3">
                    ${opcao('estudante', '🎓 Sou estudante', 'Faz atividades e quizzes e guarda a pontuação')}
                    ${opcao('pesquisador', '🔬 Sou pesquisador', 'Vê textos técnicos e envia conteúdos e artigos')}
                    ${opcao('docente', '📚 Sou docente', 'Envia conteúdos, cria atividades e anexa materiais')}
                    ${opcao('publico', '🌎 Público em geral', 'Explora, avalia e favorita o acervo')}
                </div>
                <button onclick="openRegistrationModal()" class="mt-5 text-xs font-bold text-darkGreen/75 hover:text-terracotta">← Voltar</button>
            </div>`);
    }

    function selectUserRole(role) {
        registrationData.perfil = role;
        if (role === 'publico') { finalizeRegistration(null); return; } // não há campos específicos
        renderRegistrationStep3(role);
    }

    function renderRegistrationStep3(role) {
        const titulo = role === 'estudante' ? 'Informações Acadêmicas' : 'Informações Profissionais';
        const campo = (id, rot, ph, max) => `<div><label class="rotulo" for="${id}">${rot}</label><input type="text" id="${id}" required maxlength="${max}" placeholder="${ph}" class="campo"></div>`;
        let fields = '';
        if (role === 'estudante') {
            fields = campo('reg-year', 'Ano em que está (Ex: 2º Ano / 3º Semestre):', 'Digite o ano ou período...', 50)
                   + campo('reg-institution', 'Instituição de Ensino:', 'Nome da escola ou faculdade...', 100);
        } else if (role === 'pesquisador') {
            fields = campo('reg-institution', 'Instituição de Atuação:', 'Nome da universidade ou centro...', 100)
                   + campo('reg-time', 'Há quanto tempo na área (Ex: 5 anos):', 'Tempo de atuação...', 50)
                   + campo('reg-linha', 'Linha de pesquisa:', 'Ex.: História do Sul do Brasil', 100);
        } else {
            fields = campo('reg-institution', 'Instituição de Atuação:', 'Nome da escola ou universidade...', 100)
                   + campo('reg-cert', 'Formação / Certificado (Ex: Licenciatura em História):', 'Sua formação...', 255);
        }
        openModal(`
            <div class="py-2 max-w-lg mx-auto">
                ${cabecalhoModal('ETAPA 3 DE 3', titulo)}
                <form onsubmit="finalizeRegistration(event)" class="space-y-4">
                    ${fields}
                    ${boxErro('erro-cadastro')}
                    <button type="submit" id="btn-cadastrar" class="btn-primario">Concluir Cadastro 🎉</button>
                </form>
                <button onclick="renderRegistrationStep2()" class="mt-5 text-xs font-bold text-darkGreen/75 hover:text-terracotta">← Voltar</button>
            </div>`);
    }

    async function finalizeRegistration(event) {
        if (event) event.preventDefault();
        const r = registrationData;
        const dados = { ...r, instituicao: val('reg-institution'), nivel: val('reg-year'), tempo: val('reg-time'), certificado: val('reg-cert'), linha: val('reg-linha') };
        const btn = document.getElementById('btn-cadastrar');
        if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }

        let resp;
        try { resp = await api('cadastro', dados); }
        catch (e) {
            if (event) {
                erroInline('erro-cadastro', e.message);
                if (btn) { btn.disabled = false; btn.textContent = 'Concluir Cadastro 🎉'; }
                if (/e-mail|senha|nome|nascimento/i.test(e.message)) { r.erro = e.message; setTimeout(openRegistrationModal, 1400); }
            } else { r.erro = e.message; openRegistrationModal(); }
            return;
        }

        app.usuario = resp.usuario;
        renderHeader();
        atualizarConteudoLocal();
        let extra = '';
        if (r.perfil === 'estudante') extra = `Estudante do(a) <strong>${esc(dados.nivel)}</strong> na instituição <strong>${esc(dados.instituicao)}</strong>.`;
        else if (r.perfil === 'pesquisador') extra = `Pesquisador(a) na instituição <strong>${esc(dados.instituicao)}</strong>, linha <strong>${esc(dados.linha)}</strong>.`;
        else if (r.perfil === 'docente') extra = `Docente na instituição <strong>${esc(dados.instituicao)}</strong> (${esc(dados.certificado)}).`;
        else extra = 'Aproveite para explorar todo o acervo do Tchê & Cultura!';

        openModal(`
            <div class="text-center py-6">
                <div class="w-12 h-12 bg-green-100 text-green-700 rounded-full flex items-center justify-center mx-auto mb-4 text-xl" aria-hidden="true">✓</div>
                <h2 class="text-3xl font-display font-bold text-darkGreen mb-2">Cadastro Realizado com Sucesso!</h2>
                <p class="text-xs text-darkGreen/80 max-w-sm mx-auto mb-4 leading-relaxed">
                    Parabéns, <strong>${esc(r.nome)}</strong> (${idade(r.data_nascimento)} anos)! Você já está conectado.<br>${extra}
                </p>
                <button onclick="closeModal()" class="px-6 py-3 rounded-xl bg-darkGreen text-cream font-bold text-xs uppercase tracking-wider hover:bg-darkGreenCard transition">Explorar o Site</button>
            </div>`);
        registrationData = {};
    }

    // ---------------------------------------------------------------- PERFIL
    async function openPerfil() {
        openModal(carregando('Carregando perfil...'));
        let p;
        try { p = await api('perfil'); } catch (e) { openModal(`<p class="py-10 text-center text-sm text-red-700">${esc(e.message)}</p>`); return; }
        const u = p.usuario;
        app.usuario = u;
        const ROT_DET = { instituicao: 'Instituição', nivel_escolaridade: 'Ano/Período', instituicao_trabalho: 'Instituição', certificado_exp: 'Formação', tempo_experiencia: 'Experiência', linha_pesquisa: 'Linha de pesquisa', area_responsabilidade: 'Área', data_inicio: 'Desde' };
        const det = p.detalhe ? Object.entries(p.detalhe).filter(([, v]) => v).map(([k, v]) => `<p><strong>${ROT_DET[k] || k}:</strong> ${esc(k === 'data_inicio' ? dataBR(v) : v)}</p>`).join('') : '';
        const titulo = t => `<h3 class="text-xs font-bold text-terracotta uppercase tracking-widest mb-2">${t}</h3>`;
        const vazio = t => `<p class="text-xs text-darkGreen/70 italic">${t}</p>`;

        openModal(`
            <div>
                <div class="flex flex-wrap items-center gap-4 border-b border-darkGreen/10 pb-5 mb-5 pr-12">
                    <span class="w-14 h-14 rounded-2xl bg-terracotta text-white text-2xl font-display font-bold flex items-center justify-center" aria-hidden="true">${esc(u.nome.charAt(0))}</span>
                    <div>
                        <span class="text-xs font-bold text-terracotta uppercase tracking-wider">${esc(u.classificacao)}</span>
                        <h2 class="text-3xl font-display font-bold text-darkGreen">${esc(u.nome)}</h2>
                        <p class="text-xs text-darkGreen/75">${esc(u.email)} · ${idade(u.data_nascimento)} anos${u.telefone ? ' · ' + esc(u.telefone) : ''}</p>
                    </div>
                </div>
                <div class="grid sm:grid-cols-3 gap-3 mb-6">
                    <div class="bloco"><span class="text-3xl font-serif italic text-terracotta font-bold block">${p.total_contribuicoes}</span><span class="text-[10px] font-bold uppercase tracking-wider text-darkGreen/75">contribuições enviadas</span></div>
                    <div class="bloco"><span class="text-3xl font-serif italic text-terracotta font-bold block">${p.favoritos.length}</span><span class="text-[10px] font-bold uppercase tracking-wider text-darkGreen/75">favoritos</span></div>
                    <div class="bloco text-xs space-y-1">${det || '<p class="text-darkGreen/75">Público em geral</p>'}</div>
                </div>

                <div class="flex flex-wrap gap-2 mb-6">
                    ${u.docente || u.pesquisador ? `<button onclick="openContribuir()" class="px-4 py-2.5 rounded-xl bg-terracotta text-white font-bold text-xs hover:bg-terracottaDark transition">+ Contribuir com conteúdo</button>` : ''}
                    ${u.admin ? `<button onclick="openAdmin()" class="px-4 py-2.5 rounded-xl bg-darkGreen text-cream font-bold text-xs hover:bg-darkGreenCard transition">Painel Admin</button>` : ''}
                    <button onclick="openAjuda()" class="btn-sec">Central de ajuda</button>
                    <button onclick="sair()" class="px-4 py-2.5 rounded-xl border border-red-300 text-red-700 font-bold text-xs hover:bg-red-50 transition ml-auto">Sair</button>
                </div>

                ${titulo('Meus favoritos')}
                <div class="flex flex-wrap gap-2 mb-6">${p.favoritos.length ? p.favoritos.map(f => `
                    <button onclick="abrirConteudo(${f.id})" class="px-3 py-1.5 rounded-full bg-creamCard border border-darkGreen/10 text-[11px] font-bold hover:border-terracotta transition">❤️ ${esc(f.titulo)} <span class="text-darkGreen/70">· ${esc(f.estado)}</span></button>`).join('')
                    : vazio('Clique no 🤍 de qualquer card para favoritar.')}</div>

                ${u.docente || u.pesquisador || p.contribuicoes.length ? `
                ${titulo('Minhas contribuições')}
                <div class="space-y-2 mb-6">${p.contribuicoes.length ? p.contribuicoes.map(c => `
                    <div class="p-3 rounded-xl bg-creamCard border border-darkGreen/10 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <button onclick="abrirConteudo(${c.id})" class="text-left font-bold hover:text-terracotta">#${c.id} · ${esc(c.titulo)} <span class="font-normal text-darkGreen/70">(${esc(c.tipo)} · ${esc(c.estado)})</span></button>
                            <span class="flex items-center gap-2">
                                ${Number(c.editado) ? '<span class="selo bg-darkGreen/5">editado na moderação</span>' : ''}
                                <span class="selo ${COR_STATUS[c.status] || ''}">${esc(c.status)}</span>
                                ${c.status === 'rascunho' ? `<button onclick="enviarRascunho(${c.id})" class="px-3 py-1.5 rounded-lg bg-terracotta text-white font-bold">Enviar para moderação</button>` : ''}
                            </span>
                        </div>
                        ${c.motivo_rejeicao ? `<p class="mt-2 text-red-700"><strong>Motivo da rejeição:</strong> ${esc(c.motivo_rejeicao)}</p>` : ''}
                    </div>`).join('') : vazio('Você ainda não enviou contribuições.')}</div>` : ''}

                ${u.docente ? `
                ${titulo('Minhas atividades')}
                <div class="space-y-1.5 mb-6">${p.minhas_atividades.length ? p.minhas_atividades.map(a => `
                    <button onclick="abrirConteudo(${a.conteudo_id})" class="w-full text-left flex justify-between text-xs p-2.5 rounded-lg bg-creamCard border border-darkGreen/10 hover:border-terracotta"><span><strong>${esc(a.titulo)}</strong> · ${esc(a.tipo)}</span><span class="text-darkGreen/75">${esc(a.conteudo || '')}</span></button>`).join('')
                    : vazio('Abra um conteúdo e use a "Área do docente" para criar atividades e anexar materiais.')}</div>` : ''}

                ${u.pesquisador ? `
                ${titulo('Da sua linha de pesquisa')}
                <div class="flex flex-wrap gap-2 mb-6">${p.linha_pesquisa.length ? p.linha_pesquisa.map(c => `
                    <button onclick="abrirConteudo(${c.id})" class="px-3 py-1.5 rounded-full bg-creamCard border border-darkGreen/10 text-[11px] font-bold hover:border-terracotta"><span class="text-terracotta">${esc(c.tipo)}</span> · ${esc(c.titulo)}</button>`).join('')
                    : vazio('Nenhum conteúdo publicado relacionado à sua linha de pesquisa ainda.')}</div>` : ''}

                ${u.estudante ? `
                ${titulo('Minhas tentativas')}
                <div class="space-y-1.5 mb-6">${p.tentativas.length ? p.tentativas.map(t => `
                    <div class="flex justify-between text-xs p-2.5 rounded-lg bg-creamCard border border-darkGreen/10"><span>${esc(t.atividade)} · ${esc(t.tipo)} · ${dataBR(t.data_hora)}</span><strong>${t.pontuacao} pts</strong></div>`).join('')
                    : vazio('Jogue o Quiz do Blue ou faça uma atividade para registrar sua pontuação.')}</div>` : ''}

                ${titulo('Minhas mensagens (RF-08)')}
                <div class="space-y-2">${p.mensagens.length ? p.mensagens.map(m => `
                    <div class="p-3 rounded-xl bg-creamCard border border-darkGreen/10 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong>${esc(m.topico)}</strong>
                            <span class="selo ${m.resposta ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}">${m.resposta ? 'respondida' : 'aguardando'}</span>
                        </div>
                        <p class="text-darkGreen/70 mt-1">${esc(m.canal)}${m.agente ? ' · ' + esc(m.agente) : ''} · enviada em ${dataBR(m.data_hora)}</p>
                        ${m.resposta ? `<p class="mt-2 p-2 rounded-lg bg-white/70 text-darkGreen/85">${esc(m.resposta)}</p>` : ''}
                    </div>`).join('')
                    : vazio('Use a Central de ajuda para falar com o Tchê Bot ou com a equipe.')}</div>
            </div>`);
    }

    async function enviarRascunho(id) {
        try {
            await api('enviar_rascunho', { conteudo_id: id });
            toast('Rascunho enviado para moderação!');
            openPerfil();
        } catch (e) { toast(e.message, true); }
    }

    // ---------------------------------------------------------------- CONTRIBUIÇÃO (RF-16, RF-17, RF-19, RF-20)
    function opcoesEstados() {
        return NOMES_REGIOES.map(r => `<optgroup label="${r}">${app.estados.filter(e => e.regiao === r)
            .map(e => `<option value="${e.id}">${esc(e.nome)} (${esc(e.sigla)})</option>`).join('')}</optgroup>`).join('');
    }

    function openContribuir() {
        const u = app.usuario;
        if (!u) { toast('Entre na sua conta para contribuir.', true); openLogin(); return; }
        if (!podeContribuir()) {
            openModal(`<div class="max-w-md mx-auto py-2 text-center">
                ${cabecalhoModal('ACERVO COLABORATIVO', 'Contribuições')}
                <p class="text-sm text-darkGreen/80 mb-4">O envio de conteúdos é feito por <strong>docentes</strong> e <strong>pesquisadores</strong>, para garantir a qualidade do acervo.</p>
                <p class="text-xs text-darkGreen/70">Você pode ajudar avaliando os conteúdos e enviando <strong>correções</strong> pela área de avaliação de cada um deles.</p>
            </div>`);
            return;
        }
        const tipos = TIPOS.filter(t => t !== 'Artigo Acadêmico' || u.pesquisador);
        openModal(`
            <div class="py-2 max-w-xl mx-auto">
                ${cabecalhoModal('ACERVO COLABORATIVO', 'Contribuir com conteúdo', 'Salve como rascunho ou envie direto para a moderação do administrador.')}
                <form onsubmit="enviarContribuicao(event)" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="rotulo" for="ct-tipo">Categoria:</label><select id="ct-tipo" required class="campo" onchange="ajustarFormContribuicao()">${tipos.map(t => `<option>${t}</option>`).join('')}</select></div>
                        <div><label class="rotulo" for="ct-estado">Estado:</label><select id="ct-estado" required class="campo">${opcoesEstados()}</select></div>
                    </div>
                    <div><label class="rotulo" for="ct-titulo">Título / nome:</label><input id="ct-titulo" required maxlength="100" class="campo" placeholder="Ex.: Festa do Divino de Paraty"></div>
                    <div id="ct-autor-box" class="hidden"><label class="rotulo" for="ct-autor">Autor da obra:</label><input id="ct-autor" maxlength="100" class="campo" placeholder="Ex.: Domínio popular"></div>
                    <div><label class="rotulo" for="ct-texto">Texto simplificado (para todos):</label><textarea id="ct-texto" required maxlength="2000" rows="3" class="campo" placeholder="Explique de forma simples..."></textarea></div>
                    <div><label class="rotulo" for="ct-tecnico">Texto técnico (opcional):</label><textarea id="ct-tecnico" maxlength="4000" rows="3" class="campo" placeholder="Versão aprofundada, visível para docentes e pesquisadores"></textarea></div>
                    <div><label class="rotulo" for="ct-refs">Referências${u.pesquisador ? ' (obrigatórias para pesquisador)' : ' (opcional)'}:</label><textarea id="ct-refs" ${u.pesquisador ? 'required' : ''} maxlength="1000" rows="2" class="campo" placeholder="Autor, título, ano..."></textarea></div>
                    ${boxErro('erro-contribuicao')}
                    <div class="grid sm:grid-cols-2 gap-3">
                        <button type="submit" data-situacao="rascunho" class="btn-sec py-4">Salvar rascunho</button>
                        <button type="submit" data-situacao="pendente" id="btn-contribuir" class="btn-primario">Enviar para moderação</button>
                    </div>
                </form>
            </div>`);
        ajustarFormContribuicao();
    }

    function ajustarFormContribuicao() {
        const lit = val('ct-tipo') === 'Literatura';
        document.getElementById('ct-autor-box').classList.toggle('hidden', !lit);
        document.getElementById('ct-autor').required = lit;
    }

    async function enviarContribuicao(ev) {
        ev.preventDefault();
        const situacao = ev.submitter?.dataset.situacao || 'pendente';
        const botoes = ev.target.querySelectorAll('button[type=submit]');
        botoes.forEach(b => b.disabled = true);
        try {
            const r = await api('contribuir', {
                tipo: val('ct-tipo'), estado_id: Number(val('ct-estado')), titulo: val('ct-titulo'), autor: val('ct-autor'),
                texto: val('ct-texto'), texto_tecnico: val('ct-tecnico'), referencias: val('ct-refs'), situacao
            });
            openModal(`
                <div class="text-center py-6">
                    <div class="w-12 h-12 bg-green-100 text-green-700 rounded-full flex items-center justify-center mx-auto mb-4 text-xl" aria-hidden="true">✓</div>
                    <h2 class="text-3xl font-display font-bold text-darkGreen mb-2">${r.situacao === 'rascunho' ? 'Rascunho salvo!' : 'Contribuição enviada!'}</h2>
                    <p class="text-xs text-darkGreen/80 max-w-sm mx-auto mb-6">Registrada como conteúdo <strong>#${r.conteudo_id}</strong>.
                        ${r.situacao === 'rascunho' ? 'Envie para moderação pelo seu perfil quando estiver pronta.' : 'Ela aparece no site assim que um administrador aprovar.'}</p>
                    <button onclick="openPerfil()" class="px-6 py-3 rounded-xl bg-darkGreen text-cream font-bold text-xs uppercase tracking-wider hover:bg-darkGreenCard transition">Ver minhas contribuições</button>
                </div>`);
        } catch (e) { erroInline('erro-contribuicao', e.message); botoes.forEach(b => b.disabled = false); }
    }

    // ---------------------------------------------------------------- PAINEL ADMIN (RF-23 a RF-26)
    let abaAdmin = 'contribuicoes';
    let cacheContribuicoes = [];

    async function openAdmin(aba = abaAdmin) {
        abaAdmin = aba;
        const abas = [['contribuicoes', 'Moderação'], ['estatisticas', 'Estatísticas'], ['relatorio', 'Relatório por período'], ['feedbacks', 'Feedbacks'], ['atendimentos', 'Atendimentos']];
        const topo = `
            <div class="border-b border-darkGreen/10 pb-4 mb-5 pr-12">
                <span class="text-xs font-bold text-terracotta uppercase tracking-wider">ADMINISTRAÇÃO</span>
                <h2 class="text-3xl font-display font-bold text-darkGreen mb-4">Painel do Administrador</h2>
                <div class="flex flex-wrap gap-2" role="tablist">${abas.map(([k, r]) => `<button role="tab" aria-selected="${k === aba}" onclick="openAdmin('${k}')" class="aba ${k === aba ? 'active' : ''}">${r}</button>`).join('')}</div>
            </div>`;
        openModal(topo + `<div id="admin-corpo" class="text-sm text-darkGreen/75">Carregando...</div>`);
        const corpo = document.getElementById('admin-corpo');
        try {
            if (aba === 'contribuicoes') corpo.innerHTML = await adminContribuicoes();
            else if (aba === 'estatisticas') corpo.innerHTML = await adminEstatisticas();
            else if (aba === 'relatorio') {
                const hoje = new Date().toISOString().slice(0, 10);
                corpo.innerHTML = `
                    <p class="text-xs mb-3">Executa a procedure <em>sp_relatorio_conteudos_periodo</em>.</p>
                    <form onsubmit="gerarRelatorio(event)" class="flex flex-wrap items-end gap-3 mb-4">
                        <div><label class="rotulo" for="rel-ini">Início:</label><input id="rel-ini" type="date" class="campo" value="2026-01-01"></div>
                        <div><label class="rotulo" for="rel-fim">Fim:</label><input id="rel-fim" type="date" class="campo" value="${hoje}"></div>
                        <button class="px-5 py-3 rounded-xl bg-terracotta text-white font-bold text-xs hover:bg-terracottaDark">Gerar relatório</button>
                    </form>
                    ${boxErro('erro-relatorio')}
                    <div id="rel-resultado"></div>`;
            } else if (aba === 'feedbacks') {
                const r = await api('admin_feedbacks');
                corpo.innerHTML = `<div class="space-y-2">${r.feedbacks.map(f => `
                    <div class="bloco text-xs text-darkGreen">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <p><strong class="uppercase">${esc(f.tipo)}</strong> · ${esc(f.nome)} · ${dataBR(f.data_envio)} · sobre <button onclick="abrirConteudo(${f.conteudo_id})" class="font-bold underline">${esc(f.titulo)}</button> (nota ${esc(f.nota)})</p>
                            <span class="flex items-center gap-2"><span class="selo ${COR_STATUS[f.status] || ''}">${esc(f.status)}</span>
                            ${f.status === 'pendente' ? `<button onclick="marcarAnalisado(${f.id})" class="px-3 py-1.5 rounded-lg bg-darkGreen text-cream font-bold">Marcar analisado</button>` : ''}</span>
                        </div>
                        <p class="text-darkGreen/80">${esc(f.texto)}</p>
                    </div>`).join('') || '<p class="italic">Nenhum feedback.</p>'}</div>`;
            } else {
                corpo.innerHTML = await adminAtendimentos();
            }
        } catch (e) { corpo.innerHTML = `<p class="text-red-700 text-sm">${esc(e.message)}</p>`; }
    }

    /**
     * RF-08 na visão da equipe: a fila do atendimento humano vem primeiro e cada mensagem
     * pendente tem um campo de resposta. Respondido, o texto aparece na Central de ajuda
     * e em "Minhas mensagens" do usuário que perguntou.
     */
    async function adminAtendimentos() {
        const r = await api('admin_atendimentos');
        if (!r.atendimentos.length) return '<p class="italic">Nenhum atendimento.</p>';
        const pendentes = r.atendimentos.filter(a => !a.resposta && a.canal === 'atendente humano');
        const cartao = a => `
            <div class="bloco text-xs" id="atd-${a.id}">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                    <p><strong>${esc(a.topico)}</strong></p>
                    <span class="selo ${a.resposta ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}">${a.resposta ? 'respondido' : 'aguardando'}</span>
                </div>
                <p class="text-darkGreen/75">${esc(a.nome)} · ${esc(a.email)} · ${esc(a.canal)} · ${dataBR(a.data_hora)} ${esc(String(a.data_hora).slice(11, 16))}</p>
                ${a.resposta
                    ? `<p class="mt-2 p-2 rounded-lg bg-white/70">${esc(a.resposta)}</p>
                       <p class="text-darkGreen/70 mt-1">Respondido por ${esc(a.atendente)} em ${dataBR(a.data_resposta)}</p>`
                    : (a.canal === 'atendente humano' ? `
                       <form onsubmit="responderAtendimento(event, ${a.id})" class="mt-2 space-y-2">
                           <textarea id="atd-txt-${a.id}" class="campo" rows="2" maxlength="2000" required
                                     placeholder="Escreva a resposta para ${esc(a.nome)}" aria-label="Resposta ao usuário"></textarea>
                           ${boxErro('erro-atd-' + a.id)}
                           <button class="px-4 py-2 rounded-lg bg-terracotta text-white font-bold">Responder</button>
                       </form>` : '')}
            </div>`;
        return `
            <p class="text-xs font-bold text-terracotta uppercase tracking-widest mb-2">Aguardando resposta (${pendentes.length})</p>
            <div class="space-y-2 mb-6">${pendentes.map(cartao).join('') || '<p class="text-xs italic">Nenhuma mensagem na fila.</p>'}</div>
            <p class="text-xs font-bold text-terracotta uppercase tracking-widest mb-2">Histórico</p>
            <div class="space-y-2">${r.atendimentos.filter(a => !pendentes.includes(a)).map(cartao).join('') || '<p class="text-xs italic">Sem histórico.</p>'}</div>`;
    }

    async function responderAtendimento(ev, id) {
        ev.preventDefault();
        try {
            await api('admin_responder', { id, resposta: val('atd-txt-' + id) });
            toast('Resposta enviada ao usuário!');
            document.getElementById('admin-corpo').innerHTML = await adminAtendimentos();
        } catch (e) { erroInline('erro-atd-' + id, e.message); }
    }

    async function adminContribuicoes() {
        const r = await api('admin_contribuicoes');
        cacheContribuicoes = r.contribuicoes;
        const pend = r.contribuicoes.filter(c => c.status === 'pendente'), outras = r.contribuicoes.filter(c => c.status !== 'pendente');
        const card = c => `
            <div class="bloco text-xs text-darkGreen" id="mod-${c.conteudo_id}">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                    <button onclick="abrirConteudo(${c.conteudo_id})" class="font-bold text-sm text-left hover:text-terracotta">#${c.conteudo_id} · ${esc(c.titulo)}</button>
                    <span class="selo ${COR_STATUS[c.status] || ''}">${esc(c.status)}</span>
                </div>
                <p class="text-darkGreen/70 mb-2">${esc(c.tipo)} · ${esc(c.estado)} · por <strong>${esc(c.contribuidor)}</strong> (${esc(c.classificacao)}) em ${dataBR(c.data_envio)}${c.revisado_por ? ` · revisado por ${esc(c.revisado_por)}` : ''}${c.editado_por ? ` · editado por ${esc(c.editado_por)}` : ''}</p>
                <p class="text-darkGreen/85 mb-1">${esc(c.texto_simplificado)}</p>
                ${c.texto_tecnico ? `<p class="text-darkGreen/70 mb-1"><strong>Técnico:</strong> ${esc(c.texto_tecnico)}</p>` : ''}
                ${c.referencias ? `<p class="text-darkGreen/75 mb-1"><strong>Referências:</strong> ${esc(c.referencias)}</p>` : ''}
                ${c.motivo_rejeicao ? `<p class="text-red-700"><strong>Motivo:</strong> ${esc(c.motivo_rejeicao)}</p>` : ''}
                ${c.status === 'pendente' ? `<div class="flex flex-wrap gap-2 mt-3" id="acoes-${c.conteudo_id}">
                    <button onclick="revisar(${c.conteudo_id}, 'aprovado')" class="px-4 py-2 rounded-lg bg-green-700 text-white font-bold hover:bg-green-800">✓ Aprovar</button>
                    <button onclick="formRejeitar(${c.conteudo_id})" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">✕ Rejeitar</button>
                    <button onclick="formEditar(${c.conteudo_id})" class="px-4 py-2 rounded-lg border border-darkGreen/20 font-bold">✏️ Editar antes de aprovar</button></div>` : ''}
            </div>`;
        return `<h3 class="text-xs font-bold text-terracotta uppercase tracking-widest mb-2">Aguardando moderação (${pend.length})</h3>
            <div class="space-y-2 mb-6">${pend.map(card).join('') || '<p class="italic text-xs">Nada pendente. 🎉</p>'}</div>
            <details><summary class="text-xs font-bold text-terracotta uppercase tracking-widest mb-2">Histórico (${outras.length})</summary>
            <div class="space-y-2 mt-2">${outras.map(card).join('')}</div></details>`;
    }

    function formRejeitar(id) {
        document.getElementById('acoes-' + id).innerHTML = `
            <form onsubmit="event.preventDefault(); revisar(${id}, 'rejeitado', document.getElementById('motivo-${id}').value)" class="w-full space-y-2">
                <label class="rotulo" for="motivo-${id}">Motivo da rejeição (o contribuidor verá):</label>
                <input id="motivo-${id}" required maxlength="255" class="campo" placeholder="Ex.: faltam referências">
                <div class="flex gap-2"><button class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold">Confirmar rejeição</button>
                <button type="button" onclick="openAdmin('contribuicoes')" class="btn-sec">Cancelar</button></div>
            </form>`;
        document.getElementById('motivo-' + id).focus();
    }

    function formEditar(id) {
        const c = cacheContribuicoes.find(x => Number(x.conteudo_id) === id);
        document.getElementById('acoes-' + id).innerHTML = `
            <form onsubmit="salvarEdicao(event, ${id})" class="w-full space-y-2">
                <label class="rotulo" for="ed-texto-${id}">Texto simplificado:</label><textarea id="ed-texto-${id}" maxlength="2000" rows="3" class="campo">${esc(c.texto_simplificado)}</textarea>
                <label class="rotulo" for="ed-tec-${id}">Texto técnico:</label><textarea id="ed-tec-${id}" maxlength="4000" rows="2" class="campo">${esc(c.texto_tecnico)}</textarea>
                <label class="rotulo" for="ed-ref-${id}">Referências:</label><textarea id="ed-ref-${id}" maxlength="1000" rows="2" class="campo">${esc(c.referencias)}</textarea>
                ${boxErro('erro-edicao-' + id)}
                <div class="flex gap-2"><button class="px-4 py-2 rounded-lg bg-darkGreen text-cream font-bold">Salvar edição</button>
                <button type="button" onclick="openAdmin('contribuicoes')" class="btn-sec">Cancelar</button></div>
            </form>`;
    }

    async function salvarEdicao(ev, id) {
        ev.preventDefault();
        try {
            await api('admin_editar', { conteudo_id: id, texto: val('ed-texto-' + id), texto_tecnico: val('ed-tec-' + id), referencias: val('ed-ref-' + id) });
            toast('Edição salva. Agora você pode aprovar.');
            openAdmin('contribuicoes');
        } catch (e) { erroInline('erro-edicao-' + id, e.message); }
    }

    async function revisar(id, decisao, motivo = null) {
        try {
            await api('admin_revisar', { conteudo_id: id, decisao, motivo });
            toast(decisao === 'aprovado' ? 'Contribuição aprovada e publicada!' : 'Contribuição rejeitada.');
            atualizarConteudoLocal();
            openAdmin('contribuicoes');
        } catch (e) { toast(e.message, true); }
    }

    async function adminEstatisticas() {
        const r = await api('admin_estatisticas');
        const e = r.engajamento;
        const lista = (itens, rot, valor) => itens.length ? itens.map(x => `<div class="flex justify-between text-xs py-1 border-b border-darkGreen/5"><span>${esc(x[rot])}</span><strong>${esc(x[valor])}</strong></div>`).join('') : '<p class="text-xs italic">Sem dados ainda.</p>';
        return `
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 mb-5">${[['acessos', 'acessos'], ['avaliacoes', 'avaliações'], ['favoritos', 'favoritos'], ['feedbacks', 'feedbacks'], ['atividades', 'atividades feitas'], ['atendimentos', 'atendimentos']]
                .map(([k, r]) => `<div class="bloco"><span class="text-2xl font-serif italic text-terracotta font-bold block">${esc(e[k])}</span><span class="text-[10px] font-bold uppercase tracking-wider text-darkGreen/75">${r}</span></div>`).join('')}</div>
            <div class="grid md:grid-cols-3 gap-4 text-darkGreen">
                <div class="bloco"><h3 class="text-xs font-bold uppercase tracking-widest text-terracotta mb-2">Termos mais pesquisados</h3>${lista(r.termos, 'termo', 'vezes')}</div>
                <div class="bloco"><h3 class="text-xs font-bold uppercase tracking-widest text-terracotta mb-2">Acessos por tema</h3>${lista(r.temas, 'tema', 'acessos')}</div>
                <div class="bloco"><h3 class="text-xs font-bold uppercase tracking-widest text-terracotta mb-2">Acessos por dia da semana</h3>${lista(r.dias, 'dia', 'acessos')}</div>
            </div>
            <div class="bloco mt-4 text-darkGreen"><h3 class="text-xs font-bold uppercase tracking-widest text-terracotta mb-2">Tipos culturais frequentes nos últimos 12 meses (vw_tipos_culturais_frequentes)</h3>
                ${r.frequentes.map(f => `<div class="flex flex-wrap justify-between gap-2 text-xs py-1 border-b border-darkGreen/5"><span><strong>${esc(f.tipo)}</strong> · ${esc(f.estados)}</span><span>${esc(f.total_conteudos)} · ${esc(f.frequencia)}</span></div>`).join('') || '<p class="text-xs italic">Sem dados.</p>'}</div>`;
    }

    async function marcarAnalisado(id) {
        try { await api('admin_feedback_analisado', { id }); toast('Feedback marcado como analisado.'); openAdmin('feedbacks'); }
        catch (e) { toast(e.message, true); }
    }

    async function gerarRelatorio(ev) {
        ev.preventDefault();
        const ini = val('rel-ini'), fim = val('rel-fim');
        const box = document.getElementById('erro-relatorio'), res = document.getElementById('rel-resultado');
        box.classList.add('hidden');
        try {
            const r = await api('admin_relatorio', null, `&inicio=${encodeURIComponent(ini)}&fim=${encodeURIComponent(fim)}`);
            res.innerHTML = `<p class="text-xs font-bold mb-2">${r.linhas.length} conteúdo(s) cadastrado(s) no período</p>
                <div class="overflow-x-auto rounded-xl border border-darkGreen/10 max-h-[45vh]"><table class="w-full text-xs text-left text-darkGreen">
                    <thead class="bg-darkGreen text-cream sticky top-0"><tr><th class="p-2">Código</th><th class="p-2">Tipo</th><th class="p-2">Cadastro</th><th class="p-2">Estado</th></tr></thead>
                    <tbody>${r.linhas.map(l => `<tr class="border-t border-darkGreen/10 bg-creamCard"><td class="p-2">${esc(l.codigo)}</td><td class="p-2">${esc(l.tipo_conteudo)}</td><td class="p-2">${dataBR(l.data_cadastro)}</td><td class="p-2">${esc(l.estado)}</td></tr>`).join('')}</tbody>
                </table></div>`;
        } catch (e) { res.innerHTML = ''; erroInline('erro-relatorio', e.message); }
    }

    /* ------------------------------------------------------------------------------------
     * ATIVIDADES INTERATIVAS (RF-11 e RF-13)
     * São três atividades jogáveis, cada uma com sua mecânica:
     *   quiz ............... perguntas de múltipla escolha geradas do acervo
     *   jogo da memória .... vira cartas e casa cada conteúdo com o estado dele
     *   quebra-cabeça ...... remonta um mosaico 3x3 trocando as peças de lugar
     * O servidor sorteia e corrige tudo; aqui só acontece o jogo e a exibição.
     * ------------------------------------------------------------------------------------ */
    let atividade = null;

    async function abrirAtividade(id, conteudoId) {
        openModal(carregando('Preparando a atividade...'));
        try {
            const r = await api('atividade', null, '&id=' + id);
            atividade = { ...r.atividade, conteudoId };
            if (r.atividade.tipo === 'jogo da memória') {
                iniciarMemoria(r.cartas, r.total_pares);
            } else if (r.atividade.tipo === 'quebra-cabeça') {
                iniciarQuebraCabeca(r.grade, r.ordem_inicial, conteudoId, r.imagem);
            } else {
                atividade = { ...atividade, perguntas: r.perguntas, respostas: [], indice: 0 };
                renderPerguntaAtividade();
            }
        } catch (e) { openModal(`<p class="py-10 text-center text-sm text-red-700">${esc(e.message)}</p>`); }
    }

    /** Cabeçalho comum das três atividades. */
    function cabecalhoAtividade(a, direita) {
        return `
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-darkGreen/10 pb-4 mb-5 pr-12">
                <div><span class="text-xs font-bold text-terracotta uppercase tracking-widest block">ATIVIDADE · ${esc(a.tipo)}</span>
                    <h2 class="text-2xl font-display font-bold text-darkGreen">${esc(a.titulo)}</h2></div>
                <span class="text-xs font-bold text-terracottaDark bg-terracotta/10 px-3 py-1.5 rounded-full">${direita}</span>
            </div>`;
    }

    // ------------------------------------------------- QUIZ (perguntas de múltipla escolha)
    function renderPerguntaAtividade() {
        const a = atividade, q = a.perguntas[a.indice], total = a.perguntas.length;
        openModal(`
            <div class="py-2">
                ${cabecalhoAtividade(a, `Pergunta ${a.indice + 1} de ${total}`)}
                <div class="w-full bg-creamCard h-2 rounded-full mb-6 overflow-hidden border border-darkGreen/10" role="progressbar" aria-label="Progresso da atividade" aria-valuenow="${a.indice + 1}" aria-valuemin="1" aria-valuemax="${total}">
                    <div class="bg-accentGold h-full transition-all duration-300" style="width: ${((a.indice + 1) / total) * 100}%;"></div></div>
                <div class="p-6 rounded-2xl bg-creamCard border border-darkGreen/10 shadow-sm mb-6"><h3 class="text-lg font-display font-bold text-darkGreen leading-snug">${esc(q.pergunta)}</h3></div>
                <div class="space-y-3">${q.opcoes.map((op, i) => `
                    <button onclick="responderAtividade(${i})" class="w-full text-left p-4 rounded-xl bg-cream hover:bg-accentGold/20 font-bold text-xs text-darkGreen transition border border-darkGreen/10"><strong>${String.fromCharCode(65 + i)})</strong> ${esc(op)}</button>`).join('')}</div>
            </div>`);
    }

    async function responderAtividade(i) {
        const a = atividade;
        a.respostas.push(i);
        a.indice++;
        if (a.indice < a.perguntas.length) { renderPerguntaAtividade(); return; }
        enviarResultado({ respostas: a.respostas }, 'Corrigindo...');
    }

    // ------------------------------------------------- JOGO DA MEMÓRIA (RF-11)
    let memoria = null;

    function iniciarMemoria(cartas, totalPares) {
        memoria = { cartas, total: totalPares, viradas: [], certos: [], erros: 0, travado: false };
        openModal(`
            <div class="py-2">
                ${cabecalhoAtividade(atividade, '<span id="memo-placar">0 pares · 0 erros</span>')}
                <p class="text-xs text-darkGreen/70 mb-4">Vire duas cartas e case cada <strong>conteúdo cultural</strong> com o <strong>estado</strong> de onde ele é.</p>
                <div class="memo-grade mb-5" id="memo-grade">${memoria.cartas.map((c, i) => `
                    <div class="memo-carta" id="memo-${i}">
                        <button onclick="virarCarta(${i})" aria-label="Carta ${i + 1}, fechada">
                            <span class="memo-face memo-frente" aria-hidden="true">?</span>
                            <span class="memo-face memo-tras" aria-hidden="true">
                                <span class="memo-titulo">${esc(c.face)}</span>
                                <span class="memo-rodape">${esc(c.rodape)}</span>
                            </span>
                        </button>
                    </div>`).join('')}</div>
                <button onclick="encerrarMemoria()" class="btn-sec w-full py-3">Encerrar e ver o resultado</button>
            </div>`);
    }

    function virarCarta(i) {
        const j = memoria;
        const c = j.cartas[i];
        if (j.travado || j.certos.includes(c.par) || j.viradas.includes(i)) return;
        j.viradas.push(i);

        if (j.viradas.length === 2) {
            const [a, b] = j.viradas.map(k => j.cartas[k]);
            if (a.par === b.par) {
                j.certos.push(a.par);
                j.viradas = [];
            } else {
                j.erros++;
                j.travado = true;   // deixa as duas cartas à vista por um instante antes de fechar
                setTimeout(() => { j.viradas = []; j.travado = false; pintarCartas(); }, 900);
            }
        }
        pintarCartas();
        if (j.certos.length === j.total) setTimeout(encerrarMemoria, 700);
    }

    /** Atualiza só as classes das cartas, para a animação de virar não ser reiniciada. */
    function pintarCartas() {
        const j = memoria;
        j.cartas.forEach((c, i) => {
            const el = document.getElementById('memo-' + i);
            if (!el) return;
            const certa = j.certos.includes(c.par);
            const virada = j.viradas.includes(i);
            el.classList.toggle('certa', certa);
            el.classList.toggle('virada', virada && !certa);
            el.querySelector('button').setAttribute('aria-label',
                certa || virada ? `${c.face} — ${c.rodape}${certa ? ', par formado' : ''}` : `Carta ${i + 1}, fechada`);
        });
        const placar = document.getElementById('memo-placar');
        if (placar) placar.textContent = `${j.certos.length} de ${j.total} pares · ${j.erros} erro(s)`;
    }

    function encerrarMemoria() {
        if (!memoria) return;
        const j = memoria;
        memoria = null;
        enviarResultado({ pares: j.certos, erros: j.erros }, 'Fechando o jogo...');
    }

    // ------------------------------------------------- QUEBRA-CABEÇA (RF-13)
    let quebra = null;

    function iniciarQuebraCabeca(grade, ordem, conteudoId, imagemServidor) {
        quebra = { grade, ordem: ordem.slice(), imagem: imagemServidor ? urlSegura(imagemServidor) : imagemDoConteudo(conteudoId),
            movimentos: 0, sel: null };
        openModal(`
            <div class="py-2">
                ${cabecalhoAtividade(atividade, '<span id="qc-placar">0 movimentos</span>')}
                <p class="text-xs text-darkGreen/70 mb-4">Clique em <strong>duas peças</strong> para trocá-las de lugar até a imagem ficar igual à referência.</p>
                <div class="grid sm:grid-cols-[1fr_120px] gap-5 items-start">
                    <div class="qc-grade" id="qc-grade" role="group" aria-label="Peças do quebra-cabeça"></div>
                    <div class="text-center">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-terracotta block mb-1">Referência</span>
                        <img src="${quebra.imagem}" alt="Imagem completa do quebra-cabeça" class="w-full rounded-xl border border-darkGreen/15">
                    </div>
                </div>
                <button onclick="encerrarQuebraCabeca()" class="btn-sec w-full py-3 mt-5">Encerrar e ver o resultado</button>
            </div>`);
        pintarQuebraCabeca();
        ajustarProporcaoQuebraCabeca();
    }

    /** Deixa o mosaico com a mesma proporção da imagem, para as peças não ficarem esticadas. */
    function ajustarProporcaoQuebraCabeca() {
        const img = new Image();
        img.onload = () => {
            const grade = document.getElementById('qc-grade');
            if (grade && img.naturalWidth && img.naturalHeight) {
                grade.style.aspectRatio = `${img.naturalWidth} / ${img.naturalHeight}`;
            }
        };
        img.src = quebra.imagem;
    }

    function pintarQuebraCabeca() {
        const j = quebra, n = j.grade;
        document.getElementById('qc-grade').innerHTML = j.ordem.map((peca, pos) => {
            const col = peca % n, lin = Math.floor(peca / n);
            const px = (col * 100) / (n - 1), py = (lin * 100) / (n - 1);
            return `<button class="qc-peca ${j.sel === pos ? 'sel' : ''} ${peca === pos ? 'ok' : ''}"
                        style="background-image: url('${j.imagem}'); background-position: ${px}% ${py}%;"
                        onclick="tocarPeca(${pos})" aria-label="Posição ${pos + 1}, peça ${peca + 1}${peca === pos ? ', no lugar certo' : ''}">
                        <span class="qc-num" aria-hidden="true">${peca + 1}</span></button>`;
        }).join('');
        const placar = document.getElementById('qc-placar');
        if (placar) placar.textContent = `${j.movimentos} movimento(s)`;
    }

    function tocarPeca(pos) {
        const j = quebra;
        if (j.sel === null) { j.sel = pos; pintarQuebraCabeca(); return; }
        if (j.sel === pos) { j.sel = null; pintarQuebraCabeca(); return; }
        [j.ordem[j.sel], j.ordem[pos]] = [j.ordem[pos], j.ordem[j.sel]];
        j.sel = null;
        j.movimentos++;
        pintarQuebraCabeca();
        if (j.ordem.every((peca, i) => peca === i)) setTimeout(encerrarQuebraCabeca, 600);
    }

    function encerrarQuebraCabeca() {
        if (!quebra) return;
        const j = quebra;
        quebra = null;
        enviarResultado({ ordem: j.ordem, movimentos: j.movimentos }, 'Conferindo a montagem...');
    }

    /** Foto do conteúdo quando existe na pasta; se não, a arte que o próprio site desenha. */
    function imagemDoConteudo(conteudoId) {
        const c = (conteudoAberto && conteudoAberto.id === conteudoId)
            ? conteudoAberto : app.conteudos.find(x => x.id === conteudoId);
        if (!c) return arteConteudo({ id: conteudoId, regiao: '', tipo: '', sigla: '' });
        return c.imagem_disponivel && c.ficha?.imagem ? caminhoArquivo(c.ficha.imagem, 'imagens') : arteConteudo(c);
    }

    // ------------------------------------------------- RESULTADO DAS TRÊS ATIVIDADES
    async function enviarResultado(dados, aviso) {
        const a = atividade;
        openModal(carregando(aviso));
        try {
            const r = await api('atividade_resultado', { atividade_id: a.id, ...dados });
            openModal(`
                <div class="py-2">
                    <div class="text-center mb-6 border-b border-darkGreen/10 pb-6">
                        <span class="text-xs font-bold text-accentGold uppercase tracking-widest block mb-1">RESULTADO</span>
                        <h2 class="text-3xl font-display font-bold text-darkGreen mb-3">${esc(a.titulo)}</h2>
                        <div class="inline-flex items-center bg-darkGreen text-cream px-6 py-3 rounded-2xl shadow-md">
                            <span class="text-2xl font-serif italic text-accentGold font-bold mr-2">${placarAtividade(r)}</span>
                            <span class="text-xs font-bold uppercase tracking-wider">${r.pontuacao} pts</span></div>
                        <p class="text-[11px] text-darkGreen/75 mt-3">${avisoPontuacao(r)}</p>
                    </div>
                    <div class="max-h-[42vh] overflow-y-auto pr-2 mb-5">${detalheResultado(r)}</div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <button onclick="abrirAtividade(${a.id}, ${a.conteudoId})" class="btn-primario">🔄 Jogar de novo</button>
                        <button onclick="abrirConteudo(${a.conteudoId})" class="btn-sec py-4">← Voltar ao conteúdo</button>
                    </div>
                </div>`);
        } catch (e) { openModal(`<p class="py-10 text-center text-sm text-red-700">${esc(e.message)}</p>`); }
    }

    function placarAtividade(r) {
        if (r.tipo === 'jogo da memória') return `${r.pares_certos} / ${r.total_pares} pares`;
        if (r.tipo === 'quebra-cabeça') return `${r.pecas_certas} / ${r.total_pecas} peças`;
        return `${r.acertos} / ${r.total} acertos`;
    }

    function avisoPontuacao(r) {
        if (r.salvo) return '✓ Pontuação registrada no seu histórico.';
        return app.usuario
            ? 'O histórico de pontuações é exclusivo para estudantes.'
            : '<button onclick="openLogin()" class="underline font-bold text-terracotta">Entre como estudante</button> para salvar sua pontuação.';
    }

    function detalheResultado(r) {
        if (r.tipo === 'jogo da memória') {
            return `<p class="text-xs font-bold text-terracotta uppercase tracking-widest mb-3">
                    ${r.completo ? `Você fechou todos os pares com ${r.erros} erro(s).` : 'O jogo foi encerrado antes de fechar todos os pares.'}</p>
                <div class="space-y-2">${r.dicas.map(d => `
                    <div class="p-3 rounded-xl bg-creamCard border border-darkGreen/10 text-xs">
                        <strong>${esc(d.titulo)}</strong> <span class="text-darkGreen/75">· ${esc(d.estado)}</span>
                        <p class="text-darkGreen/80 mt-1">${esc(d.dica)}</p></div>`).join('')}</div>`;
        }
        if (r.tipo === 'quebra-cabeça') {
            return `<div class="p-4 rounded-xl bg-creamCard border border-darkGreen/10 text-xs space-y-1">
                    <p class="font-bold ${r.completo ? 'text-green-800' : 'text-amber-800'}">${r.completo ? '✓ Mosaico montado!' : '✕ O mosaico ficou incompleto.'}</p>
                    <p>Peças no lugar certo: <strong>${r.pecas_certas} de ${r.total_pecas}</strong></p>
                    <p>Movimentos usados: <strong>${r.movimentos}</strong> ${r.completo && r.movimentos <= 12 ? '(dentro do ideal, pontuação cheia)' : ''}</p>
                </div>`;
        }
        const a = atividade;
        return `<div class="space-y-3">${a.perguntas.map((q, k) => {
            const ok = a.respostas[k] === r.gabarito[k];
            return `<div class="p-4 rounded-xl border ${ok ? 'bg-green-50/60 border-green-200' : 'bg-red-50/60 border-red-200'} text-xs">
                <span class="font-bold ${ok ? 'text-green-800' : 'text-red-800'} block mb-1">${k + 1}. ${ok ? '✓ Você acertou' : '✕ Você errou'}</span>
                <p class="font-bold mb-1">${esc(q.pergunta)}</p>
                ${!ok ? `<p class="text-green-700 mb-1">Resposta correta: <strong>${esc(q.opcoes[r.gabarito[k]])}</strong></p>` : ''}
                <p class="text-darkGreen/80 bg-white/70 p-2 rounded-lg italic">💡 ${esc(r.explicacoes[k])}</p></div>`;
        }).join('')}</div>`;
    }

    // ---------------------------------------------------------------- QUIZ DO BLUE (RF-05)
    const blueQuizQuestionsPool = [
        { question: "Qual foi a primeira província brasileira a abolir a escravidão, em 1884?", options: ["Ceará", "Bahia", "Rio de Janeiro", "Pernambuco"], correct: 0, explanation: "O Ceará aboliu a escravidão em 25 de março de 1884, quatro anos antes da Lei Áurea, ganhando o título de 'Terra da Luz'." },
        { question: "O Festival Folclórico de Parintins (AM) é famoso pela disputa de quais dois bois bumbás?", options: ["Caprichoso e Malhadinho", "Garantido e Caprichoso", "Flor do Campo e Garantido", "Boi de Mamão e Caprichoso"], correct: 1, explanation: "O Festival de Parintins no Amazonas traz a épica disputa entre o Boi Garantido (vermelho) e o Boi Caprichoso (azul)." },
        { question: "Qual prato tradicional do Paraná é preparado em panela de barro hermeticamente selada até a carne desfiar?", options: ["Feijão Tropeiro", "Barreado", "Maniçoba", "Chambari"], correct: 1, explanation: "O Barreado é o prato típico do litoral paranaense, cozinhado por mais de 12 horas até atingir uma consistência macia." },
        { question: "Em Goiás, qual manifestação teatral equestre encena a batalha medieval entre mouros e cristãos?", options: ["Maracatu", "Cavalhadas de Pirenópolis", "Batuque do Cururu", "Congada"], correct: 1, explanation: "As Cavalhadas de Pirenópolis encenam batalhas épicas com cavalos ornamentados durante a Festa do Divino Espírito Santo." },
        { question: "O barroco mineiro do século XVIII teve como seu maior expoente escultor e arquiteto:", options: ["Mestre Ataíde", "Aleijadinho", "Debret", "Di Cavalcanti"], correct: 1, explanation: "Antônio Francisco Lisboa, o Aleijadinho, esculpiu obras-primas em pedra-sabão e madeira nas cidades históricas de Minas Gerais." },
        { question: "O artesanato sustentável trançado com hastes que reluzem como ouro no Jalapão (TO) é feito de:", options: ["Palha de Carnaúba", "Capim Dourado", "Fibra de Tururi", "Cipó-titica"], correct: 1, explanation: "O Capim Dourado é uma preciosidade do Cerrado no Tocantins, utilizado para criar bijuterias, peças de decoração e utilitários." },
        { question: "O ritmo pernambucano caracterizado pelo uso de sombrinhas coloridas e passos acrobáticos é o:", options: ["Axé", "Frevo", "Carimbó", "Samba de Roda"], correct: 1, explanation: "O Frevo foi declarado Patrimônio Imaterial da Humanidade pela UNESCO e é símbolo do Carnaval pernambucano." },
        { question: "Qual tradição dos pampas gaúchos, ligada à erva-mate, é símbolo de hospitalidade no Rio Grande do Sul?", options: ["Fandango Caiçara", "Roda de Chimarrão", "Batuque", "Baque Solto"], correct: 1, explanation: "O hábito do Chimarrão é um símbolo de hospitalidade e identidade cultural muito forte nos pampas gaúchos." },
        { question: "A maior procissão católica do Brasil, que reúne milhões de fiéis em Belém do Pará no mês de outubro, chama-se:", options: ["Círio de Nazaré", "Festa de Iemanjá", "Lavagem do Bonfim", "Procissão do Fogaréu"], correct: 0, explanation: "O Círio de Nazaré homenageia Nossa Senhora de Nazaré e reúne mais de 2 milhões de pessoas nas ruas de Belém." },
        { question: "Em qual estado brasileiro estão localizados os geoglifos milenares gravados na terra por antigas civilizações?", options: ["Roraima", "Acre", "Mato Grosso", "Sergipe"], correct: 1, explanation: "Os geoglifos do Acre são impressionantes estruturas geométricas cavadas no solo, descobertas com o desmatamento de certas áreas." }
    ];

    let quizState = { currentQuestions: [], currentIndex: 0, userAnswers: [], score: 0 };

    function openQuiz() {
        const shuffled = [...blueQuizQuestionsPool].sort(() => 0.5 - Math.random());
        quizState = { currentQuestions: shuffled.slice(0, 5), currentIndex: 0, userAnswers: [], score: 0 };
        renderQuizQuestion();
    }

    function renderQuizQuestion() {
        const q = quizState.currentQuestions[quizState.currentIndex];
        const total = quizState.currentQuestions.length;
        const progressPercent = ((quizState.currentIndex + 1) / total) * 100;
        openModal(`
            <div class="py-2">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-darkGreen/10 pb-4 mb-6 pr-12">
                    <div>
                        <span class="text-xs font-bold text-terracotta uppercase tracking-widest block">MASCOTE BLUE APRESENTA</span>
                        <h2 class="text-2xl font-display font-bold text-darkGreen">Quiz do Blue</h2>
                    </div>
                    <span class="text-xs font-bold text-terracottaDark bg-terracotta/10 px-3 py-1.5 rounded-full">Pergunta ${quizState.currentIndex + 1} de ${total}</span>
                </div>
                <div class="w-full bg-creamCard h-2 rounded-full mb-6 overflow-hidden border border-darkGreen/10" role="progressbar" aria-label="Progresso do quiz" aria-valuenow="${quizState.currentIndex + 1}" aria-valuemin="1" aria-valuemax="${total}">
                    <div class="bg-accentGold h-full transition-all duration-300" style="width: ${progressPercent}%;"></div>
                </div>
                <div class="p-6 rounded-2xl bg-creamCard border border-darkGreen/10 shadow-sm mb-6">
                    <h3 class="text-lg font-display font-bold text-darkGreen leading-snug">${q.question}</h3>
                </div>
                <div class="space-y-3 mb-6">
                    ${q.options.map((option, idx) => `
                        <button onclick="submitQuizAnswer(${idx})" class="w-full text-left p-4 rounded-xl bg-cream hover:bg-accentGold/20 font-bold text-xs text-darkGreen transition border border-darkGreen/10 flex items-center justify-between group">
                            <span><strong>${String.fromCharCode(65 + idx)})</strong> ${option}</span>
                            <span class="opacity-0 group-hover:opacity-100 text-terracottaDark transition" aria-hidden="true">Responder →</span>
                        </button>`).join('')}
                </div>
            </div>`);
    }

    function submitQuizAnswer(selectedIndex) {
        const q = quizState.currentQuestions[quizState.currentIndex];
        const isCorrect = selectedIndex === q.correct;
        if (isCorrect) quizState.score++;
        quizState.userAnswers.push({ question: q.question, userOption: q.options[selectedIndex], correctOption: q.options[q.correct], isCorrect, explanation: q.explanation });
        quizState.currentIndex++;
        if (quizState.currentIndex < quizState.currentQuestions.length) renderQuizQuestion();
        else renderQuizResults();
    }

    async function renderQuizResults() {
        const total = quizState.currentQuestions.length;
        const score = quizState.score;
        let feedbackTitle, feedbackMsg;
        if (score === 5) { feedbackTitle = "✦ Desempenho Perfeito!"; feedbackMsg = "Você domina a cultura das 5 regiões brasileiras!"; }
        else if (score >= 3) { feedbackTitle = "★ Muito Bem!"; feedbackMsg = "Você conhece bastante sobre o nosso Brasil!"; }
        else { feedbackTitle = "✦ Valeu a tentativa!"; feedbackMsg = "Que tal revisar os pontos e tentar superar sua marca?"; }

        openModal(`
            <div class="py-2">
                <div class="text-center mb-6 border-b border-darkGreen/10 pb-6">
                    <span class="text-xs font-bold text-accentGold uppercase tracking-widest block mb-1">RESULTADO DO BLUE</span>
                    <h2 class="text-3xl font-display font-bold text-darkGreen mb-2">${feedbackTitle}</h2>
                    <p class="text-xs text-darkGreen/70 mb-4">${feedbackMsg}</p>
                    <div class="inline-flex items-center justify-center bg-darkGreen text-cream px-6 py-3 rounded-2xl shadow-md">
                        <span class="text-2xl font-serif italic text-accentGold font-bold mr-2">${score} / ${total}</span>
                        <span class="text-xs font-bold uppercase tracking-wider">acertos · ${score * 20} pts</span>
                    </div>
                    <p id="quiz-salvo" class="text-[11px] text-darkGreen/75 mt-3">Salvando resultado...</p>
                </div>
                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-2">
                        <h4 class="text-xs font-bold text-terracotta uppercase tracking-widest mb-3">Revisão das Perguntas:</h4>
                        <div class="space-y-4 max-h-[40vh] overflow-y-auto pr-2">
                            ${quizState.userAnswers.map((ans, idx) => `
                                <div class="p-4 rounded-xl border ${ans.isCorrect ? 'bg-green-50/60 border-green-200' : 'bg-red-50/60 border-red-200'}">
                                    <span class="text-xs font-bold ${ans.isCorrect ? 'text-green-800' : 'text-red-800'} block mb-1.5">${idx + 1}. ${ans.isCorrect ? '✓ Você acertou' : '✕ Você errou'}</span>
                                    <p class="text-xs font-bold text-darkGreen mb-2">${ans.question}</p>
                                    ${!ans.isCorrect ? `<p class="text-[11px] text-red-700 mb-1">Sua resposta: <span>${ans.userOption}</span></p>
                                        <p class="text-[11px] text-green-700 mb-2">Resposta correta: <strong>${ans.correctOption}</strong></p>` : ''}
                                    <p class="text-[11px] text-darkGreen/80 bg-white/70 p-2 rounded-lg border border-darkGreen/5 italic">💡 ${ans.explanation}</p>
                                </div>`).join('')}
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-terracotta uppercase tracking-widest mb-3">Ranking:</h4>
                        <div id="quiz-ranking" class="space-y-2 p-4 rounded-2xl bg-creamCard border border-darkGreen/10"><p class="text-xs text-darkGreen/70">Carregando...</p></div>
                    </div>
                </div>
                <button onclick="openQuiz()" class="btn-primario flex items-center justify-center gap-2"><span>🔄 Tentar Novamente (Novo Sorteio)</span></button>
            </div>`);

        try {
            const r = await api('quiz', { acertos: score });
            const u = app.usuario;
            document.getElementById('quiz-salvo').innerHTML = r.salvo
                ? '✓ Pontuação registrada no seu histórico.'
                : (u ? 'O histórico de pontuações é exclusivo para perfis de estudante.' : '<button onclick="openLogin()" class="underline font-bold text-terracotta">Entre como estudante</button> para salvar sua pontuação no ranking.');
            const n = await carregarNumeros();
            const el = document.getElementById('quiz-ranking');
            if (el && n) el.innerHTML = rankingHtml(n.ranking, false);
        } catch (e) {
            const el = document.getElementById('quiz-salvo');
            if (el) el.textContent = 'Não foi possível salvar: ' + e.message;
        }
    }
    </script>
</body>
</html>
