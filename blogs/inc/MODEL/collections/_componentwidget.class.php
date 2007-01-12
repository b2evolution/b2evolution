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
class ComponentWidget extends DataObject
{
	var $coll_ID;
	/**
	 * Container name
	 */
	var $sco_name;
	var $order;
	var $type;
	var $code;
	var $params;


	/**
	 * Constructor
	 */
	function ComponentWidget( $db_row = NULL, $type = 'core', $code = NULL, $params = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_widget', 'wi_', 'wi_ID' );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'type', $type );
			$this->set( 'code', $code );
			// $this->set( 'params', $params );
		}
		else
		{	// Wa are loading an object:
			$this->ID       = $db_row->wi_ID;
			$this->coll_ID  = $db_row->wi_coll_ID;
			$this->sco_name = $db_row->wi_sco_name;
			$this->type     = $db_row->wi_type;
			$this->code     = $db_row->wi_code;
			$this->params   = $db_row->wi_params;
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
			case 'coll_title'    : return T_('Blog Title');
      case 'coll_tagline'  : return T_('Blog Tagline');
      case 'coll_longdesc' : return T_('Blog Long Description');
		}

		return T_('Unknown');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;

		// Customize params to the current widget:
		$params = str_replace( '$wi_class$', 'widget_'.$this->type.'_'.$this->code, $params );

		echo $params['block_start'];

		if( $this->type != 'core' )
		{
			echo 'Not handled yet.';
		}
		else
		{
			switch( $this->code )
			{
				case 'coll_title':
					// fp> TODO: replace HTML by params (fp; will be done shortly)
					echo $params['block_title_start'];
					echo '<a href="'.$Blog->get( 'url', 'raw' ).'">';
					$Blog->disp( 'name', 'htmlbody' );
					echo '</a>';
					echo $params['block_title_end'];
					break;

	      case 'coll_tagline':
					// fp> TODO: replace HTML by params (fp; will be done shortly)
					$Blog->disp( 'tagline', 'htmlbody' );
					break;

	      case 'coll_longdesc':
					// fp> TODO: replace HTML by params (fp; will be done shortly)
					echo '<p>';
					$Blog->disp( 'longdesc', 'htmlbody' );
					echo '</p>';
					break;

				default:
					echo T_('Unknown');
			}
		}

		echo $params['block_end'];
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		$order_max = $DB->get_var(
			'SELECT MAX(wi_order)
				 FROM T_widget
				WHERE wi_coll_ID = '.$this->coll_ID.'
					AND wi_sco_name = '.$DB->quote($this->sco_name), 0, 0, 'Get current max order' );

		$this->set( 'order', $order_max+1 );

		$res = parent::dbinsert();

		$DB->commit();

		return $res;
	}
}


/*
 * $Log$
 * Revision 1.5  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.4  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 *
 * Revision 1.3  2007/01/11 02:57:25  fplanque
 * implemented removing widgets from containers
 *
 * Revision 1.2  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>