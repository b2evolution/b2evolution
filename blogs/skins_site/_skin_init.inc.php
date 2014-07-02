<?php
/**
 * This is the file to include additional styles and javascripts that will be appeared in the <head>.
 * Calls from function siteskin_init()
 *
 * @package site_skins
 *
 * @version $Id: _skin_init.inc.php 6245 2014-03-18 08:00:23Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: the following will not try to have a different domain for each blog
require_css( 'sitewide_style.css', 'rsc_url' );
?>