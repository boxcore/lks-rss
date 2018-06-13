<?php

/**
 * rss init
 */

ini_set('display_errors','On');
error_reporting(1);

if(!defined('DS'))   define('DS', DIRECTORY_SEPARATOR);       // 设定系统分割符号
if(!defined('ROOT')) define('ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);
if(!defined('RSS_LOG')) define('RSS_LOG', ROOT.'logs'.DS.'rss'.DS);

//namespace RSS\Loader;

class Loader {

    public function __construct() {

    }

    public static function autoload($class_name){
        $class_file = strtolower($class_name).".php";
        if (file_exists($class_file)){
            require_once($class_file);
        }

    }

    public static function libs($class_name){
        $class_file = ROOT.'libs'.DS.$class_name.DS.$class_name.".php";
        if (file_exists($class_file)){
            require_once($class_file);
        }

    }
}

// load diy libs
spl_autoload_register('Loader::libs');

// reqire composer libs
if(file_exists(ROOT . 'vendor/autoload.php')) require ROOT . 'vendor/autoload.php';

require ROOT.'funcs/global.fn.php';