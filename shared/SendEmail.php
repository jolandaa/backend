<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

class SendEmail{
    
    public function sendEmail($email, $name, $Subject, $content){
        
        try{
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
            $mail->Subject = $Subject;


            $mail->MsgHTML($content); 

            if(!$mail->Send()) {
               return false;

            } else {
               return true;
            }

        }
        catch(PDOException $e){
            echo "Connection error ".$e->getMessage(); 
            exit;
        }
          
    }
}