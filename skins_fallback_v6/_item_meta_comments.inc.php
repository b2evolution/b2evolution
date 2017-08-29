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


global $disp, $Session;

if( ( $disp == 'single' || $disp == 'page' ) &&
    isset( $Item ) && $Item->ID > 0 &&
    $Item->can_see_meta_comments() )
{	// Display the meta comments if current user has a permission:

	$Form = new Form();

	$total_comments_number = generic_ctp_number( $Item->ID, 'metas', 'total' );

	if( $Item->can_meta_comment() || $total_comments_number > 0 )
	{
		$Form->begin_fieldset( T_('Meta comments')
							.( $total_comments_number > 0 ? ' <span class="badge badge-important">'.$total_comments_number.'</span>' : '' ),
							array( 'class' => 'evo_item_meta_comments' ) );

		if( $Item->can_meta_comment() )
		{	// Display a form to add new meta comment if current user has a permission:
			skin_include( '_item_comment_form.inc.php', array_merge( $params, array(
					'form_title_start' => '<div class="panel '.( $Session->get('core.preview_Comment') ? 'panel-danger' : 'panel-default' ).' panel-meta">'
															.'<div class="panel-heading"><h4 class="panel-title">',
					'comment_type' => 'meta',
				) ) );
		}

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
			'comment_type'          => 'meta',
		), $params ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.

		$Form->end_fieldset();
	}
}