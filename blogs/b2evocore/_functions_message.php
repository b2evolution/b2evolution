<?php
/**
 * Email Messaging Functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */

/**
 * Display "Message User" title if it has been requested
 *
 * {@internal msg_title(-) }}
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to
 *								return value instead of displaying it
 */
function msgform_title( $prefix = ' ', $display = 'htmlbody' )
{
	global $disp;

	if( $disp == 'msgform' )
	{
		$info = $prefix.T_('Send an email message');
		if ($display)
			echo format_to_output( $info, $display );
		else
			return $info;
	}
}

?>