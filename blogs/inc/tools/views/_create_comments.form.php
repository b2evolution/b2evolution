<?php
/**
 * This file display the form to create sample comments for testing moderation
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( NULL, 'create_comments', 'post', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample comments for testing moderation') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_comments' );

	$Form->text_input( 'blog_ID', 1, 6, T_( 'Blog ID' ), '', array( 'maxlength' => 5, 'required' => true ) );
	$Form->text_input( 'num_comments', 30, 6, T_( 'How many comments per post' ), '', array( 'maxlength' => 5, 'required' => true ) );
	$Form->text_input( 'num_posts', 3, 6, T_( 'How many posts' ), '', array( 'maxlength' => 5, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );


/*
 * $Log$
 * Revision 1.4  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.3  2010/12/08 13:55:37  efy-asimo
 * Create Sample comments - fix
 *
 * Revision 1.2  2010/11/12 15:13:31  efy-asimo
 * MFB:
 * Tool 1: "Find all broken posts that have no matching category"
 * Tool 2: "Find all broken slugs that have no matching target post"
 * Tool 3: "Create sample comments for testing moderation"
 *
 */
?>