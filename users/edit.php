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

elseif (
    !isset($data->user_id)
    || !isset($data->first_name)
    || !isset($data->last_name)
    || !isset($data->email)
    || empty(trim($data->user_id))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    || empty(trim($data->email))
) :

    $fields = ['fields' => ['user_id', 'first_name', 'last_name', 'email']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $user_id = trim($data->user_id);
    $first_name = trim($data->first_name);
    $last_name = trim($data->last_name);
    $email = trim($data->email);


        try {

            $check_email = "SELECT `email` FROM `users` WHERE `email`=:email AND user_id != $user_id";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $check_email_stmt->execute();

            if ($check_email_stmt->rowCount()) :
                $returnData = msg(0, 422, 'This User already is added!');

            else :
                $insert_query = "UPDATE `users` SET 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    email = :email WHERE user_id=$user_id";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                $insert_stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);

                $insert_stmt->execute();

                $returnData = msg(1, 201, 'You have successfully edited this user.');

            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
        }
endif;

echo json_encode($returnData);