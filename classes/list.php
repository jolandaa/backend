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

elseif (
    !isset($_GET['school_id'])
    || empty(trim($_GET['school_id']))
) :

    $fields = ['fields' => ['school_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
$school_id = $_GET['school_id'];

    try {

        $list_query = "SELECT classes.class_name, classes.class_description, classes.year, classes.teacher_id, users.first_name, users.last_name, classes.class_id
                        from ((`classes` 
                        INNER JOIN `teachers` 
                        ON classes.teacher_id = teachers.teacher_id AND classes.school_id=$school_id)
                        INNER JOIN `users` 
                        ON teachers.user_id = users.user_id)";
        $query_stmt = $conn->prepare($list_query);
        $query_stmt->execute();
        $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

        $returnData = [
                'success' => 1,
                'message' => 'You have successfully get classes list',
                'list' => $row
            ];


    } catch (HttpException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;

echo json_encode($returnData);