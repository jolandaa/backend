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
    !isset($data->parent_id)
    || !isset($data->user_id)    
    || empty(trim($data->parent_id))
    || empty(trim($data->user_id))
) :

    $fields = ['fields' => ['parent_id','user_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $loggedUserRole = $isValidToken['data']['role'];

        if ($loggedUserRole === 2) {

            $parent_id = trim($data->parent_id);
            $user_id = trim($data->user_id);

            try {

                $delete_parent_query = "DELETE FROM `parents` WHERE parent_id=$parent_id";

                $delete_parent_stmt = $conn->prepare($delete_parent_query);
                $delete_parent_stmt->execute();

                $delete_user_query = "DELETE FROM `users` WHERE user_id=$user_id";

                $elete_user_stmt = $conn->prepare($delete_user_query);
                $elete_user_stmt->execute();

                $returnData = msg(1, 200, 'You have successfully deleted this parent.');

            } catch (PDOException $e) {
                $returnData = msg(0, 500, $e->getMessage());
                http_response_code(500);
                echo json_encode(['error'=>$e->getMessage()]);
                exit;
            }
        
        } else {
            return $error_responses->RoleNotAllowed();
        }

    } else {
        return $error_responses->UnAuthorized($isValidToken['message']);
    }
    
endif;

echo json_encode($returnData);