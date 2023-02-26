<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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

if ($_SERVER["REQUEST_METHOD"] != "GET") :

    $returnData = msg(0, 404, 'Page Not Found!');
elseif (!array_key_exists('Authorization', $allHeaders)) :
    $returnData = msg(0, 401, 'You need token!');
    return $error_responses->UnAuthorized();

elseif (
    !isset($_GET['parent_id'])
    || empty(trim($_GET['parent_id']))
) :

    $fields = ['fields' => ['parent_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $parent_id = $_GET['parent_id'];


        try {

            $total_students_query = "SELECT count(*) as totalStudents from `students` WHERE parent_id = $parent_id";
            $query_stmt = $conn->prepare($total_students_query);
            $query_stmt->execute();
            $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

            $total_students_this_month_query = "SELECT count(*) as totalStudentsThisMonth from `students` WHERE MONTH(created_date) = MONTH(now())
       and YEAR(created_date) = YEAR(now()) AND parent_id = $parent_id";
            $query_stmt1 = $conn->prepare($total_students_this_month_query);
            $query_stmt1->execute();
            $row1 = $query_stmt1->fetchALL(PDO::FETCH_ASSOC);


            $returnData = [
                    'success' => 1,
                    'message' => 'You have successfully get user',
                    'data' => [
                        'totalStudents'=> $row[0]['totalStudents'],
                        'totalStudentsThisMonth'=> $row1[0]['totalStudentsThisMonth']
                    ]
                ];

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