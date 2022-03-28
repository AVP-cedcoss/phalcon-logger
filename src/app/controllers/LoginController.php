<?php

use Phalcon\Mvc\Controller;

class LoginController extends Controller
{
    public function indexAction()
    {
        //IF USER SIGNED UP AND SESSION ALREADY EXISTS
        $cookie = json_decode($this -> cookie -> get('cookieDetail'));
        if (count((array)$cookie) == 2) {
            $this -> fetchUserDetail($cookie -> user_email, $cookie -> user_password);
        }
        if (count($this -> session -> get('userDetail'))) {
            $this -> login();
        }
    }

    public function signinAction()
    {
        $data = $this->request->getpost();

        $this -> fetchUserDetail($data['user_email'], $data['user_password']);

        //CALL ACTUAL LOGIN
        $this -> login();
    }

    private function fetchUserDetail($user_email, $user_password)
    {
        $user_email = $this -> namespace -> component -> sanitize($user_email);
        $user_password = $this -> namespace -> component -> sanitize($user_password);
        //SEARCH IN DB WHETHER USER EXISTS
        $user = Users::find (
            [
                'conditions' => 'user_email = :email: and user_password = :password:' ,
                'bind'       => [
                    'email' => $user_email,
                    'password' => $user_password
                ]
            ]
        );

        //IF USER NOT FOUND redirect to login form after displaying Error
        if (!count($user)) {
            // $this -> error("Invalid Details", 0)
            $this -> logs -> excludeAdapters(['main']) -> warning("Login Failed for '".$user_email."' with Password '".$user_password."'");
            $this -> response -> redirect('login');
            $this -> response -> send();
        }

        //IF USER FOUND CREATE AN OBJECT AND PUSH TO SESSION
        $user = $user[0];
        $userDetail = [
            "user_id" => $user -> user_id,
            "user_name" => $user -> user_name,
            "user_email" => $user -> user_email,
            "user_role" => $user -> user_role,
            "user_status" => $user -> user_status
        ];
        $this -> session -> set('userDetail', (object)$userDetail);

        //Cookie set if remeber me ticked
        if (isset($this->request->getpost()['remember'])) {
            $cookieDetail = [
                "user_email" => $user -> user_email,
                "user_password" => $user -> user_password
            ];
            $this -> cookie -> set('cookieDetail', json_encode($cookieDetail), time() + 3600);
            $this -> logs -> info("Cookie Created for '".$user_email."'");
        }
    }

    //ACTUAL LOGIN WITH REDIRECTION TO DIFFERENT CONTROLLERS
    private function login()
    {
        if ($this->session->get('userDetail')->user_status == 'Approved') {
            $this -> logs -> info("Login Successful for '".$this->session->get('userDetail')->user_email."'");
            $this->response->redirect('user');
        } elseif ($this->session->get('userDetail')->user_status == 'Pending') {
            $this -> logs -> excludeAdapters(['admin']) -> error("Login Successful for '".$this->session->get('userDetail')->user_email."' but User Status is: ".$this->session->get('userDetail')->user_status);
            $this->error("Account Pending Approval", 0);
        } elseif ($this->session->get('userDetail')->user_status == 'Restricted') {
            $this -> logs -> excludeAdapters(['admin']) -> error("Login Successful for '".$this->session->get('userDetail')->user_email."' but User Status is: ".$this->session->get('userDetail')->user_status);
            $this->error("Account Suspended", 0);
        } 
    }

    private function error($message, $success)
    {
        $this -> view -> success = $success;
        $this -> view -> message = $message;
    }

    //DESTROY SESSION
    public function clearAction()
    {
        $this -> session -> destroy();
        // $this -> response -> delete('cookie');
        $this -> logs -> excludeAdapters(['admin']) -> info("'".$user_email."' Logged out");
        $this -> response -> redirect('login');
    }
}