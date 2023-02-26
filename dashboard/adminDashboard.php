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
    !isset($_GET['school_id'])
    || empty(trim($_GET['school_id']))
) :

    $fields = ['fields' => ['school_id']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

                $school_id = $_GET['school_id'];


        try {

            $total_teachers_query = "SELECT count(*) as totalTeachers from `teachers` WHERE school_id = $school_id";
            $query_stmt = $conn->prepare($total_teachers_query);
            $query_stmt->execute();
            $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

            $total_teachers_this_month_query = "SELECT count(*) as totalTeachersThisMonth from `teachers`
            INNER JOIN `users`
            ON teachers.user_id = users.user_id AND teachers.school_id = $school_id AND  MONTH(created_date) = MONTH(now())
       and YEAR(created_date) = YEAR(now())";
            $total_teachers_this_month_query_stmt = $conn->prepare($total_teachers_this_month_query);
            $total_teachers_this_month_query_stmt->execute();
            $total_teachers_this_month_query_stmt_row = $total_teachers_this_month_query_stmt->fetchALL(PDO::FETCH_ASSOC);

            $total_classes_query = "SELECT count(*) as totalClasses from `classes` WHERE school_id = $school_id";
            $query_stmt1 = $conn->prepare($total_classes_query);
            $query_stmt1->execute();
            $row1 = $query_stmt1->fetchALL(PDO::FETCH_ASSOC);

            $total_classes_this_month_query = "SELECT count(*) as totalClassesThisMonth from `classes` WHERE MONTH(created_date) = MONTH(now())
       and YEAR(created_date) = YEAR(now()) AND school_id = $school_id";
            $query_stmt2 = $conn->prepare($total_classes_this_month_query);
            $query_stmt2->execute();
            $row2 = $query_stmt2->fetchALL(PDO::FETCH_ASSOC);


            $total_students_query = "SELECT count(*) as totalStudents from `students` WHERE school_id = $school_id";
            $query_stmt3 = $conn->prepare($total_students_query);
            $query_stmt3->execute();
            $row3 = $query_stmt3->fetchALL(PDO::FETCH_ASSOC);

            $total_students_this_month_query = "SELECT count(*) as totalStudentsThisMonth from `students` WHERE MONTH(created_date) = MONTH(now())
       and YEAR(created_date) = YEAR(now()) AND school_id = $school_id";
            $query_stmt4 = $conn->prepare($total_students_this_month_query);
            $query_stmt4->execute();
            $row4 = $query_stmt4->fetchALL(PDO::FETCH_ASSOC);

            $total_parents_query = "SELECT count(*) as totalParents from `parents` WHERE school_id = $school_id";
            $query_stmt5 = $conn->prepare($total_parents_query);
            $query_stmt5->execute();
            $row5 = $query_stmt5->fetchALL(PDO::FETCH_ASSOC);

            $total_parents_this_month_query = "SELECT count(*) as totalParentsThisMonth from `parents` 
            INNER JOIN `users` ON MONTH(users.created_date) = MONTH(now())
       and YEAR(users.created_date) = YEAR(now()) AND parents.school_id = $school_id AND parents.user_id = users.user_id";
            $query_stmt6 = $conn->prepare($total_parents_this_month_query);
            $query_stmt6->execute();
            $row6 = $query_stmt6->fetchALL(PDO::FETCH_ASSOC);


            $returnData = [
                    'success' => 1,
                    'message' => 'You have successfully get user',
                    'data' => [
                        'totalTeachers'=> $row[0]['totalTeachers'],
                        'totalTeachersThisMonth'=> $total_teachers_this_month_query_stmt_row[0]['totalTeachersThisMonth'],
                        'totalClasses' => $row1[0]['totalClasses'],
                        'totalClassesThisMonth' => $row2[0]['totalClassesThisMonth'],
                        'totalStudents' => $row3[0]['totalStudents'],
                        'totalStudentsThisMonth' => $row4[0]['totalStudentsThisMonth'],
                        'totalParents' => $row5[0]['totalParents'],
                        'totalParentsThisMonth' => $row6[0]['totalParentsThisMonth']
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