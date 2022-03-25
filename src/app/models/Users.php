<?php

use Phalcon\Mvc\Model;

class Users extends Model
{
    public $user_id;
    public $user_name;
    public $user_email;
    public $user_password;
    public $user_role = 'User';
    public $user_status = 'Pending';
}