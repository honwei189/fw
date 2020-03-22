<?php
/*
 * @description       : Framework routing table for app = general
 * @version           : "1.0.0"
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 22/03/2020 12:08:02
 * @last modified     : 22/03/2020 15:54:30
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

 /**
  Format :

  $route[URL] = [
      "controller" => CONTROLLER_NAME (REMOVE "controller" wording.  e.g: class filecontroller, "controller" = "file"), 
      "method" => CONTROLLER_FUNCTION_NAME,
      "header" => HTTP_HEADER (e.g: POST, GET, PUT, DELETE.  Default = All type.  This parameter is optional, not mandatory)
      "params" => URI_AS_PARAMETERS_DATA (e.g : ["id", "name"].  URI become localhost/CONTROLLER_NAME/1/MY_NAME)
    ];

    params = *, means accept all.
    e.g:  /user, params = *.  URL = http://localhost:8000/user/list_all

    All URL start with "/user", accept it

  example:

  $route['/home'] = ["controller" => "user", "method" => "get_user", "params" => ["id", "name"] ];  // e.g:  http://localhost:8000/home/1/My name
  $route['/user'] = ["controller" => "user_list", "method" => "all", "header" => "get", "params" => "*"];  // e.g: http://localhost:8000/user/list_all
 */

$route = [];

return $route;