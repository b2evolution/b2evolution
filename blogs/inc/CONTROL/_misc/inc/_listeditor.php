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

// Init fadeout result array:
$result_fadeout = array();

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
		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

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

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$Request->param( 'name', 'string', true );
		if( $Request->param_check_not_empty( 'name', T_('Please enter a string.') ) )
		{
			$DB->begin();

			if( !empty( $edited_table_ordercol ) )
			{ // The element has an order field in database
				if( $max_order = $DB->get_var( "SELECT MAX($edited_table_ordercol) FROM $edited_table" ) ) 
				{	// The new element order must be the lastest
					$max_order++;
				}
				else 
				{ // There are no elements in the database yet, so his order is set to 1. 
					$max_order = 1;
				}
			}
			$DB->query( "
				INSERT INTO $edited_table( $edited_table_namecol".
				(isset( $edited_table_ordercol ) ? ', '.$edited_table_ordercol : '')
			  .(isset( $edited_table_filtercol ) ? ', '.$edited_table_filtercol : '')
				.')
				VALUES( '.$DB->quote($name).
				(isset( $edited_table_ordercol ) ? ', '.$max_order : '')
				.(isset( $edited_table_filtercol ) ? ', '.$val_filtercol : '')
				.' )' );
			
			$DB->commit();
					
			// Add the ID of the new object to the result fadeout
			$result_fadeout[$edited_table_IDcol][] = $DB->insert_id;
				
			$Messages->add( T_('Entry created.'), 'success' );
			$name = '';
		}
		break;


	case 'update':
		// Update in database...:

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$Request->param( 'ID', 'integer', true );
		if( $Request->param_string_not_empty( 'name', T_('Please enter a string.') ) )
		{	// Update in database
			$DB->query( "
				UPDATE $edited_table
				   SET $edited_table_namecol = ".$DB->quote($name)."
				 WHERE $edited_table_IDcol = $ID" );

			$Messages->add( sprintf( T_('Entry #%d updated.'), $ID ), 'success' );
			// Add the object ID to the result fadeout
			$result_fadeout[$edited_table_IDcol][] = $ID;
			unset( $ID );
			$name = '';
		}
		else 
		{
			$action = 'edit';
		}
		break;


	case 'delete':
		// Delete entry:

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

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
		elseif( isset( $delete_restrictions ) )
		{  // Not confirmed, delete restrictions set, so check for restrictions:
			foreach( $delete_restrictions as $restriction )
			{
				if( !isset( $EvoConfig->DB['aliases'][$restriction['table']] ) )
				{	// We have no declaration for this table, we consider we don't deal with this table in this app:
					continue;
				}
				$count = $DB->get_var(
					'SELECT COUNT(*)
					   FROM '.$restriction['table'].'
					  WHERE '.$restriction['fk'].' = '.$ID,
					0, 0, 'restriction/cascade check' );
				if( $count )
				{
					$Messages->add( sprintf( $restriction['msg'], $count ), 'restrict' );
				}
			}
			if( $Messages->count('restrict') )
			{	// There are restrictions:
				$Messages->head = array(
						'container' => $restrict_title,
						'restrict' => T_('The following relations prevent deletion:')
					);
				$Messages->foot =	T_('Please delete related objects before you proceed.');
			}
			else 
			{ // There are no restrictions, so we can display delete dialog 
				$checked_delete = true;
			}
		}
		else 
		{	// No delete restrictions to check, so we can display delete dialog 
			$checked_delete = true;
		}
			
		$name = '';
		break;
		
		
	case 'move_up':
		// Move up

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$ID = param( $edited_table_prefix.'ID', 'integer', true );

		$DB->begin();
		
		// Test if the ID exist and set his order
		$order = $DB->get_var( "SELECT $edited_table_ordercol
														  FROM $edited_table
														 WHERE $edited_table_IDcol = $ID" );
		
		if( $DB->num_rows != 1 )
		{
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			$action = 'list';
			$name = '';
			$DB->commit();
			break;
			/* break */
		}
		
		// Get the ID and the order of the inferior element which his order is the nearest   	
		$rows = $DB->get_results( "SELECT $edited_table_IDcol, $edited_table_ordercol 
														 	 	 FROM $edited_table
																WHERE $edited_table_ordercol < $order"
														.(isset( $edited_table_filtercol ) ? ' AND '.$edited_table_filtercol.' = '.$val_filtercol : 0)
													." ORDER BY $edited_table_ordercol DESC 
														 		LIMIT 0,1" );

		if( count( $rows ) )
		{
			$name_ID = $edited_table_prefix.'ID';
			$ID_inf = $rows[0]->$name_ID;
			
			$name_order = $edited_table_prefix.'order';
			$order_inf = $rows[0]->$name_order;
			
			// Update the order of the ID
			$DB->query( "UPDATE $edited_table 
											SET $edited_table_ordercol = $order_inf
										WHERE $edited_table_IDcol = $ID" );
			
			// Update the order of the inferior element
			$DB->query( "UPDATE $edited_table 
											SET $edited_table_ordercol = $order
										WHERE $edited_table_IDcol = $ID_inf" );
				
		// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$edited_table_IDcol][] = $ID;
			$result_fadeout[$edited_table_IDcol][] = $ID_inf;
		}
		else 
		{
			$Messages->add( T_('This element is already at the top.'), 'error' ); 
		}	
		
		$DB->commit();
		
		$name = '';	
		break;

		
	case 'move_down':
		// Move down

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$ID = param( $edited_table_prefix.'ID', 'integer', true );

		$DB->begin();
		
		// Test if the ID exist and set his order
		$order = $DB->get_var( "SELECT $edited_table_ordercol
														  FROM $edited_table
														 WHERE $edited_table_IDcol = $ID" );
		
		if( $DB->num_rows != 1 )
		{
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			$action = 'list';
			$name = '';
			$DB->commit();
			break;
			/* break */
		}
		
		// Get the ID and the order of the inferior element which his order is the nearest   	
		$rows = $DB->get_results( "SELECT $edited_table_IDcol, $edited_table_ordercol 
														 	 	 FROM $edited_table
																WHERE $edited_table_ordercol > $order"
														.(isset( $edited_table_filtercol ) ? ' AND '.$edited_table_filtercol.' = '.$val_filtercol : 0)
													." ORDER BY $edited_table_ordercol ASC 
														 		LIMIT 0,1" );
		
		if( count( $rows ) )
		{
			$name_ID = $edited_table_prefix.'ID';
			$ID_sup = $rows[0]->$name_ID;
			
			$name_order = $edited_table_prefix.'order';
			$order_sup = $rows[0]->$name_order;
			
			// Update the order of the ID
			$DB->query( "UPDATE $edited_table 
											SET $edited_table_ordercol = $order_sup
										WHERE $edited_table_IDcol = $ID" );
			
			// Update the order of the superior element
			$DB->query( "UPDATE $edited_table 
											SET $edited_table_ordercol = $order
										WHERE $edited_table_IDcol = $ID_sup" );
			
			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$edited_table_IDcol][] = $ID;
			$result_fadeout[$edited_table_IDcol][] = $ID_sup;
		}	
		else 
		{
			$Messages->add( T_('This element is already at the bottom.'), 'error' ); 
		}	
			
			
		$DB->commit();
			
		$name = '';	
		break;


	case 'sort_by_order':
		// The list is sorted by the order column now.
		$Request->set_param( 'results_'.$edited_table_prefix.'order', '--A');
		$name = '';
		$action = 'list';
		break;


	default:
		$name = '';
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/**
 * Display payload:
 */
if( ($action == 'delete') && !$confirm && $checked_delete )
{
	?>
	<div class="panelinfo">
		<h3><?php printf( T_('Delete entry #%d?'), $ID )?></h3>

		<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

		<?php
		$Form = & new Form( '', 'form_delete', 'get', '' );

		$Form->begin_form( 'inline' );
		$Form->hidden( 'action', 'delete' );
		$Form->hidden( 'ID', $ID );
		$Form->hidden( 'confirm', 1 );
		
		// We may need to use memorized params in the next page
		$Form->hiddens_by_key( get_memorized( 'action,ID') );
		
		$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );
		$Form->end_form();

		$Form = & new Form( '', 'form_cancel', 'get', '' );
				
		$Form->begin_form( 'inline' );
		$Form->hiddens_by_key( get_memorized( 'action,ID') );
		$Form->button( array( 'submit', '', T_('CANCEL'), 'CancelButton' ) );
		$Form->end_form()
		?>

	</div>
	<?php
}


// Begin payload block:
$AdminUI->disp_payload_begin();


// EXPERIMENTAL
if ( !isset( $default_col_order ) )
{ // The default order column is not set, so the default is the name column
	$default_col_order = '-A-';
}


// Create result set:
$sql = "SELECT $edited_table_IDcol, $edited_table_namecol
  			 	FROM $edited_table"
				. ( !empty( $edited_table_filtercol ) ? ' WHERE '.$edited_table_filtercol.' = '.$val_filtercol : '' ) ; 

$Results = & new Results(	$sql, isset( $edited_table_prefix ) ? $edited_table_prefix : '',  $default_col_order );

if( isset( $list_title ) )
{
	$Results->title = $list_title;
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => $edited_table_IDcol,
		'th_start' => '<th class="firstcol shrinkwrap">',
		'td_start' => '<td class="firstcol shrinkwrap">',
		'td' => "\$$edited_table_IDcol\$",
	);

if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit:
	function link_name( $title , $ID )
	{
		return '<strong><a href="'.regenerate_url( 'action,ID', 'ID='.$ID.'&amp;action=edit' ).'">'.$title.'</a></strong>';
	}
}
else
{
	function link_name( $title , $ID )
	{
		return '<strong>'.$title.'</strong>';
	}
}
$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => $edited_table_namecol,
 		'td' => '%link_name( #'.$edited_table_namecol.'#, #'.$edited_table_IDcol.'# )%',
	);


if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit:

	if( !empty( $edited_table_ordercol ) )
	{
		$Results->cols[] = array(
				'th' => T_('Move'),
				'th_start' => '<th class="shrinkwrap">',
				'order' => $edited_table_ordercol,
				'td_start' => '<td class="shrinkwrap">',
				'td' => '{move}',
			);
	}

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
			'td_start' => '<td class="shrinkwrap lastcol">',
			'td' => '%edit_actions( #'.$edited_table_IDcol.'# )%',
		);

}


// EXPERIMENTAL
// $Results->display();
$Results->display( NULL, $result_fadeout );


// NEW ENTRY / EDIT FORM:
if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit:

	switch( $action )
	{
		case 'edit':
		case 'delete':
			$creating = false;
			break;

		default:
			$creating = true;
	}

	$Form = & new form( '', 'leditor_checkchanges' );

	$Form->begin_form( 'fform', $creating ? T_('New entry') : T_('Edit entry') );

	$Form->hidden( 'action', $creating ? 'create' : 'update' );

	if( $action == 'edit' )
	{
		$Form->hidden( 'ID', $ID );
		$Form->info( T_('ID'), $ID );
	}

	$Form->text_input( 'name', $name, min(40,$edited_name_maxlen), T_('Name'), array( 'maxlength'=>$edited_name_maxlen, 'required'=>true ) );

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

}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>