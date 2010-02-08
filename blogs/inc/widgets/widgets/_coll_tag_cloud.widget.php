<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_tag_cloud_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_tag_cloud_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_tag_cloud' );
	}


	/**
	 * Load params
	 */
	function load_from_Request()
	{
		parent::load_from_Request();

		// SPECIAL treatments:
		if( empty($this->param_array['tag_separator']) )
		{	// Default name, don't store:
			$this->set( 'tag_separator', ' ' );
		}
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Tag cloud');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Cloud of all tags; click filters blog on selected tag.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
					'type' => 'text',
					'label' => T_('Block title'),
					'defaultvalue' => T_('Tag cloud'),
					'maxlength' => 100,
				),
			'max_tags' => array(
					'type' => 'integer',
					'label' => T_('Max # of tags'),
					'size' => 4,
					'defaultvalue' => 50,
				),
			'tag_separator' => array(
					'type' => 'text',
					'label' => T_('Tag separator'),
					'defaultvalue' => ' ',
					'maxlength' => 100,
				),
			'tag_min_size' => array(
					'type' => 'integer',
					'label' => T_('Min size'),
					'size' => 3,
					'defaultvalue' => 8,
				),
			'tag_max_size' => array(
					'type' => 'integer',
					'label' => T_('Max size'),
					'size' => 3,
					'defaultvalue' => 22,
				),
			'tag_ordering' => array(
					'type' => 'select',
					'label' => T_('Ordering'),
					'options' => array( 'ASC'  => T_('Ascending'), 'RAND' => T_('Random') ),
					'defaultvalue' => 'ASC',
					'note' => T_( 'How to sort the tag cloud.' ),
				),
			'filter_list' => array(
					'type' => 'textarea',
					'label' => T_('Filter tags'),
					'note' => T_('This is a comma separated list of tags to ignore.'),
					'size' => 40,
					'rows' => 2,
				),
			), parent::get_param_definitions( $params )	);

		// add limit default 100

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		global $Blog;

		if( empty($Blog) )
		{	// Nothing to display
			return;
		}

		global $DB, $localtimenow;

// fp> verrry dirty and params; TODO: clean up
// dh> oddly, this appears to not get cached by the query cache. Have experimented a bit, but not found the reason.
//     It worked locally somehow, but not live.
//     This takes up to ~50% (but more likely 15%) off the total SQL time. With the query being cached, it would be far better.
		// get list of relevant blogs
		$where_cats = trim($Blog->get_sql_where_aggregate_coll_IDs('cat_blog_ID'));

		// build query, only joining categories, if not using all.
		$sql = "SELECT LOWER(tag_name) AS tag_name, post_datestart, COUNT(DISTINCT itag_itm_ID) AS tag_count
			  FROM T_items__tag INNER JOIN T_items__itemtag ON itag_tag_ID = tag_ID";
		if( $where_cats != '1' )
		{ // we have to join the cats
			$sql .= "
			 INNER JOIN T_postcats ON itag_itm_ID = postcat_post_ID
			 INNER JOIN T_categories ON postcat_cat_ID = cat_ID";
		}
		$sql .= "
			 INNER JOIN T_items__item ON itag_itm_ID = post_ID
			 WHERE $where_cats
			   AND post_status = 'published' AND post_datestart < '".remove_seconds($localtimenow)."'";
		
		if( $this->disp_params['filter_list'] )
		{	// Filter tags
			$filter_list = explode( ',', $this->disp_params['filter_list'] ) ;
			
			$filter_tags = array();
			foreach( $filter_list as $l_tag )
			{
				$filter_tags[] = '"'.$DB->escape(trim($l_tag)).'"';
			}
			
			$sql .= ' AND tag_name NOT IN ('.implode(', ', $filter_tags).')';
		}
		
		$sql .= "
			 GROUP BY tag_name
			 ORDER BY tag_count DESC
			 LIMIT ".$this->disp_params['max_tags'];

		$results = $DB->get_results( $sql, OBJECT, 'Get tags' );

		if( empty($results) )
		{	// No tags!
			return;
		}

		$max_count = $results[0]->tag_count;
		$min_count = $results[count($results)-1]->tag_count;
		$count_span = max( 1, $max_count - $min_count );
		$max_size = $this->disp_params['tag_max_size'];
		$min_size = $this->disp_params['tag_min_size'];
		$size_span = $max_size - $min_size;

		if ($this->disp_params['tag_ordering'] == 'ASC')
		{
			usort($results, array($this, 'tag_cloud_cmp'));
		}
		else if ($this->disp_params['tag_ordering'] == 'RAND')
		{
			shuffle( $results );
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['tag_cloud_start'];
		$count = 0;
		foreach( $results as $row )
		{
			if( $count > 0 )
			{
				echo $this->disp_params['tag_separator'];
			}
			// If there's a space in the tag name, quote it:
			$tag_name_disp = strpos($row->tag_name, ' ')
				? '&laquo;'.format_to_output($row->tag_name, 'htmlbody').'&raquo;'
				: format_to_output($row->tag_name, 'htmlbody');
			$size = floor( $row->tag_count * $size_span / $count_span + $min_size );

			echo $Blog->get_tag_link( $row->tag_name, $tag_name_disp, array(
				'style' => 'font-size: '.$size.'pt;',
				'title' => sprintf( T_('%d posts'), $row->tag_count ) ) );
			$count++;
		}
		echo $this->disp_params['tag_cloud_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	function tag_cloud_cmp($a, $b)
	{
		return strcasecmp($a->tag_name, $b->tag_name);
	}


	/**
	 * May be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @todo dh> this needs a custom implementation I believe.
	 *           It could depend on tag_ID_any (once tags have an ID)
	 *           or just the list of blogs (cont_coll_ID_*)?
	 * fp> I don't understand what you mean.
	 *
	 * @return array of keys this widget depends on
	 *
	function get_cache_keys()
	{
		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'item_ID' => 'any',							// doc???
			);
	}
	*/
}


/*
 * $Log$
 * Revision 1.29  2010/02/08 17:54:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.28  2010/01/08 22:24:18  blueyed
 * tagcloud widget: Trans fix.
 *
 * Revision 1.27  2010/01/08 20:21:17  sam2kb
 * Filter unwanted tags
 *
 * Revision 1.26  2009/12/22 08:02:12  fplanque
 * doc
 *
 * Revision 1.25  2009/12/22 03:31:37  blueyed
 * todo about tag widget block cache keys
 *
 * Revision 1.24  2009/10/28 13:39:07  waltercruz
 * Random order to tag cloud widget
 *
 * Revision 1.23  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.22  2009/09/13 21:33:44  blueyed
 * coll_tag_cloud_Widget: optimizing tag query a bit. doc.
 *
 * Revision 1.21  2009/09/13 21:29:22  blueyed
 * MySQL query cache optimization: remove information about seconds from post_datestart and item_issue_date.
 *
 * Revision 1.20  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.19  2009/04/23 19:51:40  blueyed
 * Add Blog::get_tag_link, use it where appropriate.
 *
 * Revision 1.18  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.17  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.16  2009/01/23 00:10:45  blueyed
 * coll_tag_cloud.widget: quote tags with spaces in them
 *
 * Revision 1.15  2009/01/23 00:09:41  blueyed
 * coll_tag_cloud_Widget:
 *  - fix E_FATAL, when included twice (tag_cloud_cmp function would get
 *    defined twice)
 *  - Simplify tag_cloud_cmp by using strcasecmp
 *
 * Revision 1.14  2009/01/23 00:05:25  blueyed
 * Add Blog::get_sql_where_aggregate_coll_IDs, which adds support for '*' in list of aggregated blogs.
 *
 * Revision 1.13  2009/01/13 22:51:29  fplanque
 * rollback / normalized / MFB
 *
 * Revision 1.12  2008/09/09 06:03:31  fplanque
 * More tag URL options
 * Enhanced URL resolution for categories and tags
 *
 * Revision 1.11  2008/07/17 02:03:23  afwas
 * Bug fix in DB query. Won't show tags from not published posts and future posts. Also will no longer show tags twice.
 *
 * Revision 1.10  2008/05/06 23:35:47  fplanque
 * The correct way to add linebreaks to widgets is to add them to $disp_params when the container is called, right after the array_merge with defaults.
 *
 * Revision 1.8  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.7  2007/12/23 17:47:59  fplanque
 * fixes
 *
 * Revision 1.6  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.5  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.4  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
 *
 * Revision 1.3  2007/12/22 17:19:35  fplanque
 * bugfix
 *
 * Revision 1.2  2007/12/21 21:50:28  fplanque
 * tag cloud sizing
 *
 * Revision 1.1  2007/12/20 22:59:34  fplanque
 * TagCloud widget prototype
 *
 * Revision 1.1  2007/06/25 11:02:18  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>
