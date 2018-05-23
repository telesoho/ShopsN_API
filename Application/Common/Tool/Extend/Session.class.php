<?php
namespace Common\Tool\Extend;

use Common\Tool\Tool;

class Session extends Tool
{
    public function setSession($isURL = '*')
    {
        header("Access-Control-Allow-Origin:".$isURL);
        $sessionId = isset($_POST['token']) ? $_POST['token'] : null;
        if($sessionId){
            session_write_close();
            session_id($sessionId);
            session_start();
        }
    
    }
}