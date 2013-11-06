<?php
/**
 * This is included into every email and typically includes the site name/logo as well as a personalized greeting.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings;
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
p.center {
	text-align: center;
}
.note {
	color: #999;
}
.important {
	color: #d00;
}
a {
	color: #006699;
}
img.b2evo {
	padding: 1em;
}
img {
	border: none;
}
img.avatar_before_login {
	margin-right: 2px;
	vertical-align: bottom;
}
body {
	background-color: #f4f4f4;
	padding: 2em 0 0 0;
	margin:0;
	width:100%;
}
div.email_header {
	margin: 0 2em 4px;
	padding: 0;
	text-align: right;
}
div.email_payload {
	background-color: #fff;
	border: 1px solid #ddd;
	margin: 8px 2em;
	padding: 1px 1em;
	border-radius: 5px;
}
div.email_ugc {
	margin: 1em 1em;
	background-color: #f4f4f4;
	border-left: 6px solid #ccc;
	padding: 1px 1em;
}
div.email_footer {
	margin: 12px 2em 1em;
	padding: 0 1em;
	color: #999;
	font-size: 78%;
}
p.sitename{
	font-weight: bold;
	font-size: 24px;
	margin: 0;
}
table.email_table th{
	text-align: right;
	padding-right: 10px;
}
/* User Genders: */
span.user, span.user.anonymous{
	font-weight: bold;
}
span.user.closed{
	color: #666;
}
span.user.man{
	color: #00F;
}
span.user.woman{
	color: #e100af;
}
span.user.nogender, span.user.anonymous.nogender{
	color: #000;
}
span.user img{
	position: relative;
	top: 1px;
	vertical-align: top;
}
/* Buttons: */
div.buttons {
	margin: 1ex 0;
}
div.buttons a {
	margin: 2px 14px 8px 0;
	padding: 6px 14px;
	border-radius: 4px;
	font-size: 84%;
	font-weight: bold;
	text-decoration: none;
	display: inline-block;
	box-shadow: 1px 1px 4px #c4c4c4;
}
a.button_green {
	color: #454;
	border: 1px solid #4DB120;
	background: linear-gradient(#77EB30, #50BE23);
	background: -webkit-linear-gradient(#77EB30, #50BE23);
	background: -moz-linear-gradient(#77EB30, #50BE23);
}
a.button_yellow {
	color: #554;
	border: 1px solid #e8b463;
	background: linear-gradient(#fff5bd, #ffcf09);
	background: -webkit-linear-gradient(#fff5bd, #ffcf09);
	background: -moz-linear-gradient(#fff5bd, #ffcf09);
}
a.button_gray {
	color: #555;
	border: 1px solid #ccc;
	background: linear-gradient(#f9f9f9, #ebebeb);
	background: -webkit-linear-gradient(#f9f9f9, #ebebeb);
	background: -moz-linear-gradient(#f9f9f9, #ebebeb);
}
</style>
</head>
<body>
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
