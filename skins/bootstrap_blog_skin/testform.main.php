<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Collection, $Blog;

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

/**
 * @var Message
 */
global $DB, $action, $Plugins, $Settings;

if( !isset( $display_params ) )
{
	$display_params = array();
}

if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class_thread' => 'fform',
	'form_title' => T_('New thread').( is_admin_page() ? get_manual_link( 'messages-new-thread' ) : '' ),
	'form_action' => NULL,
	'form_name' => 'thread_checkchanges',
	'form_layout' => 'compact',
	'redirect_to' => regenerate_url( 'action', '', '', '&' ),
	'cols' => 80,
	'thrdtype' => param( 'thrdtype', 'string', 'discussion' ),  // alternative: individual
	'skin_form_params' => array(),
	'allow_select_recipients' => true,
	'messages_list_start' => is_admin_page() ? '<div class="evo_private_messages_list">' : '',
	'messages_list_end' => is_admin_page() ? '</div>' : '',
), $params );

$Form = new Form( $params['form_action'], $params['form_name'], 'post', $params['form_layout'] );

$Form->switch_template_parts( $params['skin_form_params'] );

$Form->begin_form( $params['form_class_thread'], $params['form_title'], array( 'onsubmit' => 'return check_form_thread()') );
$Form->text_input( 'thrd_title', "", $params['cols'], T_('Subject'), '', array( 'maxlength'=> 255, 'required'=>true, 'class'=>'wide_input large' ) );

// Just cleaning up a bit
$template_rewrites = array('inputstart_checkbox' => '', 'inputend_checkbox' => '');
foreach($template_rewrites as $key => $value)
{
	$$key = $Form->$key;
	$Form->$key = $value;
}
$Form->checkbox_input( "check", false, "Checkbox", array('label_after_input' => true) );

foreach($template_rewrites as $key => $value)
{
	$Form->$key = $$key;
}

// display submit button, but only if enabled
$Form->end_form( array(
	array( 'submit', 'actionArray[create]', T_('Send message'), 'SaveButton' )
) );


// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------