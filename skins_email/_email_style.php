<?php
/**
 * This is config of inline css styles for email templates.
 *
 * Example usage: '<p'.emailskin_style( 'p.center' ).'>text</p>'
 * Result string: '<p style="text-align:center;">text</p>'
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $emailskins_styles;

$emailskins_styles = array(
'.p' => '
	margin: 2ex 0;
',
'p.center' => '
	text-align: center;
',
'.note' => '
	color: #999;
',
'.important' => '
	color: #d00;
',
'.a' => '
	color: #006699;
',
'img.b2evo' => '
	padding: 1em;
',
'body.email, div.email_wrap' =>'
	background-color: #f4f4f4;
	margin: 0;
	width: 100%;
',
'body.email' =>'
	padding: 0;
',
'div.email_wrap' =>'
	padding: 2em 0 0 0;
',
'div.email_header' => '
	margin: 0 2em 4px;
	padding: 0;
	text-align: right;
',
'div.email_payload' => '
	background-color: #fff;
	border: 1px solid #ddd;
	margin: 8px 10px;
	padding: 1px 1em;
	border-radius: 5px;
',
'div.email_ugc' => '
	margin: 1em 1em;
	background-color: #f4f4f4;
	border-left: 6px solid #ccc;
	padding: 1px 1em;
',
'div.email_footer' => '
	margin: 12px 0 1em;
	padding: 0 1em;
	color: #999;
	font-size: 78%;
',
'p.sitename' => '
	font-weight: bold;
	font-size: 24px;
	margin: 0;
',
'table.email_table.bordered' => '
	border-top: 1px solid #999;
	border-left: 1px solid #999;
',
'table.email_table.bordered th, table.email_table.bordered thead th, table.email_table.bordered td, table.email_table.bordered tr.row_red td' => '
	border-right: 1px solid #999;
	border-bottom: 1px solid #999;
	padding: 1px 5px;
',
'table.email_table th, table.email_table.bordered th' => '
	text-align: right;
	padding-right: 10px;
',
'table.email_table thead th, table.email_table.bordered thead th' => '
	text-align: right;
	padding-right: 10px;
	background: #CCC;
	text-align: center;
',
'table.email_table tr.row_red td, table.email_table.bordered tr.row_red td' => '
	background: #F00;
',
/* User Genders: */
'.user' => '
	font-weight: bold;
',
'.user.closed' => '
	color: #666;
',
'.user.man' => '
	color: #00F;
',
'.user.woman' => '
	color: #e100af;
',
'.user.nogender, .user.anonymous.nogender' => '
	color: #000;
',
/* Buttons: */
'div.buttons' => '
	margin: 1ex 0;
',
'div.buttons a' => '
	margin: 2px 14px 8px 0;
	padding: 6px 14px;
	border-radius: 4px;
	font-size: 84%;
	font-weight: bold;
	text-decoration: none;
	display: inline-block;
	box-shadow: 1px 1px 4px #c4c4c4;
',
'a.button_green' => '
	color: #454;
	border: 1px solid #4DB120;
	background: #50BE23 linear-gradient(#77EB30, #50BE23);
	background: #50BE23 -webkit-linear-gradient(#77EB30, #50BE23);
	background: #50BE23 -moz-linear-gradient(#77EB30, #50BE23);
',
'a.button_yellow' => '
	color: #554;
	border: 1px solid #e8b463;
	background: #ffcf09 linear-gradient(#fff5bd, #ffcf09);
	background: #ffcf09 -webkit-linear-gradient(#fff5bd, #ffcf09);
	background: #ffcf09 -moz-linear-gradient(#fff5bd, #ffcf09);
',
'a.button_gray' => '
	color: #555;
	border: 1px solid #ccc;
	background: #ebebeb linear-gradient(#f9f9f9, #ebebeb);
	background: #ebebeb -webkit-linear-gradient(#f9f9f9, #ebebeb);
	background: #ebebeb -moz-linear-gradient(#f9f9f9, #ebebeb);
'
);
?>