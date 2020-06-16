<?php
/*
Plugin Name: Piuma Image Optimizer
Version: 1.1
Description: Simple and fast WP image optimizer server you can host on your machine
Author: Lotrèk Web Agency
Author URI: https://www.lotrek.it
*/

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'salcode_add_plugin_page_settings_link');
function salcode_add_plugin_page_settings_link($links)
{
    $links[] = '<a href="' .
        admin_url('admin.php?page=piuma-plugin') .
        '">' . __('Settings') . '</a>';
    return $links;
}

add_action('admin_menu', 'piuma_image_optimizer_menu');

function piuma_image_optimizer_menu()
{
    add_menu_page('Piuma image optimizer', 'Piuma image optimizer', 'manage_options', 'piuma-plugin', 'piuma_image_optimizer_init', 'dashicons-format-image');
}



// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require_once(plugin_dir_path(__FILE__) . 'define.php');
require_once(PIO_DIRECTORY_PATH . 'classes/class-piuma-image-optimizer.php');


//$piumaImageOptimizer = new piumaImageOptimizer();
function wp_piuma_load_class() {
    new piumaImageOptimizer();
}
add_action( 'wp', 'wp_piuma_load_class' );


add_action('admin_enqueue_scripts', 'load_admin_style');
function load_admin_style()
{
    wp_enqueue_style('piuma_admin_css', plugin_dir_url(__FILE__) . '/styles.css');
}


function piuma_image_optimizer_init()
{
?>


    <div class="wrap">
        <div id="update-success" class="notice notice-success is-dismissible">
            <p>settings updated</p>
        </div>

        <div id="reset-success" class="notice notice-success is-dismissible">
            <p>settings are resetted</p>
        </div>

        <div id="generic-error" class="notice notice-error is-dismissible">
            <p>Sorry but we had an error. please contact the administrator.</p>
        </div>

        <div id="url-error" class="notice notice-error is-dismissible">
            <p>You must provide a valid url</p>
        </div>
        <div class="title-area">
            <img itemprop="image" class="TableObject-item avatar flex-shrink-0" src="https://avatars3.githubusercontent.com/u/56169391?s=200&amp;v=4" width="100" height="100" alt="@piumaio">
            <h1>Piuma Image Optimizer</h1>
        </div>
        <div class="panel">
            <div class="initial_form box">
                <hr>
                <div class="input-text-wrap">
                    <label for="base_remote_url">Your Piuma url</label>
                    <input type="url" pattern="https?://.*" name="base_remote_url" id="base_remote_url" value="<?= get_option('piuma_base_remote_url') ?>" onblur="addTrailing(this)">
                    <hr>
                </div>
                <label for="img_resize_qualiy">Image Quality</label>
                <input type="number" id="img_resize_quality" name="img_resize_quality" min="0" max="100" value="<?= get_option('piuma_img_resize_quality') ?>">

                <button class="button button-primary" onclick="update()">update</button>
                <br>
                <br>
                <button class="button button-secondary" onclick="reset()">reset to defaults</button>
            </div>


        </div>
    </div>

<?php
}

// define the init callback 
function action_init($array)
{
    wp_enqueue_script('piuma_image_optimizer_script', plugin_dir_url(__FILE__) . '/scripts.js');
    wp_localize_script('piuma_image_optimizer_script', 'PIOsettings', array(
        'user' => wp_get_current_user(),
        'nonce' =>  wp_create_nonce('wp_rest'),
        'pluginsUrl' => plugin_dir_url(__FILE__),
    ));
};

// add the action 
add_action('admin_init', 'action_init', 10, 1);
