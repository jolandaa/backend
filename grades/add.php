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
    !isset($data->teacher_id)
    || !isset($data->class_id)
    || !isset($data->date)
    || !isset($data->attendanceList)
    || empty(trim($data->teacher_id))
    || empty(trim($data->class_id))
    || empty(trim($data->date))
    || empty($data->attendanceList)
) :

    $fields = ['fields' => [ 'teacher_id', 'class_id', 'date','attendanceList']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {
        $loggedUserRole = $isValidToken['data']['role'];

        if ($loggedUserRole === 3) {
            $teacher_id = trim($data->teacher_id);
            $class_id = trim($data->class_id);
            $date = trim($data->date);
            $attendanceList = $data->attendanceList;
            $status = 2;

            try {


                for ($i=0; $i < count($attendanceList); $i++) { 
                    $student_id = $attendanceList[$i]->nr_amzes;
                    $grade = $attendanceList[$i]->grade;


                    $insert_query = "INSERT INTO `grades`( `teacher_id`, `class_id`, `date`, `status`, `student_id`, `grade`) VALUES(:teacher_id,:class_id,:date,:status,:student_id,:grade)";

                    $insert_stmt = $conn->prepare($insert_query);
                    // DATA BINDING
                    $insert_stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':class_id', $class_id, PDO::PARAM_STR);   
                    $insert_stmt->bindValue(':date', $date, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':status', $status, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':student_id', $student_id, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':grade', $grade, PDO::PARAM_STR);
                    $insert_stmt->execute();

                }

                $returnData = msg(1, 200, 'You have successfully added this grafe.');


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