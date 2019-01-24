<?php
// namespace wc;

class MCErrors
{
    public $errors = [];

    public function add($code = '', $message = '', $exit = false)
    {
    	$this->errors[] = ['code'=>$code, 'message'=>$message];
        // $this->errors = array_merge($this->errors, [$code=>$message]);
        
        // if "exit" equals true, echo the json_encode, and exit()
        if($exit === true) {
            echo json_encode(['result'=>'fail', 'errors'=>$this->get()]);
            exit();
        }
    }

    public function get()
    {
        return $this->errors;
    }
}
