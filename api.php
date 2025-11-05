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

    /**
     * Obtém o saldo de um cliente específico. [cite: 7]
     */
    function getBalance($clientId) {
        $transactions = readDb(TRANSACTIONS_DB);
        $balance = 0.0;

        foreach ($transactions as $tx) {
            if (isset($tx['client_id']) && $tx['client_id'] == $clientId) {
                if ($tx['type'] === 'credit') {
                    $balance += (float)$tx['amount'];
                } elseif ($tx['type'] === 'debit') {
                    $balance -= (float)$tx['amount'];
                }
            }
            // Tratamento para transferências (onde o cliente pode ser o remetente ou o destinatário)
            elseif (isset($tx['type']) && $tx['type'] === 'transfer') {
                if (isset($tx['from_client_id']) && $tx['from_client_id'] == $clientId) {
                    $balance -= (float)$tx['amount']; // Débito para quem envia
                }
                if (isset($tx['to_client_id']) && $tx['to_client_id'] == $clientId) {
                    $balance += (float)$tx['amount']; // Crédito para quem recebe
                }
            }
        }
        return $balance;
    }

    // --- ROTEADOR PRINCIPAL DA API ---

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;
    $clientId = $_GET['id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        // --- ENDPOINTS DE CLIENTE [cite: 6] ---
        case 'client':
            $clients = readDb(CLIENTS_DB);

            switch ($method) {
                case 'POST': // Cadastrar [cite: 6]
                    if (empty($input['name']) || empty($input['email'])) {
                        sendResponse(['error' => 'Nome e email são obrigatórios'], 400);
                    }
                    $newId = empty($clients) ? 1 : max(array_keys($clients)) + 1;
                    $newClient = [
                        'id' => $newId,
                        'name' => $input['name'],
                        'email' => $input['email'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $clients[$newId] = $newClient;
                    writeDb(CLIENTS_DB, $clients);
                    sendResponse($newClient, 201);
                    break;

                case 'GET': // Buscar por ID [cite: 6]
                    if ($clientId === null) {
                        sendResponse(['error' => 'ID do cliente é obrigatório'], 400);
                    }
                    if (!isset($clients[$clientId])) {
                        sendResponse(['error' => 'Cliente não encontrado'], 404);
                    }
                    sendResponse($clients[$clientId]);
                    break;

                case 'PUT': // Editar [cite: 6]
                    if ($clientId === null) {
                        sendResponse(['error' => 'ID do cliente é obrigatório'], 400);
                    }
                    if (!isset($clients[$clientId])) {
                        sendResponse(['error' => 'Cliente não encontrado'], 404);
                    }
                    if (empty($input['name']) || empty($input['email'])) {
                        sendResponse(['error' => 'Nome e email são obrigatórios'], 400);
                    }
                    $clients[$clientId]['name'] = $input['name'];
                    $clients[$clientId]['email'] = $input['email'];
                    writeDb(CLIENTS_DB, $clients);
                    sendResponse($clients[$clientId]);
                    break;

                case 'DELETE': // Excluir [cite: 6]
                    if ($clientId === null) {
                        sendResponse(['error' => 'ID do cliente é obrigatório'], 400);
                    }
                    if (!isset($clients[$clientId])) {
                        sendResponse(['error' => 'Cliente não encontrado'], 404);
                    }
                    unset($clients[$clientId]);
                    writeDb(CLIENTS_DB, $clients);
                    sendResponse(['message' => 'Cliente excluído com sucesso']);
                    break;

                default:
                    sendResponse(['error' => 'Método não permitido para /client'], 405);
                    break;
            }
            break;

        // --- ENDPOINTS DE TRANSAÇÃO [cite: 7] ---
        case 'credit': // Criar transação de crédito [cite: 7]
            if ($method !== 'POST') {
                sendResponse(['error' => 'Método não permitido'], 405);
            }
            if (empty($input['client_id']) || empty($input['amount'])) {
                sendResponse(['error' => 'client_id e amount são obrigatórios'], 400);
            }
            if ((float)$input['amount'] <= 0) {
                sendResponse(['error' => 'O valor (amount) deve ser positivo'], 400);
            }
            
            $transactions = readDb(TRANSACTIONS_DB);
            $newTx = [
                'id' => uniqid('tx_'),
                'client_id' => $input['client_id'],
                'type' => 'credit',
                'amount' => (float)$input['amount'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $transactions[] = $newTx;
            writeDb(TRANSACTIONS_DB, $transactions);
            sendResponse($newTx, 201);
            break;

        case 'debit': // Criar transação de débito [cite: 7]
            if ($method !== 'POST') {
                sendResponse(['error' => 'Método não permitido'], 405);
            }
            if (empty($input['client_id']) || empty($input['amount'])) {
                sendResponse(['error' => 'client_id e amount são obrigatórios'], 400);
            }
            if ((float)$input['amount'] <= 0) {
                sendResponse(['error' => 'O valor (amount) deve ser positivo'], 400);
            }

            // Validação de saldo
            $currentBalance = getBalance($input['client_id']);
            if ($currentBalance < (float)$input['amount']) {
                sendResponse(['error' => 'Saldo insuficiente'], 400);
            }

            $transactions = readDb(TRANSACTIONS_DB);
            $newTx = [
                'id' => uniqid('tx_'),
                'client_id' => $input['client_id'],
                'type' => 'debit',
                'amount' => (float)$input['amount'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $transactions[] = $newTx;
            writeDb(TRANSACTIONS_DB, $transactions);
            sendResponse($newTx, 201);
            break;

        case 'transfer': // Transferir de um cliente para outro [cite: 7]
            if ($method !== 'POST') {
                sendResponse(['error' => 'Método não permitido'], 405);
            }
            if (empty($input['from_client_id']) || empty($input['to_client_id']) || empty($input['amount'])) {
                sendResponse(['error' => 'from_client_id, to_client_id e amount são obrigatórios'], 400);
            }
            if ((float)$input['amount'] <= 0) {
                sendResponse(['error' => 'O valor (amount) deve ser positivo'], 400);
            }
            if ($input['from_client_id'] == $input['to_client_id']) {
                sendResponse(['error' => 'Cliente de origem e destino não podem ser os mesmos'], 400);
            }

            // Validação de saldo do remetente
            $senderBalance = getBalance($input['from_client_id']);
            if ($senderBalance < (float)$input['amount']) {
                sendResponse(['error' => 'Saldo insuficiente para transferência'], 400);
            }

            $transactions = readDb(TRANSACTIONS_DB);
            $newTx = [
                'id' => uniqid('tx_'),
                'type' => 'transfer',
                'from_client_id' => $input['from_client_id'],
                'to_client_id' => $input['to_client_id'],
                'amount' => (float)$input['amount'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $transactions[] = $newTx;
            writeDb(TRANSACTIONS_DB, $transactions);
            sendResponse($newTx, 201);
            break;

        case 'balance': // Saldo de um cliente [cite: 7]
            if ($method !== 'GET') {
                sendResponse(['error' => 'Método não permitido'], 405);
            }
            if ($clientId === null) {
                sendResponse(['error' => 'ID do cliente é obrigatório'], 400);
            }
            
            $clients = readDb(CLIENTS_DB);
            if (!isset($clients[$clientId])) {
                sendResponse(['error' => 'Cliente não encontrado'], 404);
            }

            $balance = getBalance($clientId);
            sendResponse([
                'client_id' => $clientId,
                'balance' => $balance
            ]);
            break;

        default:
            sendResponse(['error' => 'Ação não encontrada'], 404);
            break;
    }

?>