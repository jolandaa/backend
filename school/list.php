<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../shared/Database.php';
$allHeaders = getallheaders();

$db_connection = new Database();
$conn = $db_connection->dbConnection();

function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}

// DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

if ($_SERVER["REQUEST_METHOD"] != "GET") :

    $returnData = msg(0, 404, 'Page Not Found!');

elseif (!array_key_exists('Authorization', $allHeaders)) :
        $returnData = msg(0, 401, 'You need token!');

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    try {

        $list_query = "SELECT * from `schools`";
        $query_stmt = $conn->prepare($list_query);
        $query_stmt->execute();
        $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

        $returnData = [
                'success' => 1,
                'message' => 'You have successfully get school list',
                'list' => $row
            ];


    } catch (HttpException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;

echo json_encode($returnData);