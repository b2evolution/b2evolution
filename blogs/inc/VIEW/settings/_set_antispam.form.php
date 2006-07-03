<?php
/**
 * This file implements the UI view for the antispam settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var GeneralSettings
 */
global $Settings;

global $Plugins;


$Form = & new Form( NULL, 'antispam_checkchanges' );

$Form->begin_form( 'fform', T_('Antispam Settings') );
$Form->hiddens_by_key( get_memorized() );

$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Comments/Feedback') );
	$Form->text( 'antispam_threshold_publish', $Settings->get('antispam_threshold_publish'), 3, T_('Publishing threshold'), T_("(-100 to 100). Automatically publish feedbacks with a spam karma below this value.") );
	$Form->text( 'antispam_threshold_delete', $Settings->get('antispam_threshold_delete'), 3, T_('Deletion threshold'), T_("(-100 to 100). Automatically delete feedbacks with a spam karma over this value.") );

	$Form->info( '', sprintf( /* TRANS: %s gets replaced by the translation for this setting */ T_('Feedbacks with a spam karma between these two thresholds will get the default status of the blog ("%s").'), T_('New feedback status') ) );
$Form->end_fieldset();


$Form->begin_fieldset(); // TODO: needs legend title ("Misc"?)
	$Form->checkbox( 'antispam_block_spam_referers', $Settings->get('antispam_block_spam_referers'),
		T_('Block spam referers'), T_('If a referrer has been detected as spam, should we block the request with a "403 Forbidden" page?') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Spam detection relevance weight') );

echo '<p>'.T_('This defines the weight of the plugin, in relation to the others.').'</p>';

$karma_plugins = $Plugins->get_list_by_events( array('GetSpamKarmaForComment') );

if( empty($karma_plugins) )
{
	echo '<p>'.T_('There are no spam karma plugins enabled.').'</p>';
}
else foreach( $karma_plugins as $loop_Plugin )
{
	$Form->text( 'antispam_plugin_spam_weight['.$loop_Plugin->ID.']', $Plugins->index_ID_rows[$loop_Plugin->ID]['plug_spam_weight'], 2, $loop_Plugin->name );
}

$Form->end_fieldset();


if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}


/*
 * $Log$
 * Revision 1.5  2006/07/03 21:04:49  fplanque
 * translation cleanup
 *
 * Revision 1.4  2006/06/22 19:47:06  blueyed
 * "Block spam referers" as global option
 *
 * Revision 1.3  2006/05/02 04:36:25  blueyed
 * Spam karma changed (-100..100 instead of abs/max); Spam weight for plugins; publish/delete threshold
 *
 * Revision 1.2  2006/05/02 01:27:55  blueyed
 * Moved nofollow handling to basic antispam plugin; added Filter events to Comment class
 *
 * Revision 1.1  2006/05/01 22:20:21  blueyed
 * Made rel="nofollow" optional (enabled); added Antispam settings page
 *
 */
?>