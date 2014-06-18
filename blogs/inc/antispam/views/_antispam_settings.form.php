<?php
/**
 * This file implements the UI view for the antispam settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _antispam_settings.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var GeneralSettings
 */
global $Settings;

global $Plugins;


$Form = new Form( NULL, 'antispam_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'antispam' );
$Form->hiddens_by_key( get_memorized() );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Comments/Feedback').get_manual_link('antispam-settings-comments') );
	$Form->text( 'antispam_threshold_publish', $Settings->get('antispam_threshold_publish'), 3, T_('Publishing threshold'), T_("(-100 to 100). Automatically publish feedbacks with a spam karma below this value.") );
	$Form->text( 'antispam_threshold_delete', $Settings->get('antispam_threshold_delete'), 3, T_('Deletion threshold'), T_("(-100 to 100). Automatically delete feedbacks with a spam karma over this value.") );

	$Form->info( '', sprintf( /* TRANS: %s gets replaced by the translation for this setting */ T_('Feedbacks with a spam karma between these two thresholds will get the default status of the blog ("%s").'), T_('New feedback status') ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Misc').get_manual_link('antispam-settings-misc') );
	$Form->checkbox( 'antispam_block_spam_referers', $Settings->get('antispam_block_spam_referers'),
		T_('Block spam referers'), T_('If a referrer has been detected as spam, should we block the request with a "403 Forbidden" page?') );
	$Form->checkbox( 'antispam_report_to_central', $Settings->get('antispam_report_to_central'),
		T_('Report to central blacklist'), T_('When banning a keyword, offer an option to report to the central blacklist.')
		.' [<a href="http://b2evolution.net/about/terms.html">'.T_('Terms of service').'</a>]' );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Spam detection relevance weight').get_manual_link('antispam-settings-detection-relevance-weight') );

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

$Form->begin_fieldset( T_('Suspect users').get_manual_link('antispam-settings-suspect-users') );

	$GroupCache = & get_GroupCache( true, T_('Don\'t move suspect users') );
	$Form->select_object( 'antispam_suspicious_group', $Settings->get('antispam_suspicious_group'), $GroupCache, T_('Move suspect users to'), '', true );

	$trust_groups = $Settings->get('antispam_trust_groups') != '' ? explode( ',', $Settings->get('antispam_trust_groups') ) : array();
	$groups_options = array();
	$groups = $GroupCache->get_option_array();
	foreach( $groups as $group_ID => $group_name )
	{
		$groups_options[] = array( 'antispam_trust_groups[]', $group_ID, $group_name, in_array( $group_ID, $trust_groups ) );
	}
	$Form->checklist( $groups_options, 'antispam_trust_groups', T_('Never touch users from these groups') );

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ),
		array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}

?>