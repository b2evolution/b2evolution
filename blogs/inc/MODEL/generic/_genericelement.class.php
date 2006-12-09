<?php
/**
 * This file implements the Generic Element class, which manages user groups.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

/**
 * User Element
 *
 * Generic Element of users with specific permissions.
 *
 * @package evocore
 */
class GenericElement extends DataObject
{
	/**
	 * Name of Generic Element
	 *
	 * @var string
	 * @access protected
	 */
	var $name;


	/**
	 * Constructor
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param object DB row
	 */
	function GenericElement( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::DataObject( $tablename, $prefix, $dbIDname );

		if( $db_row != NULL )
		{
			// echo 'Instanciating existing group';
			$this->ID = $db_row->$dbIDname;
			$this->name = $db_row->{$prefix.'name'};
		}

		$Debuglog->add( "Created element <strong>$this->name</strong>", 'dataobjects' );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{


		param_string_not_empty( $this->dbprefix.'name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		return ! param_errors_detected();
	}


	/**
	 * TODO
	 *
	 */
	function disp_form()
	{
		global $ctrl, $action, $edited_name_maxlen, $form_below_list;

		// Determine if we are creating or updating...
		$creating = is_create_action( $action );

		$Form = & new Form( NULL, 'form' );

		if( !$form_below_list )
		{ // We need to display a link to cancel editing:
			$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );
		}

		$Form->begin_form( 'fform', $creating ?  T_('New element') : T_('Element') );

		$Form->hidden( 'action', $creating ? 'create' : 'update' );

		$Form->hidden( 'ctrl', $ctrl );

		$Form->hiddens_by_key( get_memorized( 'action, ctrl' ) );

		$Form->text_input( $this->dbprefix.'name', $this->name, $edited_name_maxlen, T_('name'), '', array( 'required' => true ) );

		if( ! $creating ) $Form->hidden( $this->dbIDname, $this->ID );

		if( $creating )
		{
			$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
		}
		else
		{
			$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
		}
	}


	/**
	 * Template function: return name of item
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_name( $format = 'htmlbody' )
	{
		return $this->dget( 'name', $format );
	}

}

/*
 * $Log$
 * Revision 1.8  2006/12/09 01:55:35  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.7  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.6  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.5  2006/08/20 22:25:21  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.4  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.3  2006/06/13 21:49:15  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.2.2.1  2006/06/12 20:00:37  fplanque
 * one too many massive syncs...
 *
 * Revision 1.2  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.1  2006/04/14 19:32:53  fplanque
 * generic handlers (more integration to come)
 *
 * Revision 1.2  2006/03/12 23:08:57  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.8  2006/01/10 20:59:49  fplanque
 * minor / fixed internal sync issues @ progidistri
 *
 * Revision 1.7  2005/11/04 21:42:22  blueyed
 * Use setter methods to set parameter values! dataobject::set_param() won't pass the parameter to dbchange() if it is already set to the same member value.
 *
 * Revision 1.6  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.5  2005/05/16 15:17:13  fplanque
 * minor
 *
 * Revision 1.4  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.3  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.2  2004/12/21 21:22:46  fplanque
 * factoring/cleanup
 *
 * Revision 1.1  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 */
?>