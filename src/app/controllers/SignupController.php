<?php

use Phalcon\Mvc\Controller;
use Phalcon\Escaper;

class SignupController extends Controller{

    public function IndexAction()
    {

    }

    public function registerAction(){
        $user = new Users();

        $escaper = new Escaper();
        
        $inputdata = array(
            'user_name' => $escaper->escapeHtml($this->request->getPost('user_name')),
            'user_email' => $escaper->escapeHtml($this->request->getPost('user_email')),
            'user_password' => $escaper->escapeHtml($this->request->getPost('user_password'))
        );

        $user->assign(
            $inputdata,
            [
                'user_name',
                'user_email',
                'user_password'
            ]
        );

        $success = $user->save();

        $this->view->success = $success;

        if($success) {
            $this->view->message = "Register succesfully";
            $data = $this->request->getpost();
            $user = Users::find (
                [
                    'conditions' => 'user_email = :email: and user_password = :password:' ,
                    'bind'       => [
                        'email' => $data['user_email'],
                        'password' => $data['user_password']
                    ]
                ]
            )[0];
            $userDetail = [
                "user_id" => $user -> user_id,
                "user_name" => $user -> user_name,
                "user_email" => $user -> user_email,
                "user_role" => $user -> user_role,
                "user_status" => $user -> user_status
            ];
            $this -> session -> set('userDetail', (object)$userDetail);

            //cookie setting if remember me
            if (isset($this->request->getpost()['remember'])) {
                $cookieDetail = [
                    "user_email" => $user -> user_email,
                    "user_password" => $user -> user_password
                ];
                $this -> cookie -> set('cookieDetail', json_encode($cookieDetail), time() + 3600);
            }
        } else {
            $this->view->message = "Not Register succesfully due to following reason: <br>".implode("<br>", $user->getMessages());
        }
        $this -> response -> redirect("login");
    }
}