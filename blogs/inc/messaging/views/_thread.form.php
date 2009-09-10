<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Message
 */
global $edited_Message;
global $edited_Thread;

global $action;
$creating = is_create_action( $action );

$Form = & new Form( NULL, 'thread_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('New thread') );

$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

$Form->text_input( 'thrd_recipients', $edited_Thread->recipients, 100, T_('Recipients'), T_('Enter comma separated logins'), array( 'maxlength'=> 255, 'required'=>true ) );

$Form->text_input( 'thrd_title', $edited_Thread->title, 100, T_('Title'), '', array( 'maxlength'=> 255, 'required'=>true ) );

$Form->textarea_input( 'msg_text', $edited_Message->text, 10, T_('Message'), array( 'cols'=>80, 'required'=>true ) );

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
?>