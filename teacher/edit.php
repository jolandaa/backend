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
elseif (!array_key_exists('Authorization', $allHeaders)) :
    $returnData = msg(0, 401, 'You need token!');
elseif (
    !isset($data->teacher_id)
    || !isset($data->user_id)
    || !isset($data->first_name)
    || !isset($data->last_name)
    || !isset($data->username)
    || !isset($data->email)
    || !isset($data->date_of_start)
    || !isset($data->monthly_salary)
    || empty(trim($data->teacher_id))
    || empty(trim($data->user_id))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    || empty(trim($data->username))
    || empty(trim($data->email))
    || empty(trim($data->date_of_start))
    || empty(trim($data->monthly_salary))
) :

    $fields = ['fields' => ['teacher_id','user_id', 'first_name', 'last_name', 'username','email']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $teacher_id = trim($data->teacher_id);
    $user_id = trim($data->user_id);
    $first_name = trim($data->first_name);
    $last_name = trim($data->last_name);
    $username = trim($data->username);
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
    elseif (strlen($username) < 3) :
        $returnData = msg(0, 422, 'Your username must be at least 3 characters long!');
    else :
        try {

            $check_name = "SELECT `email` FROM `users` WHERE `email`=:email AND user_id != $user_id";
            $check_name_stmt = $conn->prepare($check_name);
            $check_name_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $check_name_stmt->execute();

            if ($check_name_stmt->rowCount()) :
                $returnData = msg(0, 422, 'This email already is added!');

            else :

                $insert_query = "UPDATE `users` SET 
                    username = :username, 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    email = :email WHERE user_id=$user_id";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $insert_stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $insert_stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);

                $insert_stmt->execute();


                    $insert_teacher_query = "UPDATE `teachers` SET 
                        date_of_start = :date_of_start, 
                        monthly_salary = :monthly_salary, 
                        mobile_no = :mobile_no, 
                        father_name = :father_name, 
                        gender = :gender, 
                        date_of_birth = :date_of_birth, 
                        education = :education, 
                        experience = :experience WHERE teacher_id=$teacher_id";

                    $insert_teacher_stmt = $conn->prepare($insert_teacher_query);

                    // DATA BINDING
                    $insert_teacher_stmt->bindValue(':date_of_start', $date_of_start, PDO::PARAM_STR);   
                    $insert_teacher_stmt->bindValue(':mobile_no', $mobile_no, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindValue(':monthly_salary', $monthly_salary, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindValue(':father_name', $father_name, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindValue(':gender', $gender, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindValue(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindValue(':education', $education, PDO::PARAM_STR);
                    $insert_teacher_stmt->bindValue(':experience', $experience, PDO::PARAM_STR);

                    $insert_teacher_stmt->execute();

                    $returnData = msg(1, 201, 'You have successfully edited this teacher.');


            


            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
        }
    endif;
endif;

echo json_encode($returnData);