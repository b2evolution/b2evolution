<?php
/**
 * This file display the form to create sample messages for testing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $threads_count;

$Form = new Form( NULL, 'create_messages', 'post', 'compact' );

$Form->global_icon( T_('Cancel').'!', 'close', regenerate_url( 'action' ) );

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