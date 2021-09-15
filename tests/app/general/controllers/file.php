<?php
/*
 * @description       :
 * @version           : "1.0.0"
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 01/02/2020 14:37:10
 * @last modified     : 21/03/2020 15:10:14
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */
namespace general;

use honwei189\fw\fw as fw;

class filecontroller
{
    public function index()
    {
        echo "file";

        // pre($this->uri);
        // pre($this->http);
        $fw = new fw;
        pre($this->uri);
        pre($this->http);
        // pre($fw);
    }

    // public function abc(){
    //     echo "abc";
    // }
}
