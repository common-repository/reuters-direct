<?php
/*
 * Plugin Name: Reuters WordPress Direct
 * Version: 3.0.0
 * Description: A full-featured news aggregator, powered by Reuters Connect: Web Services, which ingests Reuters news and picture content directly into a WordPress platform. ** Please make sure plugin is deactivated/reactivated upon update **
 * Author: Reuters News Agency
 * Requires at least: 3.8
 * Tested up to: 5.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( 'includes/class-reuters-direct.php' );
require_once( 'includes/class-reuters-direct-settings.php' );

function Reuters_Direct () {
	$instance = Reuters_Direct::instance( __FILE__, '3.0.0' );
	if( is_null( $instance->settings ) ) {
		$instance->settings = Reuters_Direct_Settings::instance( $instance );
	}
	return $instance;
}

Reuters_Direct();
?>