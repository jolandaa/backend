<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../shared/Database.php';
require __DIR__ . '/../shared/errorResponses.php';
require __DIR__.'/../AuthMiddleware.php';

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
    || !isset($data->teacher_id)
    || !isset($data->name)
    || !isset($data->description)
    || !isset($data->year)
    || empty(trim($data->school_id))
    || empty(trim($data->teacher_id))
    || empty(trim($data->name))
    || empty(trim($data->description))
    || empty(trim($data->year))
) :

    $fields = ['fields' => ['school_id', 'teacher_id', 'name', 'description', 'year']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $school_id = trim($data->school_id);
        $teacher_id = trim($data->teacher_id);
        $class_name = trim($data->name);
        $class_description = trim($data->description);
        $year = trim($data->year);

        try {

            $check_name = "SELECT `class_name` FROM `classes` WHERE `class_name`=:class_name AND `school_id` =:school_id";
            $check_name_stmt = $conn->prepare($check_name);
            $check_name_stmt->bindValue(':class_name', $class_name, PDO::PARAM_STR);
            $check_name_stmt->bindValue(':school_id', $school_id, PDO::PARAM_STR);
            $check_name_stmt->execute();

            if ($check_name_stmt->rowCount()) :
                $returnData = msg(0, 422, 'This Class already is added for this school!');
                return $error_responses->BadPayload('This Class already is added for this school!');
            else :

                $insert_query = "INSERT INTO `classes`( `class_name`, `class_description`, `year`, `school_id`, `teacher_id`) VALUES(:class_name,:class_description,:year,:school_id,:teacher_id)";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':class_name', $class_name, PDO::PARAM_STR);
                $insert_stmt->bindValue(':class_description', $class_description, PDO::PARAM_STR);
                $insert_stmt->bindValue(':school_id', $school_id, PDO::PARAM_STR);
                $insert_stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_STR);
                $insert_stmt->bindValue(':year', $year, PDO::PARAM_STR);

                $insert_stmt->execute();

                $returnData = msg(1, 201, 'You have successfully added this class.');
            


            endif;
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