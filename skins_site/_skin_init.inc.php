<?php
/**
 * This is the file to include additional styles and javascripts that will be appeared in the <head>.
 * Calls from function siteskin_init()
 *
 * @package site_skins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: the following will not try to have a different domain for each blog
require_css( 'sitewide_style.css', 'rsc_url' );
?>