<?php
/**
 * This file implements the generic list editor
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
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * NOTE: It uses <code>$AdminUI->get_path(1).'.php'</code> to link back to the ID of the entry.
 *       If that causes problems later, we'd probably need to set a global like $listeditor_url.
 *
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
param( 'action', 'string', 'list' );
param( 'ID', 'integer', 0 );

/**
 * Check locked elements
 */
if( !empty( $locked_IDs )
		&& in_array( $action, array( 'edit', 'update', 'delete' ) )
		&& in_array( $ID, $locked_IDs ) )
{
	$Messages->add( T_('This element is locked and cannot be edited!') );
	$action = 'list';
}



/**
 * Perform action:
 */
switch( $action )
{

	case 'copy':
	case 'edit':
		$name = $DB->get_var( "
				SELECT $edited_table_namecol
				  FROM $edited_table
				 WHERE $edited_table_IDcol = $ID" );

		if( $DB->num_rows != 1 )
		{
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			$action = 'list';
		}
		break;


	case 'create':
		// Insert into database...:
		$Request->param( 'name', 'string', true );
		if( $Request->param_check_not_empty( 'name', T_('Please enter a string.') ) )
		{
			$DB->query( "
				INSERT INTO $edited_table( $edited_table_namecol )
				VALUES( ".$DB->quote($name).' )' );

			$Messages->add( T_('Entry created.'), 'success' );
			$name = '';
		}
		break;


	case 'update':
		// Update in database...:
		$Request->param( 'ID', 'integer', true );
		$Request->param_string_not_empty( 'name', T_('Please enter a string.') );
		{
			$DB->query( "
				UPDATE $edited_table
				   SET $edited_table_namecol = ".$DB->quote($name)."
				 WHERE $edited_table_IDcol = $ID" );

			$Messages->add( sprintf( T_('Entry #%d updated.'), $ID ), 'success' );
			unset( $ID );
			$name = '';
		}
		break;


	case 'delete':
		// Delete entry:
		param( 'ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$DB->query( "
				DELETE FROM $edited_table
				WHERE $edited_table_IDcol = $ID" );

			if( $DB->rows_affected != 1 )
			{
				$Messages->head = T_('Cannot delete entry!');
				$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			}
			else
			{
				$Messages->add( sprintf( T_('Entry #%d deleted.'), $ID ), 'success' );
			}
			unset( $ID );
			$action = 'list';
		}
		$name = '';
		break;

	default:
		$name = '';
}


/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';


/**
 * Display payload:
 */
if( ($action == 'delete') && !$confirm )
{
	?>
	<div class="panelinfo">
		<h3><?php printf( T_('Delete entry #%d?'), $ID )?></h3>

		<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

		<p>

		<?php
		$Form = & new Form( '', 'form', 'get' );

		$Form->begin_form( 'inline' );
		$Form->hidden( 'action', 'delete' );
		$Form->hidden( 'ID', $ID );
		$Form->hidden( 'confirm', 1 );
		$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );
		$Form->end_form();

		$Form->begin_form( 'inline' );
		$Form->button( array( 'submit', '', T_('CANCEL'), 'CancelButton' ) );
		$Form->end_form()
		?>

		</p>

	</div>
	<?php
}


// Begin payload block:
$AdminUI->disp_payload_begin();


// Create result set:
$Results = & new Results(	"SELECT $edited_table_IDcol, $edited_table_namecol
														 FROM $edited_table
														ORDER BY $edited_table_orderby" );

if( isset( $list_title ) )
{
	$Results->title = $list_title;
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => $edited_table_IDcol,
		'th_start' => '<tr><th class="firstcol shrinkwrap">',
		'td_start' => '<td class="firstcol shrinkwrap">',
		'td' => "\$$edited_table_IDcol\$",
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => $edited_table_namecol,
		'td' => '<strong><a href="'.$pagenow.'?ID=$'.$edited_table_IDcol.'$&amp;action=edit" title="'
		           .T_('Edit this entry...').'">$'.$edited_table_namecol.'$</a></strong>',
	);

function edit_actions( $ID )
{
	global $locked_IDs;

	$r = action_icon( T_('Duplicate...'), 'copy', regenerate_url( 'action', 'ID='.$ID.'&amp;action=copy' ) );

	if( empty( $locked_IDs ) || !in_array( $ID, $locked_IDs ) )
	{ // This element is NOT locked:
		$r = action_icon( T_('Edit...'), 'edit', regenerate_url( 'action', 'ID='.$ID.'&amp;action=edit' ) )
					.$r
					.action_icon( T_('Delete!'), 'delete', regenerate_url( 'action', 'ID='.$ID.'&amp;action=delete' ) );

	}

	return $r;
}

$Results->cols[] = array(
		'th' => T_('Actions'),
		'td' => '%edit_actions( #'.$edited_table_IDcol.'# )%',
	);

$Results->display();

// FORM:
switch( $action )
{
	case 'edit':
	case 'delete':
		$creating = false;
		break;

	default:
		$creating = true;
}

$Form = & new form( '', 'form' );

$Form->begin_form( 'fform', $creating ? T_('New entry') : T_('Edit entry') );

$Form->hidden( 'action', $creating ? 'create' : 'update' );

if( $action == 'edit' )
{
	$Form->hidden( 'div_firm_ID', $ID );
	$Form->info( T_('ID'), $ID );
}

$Form->text( 'name', $name, min(40,$edited_name_maxlen), T_('Name'), '', $edited_name_maxlen );

if( $creating )
{
	$Form->end_form( array(
		array( '', '', T_('Record'), 'SaveButton' ),
		array( 'reset', 'reset', T_('Reset'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array(
		array( '', '', T_('Update'), 'SaveButton' ),
		array( 'reset', 'reset', T_('Reset'), 'SaveButton' ) ) );
}

// End payload block:
$AdminUI->disp_payload_end();

/**
 * Display page footer:
 */
require dirname(__FILE__). '/_footer.php';
?>