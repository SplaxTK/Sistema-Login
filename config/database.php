<?php

function configuracaoBanco(string $chave, string $padrao): string
{
    $valor = getenv($chave);
    if ($valor !== false) {
        return $valor;
    }

    static $arquivoEnv = null;
    if ($arquivoEnv === null) {
        $caminhoEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        $arquivoEnv = is_readable($caminhoEnv) ? parse_ini_file($caminhoEnv, false, INI_SCANNER_RAW) : [];
        if ($arquivoEnv === false) {
            throw new RuntimeException('Não foi possível ler o arquivo de configuração .env.');
        }
    }

    if (array_key_exists($chave, $arquivoEnv)) {
        return trim((string) $arquivoEnv[$chave], "\"'");
    }

    if ($chave === 'DB_PASSWORD' && array_key_exists('SENHA', $arquivoEnv)) {
        return trim((string) $arquivoEnv['SENHA'], "\"'");
    }

    return $padrao;
}

function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = configuracaoBanco('DB_HOST', 'localhost');
    $database = configuracaoBanco('DB_NAME', 'sistema_login');
    $username = configuracaoBanco('DB_USER', 'root');
    $password = configuracaoBanco('DB_PASSWORD', '');

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
