<?php
if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
}

	$SQL = & new SQL();
	$SQL->SELECT( 'link_ID, link_ltype_ID, file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
	$SQL->FROM( 'T_links LEFT JOIN T_files ON link_file_ID = file_ID' );
	$order_fields = array( 'file_path' );

	if( isset( $db_aliases['T_contacts'] ) )
	{	// This application handles contacts:
		$SQL->SELECT_add( 'cont_ID, cont_firstname, cont_lastname' );
		$SQL->FROM_add( 'LEFT JOIN T_contacts ON link_cont_ID = cont_ID' );
		$order_fields[] = 'cont_lastname, cont_firstname';
	}

	if( isset( $db_aliases['T_establishments'] ) )
	{	// This application handles estabs:
		$SQL->SELECT_add( 'etab_ID, etab_name' );
		$SQL->FROM_add( 'LEFT JOIN T_establishments ON link_etab_ID = etab_ID' );
		$order_fields[] = 'etab_name';
	}

	if( isset( $db_aliases['T_firms'] ) )
	{	// This application handles firms:
		$SQL->SELECT_add( 'firm_ID, firm_name' );
		$SQL->FROM_add( 'LEFT JOIN T_firms ON link_firm_ID = firm_ID' );
		$order_fields[] = 'firm_name';
	}

	if( isset( $db_aliases['T_tasks'] ) )
	{	// This application handles tasks:
		$SQL->SELECT_add( 'tsk_ID, tsk_title' );
		$SQL->FROM_add( 'LEFT JOIN T_tasks ON link_dest_item_ID = tsk_ID' );
		$order_fields[] = 'tsk_title';
	}

	$SQL->WHERE( 'link_item_ID = '.$edited_Item->ID );

	$Results = & new Results( $SQL->get(), 20, 'link_' );

	$Results->title = T_('Linked to...');

	/**
	 * Display link
	 */
	function display_link( & $row )
	{
		if( !empty($row->cont_ID) )
		{
			return T_('Contact').': <a href="'.regenerate_url( 'action,pos_ID,cont_ID', 'cont_ID='.$row->cont_ID, 'contacts.php' )
						.'" title="'.T_('View this contact...').'">'.$row->cont_firstname.' '.$row->cont_lastname.'</a>';
		}
		elseif( !empty($row->etab_ID) )
		{
			return T_('Establishment').': <a href="'.regenerate_url( 'action,etab_ID', 'etab_ID='.$row->etab_ID, 'establishments.php' )
						.'" title="'.T_('View this establishment...').'">'.$row->etab_name.'</a>';
		}
		elseif( !empty($row->firm_ID) )
		{
			return T_('Firm').': <a href="'.regenerate_url( 'action,firm_ID', 'firm_ID='.$row->firm_ID, 'firms.php' )
						.'" title="'.T_('View this firm...').'">'.$row->firm_name.'</a>';
		}
		elseif( !empty($row->tsk_ID) )
		{
			return T_('Task').': <a href="'.regenerate_url( 'action,tsk_ID', 'tsk_ID='.$row->tsk_ID, 'tasks.php' )
						.'" title="'.T_('View this task...').'">'.$row->tsk_title.'</a>';
		}
		elseif( !empty($row->file_ID) )
		{
			// Instantiate a File object for this line:
			$current_File = & new File( $row->file_root_type, $row->file_root_ID, $row->file_path );
			// Flow meta data into File object:
			$current_File->load_meta( false, $row );

			// File relative path & name:
			return T_('File').': '.$current_File->url().' - '.$current_File->dget('title');
		}

		return '?';
	}

	$Results->cols[] = array(
							'th' => T_('Destination'),
							'order' => implode( ', ', $order_fields ),
							'td' => '%display_link( {row} )%',
						);

 	$Results->cols[] = array(
							'th' => T_('Unlink'),
							'td' => action_icon( T_('Delete this link!'), 'unlink',
                        '%regenerate_url( \'tsk_ID,action\', \'link_ID=$link_ID$&amp;action=delete_link\')%' ),
						);

	$Results->global_icon( T_('Link an existing firm...'), 'link',
													'?tsk_ID='.$edited_Item->ID.'&amp;action=link_firm', T_('Firm') );
	$Results->global_icon( T_('Link an existing establishment...'), 'link',
													'?tsk_ID='.$edited_Item->ID.'&amp;action=link_establishment', T_('Establishment') );
	$Results->global_icon( T_('Link an existing contact...'), 'link',
													'?tsk_ID='.$edited_Item->ID.'&amp;action=link_contact', T_('Contact') );
	$Results->global_icon( T_('Link an existing task...'), 'link',
													'?tsk_ID='.$edited_Item->ID.'&amp;action=link_task', T_('Task') );
	$Results->global_icon( T_('Link a file...'), 'link',
													'files.php?fm_mode=link_item&amp;item_ID='.$edited_Item->ID, T_('File') );

	$Results->display();

?>