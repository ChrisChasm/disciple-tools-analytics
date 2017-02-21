<?php
/**
 * Plugin Name: Disciple Tools Analytics
 *
 * client ID
 * 2811303664-k3gjc9gf5am1bnhal7rr9ig2jojgu7ik.apps.googleusercontent.com
 *
 * client secret
 * B0hFd_eZZFIeuqEMBILPgIPP
 *
 *
 */

add_action('admin_menu', 'dt_analytics_menu');

function dt_analytics_menu () {
    add_options_page('DT Analytics', 'DT Analytics', 'manage_options', 'dt-analytics', 'dt_analytics_page' );
}

function dt_analytics_page () {
    $html = '<h1>Disciple Tools Analytics</h1>';

    /* WORKING EXAMPLE OF API */
    // include your composer dependencies
    require_once ( 'vendor/autoload.php');

    $client = new Google_Client();
    $client->setApplicationName("Client_Library_Examples");
    $client->setDeveloperKey("AIzaSyBsO2bPxOP7hlL3Owo0VvqJmE9WN1mBUu8");

    $service = new Google_Service_Books($client);
    $optParams = array('filter' => 'free-ebooks');
    $results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);

    foreach ($results as $item) {
        $html .= $item['volumeInfo']['title'] . '<br />';
    }
    /* END WORKING EXAMPLE */


    echo $html;
}


