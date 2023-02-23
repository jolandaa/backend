<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../shared/Database.php';
require __DIR__.'/../AuthMiddleware.php';
require __DIR__ . '/../shared/errorResponses.php';

$allHeaders = getallheaders();

$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn, $allHeaders);
$error_responses = new ErrorResponses();

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
    return $error_responses->UnAuthorized();
elseif (
    !isset($data->user_id)
    || empty(trim($data->user_id))
) :

    $fields = ['fields' => ['user_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {
        $user_id = trim($data->user_id);

        try {

            $school_admin_list = "SELECT * FROM `schools` WHERE admin_id=$user_id";

            $school_admin_list_stmt = $conn->prepare($school_admin_list);
            $school_admin_list_stmt->execute();
            $row = $school_admin_list_stmt->fetchALL(PDO::FETCH_ASSOC);

            $disableForeignKeyChecks = "set FOREIGN_KEY_CHECKS=0";


            $disableForeignKeyChecks_stmt = $conn->prepare($disableForeignKeyChecks);
            $disableForeignKeyChecks_stmt->execute();

            if ($school_admin_list_stmt->rowCount()) :

                $delete_school = "DELETE FROM `schools` WHERE admin_id=$user_id";

                $delete_school_stmt = $conn->prepare($delete_school);
                $delete_school_stmt->execute();
            endif;

            $delete_user = "DELETE FROM `users` WHERE user_id=$user_id";

            $delete_user_stmt = $conn->prepare($delete_user);
            $delete_user_stmt->execute();

            $returnData = msg(1, 200, 'You have successfully deleted this user.');

            $enableForeignKeyChecks = "set FOREIGN_KEY_CHECKS=1";


            $enableForeignKeyChecks_stmt = $conn->prepare($enableForeignKeyChecks);
            $enableForeignKeyChecks_stmt->execute();

        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
            http_response_code(500);
            echo json_encode(['error'=>$e->getMessage()]);
            exit;
        }
    } else {
        return $error_responses->UnAuthorized($isValidToken['message']);
    }
    
endif;

echo json_encode($returnData);