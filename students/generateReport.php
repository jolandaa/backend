<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../shared/Database.php';
require __DIR__.'/../AuthMiddleware.php';
require __DIR__.'/../tcpdf/tcpdf.php';

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
    !isset($_GET['nr_amzes'])
    || empty(trim($_GET['nr_amzes']))
) :

    $fields = ['fields' => ['nr_amzes']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
    return $error_responses->BadPayload('Please Fill in all Required Fields!');
// IF THERE ARE NO EMPTY FIELDS THEN-
else :

    $isValidToken = $auth->isValidToken();
    if ($isValidToken['success'] == 1) {

        $loggedUserRole = $isValidToken['data']['role'];
        if ($loggedUserRole === 2) {

            $nr_amzes = $_GET['nr_amzes'];

            try {

                $list_query = "SELECT * from `students` WHERE `nr_amzes`=$nr_amzes";
                $query_stmt = $conn->prepare($list_query);
                $query_stmt->execute();
                $row = $query_stmt->fetchALL(PDO::FETCH_ASSOC);



                if($query_stmt->rowCount() > 0)
                {
                    $studentData = $row[0];
                    // echo print_r($studentData);
                    $name = $studentData['first_name']." ". $studentData['last_name'];
                    $fatherName = $studentData['father_name'];
                    $dateOfBirth = $studentData['date_of_birth'];
                    $email = $studentData['email'];


                    $list_query1 = "SELECT classes.class_name, AVG(grades.grade) AS average_mark  from `grades`
                                    INNER JOIN `classes`
                                    ON grades.class_id = classes.class_id
                                    WHERE grades.student_id = $nr_amzes AND grades.status = 1
                                    GROUP BY classes.class_name ";
                    $query_stmt1 = $conn->prepare($list_query1);
                    $query_stmt1->execute();
                    $row1 = $query_stmt1->fetchALL(PDO::FETCH_ASSOC);

                    $classNameOfStudent = $row1[0]['class_name'];
                    $gradeOfStudent = $row1[0]['average_mark'];




                    $list_query2 = "SELECT COUNT(*) AS count_present, (SELECT COUNT(*) FROM `attendance` WHERE attendance.attendance_value = 2 AND attendance.student_id = $nr_amzes AND attendance.status = 1) AS count_absent  from `attendance`
                                    WHERE attendance.student_id = $nr_amzes AND attendance.status = 1 AND attendance.attendance_value = 1";
                    $query_stmt2 = $conn->prepare($list_query2);
                    $query_stmt2->execute();
                    $row2 = $query_stmt2->fetchALL(PDO::FETCH_ASSOC);

                    $present = $row2[0]['count_present'];
                    $absent = $row2[0]['count_absent'];



                    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

                    $pdf->AddPage();

                    $html = '<!DOCTYPE html>
                                <html>
                                <body style="font-family: Arial, sans-serif;margin: 0;padding: 0;">
                                    <header style="background-color: #1E90FF;color: #fff;padding: 20px;text-align: center;">
                                        <h1 style="margin: 0;">Student Report</h1>
                                    </header>
                                    <main style="max-width: 800px;margin: 20px auto;padding: 20px;background-color: #fff;box-shadow: 0 0 10px rgba(0,0,0,0.2);">
                                        <table style="width: 100%;border-collapse: collapse;margin-bottom: 20px;">
                                            <tr>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Student Name:</th>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$name.'</td>
                                            </tr>
                                            <tr>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Father Name:</th>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$fatherName.'</td>
                                            </tr>
                                            <tr>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Date of Birth:</th>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$dateOfBirth.'</td>
                                            </tr>
                                            <tr>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Email:</th>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$email.'</td>
                                            </tr>
                                        </table>
                                        <h2>Grades</h2>
                                        <table>
                                            <tr>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Class</th>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Grade</th>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$classNameOfStudent.'</td>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$gradeOfStudent.'</td>
                                            </tr>
                                        </table>
                                        <h2>Atendances</h2>
                                        <table>
                                            <tr>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;"></th>
                                                <th style="background-color: #1E90FF;color: #fff;padding: 10px;border: 1px solid #ccc;text-align: left;">Total</th>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">Present</td>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$present.'</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">Absent</td>
                                                <td style="padding: 10px;border: 1px solid #ccc;text-align: left;">'.$absent.'</td>
                                            </tr>
                                        </table>
                                    </main>
                                </body>
                                </html>
                                ';

                    $pdf->writeHTML($html, true, false, true, false, '');

                    $pdf->Output('example.pdf', 'I');


                }


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