<?php
error_reporting(0);
session_start();
//echo 'hiiii';exit;
function hex2str($hex) {
    for($i=0;$i<strlen($hex);$i+=2)
       $str .= chr(hexdec(substr($hex,$i,2)));

    return base64_encode($str);
}