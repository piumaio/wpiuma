<?php
require($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

$data =  $_POST;
foreach ($data as $key => $value){
    delete_option('piuma_'.$key);
}
var_dump($data);
echo 'fatto';