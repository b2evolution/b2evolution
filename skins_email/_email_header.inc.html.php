<?php
/**
 * This is included into every email and typically includes the site name/logo as well as a personalized greeting.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $emailskins_path;

// Default params:
$params = array_merge( array(
		'include_greeting' => true
	), $params );
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<?php
if( file_exists( $emailskins_path.'_email_style.css' ) )
{ // Require the styles for email content
?>
<style>
<?php readfile( $emailskins_path.'_email_style.css' ); ?>
</style>
<?php } ?>
</head>
<body<?php echo emailskin_style( 'body.email' ); ?>>
<div class="email_wrap"<?php echo emailskin_style( 'div.email_wrap' ); ?>>
<?php
if( $Settings->get( 'notification_logo' ) != '' || $Settings->get( 'notification_long_name' ) != '' )
{ // Display email header if logo or long site name are defined
?>
<div<?php echo emailskin_style( 'div.email_header' ); ?>>
<?php
if( $Settings->get( 'notification_logo' ) != '' )
{ // Display site logo
	$site_name = $Settings->get( 'notification_long_name' ) != '' ? $Settings->get( 'notification_long_name' ) : $Settings->get( 'notification_short_name' );
	echo '<img src="'.$Settings->get( 'notification_logo' ).'" alt="'.$site_name.'" />';
}
else
{ // No logo, Display only long site name
	echo '<p'.emailskin_style( '.p+p.sitename' ).'>'.$Settings->get( 'notification_long_name' ).'</p>';
}
?>
</div>
<?php } ?>

<div class="email_payload"<?php echo emailskin_style( 'div.email_payload' ); ?>>
<?php
if( $params['include_greeting'] )
{ // Display the greeting message
?>
<p<?php echo emailskin_style( '.p' ); ?>><?php echo T_( 'Hello $login$!' ); ?></p>
<?php } ?>
