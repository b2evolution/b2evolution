<?php
/**
 * Error handling
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

if( !isset( $errors ) )
{
	$errors = array();
}

function errors_add( $string )
{
	global $errors;
	// echo 'error:'.$string;
	$errors[] = $string;
}

function errors()
{
	global $errors;
	return count( $errors );
}

function errors_display( $head, $foot, $display = true )
{
	global $errors;
	if( ! count( $errors ) )
	{
		// echo 'NO ERROR';
		return false;
	}

	$disp = '<div class="error"><p class="error">'.$head.'</p><ul class="error">';
	foreach( $errors as $error )
	{
		$disp .= '<li class="error">'.$error.'</li>';
	}		
	$disp .= '</ul><p class="error">'.$foot.'</p></div>';
	
	if( $display )
	{
		echo $disp;
		return true;
	}

	return $disp;
}

function errors_string( $head, $foot )
{
	global $errors;
	if( ! count( $errors ) )
	{
		return false;
	}
	return strip_tags($head.' '.implode(', ',$errors).' '.$foot);
}

?>