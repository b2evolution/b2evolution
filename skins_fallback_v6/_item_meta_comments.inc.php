<?php
/**
 * This is the template that displays the meta comments of the viewed post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $disp;

if( ( $disp == 'single' || $disp == 'page' ) &&
    ! empty( $Item ) &&
    is_logged_in() &&
    $current_User->check_perm( 'meta_comment', 'view', false, $Item ) )
{ // Display the meta comments if current user can edit this post:

	$Form = new Form();

	$Form->begin_form();

	$total_comments_number = generic_ctp_number( $Item->ID, 'metas', 'total' );

	$Form->begin_fieldset( T_('Meta comments')
						.( $total_comments_number > 0 ? ' <span class="badge badge-important">'.$total_comments_number.'</span>' : '' ) );

	if( $current_User->check_perm( 'meta_comment', 'add', false, $Item ) )
	{ // Display a link to add new meta comment if current user has a permission
		global $admin_url;
		echo '<p>'.action_icon( T_('Add a meta comment'), 'new', $admin_url.'?ctrl=items&amp;p='.$Item->ID.'&amp;comment_type=meta&amp;blog='.$Blog->ID.'#comments', T_('Add a meta comment').' &raquo;', 3, 4 ).'</p>';
	}

	// Unset a comment counter to set new for meta comments:
	global $comment_template_counter;
	$comment_template_counter = NULL;

	// ------------------ FEEDBACK INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array_merge( array(
		'disp_comments'         => false,
		'disp_comment_form'     => false,
		'disp_trackbacks'       => false,
		'disp_pingbacks'        => false,
		'disp_meta_comments'    => true,
		'disp_section_title'    => false,
		'disp_rating_summary'   => false,
		'disp_notification'     => false,
		'comments_per_page'     => 20,
	), $params ) );
	// Note: You can customize the default item feedback by copying the generic
	// /skins/_item_feedback.inc.php file into the current skin folder.

	$Form->end_fieldset();

	$Form->end_form();
}