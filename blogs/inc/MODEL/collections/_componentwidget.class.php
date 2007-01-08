<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class ComponentWidget
{
	var $type;
	var $code;
	var $params;


	/**
	 * Constructor
	 */
	function ComponentWidget( $db_row = NULL, $type = 'core', $code = NULL, $params = array() )
	{
		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->type = $type;
			$this->code = $code;
			$this->params = $params;
		}
		else
		{	// Wa are loading an object:
			$this->type = $db_row->wi_type;
			$this->code = $db_row->wi_code;
			$this->params = $db_row->wi_params;
		}
	}

	/**
	 * Get name of widget
	 */
	function get_name()
	{
		if( $this->type != 'core' )
		{
			return 'Not handled yet.';
		}

		switch( $this->code )
		{
			case 'coll-title'    : return T_('Blog Title');
      case 'coll-tagline'  : return T_('Blog Tagline');
      case 'coll-longdesc' : return T_('Blog Long Description');
		}

		return T_('Unknown');
	}
}


/*
 * $Log$
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>