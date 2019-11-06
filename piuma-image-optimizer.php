<?php
/*
Plugin Name: Piuma Image Optimizer
Version: 1.0
Description: Simple and fast WP image optimizer server you can host on your machine
Author: Lotrèk Web Agency
Author URI: https://www.lotrek.it
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require_once(plugin_dir_path(__FILE__) . 'define.php');
require_once(PIO_DIRECTORY_PATH . 'classes/class-piuma-image-optimizer.php');


$piumaImageOptimizer = new piumaImageOptimizer();
