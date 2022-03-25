<?php
// print_r(apache_get_modules());
// echo "<pre>"; print_r($_SERVER); die;
// $_SERVER["REQUEST_URI"] = str_replace("/phalt/","/",$_SERVER["REQUEST_URI"]);
// $_GET["_url"] = "/";
use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Http\Response;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Config;
use Phalcon\Config\ConfigFactory;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Http\Response\Cookies;

// $config = new Config([]);

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('URL_PATH', "http://localhost:8080/");
define('APP_PATH', BASE_PATH . '/app');

// Register an autoloader
$loader = new Loader();

$loader->registerDirs(
    [
        APP_PATH . "/controllers/",
        APP_PATH . "/models/",
    ]
);

$loader->registerNamespaces(
    [
        "component" => APP_PATH ."/component"
    ]
);

$loader->register();

// echo "<pre>";
// print_r($loader);
// die();

$container = new FactoryDefault();

$container->set(
    'namespace',
    function () {
        $detail = array(
            'component' => new \component\myescaper()
        );
        return (object)$detail;
    }
);

$container->set(
    'view',
    function () {
        $view = new View();
        $view->setViewsDir(APP_PATH . '/views/');
        return $view;
    }
);

$container->set(
    'config',
    function () {
        $fileName = '../app/storage/config.php';
        $factory = new ConfigFactory();
        return $factory -> newInstance("php", $fileName);
    }
);

$container->set(
    'url',
    function () {
        $url = new Url();
        $url->setBaseUri('/');
        return $url;
    }
);

$container->set(
    'db',
    function () {
        $config =$this->get('config');
        return new Mysql(
            [
                'host'     => $config['db']['host'],
                'username' => $config['db']['username'],
                'password' => $config['db']['password'],
                'dbname'   => $config['db']['dbname'],
            ]
        );
    }
);

$application = new Application($container);

$container->set(
    'session',
    function () {
            $session = new Manager();
            $files = new Stream(
                [
                    'savePath' => '/tmp',
                ]
            );
            $session->setAdapter($files);
            $session->start();
            return $session;
        }
);

$container->set(
    'cookie',
    function () {
        $cookie  = new Cookies();
        $cookie -> useEncryption(false);
        return $cookie;
    }
);

$container->set(
    'mongo',
    function () {
        $mongo = new MongoClient();

        return $mongo->selectDB('phalconBasic');
    },
    true
);

try {
    // Handle the request
    $response = $application->handle(
        $_SERVER["REQUEST_URI"]
    );

    $response->send();
} catch (\Exception $e) {
    $response = new Response();
    if (strpos($e -> getMessage(), " handler class cannot be loaded")) {
        echo "Controller Not Found";
        // Getting a response instance
        $response -> redirect('error');
        $response->send();
    } elseif (strpos($e -> getMessage(), "was not found on handler")) {
        echo "Method Not Found";
    }

}
