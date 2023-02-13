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
    !isset($data->admin_id)
    || !isset($data->name)
    || !isset($data->description)
    || !isset($data->street)
    || !isset($data->country)
    || !isset($data->city)
    || !isset($data->zipCode)
    || !isset($data->logo)
    || empty(trim($data->admin_id))
    || empty(trim($data->name))
    || empty(trim($data->description))
    || empty(trim($data->street))
    || empty(trim($data->country))
    || empty(trim($data->city))
    || empty(trim($data->zipCode))
    || empty(trim($data->logo))
) :

    $fields = ['fields' => ['admin_id', 'name', 'description', 'street','country', 'city', 'zipCode', 'logo']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $admin_id = trim($data->admin_id);
    $name = trim($data->name);
    $description = trim($data->description);
    $street = trim($data->street);
    $country = trim($data->country);
    $city = trim($data->city);
    $zipCode = trim($data->zipCode);
    $logo = trim($data->logo);


        try {

            $check_name = "SELECT `name` FROM `schools` WHERE `name`=:name";
            $check_name_stmt = $conn->prepare($check_name);
            $check_name_stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $check_name_stmt->execute();

            if ($check_name_stmt->rowCount()) :
                $returnData = msg(0, 422, 'This School already is added!');

            else :


            $check_admin = "SELECT `user_id` FROM `users` WHERE `user_id`=$admin_id";
            $check_admin_stmt = $conn->prepare($check_admin);
            $check_admin_stmt->execute();

            if ($check_admin_stmt->rowCount()) {
                $insert_query = "INSERT INTO `schools`( `name`, `description`, `logo`, `street`, `country`, `city`, `zipCode`, `admin_id`) VALUES(:name,:description,:logo,:street,:country,:city,:zipCode,:admin_id)";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':admin_id', $admin_id, PDO::PARAM_STR);
                $insert_stmt->bindValue(':name', $name, PDO::PARAM_STR);
                $insert_stmt->bindValue(':description', $description, PDO::PARAM_STR);
                $insert_stmt->bindValue(':street', $street, PDO::PARAM_STR);
                $insert_stmt->bindValue(':country', $country, PDO::PARAM_STR);
                $insert_stmt->bindValue(':city', $city, PDO::PARAM_STR);
                $insert_stmt->bindValue(':zipCode', $zipCode, PDO::PARAM_STR);
                $insert_stmt->bindValue(':logo', $logo, PDO::PARAM_STR);

                $insert_stmt->execute();


                $returnData = msg(1, 201, 'You have successfully added this school.');
            } else {
                $returnData = msg(0, 400, 'You should choose a valid user id for school administration.');

            }


            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
        }
endif;

echo json_encode($returnData);