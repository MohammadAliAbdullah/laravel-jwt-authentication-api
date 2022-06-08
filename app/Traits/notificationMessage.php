<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait notificationMessage
{
    private $success = 200;
    private $unauthorised = 401;

    public function successFull()
    {
        return  [
            "status" => $this->success,
            "success" => false,
            "message" => "Welcome ! You are successfull."
        ];
    }

    public function failed()
    {
        return  [
            "status" => $this->unauthorised,
            "success" => false,
            "message" => "Sorry ! Failed to Job."
        ];
    }
}
