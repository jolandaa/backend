<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../shared/Database.php';
require __DIR__.'/../AuthMiddleware.php';

$allHeaders = getallheaders();

$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn, $allHeaders);

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
elseif (
    !isset($_GET['nr_amzes'])
    || empty(trim($_GET['nr_amzes']))
) :

    $fields = ['fields' => ['nr_amzes']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
$nr_amzes = $_GET['nr_amzes'];

    try {

        $list_query = "SELECT * from `students` WHERE `nr_amzes`=$nr_amzes";
        $query_stmt = $conn->prepare($list_query);
        $query_stmt->execute();
        $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

        $returnData = [
                'success' => 1,
                'message' => 'You have successfully get student',
                'data' => $row[0]
            ];

    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;

echo json_encode($returnData);