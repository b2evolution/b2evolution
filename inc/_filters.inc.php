<?php
/**
 * This is the handler/dispatcher for asynchronous calls (both AJax calls and HTTP GET fallbacks)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$action = param( 'action', 'string', '' );

switch( $action )
{
	case 'collapse_filter':
	case 'expand_filter':
		param( 'target', 'string', '' );
		if( !empty( $target ) )
		{	// We want to record a 'collapse'/'expand' value:
			$target_status = $action == 'collapse_filter' ? 'collapsed' : 'expanded';
			if( preg_match( '/_(filters|colselect)$/', $target ) )
			{	// accept all _filters and _colselect open/close requests!
				// We have a valid value:
				$Session->set( $target, $target_status );
			}
			else
			{	// Warning: you may not see this on AJAX calls
				$Messages->add( 'Cannot ['.$target_status.'] unknown param ['.$target.']' );
			}
		}
		break;
}

?>