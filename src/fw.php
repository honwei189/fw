<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 12/05/2019 22:03:39
 * @last modified     : 22/03/2020 22:10:17
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189\fw;

use \honwei189\config as config;
use \honwei189\flayer as flayer;

/**
 *
 * Tiny & lightweight framework.  Supports MVC.  Use for Web App or RESTful API
 *
 * s = prefix keyword. switch module / application
 *
 *
 * @package     fw
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/fw/
 * @link        https://appsw.dev
 * @link        https://justtest.app
 * @version     "1.0.1" 22/03/2020 15:56:39 Rectified some bugs and do some small enhancements
 * @since       "1.0.0"
 */
class fw
{
    public $data = null;
    public $http = null;
    public $uri  = null;

    private $app           = "general";
    private $configuration = [];
    private $controller    = null;
    private $html          = null;
    private $method        = null;
    private $multi_app     = true;
    private $path          = null;

    public function __autoload($class)
    {
        if (stripos($class, "\\") !== false) {
            // $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            // $_     = explode(DIRECTORY_SEPARATOR, $class);
            // $class = str_ireplace("model", "", end($_));
            // array_pop($_);
            // $class = join(DIRECTORY_SEPARATOR, $_) . DIRECTORY_SEPARATOR. "models".DIRECTORY_SEPARATOR."$class";

            include_once $this->path . "app" . DIRECTORY_SEPARATOR . str_replace("\\model\\", "\\models\\", $class) . ".php";
        } else if (stripos($class, "model") !== false) {
            $this->load_model(str_ireplace("model", "", $class));
        } else {
            $this->load_class($class);
        }

        // spl_autoload_register(__NAMESPACE__ . '\my_autoloader');
    }

    public function __construct()
    {
        $this->http = flayer::bind("\\honwei189\\http");

        if (php_sapi_name() == 'cli-server') {
            if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
                return false;
            }

            // if (preg_match('/\.css|\.js|\.jpg|\.png|\.map$/', $_SERVER['REQUEST_URI'], $match)) {
            //     $mimeTypes = [
            //         '.css' => 'text/css',
            //         '.js'  => 'application/javascript',
            //         '.jpg' => 'image/jpg',
            //         '.png' => 'image/png',
            //         '.map' => 'application/json'
            //     ];
            //     $path = __DIR__ . $_SERVER['REQUEST_URI'];

            //     if (is_file($path)) {
            //         header("Content-Type: {$mimeTypes[$match[0]]}");
            //         require $path;
            //         exit;
            //     }
            // }
        }

        $this->detect_path();
        $config = null;

        spl_autoload_register(array($this, '__autoload'));

        if (isset($this->configuration['MULTI_APP']) && $this->configuration['MULTI_APP'] === 1) {
            if (!isset($_SESSION)) {
                session_start();
            }
            $this->multi_app = true;
        }

        if (isset($this->configuration['APP']{0})) {
            $this->app = $this->configuration['APP'];
        }

        if (isset($_SESSION['APP']{0})) {
            $this->app = $_SESSION['APP'];
        }
    }

    public function bootstrap()
    {
        $reroute = false;

        if (php_sapi_name() != "cli") {
            $this->load_routes();

            if (isset($this->configuration['ROUTES']) && is_array($this->configuration['ROUTES'])) {
                $reroute = $this->uri_reroute();
            }

            if (!$reroute) {
                $this->uri_route();
            }

            unset($reroute);
        }

        if (!config::is_loaded()) {
            config::load();
        }

        $this->controller();
    }

    public function detect_path()
    {
        if (is_null($this->path)) {
            if (php_sapi_name() == "cli") {
                $this->path = realpath(__DIR__ . (isset($_SERVER['SHELL']) ? "/../../../../../" : "../../../../../../"));
            } else {
                $this->path = substr_replace($_SERVER['DOCUMENT_ROOT'], "", strrpos($_SERVER['DOCUMENT_ROOT'], "public"));
            }
        }
    }

    public function embed($label, $string)
    {
        if (!is_value($this->html)) {
            $this->embed($label, $string);
        } else {
            $this->html = str_replace("{{" . $label . "}}", $string, $this->html);
        }
    }

    public static function embed_file($key, $str)
    {
        $this->embed($key, $str);
    }

    public static function embed_text($key, $str)
    {
        $this->html = str_replace("{{" . $key . "}}", $str, self::$html);
    }

    public function embed_view($label, $file, $data = null)
    {
        $this->html = str_replace("{{" . $label . "}}", $this->load_views($file, $data, false), $this->html);
    }

    public function execute()
    {
        $reroute = false;

        if (isset($this->configuration['routes']) && is_array($this->configuration['routes'])) {
            $reroute = $this->uri_reroute();
        }

        if (!$reroute) {
            $this->uri_route();
        }

        unset($reroute);
        $this->controller();
    }

    public function load_class($class)
    {
        if (!class_exists($class)) {
            $file = $this->path . "/libs/{$class}.php";

            if (is_file($file)) {
                include_once $file;
            }
        }
    }

    public function load_controller($file, $exit_if_not_found = false)
    {
        if (is_file($file)) {
            include_once $file;
            $c               = "";
            $controller_name = $this->controller . "controller";
            $method          = $this->method;
            $instance        = null;

            if (class_exists($controller_name) || class_exists($this->app . "\\" . $controller_name)) {
                if (method_exists($controller_name, "__construct") || method_exists($controller_name, "index")) {
                    $instance = new $controller_name($this);
                } else if (method_exists($this->app . "\\" . $controller_name, "__construct") || method_exists($this->app . "\\" . $controller_name, "index")) {
                    $c        = "\\{$this->app}\\$controller_name";
                    $instance = new $c($this);
                }

                if (is_value($c)) {
                    $controller_name = $c;
                }

                if (method_exists($controller_name, $this->method)) {
                    $this->uri = str_replace("/" . $this->controller . "/$method", "", $_SERVER['REQUEST_URI']);

                    if ($this->uri === "/") {
                        $this->uri = "";
                    }

                    $instance->data = $this->params;
                    $instance->http = $this->http;
                    $instance->uri  = $this->uri;
                    $instance->$method();
                } else {
                    if ($exit_if_not_found) {
                        return false;
                    }

                    $this->uri = str_replace("/" . $this->controller, "", $_SERVER['REQUEST_URI']);
                    if (strpos($this->uri, "/") === 0) {
                        $this->uri = substr($this->uri, 1);
                    }

                    if ($this->method != "index") {
                        // if (!is_array($uri)) {
                        //     $this->uri = $method;
                        // } else {
                        //     array_unshift($this->uri, $this->method);
                        // }
                    } else {
                        $this->method = null;
                    }

                    $instance->data = $this->params;
                    $instance->http = $this->http;
                    $instance->uri  = $this->uri;
                    $instance->index();
                }
            }

            $c = null;
            return true;
        } else {
            return false;
        }
    }

    public function load_model($model)
    {
        $file = $this->path . "/app/" . ($this->multi_app ? $this->app . "/" : "") . "models/{$model}.php";

        if (is_file($file)) {
            include_once $file;
        }
    }

    public function load_routes()
    {
        $file = $this->path . "app" . DIRECTORY_SEPARATOR . ($this->multi_app ? $this->app . DIRECTORY_SEPARATOR : "") . "routes.php";

        if (is_file($file)) {
            $this->configuration['ROUTES'] = include $file;
        }
    }

    public function load_views($views, $data = null, $output = false)
    {
        $contents = null;
        $file     = $this->path . "/app/" . ($this->multi_app ? $this->app . "/" : "") . "views/{$views}.php";

        if (is_file($file)) {
            if (!is_null($data)) {
                extract($data);
            }

            if ($output) {
                include $file;
            } else {
                ob_start();
                include_once $file;
                $contents = ob_get_contents();
                ob_end_clean();
            }
        }

        if (!is_null($data)) {
            foreach ($data as $k => &$v) {
                //free extracted variables
                unset($$k);
            }
        }

        unset($vars);

        return $contents;
    }

    public function render()
    {
        $this->html = preg_replace("|\{\{(.*?)\}\}|isU", "", $this->html);

        if (!isset($this->html {0})) {
            echo $this->html;
        } else {
            if ($this->html_cache && !$this->html_cache_exist) {
                $fp = fopen($this->path . "/app/" . ($this->multi_app ? $this->app . "/" : "") . "cache/" . str_replace("/", "_", $this->route_map) . ".php", "w+");
                fwrite($fp, $this->html);
                fclose($fp);
            }

            echo $this->html;
            $this->html = null;
        }
    }

    public static function set_app($name)
    {
        $this->app = $name;
    }

    public static function session_start($expire = null)
    {
        if ($expire == 0) {
            //$expire = ini_get('session.gc_maxlifetime');
        } else {
            ini_set('session.gc_maxlifetime', $expire);
        }

        if (empty($_COOKIE['s'])) {
            session_set_cookie_params($expire);
            session_name("s");
            session_start();

            if (is_null($expire)) {
            } elseif ($expire == 0) {
                setcookie("s", session_id(), 0, "/", $_SERVER['HTTP_HOST']);
            } else {
                setcookie("s", session_id(), time() + $expire, "/", $_SERVER['HTTP_HOST']);
            }
        } else {
            session_name("s");
            session_start();

            if (is_null($expire)) {
            } elseif ($expire == 0) {
                setcookie("s", session_id(), 0, "/", $_SERVER['HTTP_HOST']);
            } elseif (!is_null($expire)) {
                setcookie("s", session_id(), time() + $expire, "/", $_SERVER['HTTP_HOST']);
            }
        }
    }

    public function set_path($path)
    {
        $this->path = $path;
        $path       = null;
    }

    public static function template($name, $module = "")
    {
        if (self::$path == "") {
            self::$path = substr(realpath(dirname(__FILE__)), 0, -2);
        }

        if (empty($module)) {
            $module = self::$app;
        }

        $file = self::$path . "/app/{$module}/templates/{$name}.php";

        if (!file_exists($file)) {
            echo "<h1>$file not found</h1>";
            return;
        }

        ob_start();
        include $file;
        self::$html = ob_get_contents();
        ob_end_clean();

        if (stripos(self::$html, "{{tpl::") !== false) {
            preg_match_all("|\{\{tpl::(.*?)\}\}+|si", self::$html, $reg);
            if (is_array($reg)) {
                foreach ($reg[0] as $idx => $val) {

                    $tpl = self::$path . "/app/{$module}/templates/" . strtolower($reg[1][$idx]) . ".php";
                    //if(file_exists($tpl)){
                    if (is_file($tpl)) {
                        ob_start();
                        include $tpl;
                        $contents = Ob_get_contents();
                        ob_end_clean();

                        self::$html = str_replace($val, $contents, self::$html);
                        unset($contents);
                    } else {
                        self::$html = str_replace($val, "<h1>template file -- \"$tpl\" not found</h1>", self::$html);
                    }

                    unset($tpl);
                }
            }

            unset($reg);
        }

        unset($file);
    }

    public function view($views, $vars = null)
    {
        return $this->load_views($views, $vars);
    }

    private function controller()
    {
        $file = $this->path . "app" . DIRECTORY_SEPARATOR . ($this->multi_app ? $this->app . DIRECTORY_SEPARATOR : "") . "controllers" . DIRECTORY_SEPARATOR . $this->controller . ".php";

        if (!$this->load_controller($file)) {
            $this->method     = $this->controller;
            $this->controller = "main";

            $file = $this->path . "app" . DIRECTORY_SEPARATOR . ($this->multi_app ? $this->app . DIRECTORY_SEPARATOR : "") . "controllers" . DIRECTORY_SEPARATOR . "main.php";

            if (!$this->load_controller($file, true)) {
                $this->http->http_error(404);
            }
        }
    }

    private function model()
    {}

    private function uri_route()
    {
        $uri = parse_url(urldecode($_SERVER['REQUEST_URI']));

        if (isset($uri['path']) && !empty($uri['path'])) {
            if ($uri['path'] === "/") {
                $this->controller = "main";
                $this->method     = "index";
            } else {
                $_ = preg_split("|\s*/\s*|", $uri['path'], -1, PREG_SPLIT_NO_EMPTY);

                if ($_[0] == "s") {
                    $this->controller = $_[1];
                    $this->method     = "index";
                    // $this->app($this->controller);

                    if (count($_) > 2) {
                        array_shift($_);
                        array_shift($_);

                        $url = "/" . join("/", $_);
                    } else {
                        $url = "/";
                    }

                    header("location: $url");
                } else {
                    if ($_[0] == "signout") {
                        $this->app        = "general";
                        $this->controller = "main";
                        $this->method     = $_[0];
                    }

                    $file = $this->path . "/app/" . ($this->multi_app ? $this->app . "/" : "") . "controllers/main.php";
                    include $file;

                    if (method_exists("mainController", $_[0])) {
                        $this->controller = "main";
                        $this->method     = $_[0];
                    } else {
                        $this->controller = $_[0];

                        if (isset($_[1])) {
                            $this->method = $_[1];
                        } else {
                            $this->method = "index";
                        }
                    }
                }

                unset($_);
            }
        } else {
            $this->controller = "main";
            $this->method     = "index";
        }

        $this->route_map = $this->controller . "/" . $this->method;
        unset($uri);
    }

    private function uri_reroute()
    {
        /*
        e.g:

        $route['/home'] = array("controller"=>"user", "method"=>"get_user", "params"=> array("id", "name") );
         */
        $match = false;

        $uri = parse_url($_SERVER['REQUEST_URI']);
        if (isset($uri['path']) && $uri['path'] !== "/") {
            $_ = preg_split("|/|", $uri['path'], -1, PREG_SPLIT_NO_EMPTY);

            $i      = 0;
            $route  = null;
            $router = null;
            // while ($match === false) {
            //     $path = $_[$i];

            //     if (isset($this->configuration['ROUTES']['/' . $path])) {
            //         $match           = true;
            //         $route           = "/$path";
            //         $router          = $this->configuration['ROUTES']['/' . $path];
            //         $this->route_map = $route;
            //     }

            //     if (isset($this->configuration['ROUTES'][$path])) {
            //         $match           = true;
            //         $route           = "$path";
            //         $router          = $this->configuration['ROUTES'][$path];
            //         $this->route_map = $route;
            //     }

            //     ++$i;
            // }

            // unset($_);
            // unset($i);
            // unset($uri);
            // unset($path);

            if (isset($_) && is_array($_) && count($_) > 0) {
                $path = "";
                foreach ($_ as $k => $v) {
                    $path .= "/" . $v;

                    if (isset($this->configuration['ROUTES'][$path])) {
                        $match           = true;
                        $route           = "$path";
                        $router          = $this->configuration['ROUTES'][$path];
                        $this->route_map = $route;

                        // if(is_array($router['params'])){

                        // }elseif($router['params'] == "*"){
                        //     break;
                        // }

                        break;
                    }
                }
            }

            if (isset($router['controller'])) {
                $this->controller = $router['controller'];
            } else {
                $this->controller = "main";
            }

            if (isset($router['method'])) {
                $this->method = $router['method'];
            } else {
                $this->method = "index";
            }

            if (isset($router['action']) && isset($this->http)) {
                if (trim(strtolower($router['action'])) != strtolower($this->http->action)) {
                    $this->http->http_error(403);
                }
            }
            
            if (isset($router['type']) && isset($router['type'])){
                if (trim(strtolower($router['type'])) == "json" && $this->http->type != "json"){
                    $this->http->http_error(406, "Form " . $this->http->action . " data is not json");
                }
            }

            if (isset($router['params'])) {
                if (is_array($router['params'])) {
                    $uri = preg_split("|/|", str_replace($route, "", $_SERVER['REQUEST_URI']), -1, PREG_SPLIT_NO_EMPTY);

                    if (is_array($router['params'])) {
                        $_ = $router['params'];
                        unset($router['params']);

                        $router['params'][] = $_;
                        unset($_);
                    }

                    $nums_params = count($router['params'][0]);

                    if ($nums_params > 0) {
                        foreach ($router['params'][0] as $k => $v) {
                            $this->params[$v] = trim(urldecode($uri[$k]));
                        }
                    }

                    $i = 0;
                    $x = 0;
                    while ($nums_params < $i) {
                        if (isset($uri[$x]) && is_value($uri[$x])) {
                            $this->params[$router['params'][$i]] = trim(urldecode($uri[$x]));
                            ++$i;
                        }
                        ++$x;
                    }

                } elseif ($router['params'] != "*") {
                    return $this->http->http_error(404);
                }
            } else {
                $uri = preg_split("|/|", str_replace($route, "", $_SERVER['REQUEST_URI']), -1, PREG_SPLIT_NO_EMPTY);

                if (count($uri) > 0) {
                    return $this->http->http_error(404);
                }
            }

            unset($uri);
            unset($i);
            unset($x);
            unset($route);
            unset($router);
        }

        return $match;
    }
}
