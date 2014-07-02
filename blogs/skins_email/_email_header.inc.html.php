<?php
/**
 * This is included into every email and typically includes the site name/logo as well as a personalized greeting.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: _email_header.inc.html.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $emailskins_path;
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
if( file_exists( $emailskins_path.'_email_style.css' ) )
{ // Require the styles for email content
?>
<style>
<?php readfile( $emailskins_path.'_email_style.css' ); ?>
</style>
<?php } ?>
</head>
<body class="email">
<?php
if( $Settings->get( 'notification_logo' ) != '' || $Settings->get( 'notification_long_name' ) != '' )
{ // Display email header if logo or long site name are defined
?>
<div class="email_header">
<?php
if( $Settings->get( 'notification_logo' ) != '' )
{ // Display site logo
	$site_name = $Settings->get( 'notification_long_name' ) != '' ? $Settings->get( 'notification_long_name' ) : $Settings->get( 'notification_short_name' );
	echo '<img src="'.$Settings->get( 'notification_logo' ).'" alt="'.$site_name.'" />';
}
else
{ // No logo, Display only long site name
	echo '<p class="sitename">'.$Settings->get( 'notification_long_name' ).'</p>';
}
?>
</div>
<?php } ?>

<div class="email_payload">
<p><?php echo T_( 'Hello $login$ !' ); ?></p>
