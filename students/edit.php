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
    || !isset($data->first_name)
    || !isset($data->last_name)
    || !isset($data->nr_amzes)
    || !isset($data->email)
    || !isset($data->date_of_join)
    || !isset($data->class_id)
    || !isset($data->gender)
    || empty(trim($data->school_id))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    || empty(trim($data->nr_amzes))
    || empty(trim($data->email))
    || empty(trim($data->date_of_join))
    || empty(trim($data->class_id))
    || empty(trim($data->gender))
) :

    $fields = ['fields' => ['nr_amzes', 'school_id', 'first_name', 'last_name', 'nr_amzes','date_of_join','class_id','email','gender']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {
        $school_id = trim($data->school_id);
        $first_name = trim($data->first_name);
        $last_name = trim($data->last_name);
        $nr_amzes = trim($data->nr_amzes);
        $date_of_join = trim($data->date_of_join);
        $class_id = trim($data->class_id);
        $email = trim($data->email);
        $gender = trim($data->gender);

        $mobile_no = null;
        $parent_id = null;
        $picture = null;
        $date_of_birth = null;
        $father_name = null;
        $father_mobile_no = null;
        $father_education = null;
        $father_profession = null;
        $mother_first_name = null;
        $mother_education = null;
        $mother_profession = null;
        $mother_mobile_no = null;

        if (isset($data->mobile_no)) {
            $mobile_no = $data->mobile_no;
        }
        if (isset($data->parent_id)) {
            $parent_id = $data->parent_id;
        }
        if (isset($data->picture)) {
            $picture = $data->picture;
        }
        if (isset($data->date_of_birth)) {
            $date_of_birth = $data->date_of_birth;
        }
        if (isset($data->father_mobile_no)) {
            $father_mobile_no = $data->father_mobile_no;
        }
        if (isset($data->father_name)) {
            $father_name = $data->father_name;
        }  
        if (isset($data->father_education)) {
            $father_education = $data->father_education;
        }
        if (isset($data->father_profession)) {
            $father_profession = $data->father_profession;
        }
        if (isset($data->mother_first_name)) {
            $mother_first_name = $data->mother_first_name;
        }
        if (isset($data->mother_education)) {
            $mother_education = $data->mother_education;
        }
        if (isset($data->mother_profession)) {
            $mother_profession = $data->mother_profession;
        }
        if (isset($data->mother_mobile_no)) {
            $mother_mobile_no = $data->mother_mobile_no;
        } 

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) :
            $returnData = msg(0, 422, 'Invalid Email Address!');
            return $error_responses->BadPayload('Invalid Email Address!');
        else :
            try {


                    $insert_query = "UPDATE `students` SET 
                    date_of_join=:date_of_join,
                    parent_id=:parent_id,
                    class_id=:class_id,
                    first_name=:first_name,
                    last_name=:last_name,
                    email=:email,
                    mobile_no=:mobile_no,
                    gender=:gender,
                    picture=:picture,
                    date_of_birth=:date_of_birth,
                    father_name=:father_name,
                    father_mobile_no=:father_mobile_no,
                    father_education=:father_education,
                    father_profession=:father_profession,
                    mother_first_name=:mother_first_name,
                    mother_education=:mother_education,
                    mother_profession=:mother_profession,
                    mother_mobile_no=:mother_mobile_no WHERE nr_amzes=$nr_amzes";

                    $insert_stmt = $conn->prepare($insert_query);

                    // DATA BINDING
                    $insert_stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':date_of_join', $date_of_join, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':class_id', $class_id, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':gender', $gender, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':mobile_no', $mobile_no, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':parent_id', $parent_id, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':picture', $picture, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':father_mobile_no', $father_mobile_no, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':father_name', $father_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':father_education', $father_education, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':father_profession', $father_profession, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':mother_first_name', $mother_first_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':mother_education', $mother_education, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':mother_profession', $mother_profession, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':mother_mobile_no', $mother_mobile_no, PDO::PARAM_STR);

                    $insert_stmt->execute();

                    $returnData = msg(1, 200, 'You have successfully added this student.');

                


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