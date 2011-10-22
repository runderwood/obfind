<?php

if(getenv('OBF_LIBDIR')) define('OBF_LIBDIR', getenv('OBF_LIBDIR'));

if(!defined('OBF_LIBDIR')) die('Shit is all fucked up and bullshit: missing libdir.');

require_once(OBF_LIBDIR.'obfind.php');

OBFind::init();

try {
    Glue::stick(array(
        '/' => 'obfind_handler_home',
        '/sms' => 'obfind_handler_sms'
    ), '_obfind'
);
} catch(OBFindException $e) {
       
} catch(Exception $e) {
    header('HTTP/1.1 404 Not Found');
    die($e->getMessage());
}
