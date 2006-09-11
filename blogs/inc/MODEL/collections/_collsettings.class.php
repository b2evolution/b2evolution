<?php
/**
 * This file implements the CollectionSettings class which handle coll_ID/name/value triplets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/../settings/_abstractsettings.class.php';

/**
 * Class to handle the settings for collections
 *
 * @package evocore
 */
class CollectionSettings extends AbstractSettings
{
	/**
	 * The default settings to use, when a setting is not defined in the database.
	 *
	 * @access protected
	 */
	var $_defaults = array(
			'new_feedback_status' => 'draft',  // 'draft', 'published' or 'deprecated'
			'chapter_links' => 'param_num',		 // 'param_num', 'subchap', 'chapters'
		);


	/**
	 * Constructor
	 */
	function CollectionSettings()
	{
		parent::AbstractSettings( 'T_coll_settings', array( 'cset_coll_ID', 'cset_name' ), 'cset_value', 1 );
	}
}


/*
 * $Log$
 * Revision 1.3  2006/09/11 19:36:58  fplanque
 * blog url ui refactoring
 *
 * Revision 1.2  2006/04/21 23:14:16  blueyed
 * Add Messages according to Comment's status.
 *
 * Revision 1.1  2006/04/20 16:31:30  fplanque
 * comment moderation (finished for 1.8)
 *
 */
?>