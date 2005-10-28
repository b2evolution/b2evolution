<?php
/**
 * This file implements the UI controller for plugins management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require( dirname(__FILE__). '/_header.php' );
$AdminUI->set_path( 'options', 'plugins' );

param( 'action', 'string' );

require( dirname(__FILE__). '/_menutop.php' );


// Check permission to display:
$current_User->check_perm( 'options', 'view', true );


// Begin payload block:
$AdminUI->disp_payload_begin();

// Discover additional plugins:
$AvailablePlugins = & new Plugins();
$AvailablePlugins->discover();
$AvailablePlugins->sort('name');


switch( $action )
{
	case 'install':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		// Install plugin:
		param( 'plugin', 'string', true );
		$Messages->add( T_('Installing plugin:').' '.$plugin, 'success' );
		$Plugins->install( $plugin );
		break;

	case 'uninstall':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		// Uninstall plugin:
		param( 'plugin_ID', 'int', true );
		$Messages->add( T_('Uninstalling plugin #').$plugin_ID, 'success' );
		$Plugins->uninstall( $plugin_ID );
		break;

	case 'info':
		// Display plugin info:
		param( 'plugin', 'string', true );
		$Plugin = $AvailablePlugins->get_by_name( $plugin );
		?>
		<fieldset class="fform">
			<legend><?php echo T_('Plugin info') ?></legend>
			<?php form_info( T_('Name'), $Plugin->name( 'htmlbody', false ) ); ?>
			<?php form_info( T_('Code'), $Plugin->code, T_('This 8 character code uniquely identifies the functionality of this plugin.') ); ?>
			<?php form_info( T_('Short desc'), $Plugin->short_desc( 'htmlbody', false ) ); ?>
			<?php form_info( T_('Long desc'), $Plugin->long_desc( 'htmlbody', false ) ); ?>
		</fieldset>
		<?php
		break;
}

$Messages->displayParagraphs( 'note' );


require dirname(__FILE__).'/_set_plugins.form.php';

// End payload block:
$AdminUI->disp_payload_end();

require dirname(__FILE__).'/_footer.php';
?>