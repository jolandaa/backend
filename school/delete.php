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
    !isset($data->school_id)
    || empty(trim($data->school_id))
) :

    $fields = ['fields' => ['school_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $loggedUserRole = $isValidToken['data']['role'];

        if ($loggedUserRole === 1) {

            $school_id = trim($data->school_id);

            try {

                $insert_query = "DELETE FROM `schools` WHERE school_id=$school_id";

                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->execute();

                $returnData = msg(1, 200, 'You have successfully deleted this school.');

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