<?php
/**
 * This is the template that displays the workflow properties of the viewed post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $disp;

if( ( $disp == 'single' || $disp == 'page' ) &&
    isset( $Item ) && $Item->ID > 0 &&
    ! $Item->can_meta_comment() && // If user can write internal comment then we display the workflow form in the internal comment form instead of here
    $Item->can_edit_workflow() )
{
	echo '<p class="evo_param_error">Please use the Worfklow properties widget to set workflow properties.</p>';
}

?>
