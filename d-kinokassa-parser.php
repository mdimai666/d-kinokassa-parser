<?php
/*
Plugin Name: Kinokassa parser
Plugin URI: http://amai-lab.com
Description: Предоставляет список афиш
Version: 0.6
Author: mdimai666
Author URI: http://amai-lab.com
*/

require_once 'DSocialFeed.php';
require_once 'social_feed_admin.php';
require_once 'modules/kinokassa/kinokassa.php';
require_once 'shortcodes.php';


$scripts_version = '0.5.0'; //for cache update



// for front end
// add_action('wp_enqueue_scripts', array(&$this, 'dsocial_feed_enqueue_scripts'));
// for back end
// add_action('admin_enqueue_scripts', array(&$this, 'dsocial_feed_enqueue_scripts'));
add_action('admin_enqueue_scripts', 'dsocial_feed_enqueue_scripts');

function dsocial_feed_enqueue_scripts() {
    if ( 1 ) {
        wp_register_script('dparser_adminscripts', plugins_url('/front/adminscripts.js',__FILE__),'', $scripts_version,true);
        wp_enqueue_script('dparser_adminscripts');

    }
}

function amai_varibles_admin_js() {
    
    $amai = json_encode([
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myajax-nonce'),
    ]);

    echo "
        <script>
            var amai = $amai;
        </script>";
}
add_action('admin_footer', 'amai_varibles_admin_js');

//---------------------

// http://wordpress.localhost:81/wp-json/dkk/v1/status
add_action( 'rest_api_init', function () {
    // register_rest_route( 'dsearch/v1', '/posts/(?P<id>\d+)', array(
    //   'methods' => 'GET',
    //   'callback' => 'd_ajax_status',
    // ));
    register_rest_route( 'dkk/v1', '/status/', array(
      'methods' => ['GET'],
      'callback' => 'd_ajax_status',
    ));
});

if ( ! function_exists( 'd_ajax_status' ) ) :
function d_ajax_status(){

    $feed = new DSocialFeed();
    $option = $feed->get_option(true);

    
    $response = [
        'Response' => 'OK',
        'Data' => $option,
    ];


    wp_send_json($response);
    // wp_send_json($response);

}
endif;