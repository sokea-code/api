<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type");

// Include database connection
require_once './Database.php';
$database = new Database();
$db = $database->getConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Simple router
switch ($method) {
    case 'GET':
        // Check if requesting a specific product
        if (isset($_GET['id'])) {
            // Get single item by ID
            $id = htmlspecialchars(strip_tags($_GET['id']));
            
            // Validate ID is numeric
            if (!is_numeric($id)) {
                http_response_code(400);
                echo json_encode(array("message" => "Invalid product ID"));
                break;
            }
            
            $query = "SELECT id, name, price, image_path FROM items WHERE id = ? LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(array(
                    "message" => "Product not found",
                    "requested_id" => $id
                ));
            }
        } else {
            // Get all items
            $query = "SELECT id, name, price, image_path FROM items";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $items = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = $row;
            }
            
            http_response_code(200);
            echo json_encode($items);
        }
        break;
        
    case 'POST':
        // Create new item
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->name) && !empty($data->price)) {
            $query = "INSERT INTO items 
                     SET name=:name, price=:price, image_path=:image_path";
            
            $stmt = $db->prepare($query);
            
            // Sanitize
            $name = htmlspecialchars(strip_tags($data->name));
            $price = htmlspecialchars(strip_tags($data->price));
            $image_path = htmlspecialchars(strip_tags($data->image_path ?? null));
            
            // Bind values
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":image_path", $image_path);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Item created",
                    "id" => $db->lastInsertId()
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create item"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required data (name and price)"));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>