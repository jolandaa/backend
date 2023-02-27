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
    !isset($_GET['class_id'])
    || !isset($_GET['date'])
    || empty(trim($_GET['date']))
    || empty(trim($_GET['class_id']))
) :

    $fields = ['fields' => ['class_id','date']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {
        $loggedUserRole = $isValidToken['data']['role'];

        if ($loggedUserRole === 3) {
            $class_id = $_GET['class_id'];
            $date = $_GET['date'];

            try {

                $list_query = "SELECT * 
                                from `grades` 
                                INNER JOIN `classes` 
                                ON grades.class_id = $class_id AND grades.date = '" .date('Y-m-d', strtotime($date)). "' AND grades.status != 1 AND classes.class_id = grades.class_id
                                INNER JOIN `students`
                                ON grades.student_id = students.nr_amzes";
                $query_stmt = $conn->prepare($list_query);
                $query_stmt->execute();
                $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

                $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully get grades.',
                        'data' => $row
                    ];

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