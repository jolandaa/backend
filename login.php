<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'/shared/Database.php';
require __DIR__.'/shared/JwtHandler.php';

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

$db_connection = new Database();
$conn = $db_connection->dbConnection();

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->email) 
    || !isset($data->password)
    || empty(trim($data->email))
    || empty(trim($data->password))
    ):

    $fields = ['fields' => ['email','password']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
    http_response_code(422);
    echo json_encode(['error'=>'Please Fill in all Required Fields!',$fields]);
    exit;

// IF THERE ARE NO EMPTY FIELDS THEN-
else:

    $email = trim($data->email);
    $password = trim($data->password);

    // CHECKING THE EMAIL FORMAT (IF INVALID FORMAT)
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,422,'Invalid Email Address!');
        http_response_code(422);
        echo json_encode(['error'=>'Invalid Email Address!']);
        exit;
    
    // IF PASSWORD IS LESS THAN 8 THE SHOW THE ERROR
    elseif(strlen($password) < 8):
        $returnData = msg(0,422,'Your password must be at least 8 characters long!');
        http_response_code(422);
        echo json_encode(['error'=>'Your password must be at least 8 characters long!']);
        exit;

    // THE USER IS ABLE TO PERFORM THE LOGIN ACTION
    else:
        try{
            
            $fetch_user_by_email = "SELECT * FROM `users` WHERE `email`=:email";
            $query_stmt = $conn->prepare($fetch_user_by_email);
            $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED BY EMAIL
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                $check_password = password_verify($password, $row['password']);
                $user_id = $row['user_id'];
                $role = $row['role'];
                $username = $row['username'];
                $email = $row['email'];
                $status = $row['status'];
                $firstname = $row['first_name'];
                $lastname = $row['last_name'];

                // VERIFYING THE PASSWORD (IS CORRECT OR NOT?)
                // IF PASSWORD IS CORRECT THEN SEND THE LOGIN TOKEN
                if($check_password):

                    $jwt = new JwtHandler();
                    $token = $jwt->jwtEncodeData(
                        'http://localhost/WEB/backend/',
                        array("user_id"=> $user_id,"role"=> $role)
                    );


                    
                    $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully logged in.',
                        'user' => [
                            'user_id' => $user_id,
                            'role' => $role,
                            'username' => $username,
                            'email' => $email,
                            'status' => $status,
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'token' => $token
                        ]
                    ];


                    if ($role == 2) {
                        $fetch_school_by_user = "SELECT * FROM `schools` WHERE admin_id=$user_id";
                        $query_school_by_user_stmt = $conn->prepare($fetch_school_by_user);
                        $query_school_by_user_stmt->execute(); 

                        if($query_school_by_user_stmt->rowCount()):

                            $school_by_user_row = $query_school_by_user_stmt->fetch(PDO::FETCH_ASSOC);
                            $returnData['user']['school_id'] = $school_by_user_row['school_id'];
                        endif;                       
                    } elseif ($role == 3) {
                        $fetch_teacher_by_user = "SELECT * FROM `teachers` WHERE user_id=$user_id";
                        $query_teacher_by_user_stmt = $conn->prepare($fetch_teacher_by_user);
                        $query_teacher_by_user_stmt->execute(); 
                        if($query_teacher_by_user_stmt->rowCount()):

                            $teacher_by_user_row = $query_teacher_by_user_stmt->fetch(PDO::FETCH_ASSOC);
                            $returnData['user']['teacher_profile'] = $teacher_by_user_row;
                        endif;
                    } elseif ($role == 4) {
                        $fetch_parent_by_user = "SELECT * FROM `parents` WHERE user_id=$user_id";
                        $query_parent_by_user_stmt = $conn->prepare($fetch_parent_by_user);
                        $query_parent_by_user_stmt->execute(); 
                        if($query_parent_by_user_stmt->rowCount()):

                            $parent_by_user_row = $query_parent_by_user_stmt->fetch(PDO::FETCH_ASSOC);
                            $returnData['user']['parent_profile'] = $parent_by_user_row;
                        endif;
                    } else {
                        
                    }
                // IF INVALID PASSWORD
                else:
                    $returnData = msg(0,422,'Invalid Password!');
                    http_response_code(422);
                    echo json_encode(['error'=>'Invalid Password!']);
                    exit;
                endif;

            // IF THE USER IS NOT FOUNDED BY EMAIL THEN SHOW THE FOLLOWING ERROR
            else:
                $returnData = msg(0,422,'Invalid Email Address!');
                http_response_code(422);
                echo json_encode(['error'=>'Invalid Email Address!']);
                exit;
            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
            http_response_code(500);
            echo json_encode(['error'=>$e->getMessage()]);
            exit;
        }

    endif;

endif;

echo json_encode($returnData);