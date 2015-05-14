<?php
/**
 * This is the HTML header include template.
 *
 * 
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// NEW default params for v6:
$params = array_merge( array(
	'html_tag' => '<!DOCTYPE html>'."\r\n"
	             .'<html lang="'.locale_lang( false ).'">',
	'viewport_tag' => '#responsive#',
	'auto_pilot'    => 'seo_title',
), $params );

// Fallback to API v5:
require dirname(__FILE__).'/../skins_fallback_v5/_html_header.inc.php';