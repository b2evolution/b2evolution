<?php
/**
 * This file implements the generic list editor
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
$error_head = '';
param( 'action', 'string', 'list' );

/**
 * Perform action:
 */
switch( $action )
{

	case 'copy':
	case 'edit':
		param( 'ID', 'integer', true );
		$name = $DB->get_var( "SELECT $edited_table_namecol
														 FROM $edited_table
														WHERE $edited_table_IDcol = $ID" );
		if( $DB->num_rows != 1 )
		{
			$error_head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			$action = 'list';
		}
		break;

	case 'create':
		// Insert into database...:
		param( 'name', 'string', true );

		$DB->query( "INSERT INTO $edited_table( $edited_table_namecol )
									VALUES( ".$DB->quote($name).' )' );

		$Messages->add( T_('Entry created.'), 'note' );
		unset( $ID );
		$name = '';
		break;


	case 'update':
		// Update in database...:
		param( 'ID', 'integer', true );
		param( 'name', 'string', true );

		$DB->query( "UPDATE $edited_table
										SET $edited_table_namecol = ".$DB->quote($name)."
									WHERE	$edited_table_IDcol = $ID" );

		$Messages->add( sprintf( T_('Entry #%d updated.'), $ID ), 'note' );
		unset( $ID );
		$name = '';
		break;


	case 'delete':
		// Delete entry:
		param( 'ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$DB->query( "DELETE FROM $edited_table
								 		WHERE $edited_table_IDcol = $ID" );

			if( $DB->rows_affected != 1 )
			{
				$error_head = T_('Cannot delete entry!');
				$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			}
			else
			{
				$Messages->add( sprintf( T_('Entry #%d deleted.'), $ID ), 'note' );
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

// Display messages:
if( $Messages->count( 'all' ) )
{ // we have errors/notes
	?>
	<div class="panelinfo">
	<?php
		$Messages->display( $error_head, '', true, 'all' );
	?>
	</div>
	<?php
}


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


$AdminUI->dispSubmenu();

// Create result set:
$Results = new Results(	"SELECT $edited_table_IDcol, $edited_table_namecol
													 FROM $edited_table
													ORDER BY $edited_table_orderby" );

if( isset( $list_title ) )
{
	$Results->title = $list_title;
}

$Results->col_headers = array(
													T_('ID'),
													T_('Name'),
													T_('Actions')
												 );

$Results->col_orders = array(
													$edited_table_IDcol,
													$edited_table_namecol
												 );

$Results->cols = array(
					"\$$edited_table_IDcol\$",
					'<strong><a href="?ID=$'.$edited_table_IDcol.'$&amp;action=edit" title="'.
						T_('Edit this entry...').'">$'.$edited_table_namecol.'$</a></strong>',
				action_icon( T_('Edit...'), 'edit',
											'%regenerate_url( \'action\', \'ID=$'.$edited_table_IDcol.'$&amp;action=edit\')%' ).
				action_icon( T_('Duplicate...'), 'copy',
											'%regenerate_url( \'action\', \'ID=$'.$edited_table_IDcol.'$&amp;action=copy\')%' ).
				action_icon( T_('Delete!'), 'delete',
											'%regenerate_url( \'action\', \'ID=$'.$edited_table_IDcol.'$&amp;action=delete\')%' ),
				);

if( isset( $results_params ) )
	$Results->params = & $results_params;

$Results->display();

$Form = & new form( '', 'form' );

$Form->begin_form( 'fform', ( $action != 'edit' ) ? T_('New entry') : T_('Edit entry') );

$Form->hidden( 'action', ($action != 'edit' ) ? 'create' : 'update' );

if( $action == 'edit' )
{
	$Form->hidden( 'div_firm_ID', $ID );
	$Form->info( T_('ID'), $ID );
}

$Form->text( 'name', $name, min(40,$edited_name_maxlen), T_('Name'), '', $edited_name_maxlen );

if( $action != 'edit' )
{
	$Form->end_form( array( array( '', '', T_('Record'), 'SaveButton' ),
									 array( 'reset', 'reset', T_('Update'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( '', '', T_('Update'), 'SaveButton' ),
									 array( 'reset', 'reset', T_('Update'), 'SaveButton' ) ) );
}

echo '</div>';

/**
 * Display page footer:
 */
require dirname(__FILE__). '/_footer.php';
?>