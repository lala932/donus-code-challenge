# Desafio de Código Donuz - API PHP Simples

Esta é uma implementação simples em PHP puro da API solicitada no desafio de código, utilizando arquivos JSON como banco de dados.

Esta solução inclui:
* `api.php` (O backend com toda a lógica)
* `index.html` (Um frontend simples para testar todas as operações da API)

## Requisitos

* PHP 7.4 ou superior (para executar o servidor embutido)
* Um navegador web (para acessar o frontend)

## 🚀 Executando o Projeto (Backend + Frontend)

1.  **Coloque os arquivos no mesmo diretório:**
    Certifique-se de que os seguintes arquivos estejam na mesma pasta:
    * `api.php`
    * `index.html`
    * `clients.json` (começando com `{}`)
    * `transactions.json` (começando com `[]`)

2.  **Inicie o servidor local:**
    Abra seu terminal, navegue até o diretório do projeto e execute o servidor embutido do PHP:

    ```bash
    php -S localhost:8000
    ```

3.  **Acesse a Aplicação:**
    Abra seu navegador e acesse o frontend em:

    ```
    http://localhost:8000
    ```
    *(Você também pode usar `http://localhost:8000/index.html`)*

    A página `index.html` carregará e você poderá usar os formulários para testar todas as funcionalidades do backend.

## Endpoints da API

*(O frontend `index.html` já consome todos estes endpoints. Esta seção é apenas para referência.)*

**URL Base:** `http://localhost:8000/api.php`

---

### 👤 Clientes

#### 1. Cadastrar Cliente
* **Método:** `POST`
* **URL:** `?action=client`
* **Corpo (JSON):**
    ```json
    {
    	"name": "Cliente Exemplo",
    	"email": "exemplo@email.com"
    }
    ```

#### 2. Buscar Cliente por ID
* **Método:** `GET`
* **URL:** `?action=client&id=1`

#### 3. Editar Cliente
* **Método:** `PUT`
* **URL:** `?action=client&id=1`
* **Corpo (JSON):**
    ```json
    {
    	"name": "Cliente Nome Atualizado",
    	"email": "novo@email.com"
    }
    ```

#### 4. Excluir Cliente
* **Método:** `DELETE`
* **URL:** `?action=client&id=1`

---

### 💸 Transações

#### 1. Criar Transação de Crédito
* **Método:** `POST`
* **URL:** `?action=credit`
* **Corpo (JSON):**
    ```json
    {
    	"client_id": 1,
    	"amount": 100.50
    }
    ```

#### 2. Criar Transação de Débito
* **Método:** `POST`
* **URL:** `?action=debit`
* **Corpo (JSON):**
    ```json
    {
    	"client_id": 1,
    	"amount": 50.00
    }
    ```

#### 3. Transferir (Cliente para Cliente)
* **Método:** `POST`
* **URL:** `?action=transfer`
* **Corpo (JSON):**
    ```json
    {
    	"from_client_id": 1,
    	"to_client_id": 2,
    	"amount": 25.00
    }
    ```

#### 4. Consultar Saldo
* **Método:** `GET`
* **URL:** `?action=balance&id=1`

---

### Pontos de Bônus

* **Código Limpo e Estruturado:** O código foi organizado em funções auxiliares e um roteador centralizado.
* **Testabilidade:** Funções como `getBalance()` são separadas da lógica de request, facilitando testes unitários (embora os testes não tenham sido implementados, conforme a instrução).