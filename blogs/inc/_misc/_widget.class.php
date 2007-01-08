<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Widget class which provides an interface to widget methods for other classes.
 *
 * It provides a method {@link replace_vars()} that can be used to replace object properties in given strings.
 * You can also register global action icons.
 *
 * @package evocore
 * @abstract
 */
class Widget
{
	/**
	 * Title of the widget (to be displayed)
	 */
	var $title;

	/**
	 * List of registered global action icons that get substituted through '$global_icons$'.
	 * @see global_icon()
	 */
	var $global_icons = array();

	/**
	 * Registers a global action icon
	 *
	 * @param string TITLE text (IMG and A link)
	 * @param string icon code for {@link get_icon()}
	 * @param string URL to link to
	 * @param integer 1-5: weight of the icon. the icon will be displayed only if its weight is >= than the user setting threshold
	 * @param integer 1-5: weight of the word. the word will be displayed only if its weight is >= than the user setting threshold
	 * @param array Additional attributes to the A tag. See {@link action_icon()}.
	 */
	function global_icon( $title, $icon, $url, $word = '', $icon_weight = 3, $word_weight = 2, $link_attribs = array( 'class'=>'action_icon' ) )
	{
		$this->global_icons[] = array(
			'title' => $title,
			'icon'  => $icon,
			'url'   => $url,
			'word'  => $word,
			'icon_weight'  => $icon_weight,
			'word_weight'  => $word_weight,
			'link_attribs' => $link_attribs );
	}


	/**
	 * Replaces $vars$ with appropriate values.
	 *
	 * You can give an alternative string to display, if the substituted variable
	 * is empty, like:
	 * <code>$vars "Display if empty"$</code>
	 *
	 * @param string template
	 * @param array optional params that are put into {@link $this->params}
	 *              to be accessible by derived replace_callback() methods
	 * @return string The substituted string
	 */
	function replace_vars( $template, $params = NULL )
	{
		if( !is_null( $params ) )
		{
			$this->params = $params;
		}

		return preg_replace_callback(
			'~\$([a-z_]+)(?:\s+"([^"]*)")?\$~', # pattern
			array( $this, 'replace_callback_wrapper' ), # callback
			$template );
	}


	/**
	 * This is an additional wrapper to {@link replace_vars()} that allows to react
	 * on the return value of it.
	 *
	 * Used by replace_callback()
	 *
	 * @param array {@link preg_match() preg match}
	 * @return string
	 */
	function replace_callback_wrapper( $match )
	{
		// Replace the variable with its content (which will be computed on the fly)
		$r = $this->replace_callback( $match );

		if( empty($r) )
		{	// Empty result
			if( !empty($match[2]) )
			{
				return $match[2]; // "display if empty"
			}

			// return $match[1];
		}
		return $r;
	}


	/**
	 * Callback function used to replace only necessary values in template.
	 *
	 * This gets used by {@link replace_vars()} to replace $vars$.
	 *
	 * @param array {@link preg_match() preg match}. Index 1 is the template variable.
	 * @return string to be substituted
	 */
	function replace_callback( $matches )
	{
		//echo $matches[1];
		switch( $matches[1] )
		{
			case 'global_icons' :
				// Icons for the whole result set:
				return $this->gen_global_icons();

			case 'title':
				// Results title:
				return $this->title;

			default:
				return $matches[1];
		}
	}


	/**
	 * Generate img tags for registered icons, through {@link global_icon()}.
	 *
	 * This is used by the default callback to replace '$global_icons$'.
	 */
	function gen_global_icons()
	{
		$r = '';

		foreach( $this->global_icons as $icon_params )
		{
			$r .= action_icon( $icon_params['title'], $icon_params['icon'], $icon_params['url'], $icon_params['word'],
						$icon_params['icon_weight'], $icon_params['word_weight'], $icon_params['link_attribs'] );
		}

		return $r;
	}

}


/**
 * Class Table
 * @package evocore
 */
class Table extends Widget
{
	/**
	 * Display parameters
	 */
	var $params = NULL;

	/**
	 * Total number of pages
	 */
	var $total_pages = 1;

	/**
	 * Number of cols.
	 */
	var $nb_cols;


	/**
	 * Initialize things in order to be ready for displaying.
	 *
	 * Lazy fills $this->params
	 */
	function display_init( $display_params = NULL )
	{
		global $AdminUI;
		if( empty( $this->params ) && isset( $AdminUI ) )
		{ // Use default params from Admin Skin:
			$this->params = $AdminUI->get_template( 'Results' );
		}

		// Make sure we have display parameters:
		if( !is_null($display_params) )
		{ // Use passed params:
			//$this->params = & $display_params;
			if( !empty( $this->params ) )
			{
				$this->params = array_merge( $this->params, $display_params );
			}
			else
			{
				$this->params = & $display_params;
			}
		}
	}


	/**
	 * Display list/table start.
	 *
	 * Typically outputs UL or TABLE tags.
	 */
	function display_list_start()
	{
		if( $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_start'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_start'];
		}
	}


	/**
	 * Display list/table end.
	 *
	 * Typically outputs </ul> or </table>
	 */
	function display_list_end()
	{
		if( $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_end'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_end'];
		}
	}


	/**
	 * Display list/table head.
	 *
	 * This includes list head/title and column headers.
	 * EXPERIMENTAL: also dispays <tfoot>
	 *
	 * @access protected
	 */
	function display_head()
	{
		echo $this->params['head_start'];

		// DISPLAY TITLE:
		if( isset($this->title) )
		{ // A title has been defined for this result set:
			echo $this->replace_vars( $this->params['head_title'] );
		}

 		// DISPLAY COLUMN HEADERS:
		if( isset( $this->cols ) )
		{
		}

		echo $this->params['head_end'];


		// Experimental:
		echo $this->params['tfoot_start'];
		echo $this->params['tfoot_end'];
	}


	/**
	 * Widget callback for template vars.
	 *
	 * This allows to replace template vars, see {@link Widget::replace_callback()}.
	 *
	 * @return string
	 */
	function replace_callback( $matches )
	{
		// echo '['.$matches[1].']';
		switch( $matches[1] )
		{
			case 'nb_cols' :
				// Number of columns in result:
				if( !isset($this->nb_cols) )
				{
					$this->nb_cols = count($this->cols);
				}
				return $this->nb_cols;

			default :
				return parent::replace_callback( $matches );
		}
	}

}

/*
 * $Log$
 * Revision 1.11  2007/01/08 23:44:19  fplanque
 * inserted Table widget
 * WARNING: this has nothing to do with ComponentWidgets...
 * (except that I'm gonna need the Table Widget when handling the ComponentWidgets :>
 *
 * Revision 1.10  2006/11/26 01:42:10  fplanque
 * doc
 *
 */
?>