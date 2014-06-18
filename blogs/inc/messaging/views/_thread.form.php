<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _thread.form.php 6479 2014-04-16 07:18:54Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Message
 */
global $edited_Message;
global $edited_Thread;

global $DB, $action;

global $Blog;

$creating = is_create_action( $action );

if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class' => 'fform',
	'form_title' => T_('New thread'),
	'form_action' => NULL,
	'form_name' => 'thread_checkchanges',
	'form_layout' => 'compact',
	'redirect_to' => regenerate_url( 'action', '', '', '&' ),
	'cols' => 80,
	'thrdtype' => param( 'thrdtype', 'string', 'discussion' ),  // alternative: individual
	'skin_form_params' => array(),
	'allow_select_recipients' => true,
	), $params );

$Form = new Form( $params['form_action'], $params['form_name'], 'post', $params['form_layout'] );

$Form->switch_template_parts( $params['skin_form_params'] );

if( is_admin_page() )
{
	$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );
}

$Form->begin_form( $params['form_class'], $params['form_title'], array( 'onsubmit' => 'return check_form_thread()') );

	$Form->add_crumb( 'messaging_threads' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)
	$Form->hidden( 'redirect_to', $params[ 'redirect_to' ] );
	if( !empty( $Blog ) )
	{ // Set blog as hidden param, because we may need the blog locale after submit
		// This issues should be solved differently
		$Form->hidden( 'blog', $Blog->ID );
	}

if( $params['allow_select_recipients'] )
{	// User can select recipients
	$Form->text_input( 'thrd_recipients', $edited_Thread->recipients, $params['cols'], T_('Recipients'),
		'<noscript>'.T_('Enter usernames. Separate with comma (,)').'</noscript>', array( 'maxlength'=> 255, 'required'=>true, 'class'=>'wide_input' ) );

	echo '<div id="multiple_recipients">';
	$Form->radio( 'thrdtype', $params['thrdtype'], array(
									array( 'discussion', T_( 'Start a group discussion' ) ),
									array( 'individual', T_( 'Send individual messages' ) )
								), T_('Multiple recipients'), true );
	echo '</div>';
}
else
{	// No available to select recipients, Used in /contact.php
	$Form->info( T_('Recipients'), $edited_Thread->recipients );
	foreach( $recipients_selected as $recipient )
	{
		$Form->hidden( 'thrd_recipients_array[id][]', $recipient['id'] );
		$Form->hidden( 'thrd_recipients_array[title][]', $recipient['title'] );
	}
}

$Form->text_input( 'thrd_title', $edited_Thread->title, $params['cols'], T_('Subject'), '', array( 'maxlength'=> 255, 'required'=>true, 'class'=>'wide_input' ) );

$Form->textarea_input( 'msg_text', isset( $edited_Thread->text ) ? $edited_Thread->text : $edited_Message->text, 10, T_('Message'), array(
		'cols' => $params['cols'],
		'required' => true
	) );

global $thrd_recipients_array, $recipients_selected;
if( !empty( $thrd_recipients_array ) )
{	// Initialize the preselected users (from post request or when user send a message to own contacts)
	foreach( $thrd_recipients_array['id'] as $rnum => $recipient_ID )
	{
		$recipients_selected[] = array(
			'id'    => $recipient_ID,
			'title' => $thrd_recipients_array['title'][$rnum]
		);
	}
}

// display submit button, but only if enabled
$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Send message'), 'SaveButton' ) ) );

if( $params['allow_select_recipients'] )
{	// User can select recipients
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	check_multiple_recipients();
} );

jQuery( '#thrd_recipients' ).tokenInput(
	'<?php echo get_samedomain_htsrv_url(); ?>anon_async.php?action=get_recipients',
	{
		theme: 'facebook',
		queryParam: 'term',
		propertyToSearch: 'title',
		preventDuplicates: true,
		prePopulate: <?php echo evo_json_encode( $recipients_selected ) ?>,
		hintText: '<?php echo TS_('Type in a username') ?>',
		noResultsText: '<?php echo TS_('No results') ?>',
		searchingText: '<?php echo TS_('Searching...') ?>',
		tokenFormatter: function( item )
		{
			return '<li>' +
					item.title +
					'<input type="hidden" name="thrd_recipients_array[id][]" value="' + item.id + '" />' +
					'<input type="hidden" name="thrd_recipients_array[title][]" value="' + item.title + '" />' +
				'</li>';
		},
		resultsFormatter: function( item )
		{
			var title = item.title;
			if( item.fullname != null && item.fullname !== undefined )
			{
				title += '<br />' + item.fullname;
			}
			return '<li>' +
					item.picture +
					'<div>' +
						title +
					'</div><span></span>' +
				'</li>';
		},
		onAdd: function()
		{
			check_multiple_recipients();
		},
		onDelete: function()
		{
			check_multiple_recipients();
		},
	}
);

/**
 * Show the multiple recipients radio selection if the number of recipients more than one
 */
function check_multiple_recipients()
{
	if( jQuery( 'input[name="thrd_recipients_array[title][]"]' ).length > 1 )
	{
		jQuery( '#multiple_recipients' ).show();
	}
	else
	{
		jQuery( '#multiple_recipients' ).hide();
	}
}

/**
 * Check form fields before send a thread data
 *
 * @return boolean TRUE - success filling of the fields, FALSE - some erros, stop a submitting of the form
 */
function check_form_thread()
{
	if( jQuery( 'input#token-input-thrd_recipients' ).val() != '' )
	{	// Don't submit a form with incomplete username
		alert( '<?php echo TS_('Please complete the entering of an username.') ?>' );
		jQuery( 'input#token-input-thrd_recipients' ).focus();
		return false;
	}

	return true;
}
</script>
<?php
}
?>