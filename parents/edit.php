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
    || !isset($data->first_name)
    || !isset($data->last_name)
    || !isset($data->username)
    || !isset($data->email)
    || empty(trim($data->parent_id))
    || empty(trim($data->user_id))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    || empty(trim($data->username))
    || empty(trim($data->email))
) :

    $fields = ['fields' => ['parent_id','user_id', 'first_name', 'last_name', 'username','email']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {
        $parent_id = trim($data->parent_id);
        $user_id = trim($data->user_id);
        $first_name = trim($data->first_name);
        $last_name = trim($data->last_name);
        $username = trim($data->username);
        $email = trim($data->email);


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) :
            $returnData = msg(0, 422, 'Invalid Email Address!');
            return $error_responses->BadPayload('Invalid Email Address!');
        elseif (strlen($username) < 3) :
            $returnData = msg(0, 422, 'Your username must be at least 3 characters long!');
            return $error_responses->BadPayload('Your username must be at least 3 characters long!');
        else :
            try {

                $check_name = "SELECT `email` FROM `users` WHERE `email`=:email AND user_id != $user_id";
                $check_name_stmt = $conn->prepare($check_name);
                $check_name_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $check_name_stmt->execute();

                if ($check_name_stmt->rowCount()) :
                    $returnData = msg(0, 422, 'This email already is added!');
                    return $error_responses->BadPayload('This email already is added!');
                else :

                    $insert_query = "UPDATE `users` SET 
                        username = :username, 
                        first_name = :first_name, 
                        last_name = :last_name, 
                        email = :email WHERE user_id=$user_id";

                    $insert_stmt = $conn->prepare($insert_query);

                    // DATA BINDING
                    $insert_stmt->bindValue(':username', $username, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);

                    $insert_stmt->execute();

                    $returnData = msg(1, 200, 'You have successfully edited this parent.');


                endif;
            } catch (PDOException $e) {
                $returnData = msg(0, 500, $e->getMessage());
                http_response_code(500);
                echo json_encode(['error'=>$e->getMessage()]);
                exit;
            }
        endif;
    } else {
        return $error_responses->UnAuthorized($isValidToken['message']);
    }

    
endif;

echo json_encode($returnData);