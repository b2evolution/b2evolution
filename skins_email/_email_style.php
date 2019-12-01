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
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
	font-size: 14px;
	line-height: 20px;
',
'body.email' =>'
	padding: 0;
',
'div.email_wrap' =>'
	padding: 2em 0 0 0;
',
'div.email_header' => '
	margin: 0 2em 12px;
	padding: 0;
	text-align: center;
',
'div.email_payload' => '
	background-color: #fff;
	border-top: 1px solid #ddd;
	border-bottom: 1px solid #ddd;
	margin: 8px 0px;
	padding: 30px 1em;
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
'.user.normal_weight' => '
	font-weight: normal;
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
	margin: 3px 0;
	padding: 6px 14px;
	border-radius: 4px;
	text-decoration: none;
	display: inline-block;
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
',

'div.btn a' => '
	margin: 3px;
	padding: 6px 14px;
	border-radius: 4px;
	text-decoration: none;
	display: inline-block;
	font-size: 1em;
',
'a.btn' => '
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid transparent;
	border-radius: 4px;
',
'a.btn-primary' => '
	color: #fff;
	background-color: #337ab7;
	border-color: #2e6da4;
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid #2e6da4;
	border-radius: 4px;
',
'a.btn-warning' => '
	color: #fff;
	background-color: #f0ad4e;
	border-color: #eea236;
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid #eea236;
	border-radius: 4px;
',
'a.btn-default' => '
	color: #333;
	background-color: #fff;
	border-color: #ccc;
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid #ccc;
	border-radius: 4px;
',
'a.btn-success' => '
	color: #fff;
	background-color: #449d44;
	border-color: #4cae4c;
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid #4cae4c;
	border-radius: 4px;
',
'a.btn-danger' => '
	color: #fff;
	background-color: #d9534f;
	border-color: #d43f3a;
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid #d43f3a;
	border-radius: 4px;
',
'a.btn-info' => '
	color: #fff;
	background-color: #5bc0de;
	border-color: #46b8da;
	padding: 6px 12px;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	user-select: none;
	background-image: none;
	border: 1px solid #46b8da;
	border-radius: 4px;
',
'a.btn-sm' => '
	font-size: 0.85em;
	padding: 5px 10px;
	border-radius: 4px;
	line-height: 1.5;
',
/* Labels */
'.label' => '
	display: inline;
	padding: .2em .6em .3em;
	font-size: 75%;
	font-weight: 700;
	line-height: 1;
	color: #fff;
	text-align: center;
	white-space: nowrap;
	vertical-align: baseline;
	border-radius: .25em;
',
'.label-default' => '
	background-color: #777;
',
'.label-primary' => '
	background-color: #337ab7;
',
'.label-success' => '
	background-color: #5cb85c;
',
'.label-info' => '
	background-color: #5bc0de;
',
'.label-warning' => '
	background-color: #f0ad4e;
',
'.label-danger' => '
	background-color: #d9534f;
',
'.label-status-meta' => '
	background-color: #5bc0de;
',
'.label-status-draft' => '
	background-color: #ff9;
	color: #000;
	border: 1px solid #ccc;
',
'.label-status-private' => '
	background-color: #DC143C;
',
'.label-status-review' => '
	background-color: #CC0099;
',
'.label-status-protected' => '
	background-color: #FF9C2A;
',
'.label-status-community' => '
	background-color: #2E8BB9;
',
'.label-status-published' => '
	background-color: #99EE44;
	color: #000;
',
/* Polls */
'li.evo_poll_option' => '
	margin-top: 1ex;
	margin-bottom: 1ex;
',
/* Images */
'.image_block' => '
	text-align: center;
',
'.image_block img' => '
	margin: 1ex;
',
'.image_legend' => '
	font-size: 84%;
	color: #aaa;
	margin: 1ex;
',
'.evo_image_block' => '
	text-align: center;
',
'.evo_image_legend' => '
	font-size: 84%;
	color: #aaa;
	padding: 9px 6px 6px 6px;
	text-align: center;
',
'.img-responsive' => '
	display: inline-block;
',
'.evo_thumbnail' => '
	max-width: 100%;
',
'.evo_thumbnail__left' => '
  float: left;
	margin: 0 4px 4px 0;
',
'.evo_thumbnail__right' => '
	float: right;
	margin: 0 0 4px 4px;
',
'.border' => '
	border: 1px solid #666;
',
'.noborder' => '
	border: none;
',
'.rounded' => '
	border-radius: 4px;
',
'.squared' => '
	border-radius: 0;
',
);
?>