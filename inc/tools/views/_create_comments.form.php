<?php
/**
 * This file display the form to create sample comments for testing moderation
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( NULL, 'create_comments', 'post', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample comments for testing moderation') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_comments' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'blog_ID', 1, 6, T_( 'Blog ID' ), '', array( 'maxlength' => 5, 'required' => true ) );
	$Form->text_input( 'num_comments', 30, 6, T_( 'How many comments per post' ), '', array( 'maxlength' => 6, 'required' => true ) );
	$Form->text_input( 'num_posts', 3, 6, T_( 'How many posts' ), '', array( 'maxlength' => 5, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );

?>