<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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

if ($_SERVER["REQUEST_METHOD"] != "POST") :

    $returnData = msg(0, 404, 'Page Not Found!');

elseif (!array_key_exists('Authorization', $allHeaders)) :
    $returnData = msg(0, 401, 'You need token!');
elseif (
    !isset($data->teacher_id)
    || !isset($data->user_id)    
    || empty(trim($data->teacher_id))
    || empty(trim($data->user_id))
) :

    $fields = ['fields' => ['teacher_id','user_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $teacher_id = trim($data->teacher_id);
    $user_id = trim($data->user_id);

        try {

            $delete_teacher_query = "DELETE FROM `teachers` WHERE teacher_id=$teacher_id";

            $delete_teacher_stmt = $conn->prepare($delete_teacher_query);
            $delete_teacher_stmt->execute();

            $delete_user_query = "DELETE FROM `users` WHERE user_id=$user_id";

            $elete_user_stmt = $conn->prepare($delete_user_query);
            $elete_user_stmt->execute();

            $returnData = msg(1, 201, 'You have successfully deleted this teacher.');

        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
        }
endif;

echo json_encode($returnData);