<?php

$hostname = "localhost";
$dbname = "nunes_sports";
$username = "nunes_sports";
$password = "nunes_sports";

function getJsonFromRequest() {
    $jsonData = file_get_contents('php://input');
    
    // Decode the JSON data
    $data = json_decode($jsonData, true);
    
    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'JSON decoding error: ' . json_last_error_msg();
    }

    return $data;
}

function getSqlBindType($variable): string {
    switch (gettype($variable)) {
    case 'intiger':
        return 'i';
        break;
    case 'double':
        return 'd';
        break;
    case 'string':
        return 's';
        break;
    }
    return '';
}

function makePatchStatement($conn, $table, $idName, $id, $data): mysqli_stmt {
    $fields = [];
    $types = '';
    $values = [];

    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $types .= getSqlBindType($value);
        $values[] = $value;
    }

    // Join fields for SQL SET clause
    $setClause = implode(', ', $fields);

    // Construct SQL query
    $sql = "UPDATE $table SET $setClause WHERE $idName = ?"; // Add your condition for WHERE clause

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $stmt->error);
    }

    // Add the user ID as the last parameter
    $types .= 'i'; // Assuming ID is an integer
    $values[] = $id;

    // Function to pass parameters by reference
    function ref_values($arr) {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        return $arr;
    }

    // Bind parameters dynamically
    $bindParams = array_merge([$types], $values);
    call_user_func_array([$stmt, 'bind_param'], ref_values($bindParams));

    return $stmt;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "PATCH" || $_SERVER["REQUEST_METHOD"] == "DELETE") {

    $resp = [];

    $conn = new mysqli($hostname, $dbname, $username, $password);

    switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST":    // adicionar um produto
        {
            $data = getJsonFromRequest();
            $stmt = $conn->prepare("INSERT INTO products (nome, preco, descricao) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $data['nome'], $data['preco'], $data['descricao']);
            if ($stmt->execute()) {
                $resp["status"] = "success";
                $resp["codigo"] = $conn->insert_id;
            }
            else {
                $resp["status"] = "success";
                $resp["error"] = $stmt->error;
            }
        }
        break;
    case "GET":     // listar produtos
        {
            $stmt;
            if (isset($_GET['codigo'])) {
                $stmt = $conn->prepare("SELECT * FROM products WHERE codigo = ?");
                $stmt->bind_param("i", $_GET["codigo"]);
            }
            else {
                $stmt = $conn->prepare("SELECT * FROM products");
            }

            if ($stmt->execute()) {

                $result = $stmt->get_result();
                $rows = [];
                for ($i = 0; $i < 10; $i++) {
                    $row = $result->fetch_assoc();
                    if ($row) {
                        $rows[] = $row;
                    }
                    else {
                        break;
                    }
                }
                $resp["rows"] = $rows;
                $resp["status"] = "success";
            }
            else {
                $resp["status"] = "error";
                $resp["error"] = $stmt->error;
            }
        }
        break;
    case "PATCH":   // atualizar um produto
        {
            $data = getJsonFromRequest();
            if (isset($data['codigo']) && (isset($data['nome']) || isset($data['descricao']) || isset($data['preco']))) {
                $codigo = $data['codigo'];
                unset($data['codigo']);

                $stmt = makePatchStatement($conn, 'products', 'codigo', $codigo, $data);


                if ($stmt->execute()) {
                    $resp["status"] = 'success';
                }
                else {
                    $resp["status"] = 'error';
                    $resp["error"] = $stmt->error;
                }
            }
            else {
                $resp["status"] = "error";
                $resp["error"] = "Deve ser passado o código do produto e pelo menos um campo a ser atualizado.";
            }
        }
        break;
    case "DELETE":  // remover um produto
        {
            $data = getJsonFromRequest();
            if (isset($data["codigo"])) {
                $stmt = $conn->prepare("DELETE FROM products WHERE codigo = ?");
                $stmt->bind_param("i", $data["codigo"]);
                if ($stmt->execute()) {
                    $resp["status"] = "success";
                }
                else {
                    $resp["status"] = "error";
                    $resp["error"] = $stmt->error;
                }
            }
            else {
                $resp["status"] = "error";
                $resp["error"] = "Deve ser passado o código do produto para apagá-lo";
            }
        }
        break;
    }

    $conn->close();

    echo json_encode($resp);
}
else {
    echo "Método HTTP ".$_SERVER["REQUEST_METHOD"]." não implementado";
}