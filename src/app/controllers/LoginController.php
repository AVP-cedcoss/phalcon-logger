<?php

use Phalcon\Mvc\Controller;

class LoginController extends Controller
{
    /**
     * Index Function of Login Controller
     * Checks if Cookie Exists then logs the User In
     *
     * @return void
     */
    public function indexAction()
    {
        //IF USER SIGNED UP AND SESSION ALREADY EXISTS
        $cookie = json_decode($this->cookie->get('cookieDetail'));
        if (count((array)$cookie) == 2) {
            $this->fetchUserDetail($cookie->user_email, $cookie->user_password);
        }
        if (count($this->session->get('userDetail'))) {
            $this->login();
        }
    }

    /**
     * Default Sign In Action
     * fetches Post and passes the input Variables to search in Database
     *
     * @return void
     */
    public function signinAction()
    {
        $data = $this->request->getpost();

        $this->fetchUserDetail($data['user_email'], $data['user_password']);

        //CALL ACTUAL LOGIN
        $this->login();
    }

    /**
     * fetchUserDetail
     * fetches user Detail from Database
     *
     * @param [type] $user_email
     * @param [type] $user_password
     * @return void
     */
    private function fetchUserDetail($user_email, $user_password)
    {
        $user_email = $this->namespace->component->sanitize($user_email);
        $user_password = $this->namespace->component->sanitize($user_password);
        //SEARCH IN DB WHETHER USER EXISTS
        $user = Users::find(
            [
                'conditions' => 'user_email = :email: and user_password = :password:',
                'bind'       => [
                    'email' => $user_email,
                    'password' => $user_password
                ]
            ]
        );

        //IF USER NOT FOUND redirect to login form after displaying Error
        if (!count($user)) {
            // $this -> error("Invalid Details", 0)
            $this->logs->excludeAdapters(['main'])->warning("Login Failed for '" . $user_email . "' with Password '" . $user_password . "'");
            $this->response->redirect('login');
            $this->response->send();
        }

        //IF USER FOUND CREATE AN OBJECT AND PUSH TO SESSION
        $user = $user[0];
        $userDetail = [
            "user_id" => $user->user_id,
            "user_name" => $user->user_name,
            "user_email" => $user->user_email,
            "user_role" => $user->user_role,
            "user_status" => $user->user_status
        ];
        $this->session->set('userDetail', (object)$userDetail);

        //Cookie set if remeber me ticked
        if (isset($this->request->getpost()['remember'])) {
            $cookieDetail = [
                "user_email" => $user->user_email,
                "user_password" => $user->user_password
            ];
            $this->cookie->set('cookieDetail', json_encode($cookieDetail), time() + 3600);
            $this->logs->info("Cookie Created for '" . $user_email . "'");
        }
    }

    /**
     * Login function
     * Actual Login Redirects based on Role and Status of User
     *
     * @return void
     */
    private function login()
    {
        if ($this->session->get('userDetail')->user_status == 'Approved') {
            $this->logs->info("Login Successful for '" . $this->session->get('userDetail')->user_email . "'");
            $this->response->redirect('user');
        } elseif ($this->session->get('userDetail')->user_status == 'Pending') {
            $this->logs->excludeAdapters(['admin'])->error("Login Successful for '" . $this->session->get('userDetail')->user_email . "' but User Status is: " . $this->session->get('userDetail')->user_status);
            $this->error("Account Pending Approval", 0);
        } elseif ($this->session->get('userDetail')->user_status == 'Restricted') {
            $this->logs->excludeAdapters(['admin'])->error("Login Successful for '" . $this->session->get('userDetail')->user_email . "' but User Status is: " . $this->session->get('userDetail')->user_status);
            $this->error("Account Suspended", 0);
        }
    }

    private function error($message, $success)
    {
        $this->view->success = $success;
        $this->view->message = $message;
    }

    /**
     * clearAction
     * Destroys Session and cookie
     *
     * @return void
     */
    public function clearAction()
    {
        $this->session->destroy();
        $this->cookie->get('cookieDetail')->delete();
        $this->logs->excludeAdapters(['admin'])->info("'" . $this->session->get('userDetail')->user_email . "' Logged out");
        $this->logs->info("'" . $this->session->get('userDetail')->user_email . "' Cookie Destroyed");
        $this->response->redirect('login');
    }
}
