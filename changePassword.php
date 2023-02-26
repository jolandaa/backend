<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'/shared/Database.php';
require __DIR__. '/shared/errorResponses.php';
require __DIR__.'/AuthMiddleware.php';

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}
$allHeaders = getallheaders();

$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn, $allHeaders);
$error_responses = new ErrorResponses();

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');
elseif (!array_key_exists('Authorization', $allHeaders)) :
    $returnData = msg(0, 401, 'You need token!');
    return $error_responses->UnAuthorized();
// CHECKING EMPTY FIELDS
elseif(!isset($data->currentPassword) 
    || !isset($data->password)
    || !isset($data->passwordConfirm)
    || !isset($data->email)
    || empty(trim($data->currentPassword))
    || empty(trim($data->password))
    || empty(trim($data->passwordConfirm))
    || empty(trim($data->email))
    ):

    $fields = ['fields' => ['currentPassword','password','passwordConfirm']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $currentPassword=trim($data->currentPassword); 
        $password=trim($data->password);  
        $passwordConfirm=trim($data->passwordConfirm); 
        $email=trim($data->email); 
        try {


            $list_query = "SELECT password from users where email='$email'";
            $query_stmt = $conn->prepare($list_query);
            $query_stmt->execute();
            $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

            if(password_verify($currentPassword,$row[0]['password'])){
                if($passwordConfirm ==''){
                    $error[] = 'Please confirm the password.';
                    return $error_responses->BadPayload('Please confirm the password.');
                }
                if($password != $passwordConfirm){
                    $error[] = 'Passwords do not match.';
                    return $error_responses->BadPayload('Passwords do not match.');

                }
                  if(strlen($password)<5){ // min 
                    $error[] = 'The password is 6 characters long.';
                    return $error_responses->BadPayload('The password is 6 characters long.');

                }
                
                 if(strlen($password)>20){ // Max 
                    $error[] = 'Password: Max length 20 Characters Not allowed';
                    return $error_responses->BadPayload('Password: Max length 20 Characters Not allowed');

                }

                if(!isset($error)) {
                    $options = array("cost"=>4);
                    $password = password_hash($password,PASSWORD_BCRYPT,$options);

                    $change_query = "UPDATE users SET password='$password' WHERE email='$email'";
                    $change_query_stmt = $conn->prepare($change_query);
                    $change_query_stmt->execute();


                    if($change_query_stmt)
                    {

                        $returnData = [
                            'success' => 1,
                            'message' => 'You have successfully change password.',
                            'data' => true
                        ];
                    }
                    else 
                    {
                    $error[]='Something went wrong';
                    return $error_responses->BadPayload('Something went wrong');

                   }
                }
            } else {
                    $error[]='Current password does not match.'; 
                    return $error_responses->BadPayload('Current password does not match.');
                }

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