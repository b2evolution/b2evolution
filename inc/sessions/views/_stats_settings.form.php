<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;

global $collections_Module;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->add_crumb( 'statssettings' );
$Form->hidden( 'ctrl', 'stats' );
$Form->hidden( 'tab', 'settings' );
$Form->hidden( 'action', 'update_settings' );

// --------------------------------------------

$Form->begin_fieldset( T_('Hit & session logging').get_manual_link('hit_logging') );

	$Form->checklist( array(
			array( 'log_public_hits', 1, T_('on every public page'), $Settings->get('log_public_hits') ),
			array( 'log_admin_hits', 1, T_('on every admin page'), $Settings->get('log_admin_hits') ) ),
		'log_hits', T_('Log hits') );

	// TODO: draw a warning sign if set to off
	$Form->radio_input( 'auto_prune_stats_mode', $Settings->get('auto_prune_stats_mode'), array(
			array(
				'value'=>'off',
				'label'=>T_('Off'),
				'note'=>T_('Not recommended! Your database will grow very large!'),
				'onclick'=>'jQuery("#auto_prune_stats_container").hide();' ),
			array(
				'value'=>'page',
				'label'=>T_('On every page'),
				'note'=>T_('This is guaranteed to work but uses extra resources with every page displayed.'),
				'onclick'=>'jQuery("#auto_prune_stats_container").show();' ),
			array(
				'value'=>'cron',
				'label'=>T_('With a scheduled job'),
				'note'=>T_('Recommended if you have your scheduled jobs properly set up.'), 'onclick'=>'jQuery("#auto_prune_stats_container").show();' ) ),
		T_('Auto pruning'),
		array( 'note' => T_('Note: Even if you don\'t log hits, you still need to prune sessions!'),
		'lines' => true ) );

	echo '<div id="auto_prune_stats_container">';
	$Form->text_input( 'auto_prune_stats', $Settings->get('auto_prune_stats'), 5, T_('Prune after'), T_('days. How many days of hits & sessions do you want to keep in the database for stats?') );
	echo '</div>';

	if( $Settings->get('auto_prune_stats_mode') == 'off' )
	{ // hide the "days" input field, if mode set to off:
		echo '<script type="text/javascript">jQuery("#auto_prune_stats_container").hide();</script>';
	}

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>