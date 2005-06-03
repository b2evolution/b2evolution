<?php
/**
 * This file implements the ResultSel class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI grants François PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_results.class.php';


/**
 * ResultSel class: displays Results and provides Selection capabilities
 *
 */
class ResultSel extends Results
{
	/**
	 * var Form
	 */
	var $Form;

	var $current_selection_ID;
	var $table_selections;
	var $field_selected;
	var $field_selection;


	/**
	 * Constructor
	 *
	 * @param string fieldname of item ID to select on
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param integer current selection ID
	 * @param string SQL query
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string default ordering of columns (special syntax) if not URL specified
	 * @param integer number of lines displayed on one screen
	 */
	function ResultSel( $field_ID, $table_selections, $field_sel_ID, $field_sel_name,
											$table_objsel, $field_selected, $field_selection, $current_selection_ID,
											$sql, $param_prefix = '', $default_order = '', $limit = 20 )
	{
		// Call parent:
		parent::Results( $sql, $param_prefix, $default_order, $limit );

		$this->current_selection_ID = $current_selection_ID;
		$this->table_selections     = $table_selections;
		$this->field_sel_ID         = $field_sel_ID;
		$this->field_sel_name       = $field_sel_name;
		$this->table_objsel         = $table_objsel;
		$this->field_selected       = $field_selected;
		$this->field_selection      = $field_selection;

		// Presets a selection checkbox:
		$this->cols[] = array(
						'th' => T_('Sel'),
						'td_start' => '<td class="firstcol shrinkwrap">',
						'td' => '%selection_checkbox( #'.$field_ID.'#, \''.$param_prefix.'\' )%',
					);

	}


	/**
	 * Display list/table start preceeded by <form> opening.
	 */
	function display_list_start()
	{
		#global $firm_items, $selection_firm_ID;
		global $item_ID_array;

		#$this->Form = new Form( regenerate_url( 'firm_items', $firm_items ), 'firms_form', 'post', 'none' ); // COPY!!
		$this->Form = new Form( regenerate_url(), $this->param_prefix.'form', 'post', 'none' ); // COPY!!

		$this->Form->begin_form( '' );

		#$this->Form->hidden( 'category', 'firms' );
		$this->Form->hidden( $this->param_prefix.'update_selection', 1 );// the database has to be updated :

		// Sets the cols_check global variable to verify if checkboxes
		// have to be checked in the result set :
		cols_check( $this->current_selection_ID, $this->table_objsel, $this->field_selected, $this->field_selection );

		// item_ID_array must be emptied to avoid conflicts with previous result sets :
		// TODO: put this into object
		$item_ID_array = array();

		// list/table start:
		parent::display_list_start();
	}


	/**
	 * Display list/table end followed by </form> closing.
	 *
	 * Typically outputs </ul> or </table>
	 */
	function display_list_end()
	{
		global $item_ID_array;

		echo $this->replace_vars( $this->params['functions_start'] );

			echo '<a href="'.regenerate_url().'" onclick="check( this, true );return false;">'.T_('Check all')
					.'</a> | <a href="'.regenerate_url().'" onclick="check( this, false );return false;">'.T_('Uncheck all').'</a> ';

			// construction of the select menu :
			$selection_name = selection_select_tag( $this->param_prefix, $this->table_selections, $this->field_sel_name, $this->field_sel_ID, $this->current_selection_ID );

			$this->Form->text( 'selection_'.$this->param_prefix.'name', $selection_name, 25, T_('New selection name') );

			// List of IDs displayed on this page (needed for deletes):
			$this->Form->hidden( 'item_ID_list', implode( $item_ID_array, ',' ) );

			$this->Form->submit( array( '', T_('Update selection'), 'SaveButton' ) );

		echo $this->replace_vars( $this->params['functions_end'] );


		// list/table end:
		parent::display_list_end();


		$this->Form->end_form();

	}


}


/**
 * Sets the cols_check global variable to verify if checkboxes have to be checked in the result set
 *
 * @param the selection ID
 * @param the name of the attachment table (which links the items and the selections
 * @param the item id field
 * @param the selection id field
 */
function cols_check( $selection_ID, $sel_table, $sel_table_item, $sel_table_selection )
{
	global $DB, $cols_check;

	if( $selection_ID !== 0 )
	{
		$sql_check = 'SELECT '.$sel_table_item.' FROM '.$sel_table.' WHERE '.$sel_table_selection.'='.$selection_ID;
		$cols_check = $DB->get_col( $sql_check );
	}
	else
	{
		$cols_check = array();
	}
}


/**
 * Display a checkbox allowing to add the item to a selection
 *
 * Only one checkbox will be displayed for each ID.
 * IDs which are already in the selection will be pre-checked.
 *
 * @deprecated should go into ResultSel class
 *
 * @param integer item ID
 * @param string item name / prefix for form values
 * @return string the correct input tag
 */
function selection_checkbox( $item_ID, $param_prefix )
{
	// List of checkboxes to pre-check:
	global $cols_check;
	// List of already displayed checkboxes (can be used outside to get a list of checkboxes which have been displayed)
	global $item_ID_array;

	if( in_array( $item_ID, $item_ID_array ) )
	{	// We have already displayed a checkbox for this ID
		return '&nbsp;';	// nbsp is for IE...
	}

	$item_ID_array[] = $item_ID; //construction of the ID list

	$r = '<input type="checkbox" class="checkbox" name="'.$param_prefix.'items[]" value='.$item_ID;

	if( in_array( $item_ID, $cols_check ) )
	{	// already in selection:
		$r .= ' checked="checked" ';
	}

	$r .= ' />';

	return $r;
}


/**
 * Creates the select tag in the search menu and fills it with the appropriate option tags
 *
 * @param string the selection category prefix(the one used during the construction of the result object)
 * @param string the name of the table containing the selections
 * @param string the name field  in the selection table
 * @param string the id field in the selection table
 * @param integer the current selection id
 */
function selection_select_tag(
															$category_prefix,
															$selections_table,
															$selections_table_name,
															$selections_table_ID,
															$selection_ID
															)
{
	global $DB, $selection_name;

	$r = T_('Selection');
	$r .= ' <select name="selection_'.$category_prefix.'ID"
					onchange="selform = get_form( this );selform.elements[\''.$category_prefix.'update_selection\'].value=0;selform.submit()" >'."\n";
	// in the onchange attribute, option_db is set to 0 to avoid updating the database
	$r .= '<option value="0">'.T_('New selection')."</option>\n";

	$sql = 'SELECT * FROM '.$selections_table.' ORDER BY '.$selections_table_name;
	$rows = $DB->get_results( $sql );
	if( !empty( $rows ) )
	{
		$selection_name = '';
		foreach( $rows as $row )
		{ // construction of the option tags
			if( $row->$selections_table_ID == $selection_ID )
			{ // option selected by default
				$selected = ' selected="selected" ';
				$selection_name = $row->$selections_table_name;
			}
			else
			{
				$selected = '';
			}
			$r .= '<option value="'.$row->$selections_table_ID.'" '.$selected.' >'.$row->$selections_table_name."</option>\n";
		}
	}
	$r .= "</select>\n\n";

	echo $r;

	return $selection_name;
}


/**
 * Manages the various database changes to make on selections
 *
 * @param string the selection category
 * @param string the action currently effectuated
 * @param integer the current selection id
 * @param string the current selection name
 * @param array the items of the selection
 */
function selection_action( $category, $action, $selection_ID, $selection_name, $items )
{ // the form has been submitted to act on the database and not only to change the display

	global $DB, $Messages, $confirm, $item_ID_list;

	switch( $category )
	{ // definition of the table and column names depending on the selection category

		case 'contacts': // parameters of contact selections
			global $selection_cont_ID, $selection_cont_name;
			$selections_table = 'T_contselections';
			$sel_table = 'T_cont_csel';
			$selections_table_name = 'csel_name';
			$selections_table_id = 'csel_ID';
			$sel_table_selection = 'cocs_csel_ID';
			$sel_table_item = 'cocs_cont_ID';
			$category_table = 'T_contacts';
			$category_name = 'cont_lastname, cont_firstname';
			break;

		case 'firms': // parameters of firm selections
			global $selection_firm_ID, $selection_firm_name;
			$selections_table = 'T_firmselections';
			$sel_table = 'T_firm_fsel';
			$selections_table_name = 'fsel_name';
			$selections_table_id = 'fsel_ID';
			$sel_table_selection = 'fifs_fsel_ID';
			$sel_table_item = 'fifs_firm_ID';
			$category_table = 'T_firms';
			$category_name = 'firm_name';
			break;

		case 'tasks': // parameters of task selections
			global $selection_tsk_ID, $selection_tsk_name;
			$selections_table = 'T_taskselections';
			$sel_table = 'T_tsk_tsel';
			$selections_table_name = 'tsel_name';
			$selections_table_id = 'tsel_ID';
			$sel_table_selection = 'tkts_tsel_ID';
			$sel_table_item = 'tkts_tsk_ID';
			$category_table = 'T_tasks';
			$category_name = 'task_title';
			break;

		case 'etabs': // parameters of establishment selections
			global $selection_etab_ID, $selection_etab_name;
			$selections_table = 'T_etabselections';
			$sel_table = 'T_etab_esel';
			$selections_table_name = 'esel_name';
			$selections_table_id = 'esel_ID';
			$sel_table_selection = 'etes_esel_ID';
			$sel_table_item = 'etes_etab_ID';
			$category_table = 'T_establishments';
			$category_name = 'etab_name';
			break;

		case 'addresses': // parameters of address selections
			global $selection_addr_ID, $selection_addr_name;
			$selections_table = 'T_addrselections';
			$sel_table = 'T_addr_asel';
			$selections_table_name = 'asel_name';
			$selections_table_id = 'asel_ID';
			$sel_table_selection = 'adas_asel_ID';
			$sel_table_item = 'adas_addr_ID';
			$category_table = 'T_addresses';
			$category_name = 'addr_name';
			break;

		default: // default parameters
			$selections = '';
			$sel_table = '';
			$selections_table_name = '';
			$selections_table_id = '';
			$sel_table_selection = '';
			$sel_table_item = '';
			$category_table = '';
			$category_name = '';
			break;

	}

	switch( $action )
	{

		// creation of a new selection
		case 'create':

			if( empty($selection_name) )
			{	// No name provided:
				$Messages->add( T_('Cannot create a selection with an empty name'), 'error' );
				break;
			}

			$sql_selections = "INSERT INTO $selections_table ( $selections_table_name )
															VALUES( ".$DB->quote($selection_name)." ) ";		 // construction of the query
			$DB->query( $sql_selections ); // insertion of a new selection in the database

			$selection_ID = mysql_insert_id(); // id generated by the last sql query

			if( !empty( $items ) )
			{ // nothing must be inserted if no items are selected
				$sql_sel = 'INSERT INTO '.$sel_table.'( '.$sel_table_item.', '.$sel_table_selection .')  VALUES ';
				$sel_array = array();

				$i = 0;
				foreach( $items as $item )
				{ // construction of the sql query depending on selected values in the result table
					$sel_array[$i++] = ' ('.$item.','.$selection_ID.' ) ';
				}
				$sql_sel .= implode( $sel_array, ',' );
				$DB->query( $sql_sel ); // insertion of the relation between selections and items in the database
			}

			switch( $category )
			{ // attribution of new values to some parameters so that the newly created selection can become the current one
				case 'firms':
					$selection_firm_ID = $selection_ID;
					$selection_firm_name = $selection_name;
					break;

				case 'tasks':
					$selection_tsk_ID = $selection_ID;
					$selection_tsk_name = $selection_name;
					break;

				case 'etabs':
					$selection_etab_ID = $selection_ID;
					$selection_etab_name = $selection_name;
					break;

				case 'contacts':
					$selection_cont_ID = $selection_ID;
					$selection_cont_name = $selection_name;
					break;

				case 'addresses':
					$selection_addr_ID = $selection_ID;
					$selection_addr_name = $selection_name;
					break;

			}

			$Messages->add( T_('Selection created.'), 'success' );

			break;


		case 'edit':
		case 'update':
			// update of an existing selection
			$DB->begin();

 			if( empty($selection_name) )
			{	// No name provided:
				$Messages->add( T_('Please provide a selection name'), 'error' );
			}
			else
			{	// Update name:
				$sql_selections = "UPDATE $selections_table
															SET $selections_table_name = ".$DB->quote($selection_name)."
														WHERE $selections_table_id = $selection_ID"; // construction of the update query
				$DB->query( $sql_selections );
			}

			if( preg_match( '#[0-9,]+#', $item_ID_list ) )
			{ // check the format of the item list to avoid sql injection
				$sql_delete = 'DELETE FROM '.$sel_table.' WHERE '.$sel_table_selection.' = '.$selection_ID
											.' AND '.$sel_table_item.' IN ('.$item_ID_list.')'; // deletion of the former db entries
				$DB->query( $sql_delete );

				$Messages->add( T_('Obsolete selection entries deleted'), 'success' );
			}

			if( !empty( $items ) )
			{ // there have been some items selected in the result table: they must be inserted into the database
				$sql_sel = 'INSERT INTO '.$sel_table.'( '.$sel_table_item.', '.$sel_table_selection .')  VALUES ';
				$sel_array = array();

				foreach( $items as $item )
				{ // construction of the sql query depending on selected values in the result table
					$sel_array[] = ' ( '.$item.', '.$selection_ID.' ) ';
				}
				$sql_sel .= implode( $sel_array, ',' );
				$DB->query( $sql_sel ); // insertion of the relation between selections and items in the database

				$Messages->add( T_('New selections entries inserted'), 'success' );
			}

			$DB->commit();
			break;


		case 'copy':
			// creation of a new selection with the same name
			$sql_selections = 'INSERT INTO '.$selections_table.'('.$selections_table_name.
												') VALUES( "'.$selection_name.'" )';
			$DB->query( $sql_selections );
			$Messages->add( T_('Selection copied'), 'success' );

			$new_selection_ID = mysql_insert_id();// gets the new selection id

			// creation of the links between the new selection and the selected items
			$sql_sel = 'INSERT INTO '.$sel_table.'( '.$sel_table_item.', '.$sel_table_selection.' ) '
								 .'SELECT '.$sel_table_item.', '.$new_selection_ID.' FROM '.$sel_table.' WHERE '
												.$sel_table_selection.'='.$selection_ID;
			$DB->query( $sql_sel );
			$Messages->add( T_('Selection links copied'), 'success' );

			$selection_ID = $new_selection_ID;

			break;


		case 'delete':
			// deletion of the selection
			if( !$confirm )
			{ // ask for confirmation before deleting
				?>
				<div class="panelinfo">
					<h3><?php printf( T_('Delete selection &laquo;%s&raquo;?'), $selection_name )?></h3>

					<p><?php echo T_('Warning').': '.T_('Cascading deletes!') ?></p>

					<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

					<p>

				<?php
					$Form = & new Form( regenerate_url( 'tab', 'tab='.$category ), 'form', 'get' );

					$action = '';

					$Form->begin_form( 'inline' );
					$Form->hidden( 'action', 'delete' );
					$Form->hidden( 'selection_ID', $selection_ID );
					$Form->hidden( 'confirm', 1 );
					$Form->hidden( 'tab', $category );
					$Form->button( array( 'submit', '', T_('I am sure!'), 'DeleteButton' ) );
					$Form->end_form();

					$Form->begin_form( 'inline' );
					$Form->button( array( 'submit', '', T_('CANCEL'), 'CancelButton' ) );
					$Form->end_form();
				?>

				</p>

				</div>
				<?php
			}
			else
			{ // the deletion has been confermed
				$sql_sel = 'DELETE FROM '.$sel_table.' WHERE '.$sel_table_selection.'='.$selection_ID;
				$DB->query( $sql_sel );// deletion of the links between the selection and the selected items
				$Messages->add( T_('Selection attachments deleted'), 'success' );

				$sql_selections = 'DELETE FROM '.$selections_table.' WHERE '.$selections_table_id.'='.$selection_ID;
				$DB->query( $sql_selections );// deletion of the selection
				$Messages->add( T_('Selection deleted'), 'success' );
			}

			$selection_ID = -1;

			break;

		default:
			break;
	}

	return $selection_ID;

}


/*
 * $Log$
 * Revision 1.2  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.1  2005/06/02 18:50:53  fplanque
 * no message
 *
 */
?>