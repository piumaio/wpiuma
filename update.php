<?php
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$data =  $_POST;

foreach ($data as $key => $value) {
    if (get_option('piuma_' . $key) === false) {
        add_option('piuma_' . $key, $value, '', 'yes');
    } {
        update_option('piuma_' . $key, $value, 'yes');
    }
}
var_dump($data);
echo 'aggiornato';
