<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");



require __DIR__.'/shared/Database.php';
require __DIR__. '/shared/errorResponses.php';
require __DIR__.'/shared/JwtHandler.php';



function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}


$db_connection = new Database();
$conn = $db_connection->dbConnection();
$error_responses = new ErrorResponses();

$data = json_decode(file_get_contents("php://input"));

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->email) 
    || empty(trim($data->email))
    ):
    return $error_responses->BadPayload('Please Fill in all Required Fields!');

else:
    $email=trim($data->email); 

    try {
      $list_query = "SELECT * from users where email='$email'";
      $query_stmt = $conn->prepare($list_query);
      $query_stmt->execute();
        $row = $query_stmt->fetch(PDO::FETCH_ASSOC);


      if ($query_stmt->rowCount()) {

        $user_id = $row['user_id'];
                $role = $row['role'];
$jwt = new JwtHandler();
                    $token = $jwt->jwtEncodeData(
                        'http://localhost/WEB/backend/',
                        array("user_id"=> $user_id,"role"=> $role)
                    );

 $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully logged in.',
                        'data' => [
                            'user_id' => $user_id,
                            'role' => $role,
                            'email' => $email,
                            'token' => $token
                        ]
                    ];
      }

    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
        http_response_code(500);
        echo json_encode(['error'=>$e->getMessage()]);
        exit;
    }

endif;
echo json_encode($returnData);

?>