<?php
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');


function updatePiumaOptions($field, $value){
    if (get_option('piuma_' . $field) === false) {
        add_option('piuma_' . $field, $value, '', 'yes');
    } {
        update_option('piuma_' . $field, $value, 'yes');
    }
}

if(current_user_can('administrator')) {
    $data =  $_POST;
    $remote_url = $data['base_remote_url'];
    $quality = $data['img_resize_quality'];
    $convert = $data['img_convert'];
    $min = 0;
    $max = 100;
    if(wp_http_validate_url($remote_url)){
        updatePiumaOptions('base_remote_url', $remote_url);
    } else {
        echo 'must be a valid url!!!';
        http_response_code(400);
    }

    updatePiumaOptions('img_convert', $convert);

    if( ($quality >= $min) && ($quality <= $max)){
        updatePiumaOptions('img_resize_quality', $quality);
    }else{
        echo 'must be between 0 and 100!!!';
        http_response_code(400);
    }
}
