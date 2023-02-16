<?php

class ErrorResponses{
    
    public function badRequest(){
        http_response_code(400);
	    echo json_encode(['error'=>'Bad Request!']);
	    exit;
    }

    public function UnAuthorized($msg='You need token!'){
        http_response_code(401);
	    echo json_encode(['error'=>$msg]);
	    exit;
    }
}