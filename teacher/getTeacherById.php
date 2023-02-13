<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../shared/Database.php';
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
    !isset($_GET['teacher_id'])
    || empty(trim($_GET['teacher_id']))
) :

    $fields = ['fields' => ['teacher_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
$teacher_id = $_GET['teacher_id'];

    try {

        $list_query = "SELECT * 
                        from `teachers` 
                        INNER JOIN `users` 
                        ON teachers.teacher_id = $teacher_id AND teachers.user_id = users.user_id";
        $query_stmt = $conn->prepare($list_query);
        $query_stmt->execute();
        $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

        $returnData = [
                'success' => 1,
                'message' => 'You have successfully get techer.',
                'data' => $row[0]
            ];

    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
endif;

echo json_encode($returnData);