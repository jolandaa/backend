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
    || !isset($data->username)
    || !isset($data->password)
    || !isset($data->role)
    || !isset($data->email)
    || !isset($data->date_of_start)
    || !isset($data->monthly_salary)
    || empty(trim($data->school_id))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    || empty(trim($data->username))
    || empty(trim($data->password))
    || empty(trim($data->role))
    || empty(trim($data->email))
    || empty(trim($data->date_of_start))
    || empty(trim($data->monthly_salary))
) :

    $fields = ['fields' => ['school_id', 'first_name', 'last_name', 'username','password','role','email']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {
        $school_id = trim($data->school_id);
        $first_name = trim($data->first_name);
        $last_name = trim($data->last_name);
        $username = trim($data->username);
        $password = trim($data->password);
        $role = trim($data->role);
        $email = trim($data->email);
        $date_of_start = trim($data->date_of_start);
        $monthly_salary = trim($data->monthly_salary);

        $mobile_no = null;
        $father_name = null;
        $gender = null;
        $date_of_birth = null;
        $education = null;
        $experience = null;

        if (isset($data->mobile_no)) {
            $mobile_no = $data->mobile_no;
        }
        if (isset($data->father_name)) {
            $father_name = $data->father_name;
        }
        if (isset($data->gender)) {
            $gender = $data->gender;
        }
        if (isset($data->date_of_birth)) {
            $date_of_birth = $data->date_of_birth;
        }
        if (isset($data->education)) {
            $education = $data->education;
        }
        if (isset($data->experience)) {
            $experience = $data->experience;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) :
            $returnData = msg(0, 422, 'Invalid Email Address!');
            return $error_responses->BadPayload('Invalid Email Address!');
        elseif (strlen($username) < 3) :
            $returnData = msg(0, 422, 'Your username must be at least 3 characters long!');
            return $error_responses->BadPayload('Your username must be at least 3 characters long!');
        else :
            try {

                $check_name = "SELECT `email` FROM `users` WHERE `email`=:email";
                $check_name_stmt = $conn->prepare($check_name);
                $check_name_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $check_name_stmt->execute();

                if ($check_name_stmt->rowCount()) :
                    $returnData = msg(0, 422, 'This user already is added!');
                    return $error_responses->BadPayload('This user already is added!');
                else :
                    $created_date =  date('Y-m-d');


                    $insert_query = "INSERT INTO `users`( `username`, `password`, `first_name`, `email`, `last_name`, `role`,`created_date`) VALUES(:username,:password,:first_name,:email,:last_name,:role,:created_date)";

                    $insert_stmt = $conn->prepare($insert_query);

                    // DATA BINDING
                    $insert_stmt->bindValue(':username', $username, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);   
                    $insert_stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':role', $role, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':created_date', $created_date, PDO::PARAM_STR);

                    $insert_stmt->execute();

                    if ($insert_stmt->rowCount()) {

                        $select_query = "SELECT * FROM `users` WHERE `email`=:email";
                        $check_email_stmt = $conn->prepare($select_query);
                        $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                        $check_email_stmt->execute();

                        if ($check_email_stmt->rowCount()) :

                            $check_email_stmt_row = $check_email_stmt->fetchALL(PDO::FETCH_ASSOC);
                            $user_id = $check_email_stmt_row[0]['user_id'];

                            $insert_teacher_query = "INSERT INTO `teachers`( `user_id`, `date_of_start`, `school_id`, `monthly_salary`,`mobile_no`,`father_name`,`gender`,`date_of_birth`,`education`,`experience`) VALUES(:user_id,:date_of_start,:school_id,:monthly_salary,:mobile_no,:father_name,:gender,:date_of_birth,:education,:experience)";
                            $insert_teacher_stmt = $conn->prepare($insert_teacher_query);

                            // DATA BINDING
                            $insert_teacher_stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':date_of_start', $date_of_start, PDO::PARAM_STR);   
                            $insert_teacher_stmt->bindValue(':school_id', $school_id, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':mobile_no', $mobile_no, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':monthly_salary', $monthly_salary, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':father_name', $father_name, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':gender', $gender, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':education', $education, PDO::PARAM_STR);
                            $insert_teacher_stmt->bindValue(':experience', $experience, PDO::PARAM_STR);

                            $insert_teacher_stmt->execute();

                            $returnData = msg(1, 200, 'You have successfully added this teacher.');

                        endif;
                    } else {
                        $returnData = msg(0, 400, 'Error');
                    }

                


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