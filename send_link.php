<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/shared/Database.php';
require __DIR__. '/shared/errorResponses.php';
require __DIR__.'/shared/JwtHandler.php';

require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';

function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}


$db_connection = new Database();
$conn = $db_connection->dbConnection();
$error_responses = new ErrorResponses();

$data = json_decode(file_get_contents("php://input"));

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->email) 
    || empty(trim($data->email))
    ):
    return $error_responses->BadPayload('Please Fill in all Required Fields!');

else:
    $email=trim($data->email); 

    try {
        $list_query = "SELECT * from users where email='$email'";
        $query_stmt = $conn->prepare($list_query);
        $query_stmt->execute();
        $row = $query_stmt->fetch(PDO::FETCH_ASSOC);

        if ($query_stmt->rowCount()) {

            $user_id = $row['user_id'];
            $name = $row['first_name'];
            $role = $row['role'];
            $jwt = new JwtHandler();
            $token = $jwt->jwtEncodeData(
                'http://localhost/WEB/backend/',
                array("user_id"=> $user_id,"role"=> $role)
            );



            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->Mailer = "smtp";

            $mail->SMTPDebug  = 1;  
            $mail->SMTPAuth   = TRUE;
            $mail->SMTPSecure = "tls";
            $mail->Port       = 587;
            $mail->Host       = "smtp.gmail.com";
            $mail->Username   = "bregujola@gmail.com";
            $mail->Password   = "frucuajvouczcmul";

            $mail->IsHTML(true);
            $mail->AddAddress($email, $name);
            $mail->SetFrom("bregujola@gmail.com", "School Management System");
            $mail->AddReplyTo("bregujola@gmail.com", "School Management System");
            $mail->Subject = "Reset your password";
            $resetLink = "http://localhost:4200/forgot-passw/".$token."/".$email;
            $content = '
                <!doctype html>
                <html lang="en-US">

                <head>
                    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
                    <title>Reset Password Email Template</title>
                    <meta name="description" content="Reset Password Email Template.">
                    <style type="text/css">
                        a:hover {text-decoration: underline !important;}
                    </style>
                </head>

                <body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
                    <!--100% body table-->
                    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
                        style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: "Open Sans", sans-serif;">
                        <tr>
                            <td>
                                <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto;" width="100%" border="0"
                                    align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="height:80px;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="height:20px;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                                style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                                                <tr>
                                                    <td style="height:40px;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:0 35px;">
                                                        <h1 style="color:#1e1e2d; font-weight:500; margin:0;font-size:32px;font-family:"Rubik",sans-serif;">You have
                                                            requested to reset your password</h1>
                                                        <span
                                                            style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;"></span>
                                                        <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                                            We cannot simply send you your old password. A unique link to reset your
                                                            password has been generated for you. To reset your password, click the
                                                            following link and follow the instructions.
                                                        </p>
                                                        <a href="'.$resetLink.'"
                                                            style="background:#039BE5;text-decoration:none !important; font-weight:500; margin-top:35px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:50px;">Reset
                                                            Password</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="height:40px;">&nbsp;</td>
                                                </tr>
                                            </table>
                                        </td>
                                    <tr>
                                        <td style="height:20px;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="height:80px;">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!--/100% body table-->
                </body>

                </html>';

            $mail->MsgHTML($content); 
            if(!$mail->Send()) {
               $returnData = [
                            'success' => 1,
                            'message' => 'Error while sending Email.',
                            'data' => false
                        ];

            } else {
               $returnData = [
                            'success' => 1,
                            'message' => 'Email sent successfully.',
                            'data' => true
                        ];
            }
        }


    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
        http_response_code(500);
        echo json_encode(['error'=>$e->getMessage()]);
        exit;
    }
endif;
echo json_encode($returnData);

?>