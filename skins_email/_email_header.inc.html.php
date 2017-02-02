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
$notification_logo_file_ID = intval( $Settings->get( 'notification_logo_file_ID' ) );
if( $notification_logo_file_ID > 0 || $Settings->get( 'notification_long_name' ) != '' )
{	// Display email header if logo or long site name are defined:
?>
<div<?php echo emailskin_style( 'div.email_header' ); ?>>
<?php

if( $notification_logo_file_ID > 0 &&
    ( $FileCache = & get_FileCache() ) &&
    ( $File = $FileCache->get_by_ID( $notification_logo_file_ID, false ) ) &&
    $File->is_image() )
{	// Display site logo image if the file exists in DB and it is an image:
	$site_name = $Settings->get( 'notification_long_name' ) != '' ? $Settings->get( 'notification_long_name' ) : $Settings->get( 'notification_short_name' );
	echo '<img src="'.$File->get_url().'" alt="'.$site_name.'" />';
}
else
{	// Display only long site name if the logo file cannot be used by some reason above:
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
<p<?php echo emailskin_style( '.p' ); ?>><?php echo T_( 'Hello $username$!' ); ?></p>
<?php } ?>
