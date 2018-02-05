<?php
/**
 * This is included into every email and typically includes a personalized greeting.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'include_greeting' => true,
		'recipient_User' => NULL,
	), $params );

if( $params['include_greeting'] )
{ // Display the greeting message
	echo sprintf( T_( 'Hello %s!' ), empty( $params['recipient_User'] ) ? '$name$' : '$username$' )."\n\n";
}
?>