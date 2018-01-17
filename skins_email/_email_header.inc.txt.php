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
	if( ! empty( $params['newsletter'] ) )
	{ // This is a newsletter
		if( ! empty( $recipient_User ) )
		{
			echo T_('Hello $firstname_and_login$!')."\n\n";
		}
		elseif( ! empty( $params['anonymous_recipient_name'] ) )
		{
			echo T_('Hello $name$!')."\n\n";
		}
		else
		{
			echo T_('Hello')."!\n\n";
		}

		echo T_('Here are some news')."...\n\n";
	}
	else
	{
		if( ! empty( $recipient_User ) )
		{
			echo T_('Hello $username$!')."\n\n";
		}
		elseif( ! empty( $params['anonymous_recipient_name'] ) )
		{
			echo T_('Hello $name$!')."\n\n";
		}
		else
		{
			echo T_('Hello')."!\n\n";
		}
	}
}
?>