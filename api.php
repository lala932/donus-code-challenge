<?php
    // Define o cabeçalho da resposta como JSON
    header("Content-Type: application/json; charset=UTF-8");

    // Define os caminhos para nossos arquivos de "banco de dados"
    define('CLIENTS_DB', __DIR__ . '/clients.json');
    define('TRANSACTIONS_DB', __DIR__ . '/transactions.json');

    // --- FUNÇÕES AUXILIARES DE BANCO DE DADOS (JSON) ---

    /**
     * Lê os dados de um arquivo JSON.
     * Cria o arquivo se ele não existir.
     */
    function readDb($file) {
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }
        $data = file_get_contents($file);
        return json_decode($data, true);
    }

    /**
     * Escreve dados em um arquivo JSON.
     */
    function writeDb($file, $data) {
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Envia uma resposta JSON padronizada e encerra o script.
     */
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
?>