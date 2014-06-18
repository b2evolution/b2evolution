<?php
/**
 * This file display the form to create sample messages for testing
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
 * @version $Id: _create_messages.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $threads_count;

$Form = new Form( NULL, 'create_messages', 'post', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample messages for testing moderation') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action', 'create_sample_messages' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'num_loops', 3, 50, T_( 'How many loops' ), '', array( 'maxlength' => 11, 'required' => true, 'note' => sprintf( T_('1 loop will create %d conversations'), $threads_count ) ) );
	$Form->text_input( 'num_messages', 5, 50, T_( 'How many messages in each conversation' ), '', array( 'maxlength' => 11, 'required' => true ) );
	$Form->text_input( 'num_words', 3, 50, T_( 'How many words in each message' ), '', array( 'maxlength' => 11, 'required' => true ) );
	$Form->text_input( 'max_users', 3, 50, T_( 'Max # of participants in a conversation' ), '', array( 'maxlength' => 11, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );

?>