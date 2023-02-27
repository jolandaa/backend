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

    public function BadPayload($msg=''){
        http_response_code(422);
        echo json_encode(['error'=>$msg]);
        exit;
    }

    public function PageNotFound($msg='Page Not Found!'){
        http_response_code(405);
        echo json_encode(['error'=>$msg]);
        exit;
    }

    public function RoleNotAllowed($msg='You are not allowed to access this page'){
        http_response_code(403);
        echo json_encode(['error'=>$msg]);
        exit;
    }    
}