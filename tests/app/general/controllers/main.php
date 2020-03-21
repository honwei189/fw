<?php
/*
 * @description       : 
 * @version           : "1.0.0" 
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 01/02/2020 14:37:10
 * @last modified     : 21/03/2020 12:23:21
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */
namespace general;
// use \honwei189\flayer as flayer;
// use model\common as common;
// use \general\mainModel as main;

class mainController extends \honwei189\fw\fw
{
    // public $data;
    private $common;

    public function __construct()
    {
        $this->common = \honwei189\flayer::bind("general\\model\common");
    }

    public function __destruct()
    {}
    
    public function index()
    {
        $this->common->abc();
    }
}
