<?php
/**
 * This file implements the UI controller for Antispam Features.
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
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


$AdminUI->set_path( 'options', 'antispam' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'submit', 'array', array() );
		if( isset($submit['restore_defaults']) )
		{ // RESTORE DEFAULTS:
			$Settings->delete_array( array(
				'antispam_threshold_publish', 'antispam_threshold_delete', 'antispam_block_spam_referers' ) );

			// Set "spam detection relevance weight" back to 1 for all plugins:
			$changed = $DB->query( 'UPDATE T_plugins SET plug_spam_weight = 1' );

			if( $Settings->dbupdate() || $changed )
			{
				$Messages->add( T_('Restored default values.'), 'success' );
			}
			else
			{
				$Messages->add( T_('Settings have not changed.'), 'note' );
			}
		}
		else
		{ // UPDATE:
			param_integer_range( 'antispam_threshold_publish', -100, 100, T_('The threshold must be between -100 and 100.') );
			$Settings->set( 'antispam_threshold_publish', $antispam_threshold_publish );

			param_integer_range( 'antispam_threshold_delete', -100, 100, T_('The threshold must be between -100 and 100.') );
			$Settings->set( 'antispam_threshold_delete', $antispam_threshold_delete );

			param( 'antispam_block_spam_referers', 'integer', 0 );
			$Settings->set( 'antispam_block_spam_referers', $antispam_block_spam_referers );

			$changed_weight = false;
			param( 'antispam_plugin_spam_weight', 'array', array() );
			foreach( $antispam_plugin_spam_weight as $l_plugin_ID => $l_weight )
			{
				if( ! is_numeric($l_weight) )
				{
					continue;
				}
				if( $l_weight < 0 || $l_weight > 100 )
				{
					param_error( 'antispam_plugin_spam_weight['.$l_plugin_ID.']', T_('Spam weight has to be in the range of 0-100.') );
					continue;
				}
				if( $DB->query( '
						UPDATE T_plugins
						   SET plug_spam_weight = '.$DB->quote($l_weight).'
						 WHERE plug_ID = '.(int)$l_plugin_ID ) )
				{
					$changed_weight = true;
				}
			}
			if( $changed_weight )
			{ // Reload plugins table (for display):
				$Plugins->loaded_plugins_table = false;
				$Plugins->load_plugins_table();
			}


			if( ! $Messages->count('error') )
			{
				if( $Settings->dbupdate() || $changed_weight )
				{
					$Messages->add( T_('Settings updated.'), 'success' );
				}
				else
				{
					$Messages->add( T_('Settings have not changed.'), 'note' );
				}
			}
		}
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/_set_antispam.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>