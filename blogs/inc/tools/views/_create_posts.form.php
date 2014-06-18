<?php
/**
 * This file display the form to create sample posts for testing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id: _create_posts.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( NULL, 'create_posts', 'post', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample post for testing moderation') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_posts' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'blog_ID', 1, 50, T_( 'Blog ID' ), '', array( 'maxlength' => 11, 'required' => true ) );
	$Form->text_input( 'num_posts', 3, 50, T_( 'How many posts' ), '', array( 'maxlength' => 11, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );

?>