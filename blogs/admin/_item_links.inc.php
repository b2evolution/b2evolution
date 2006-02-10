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

	if( isset( $EvoConfig->DB['aliases']['T_contacts'] ) )
	{	// This application handles contacts:
		$SQL->SELECT_add( ', cont_ID, cont_firstname, cont_lastname' );
		$SQL->FROM_add( 'LEFT JOIN T_contacts ON link_cont_ID = cont_ID' );
		$order_fields[] = 'cont_lastname, cont_firstname';
	}

	if( isset( $EvoConfig->DB['aliases']['T_establishments'] ) )
	{	// This application handles estabs:
		$SQL->SELECT_add( ', etab_ID, etab_name' );
		$SQL->FROM_add( 'LEFT JOIN T_establishments ON link_etab_ID = etab_ID' );
		$order_fields[] = 'etab_name';
	}

	if( isset( $EvoConfig->DB['aliases']['T_firms'] ) )
	{	// This application handles firms:
		$SQL->SELECT_add( ', firm_ID, firm_name' );
		$SQL->FROM_add( 'LEFT JOIN T_firms ON link_firm_ID = firm_ID' );
		$order_fields[] = 'firm_name';
	}

	// Linked items:
	$SQL->SELECT_add( ', itm_ID, itm_title' );
	$SQL->FROM_add( 'LEFT JOIN T_items ON link_dest_itm_ID = itm_ID' );
	$order_fields[] = 'itm_title';
	}

	$SQL->WHERE( 'link_itm_ID = '.$edited_Item->ID );

	$Results = & new Results( $SQL->get(), 'link_' );

	$Results->title = T_('Linked to...');

	/*
	 * TYPE
	 */
	function display_type( & $row )
	{
		if( !empty($row->cont_ID) )
		{
			return T_('Contact');
		}
		elseif( !empty($row->etab_ID) )
		{
			return T_('Establishment');
		}
		elseif( !empty($row->firm_ID) )
		{
			return T_('Firm');
		}
		elseif( !empty($row->itm_ID) )
		{
			return T_('Item');
		}
		elseif( !empty($row->file_ID) )
		{
			return T_('File');
		}

		return '?';
	}
	$Results->cols[] = array(
							'th' => T_('Type'),
							'th_start' => '<th class="firstcol shrinkwrap">',
							'order' => implode( ', ', $order_fields ),
							'td_start' => '<td class="firstcol shrinkwrap">',
							'td' => '%display_type( {row} )%',
						);


	/*
	 * Sub Type column
	 */
	function display_subtype( & $row )
	{
		if( !empty($row->file_ID) )
		{
			global $current_File;
			// Instantiate a File object for this line:
			$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY!
			// Flow meta data into File object:
			$current_File->load_meta( false, $row );

			// File type:
			return $current_File->get_view_link( $current_File->get_icon(), T_('Let browser handle this file!')  ).' '.$current_File->get_type();
		}
	}
	$Results->cols[] = array(
							'th' => T_('Sub-Type'),
							'td_start' => '<td class="shrinkwrap">',
							'td' => '%display_subtype( {row} )%',
						);


	/*
	 * LINK column
	 */
	function display_link( & $row )
	{
		if( !empty($row->itm_ID) )
		{
			return '<a href="'.regenerate_url( 'action,itm_ID', 'itm_ID='.$row->itm_ID, 'tasks.php' )
						.'" title="'.T_('View this item...').'">'.$row->itm_title.'</a>';
		}
		elseif( !empty($row->file_ID) )
		{
			global $current_File, $edited_Item;

			// File relative path & name:
			return $current_File->edit_link( '&amp;fm_mode=link_item&amp;itm_ID='.$edited_Item->ID ).'<span class="filemeta"> - '.$current_File->dget('title').'</span>';
		}

		return '?';
	}
	$Results->cols[] = array(
							'th' => T_('Destination'),
							'td' => '%display_link( {row} )%',
						);

	if( $edit_allowed )
	{	// Check that we have permission to edit item:
		function file_actions( $link_ID )
		{
			global $current_File, $edited_Item;

			$r = '';

			if( isset($current_File) )
			{
				$title = T_('Locate this file!');
				$r = $current_File->edit_link( '&amp;fm_mode=link_item&amp;itm_ID='.$edited_Item->ID, get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title ).' ';
			}

			return $r.action_icon( T_('Delete this link!'), 'unlink',
		                      regenerate_url( 'itm_ID,action', "link_ID=$link_ID&amp;action=delete_link" ) );
		}
		$Results->cols[] = array(
								'th' => T_('Actions'),
								'td_start' => '<td class="lastcol shrinkwrap">',
								'td' => '%file_actions( #link_ID# )%',
							);
	}

	if( $current_User->check_perm( 'files', 'view' ) )
	{
		$Results->global_icon( T_('Link a file...'), 'link',
														'files.php?fm_mode=link_item&amp;item_ID='.$edited_Item->ID, T_('File') );
	}

	$Results->display();

?>