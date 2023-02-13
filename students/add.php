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

    $fields = ['fields' => ['school_id', 'first_name', 'last_name', 'nr_amzes','date_of_join','class_id','email','gender']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

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
    else :
        try {

            $check_nr_amzes = "SELECT `nr_amzes` FROM `students` WHERE `nr_amzes`=:nr_amzes";
            $check_nr_amzes_stmt = $conn->prepare($check_nr_amzes);
            $check_nr_amzes_stmt->bindValue(':nr_amzes', $nr_amzes, PDO::PARAM_STR);
            $check_nr_amzes_stmt->execute();

            if ($check_nr_amzes_stmt->rowCount()) :
                $returnData = msg(0, 422, 'This student already is added!');

            else :

                $insert_query = "INSERT INTO `students`( `school_id`, `nr_amzes`, `first_name`, `email`, `last_name`, `date_of_join`,`class_id`,`gender`,`mobile_no`,`parent_id`,`picture`,`date_of_birth`,`father_mobile_no`,`father_name`,`father_education`,`father_profession`,`mother_first_name`,`mother_education`,`mother_profession`,`mother_mobile_no`) VALUES(:school_id,:nr_amzes,:first_name,:email,:last_name,:date_of_join,:class_id,:gender,:mobile_no,:parent_id,:picture,:date_of_birth,:father_mobile_no,:father_name,:father_education,:father_profession,:mother_first_name,:mother_education,:mother_profession,:mother_mobile_no)";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':school_id', $school_id, PDO::PARAM_STR);
                $insert_stmt->bindValue(':nr_amzes', $nr_amzes, PDO::PARAM_STR);   
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

                $returnData = msg(1, 201, 'You have successfully added this student.');

            


            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
        }
    endif;
endif;

echo json_encode($returnData);