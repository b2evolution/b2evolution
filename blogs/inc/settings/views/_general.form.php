<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id: _general.form.php 6135 2014-03-08 07:54:05Z manuel $
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

$Form->add_crumb( 'globalsettings' );
$Form->hidden( 'ctrl', 'gensettings' );
$Form->hidden( 'action', 'update' );

// --------------------------------------------

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->begin_fieldset( T_('Locking down b2evolution for maintenance, upgrade or server switching...') );
		$Form->checkbox_input( 'system_lock', $Settings->get('system_lock'), T_('Lock system'), array(
				'note' => T_('check this to prevent login (except for admins) and sending comments/messages. This prevents the DB from receiving updates (other than logging)').'<br />'.
				          T_('Note: for a more complete lock down, rename the file /conf/_maintenance.html to /conf/maintenance.html (complete lock) or /conf/imaintenance.html (gives access to /install)') ) );
	$Form->end_fieldset();
}

// --------------------------------------------

$Form->begin_fieldset( T_('Caching') );

	$Form->checkbox_input( 'general_cache_enabled', $Settings->get('general_cache_enabled'), T_('Enable general cache'), array( 'note'=>T_('Cache rendered pages that are not controlled by a skin. See Blog Settings for skin output caching.') ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Online Help').get_manual_link('online help'));
	$Form->checkbox_input( 'webhelp_enabled', $Settings->get('webhelp_enabled'), T_('Online Help links'), array( 'note' => T_('Online help links provide context sensitive help to certain features.' ) ) );
$Form->end_fieldset();

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

// --------------------------------------------

$Form->begin_fieldset( T_('Comment recycle bin').get_manual_link('recycle-bin-settings') );

	$Form->text_input( 'auto_empty_trash', $Settings->get('auto_empty_trash'), 5, T_('Prune recycled comments after'), T_('days.') );

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save changes!'), 'SaveButton' ) ) );
}

?>