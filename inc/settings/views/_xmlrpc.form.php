<?php
/**
 * This file implements the UI view for XML-RPC settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $baseurl;


$Form = new Form( NULL, 'remotepublish_checkchanges' );

$Form->begin_form('fform');

$Form->add_crumb( 'globalsettings' );
$Form->hidden( 'ctrl', 'remotepublish' );
$Form->hidden( 'tab', 'xmlrpc' );
$Form->hidden( 'action', 'update' );

// fp> TODO: it would be awesome to be able to enable the different APIs individually
// that way you minimalize security/spam risks by enable just what you need.
$Form->begin_fieldset( T_('Remote publishing').get_manual_link('remote_publishing') );
	$Form->checkbox_input( 'general_xmlrpc', $Settings->get('general_xmlrpc'), T_('Enable XML-RPC'), array( 'note' => T_('Enable the Movable Type, MetaWeblog, WordPress, Blogger and B2 XML-RPC publishing protocols.') ) );
	$Form->text_input( 'xmlrpc_default_title', $Settings->get('xmlrpc_default_title'), 50, T_('Default title'), '<br />'.T_('Default title for items created with a XML-RPC API that doesn\'t send a post title (e. g. the Blogger API).'), array( 'maxlength' => 255 ) );
$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>