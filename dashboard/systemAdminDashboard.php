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

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $loggedUserRole = $isValidToken['data']['role'];


        if ($loggedUserRole === 1) {
            try {

                $total_schools_query = "SELECT count(*) as totalSchools from `schools`";
                $query_stmt = $conn->prepare($total_schools_query);
                $query_stmt->execute();
                $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);

                $total_schools_this_month_query = "SELECT count(*) as totalSchoolsThisMonth from `schools` WHERE MONTH(created_date) = MONTH(now())
                and YEAR(created_date) = YEAR(now())";
                $total_schools_this_month_query_stmt = $conn->prepare($total_schools_this_month_query);
                $total_schools_this_month_query_stmt->execute();
                $total_schools_this_month_query_stmt_row = $total_schools_this_month_query_stmt->fetchALL(PDO::FETCH_ASSOC);

                $total_admin_users_query = "SELECT count(*) as totalAdminUsers from `users` WHERE role = 2";
                $query_stmt1 = $conn->prepare($total_admin_users_query);
                $query_stmt1->execute();
                $row1 = $query_stmt1->fetchALL(PDO::FETCH_ASSOC);

                $total_admin_users_this_month_query = "SELECT count(*) as totalAdminUsersThisMonth from `users` WHERE role = 2 AND MONTH(created_date) = MONTH(now())
                and YEAR(created_date) = YEAR(now())";
                $query_stmt2 = $conn->prepare($total_admin_users_this_month_query);
                $query_stmt2->execute();
                $row2 = $query_stmt2->fetchALL(PDO::FETCH_ASSOC);


                $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully get user',
                        'data' => [
                            'totalSchools'=> $row[0]['totalSchools'],
                            'totalSchoolsThisMonth'=> $total_schools_this_month_query_stmt_row[0]['totalSchoolsThisMonth'],
                            'totalAdminUsers' => $row1[0]['totalAdminUsers'],
                            'totalAdminUsersThisMonth' => $row2[0]['totalAdminUsersThisMonth']
                        ]
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