<?php
// Plugin Defines
define( 'PIO_FILE', __FILE__ );
define( 'PIO_DIRECTORY', dirname(__FILE__) );
define( 'PIO_TEXT_DOMAIN', dirname(__FILE__) );
define( 'PIO_DIRECTORY_BASENAME', plugin_basename( PIO_FILE ) );
define( 'PIO_DIRECTORY_PATH', plugin_dir_path( PIO_FILE ) );
define( 'PIO_DIRECTORY_URL', plugins_url( null, PIO_FILE ) );

// define( 'PIO_MEDIA_REMOTE_BASE_DIR', ( '') );
define( 'PIO_MEDIA_DIR', ( 'piuma/0_0_80/' . get_home_url() . '/wp-content/uploads/') );