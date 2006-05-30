<?php
/**
 * This file implements the Categories plugin.
 *
 * Displays a list of categories (chapters and subchapters) for the blog.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Categories Plugin
 *
 * This plugin displays a list of categories (chapters and subchapters) for the blog.
 */
class categories_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name = 'Categories Skin Tag';
	var $code = 'evo_Cats';
	var $priority = 60;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';


	/**
	 * Constructor
	 */
	function categories_plugin()
	{
		$this->short_desc = T_('This skin tag displays the list of available categories for the blog.');
		$this->long_desc = T_('Categories are indeed chapters and sub-chapters in the blog.');

 		$this->dbtable = 'T_posts';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';
	}


 	/**
	 * Event handler: SkinTag
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'title' : (Default: '<h3>'.T_('Categories').'</h3>')
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 *                - 'context_isolation' : what params need override when changing date/range (Default: 'm,w,p,title,unit,dstart' )
	 *                - 'form' : true|false (default: false)
	 *                - 'list_start' : (Default '<ul>'), does not get displayed for empty lists
	 *                - 'list_end' : (Default '</ul>'), does not get displayed for empty lists
	 *                - 'line_start' : (Default '<li>')
	 *                - 'line_end' : (Default '</li>')
	 *                - 'group_start' : (Default '<ul>') - (for BLOG 1 Categories)
	 *                - 'group_end' : (Default "</ul>\n") - (for BLOG 1 Categories)
	 *                - 'collist_start' : (Default '') - (for BLOG 1 Categories)
	 *                - 'collist_end' : (Default "\n") - (for BLOG 1 Categories)
	 *                - 'coll_start' : (Default '<h4>') - (for BLOG 1 Categories)
	 *                - 'coll_end' : (Default "</h4>\n") - (for BLOG 1 Categories)
	 *                - 'option_all' : (Default T_('All'))
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
	 	global $cache_categories;
		/**
		 * @todo get rid of these globals:
		 */
		global $blog, $cat_modifier;

		/**
		 * Default params:
		 */
		// This is what will enclose the block in the skin:
		if(!isset($params['block_start'])) $params['block_start'] = '<div class="bSideItem">';
		if(!isset($params['block_end'])) $params['block_end'] = "</div>\n";

		// Title:
		if(!isset($params['title']))
			$params['title'] = '<h3>'.T_('Categories').'</h3>';

		// Link type:
		if(!isset($params['link_type'])) $params['link_type'] = 'canonic';
		// if(!isset($params['context_isolation'])) $params['context_isolation'] = 'm,w,p,title,unit,dstart';

		// Add form fields?:
		if(!isset($params['form'])) $params['form'] = false;


		// This is what will enclose the category list:
		if(!isset($params['list_start'])) $params['list_start'] = '<ul>';
		if(!isset($params['list_end'])) $params['list_end'] = "</ul>\n";

		// This is what will separate the category links:
		if(!isset($params['line_start'])) $params['line_start'] = '<li>';
		if(!isset($params['line_end'])) $params['line_end'] = "</li>\n";

		// This is what will enclose the sub chapter lists:
		if(!isset($params['group_start'])) $params['group_start'] = '<ul>';
		if(!isset($params['group_end'])) $params['group_end'] = "</ul>\n";

		// This is what will enclose the global list if several blogs are listed on the same page:
		if(!isset($params['collist_start'])) $params['collist_start'] = '';
		if(!isset($params['collist_end'])) $params['collist_end'] = "\n";

		// This is what will separate blogs/collections when several of them are listed on the same page:
		if(!isset($params['coll_start'])) $params['coll_start'] = '<h4>';
		if(!isset($params['coll_end'])) $params['coll_end'] = "</h4>\n";


 		if(!isset($params['option_all'])) $params['option_all'] = T_('All');


		// Save params for others functions:
		$this->params = $params;


		// make sure the caches are loaded:
		cat_query( $params['link_type'], $this->dbtable, $this->dbprefix, $this->dbIDname );


		// START DISPLAY:
		echo $params['block_start'];

		echo $params['title'];

		if( $blog > 1 )
		{ // We want to display cats for one blog
			$tmp_disp = '';

			if( $params['option_all'] )
			{	// We want to display a link to all cats:
				$tmp_disp .= $this->params['line_start'].'<a href="';
				if( $this->params['link_type'] == 'context' )
				{	// We want to preserve current browsing context:
					$tmp_disp .= regenerate_url( 'cats,catsel' );
				}
				else
				{
					$tmp_disp .= get_bloginfo('blogurl');
				}
				$tmp_disp .= '">'.$params['option_all'].'</a>';
				$tmp_disp .= $this->params['line_end'];
			}
			$tmp_disp .= cat_children( $cache_categories, $blog, NULL,
			                           array( $this, 'callback_before_first' ), array( $this, 'callback_before_each' ),
			                           array( $this, 'callback_after_each' ), array( $this, 'callback_after_last' ), 0 );
			if( ! empty($tmp_disp) )
			{
				echo $params['list_start'];
				echo $tmp_disp;
				echo $params['list_end'];
			}
		}
		else
		{ // We want to display cats for all blogs
			echo $params['collist_start'];

			for( $curr_blog_ID=blog_list_start();
						$curr_blog_ID!=false;
						 $curr_blog_ID=blog_list_next() )
			{
				if( ! blog_list_iteminfo('disp_bloglist', false) )
				{ // Skip Blogs that should not get displayed in public blog list
					continue;
				}

				// run recursively through the cats
				$cat_list = cat_children( $cache_categories, $curr_blog_ID, NULL,
							array( $this, 'callback_before_first' ), array( $this, 'callback_before_each' ),
							array( $this, 'callback_after_each' ), array( $this, 'callback_after_last' ), 0 );

/* Make this a param with default to OFF (NO Skip) because even if there are no cats, the blog name is a clickable root category itself
				if( empty( $cat_list ) )
				{ // Skip Blogs that have no categories!
					continue;
				}
*/

				echo $params['coll_start'];
				echo '<a href="';
				if( $this->params['link_type'] == 'context' )
				{	// We want to preserve current browsing context:
					echo regenerate_url( 'blog,cats,catsel', 'blog='.$curr_blog_ID );
				}
				else
				{
					blog_list_iteminfo('blogurl');
				}
				echo '">';
				blog_list_iteminfo('name');
				echo '</a>';
				echo $params['coll_end'];

				if( ! empty($cat_list) )
				{
					echo $params['list_start'];
					echo $cat_list;
					echo $params['list_end'];
				}
			}

			echo $params['collist_end'];
		}

		if( $params['form'] )
		{	// We want to add form fields:
		?>
			<span class="line"> <?php /* blueyed>> using div.line here makes them "blocks" in Konqueror/Safari(?) */ ?>
				<input type="radio" name="cat" value="" id="catANY" class="radio" <?php if( $cat_modifier != '-' && $cat_modifier != '*' ) echo 'checked="checked" '?> />
				<label for="catANY"><?php echo T_('ANY') ?></label>
			</span>
			<span class="line">
				<input type="radio" name="cat" value="-" id="catANYBUT" class="radio" <?php if( $cat_modifier == '-' ) echo 'checked="checked" '?> />
				<label for="catANYBUT"><?php echo T_('ANY BUT') ?></label>
			</span>
			<span class="line">
				<input type="radio" name="cat" value="*" id="catALL" class="radio" <?php if( $cat_modifier == '*' ) echo 'checked="checked" '?> />
				<label for="catALL"><?php echo T_('ALL') ?></label>
			</span>
		<?php
		}

		echo $params['block_end'];

		return true;
	}


	function callback_before_first( $parent_cat_ID, $level )
	{ // callback to start sublist
		$r = '';
		if( $level > 0 ) $r .= $this->params['group_start'];
		return $r;
	}


	function callback_before_each( $cat_ID, $level )
	{ // callback to display sublist element
		global $tab, $blog, $cat_array;
		$cat = get_the_category_by_ID( $cat_ID );
		$r = $this->params['line_start'];

		if( $this->params['form'] )
		{	// We want to add form fields:
			$r .= '<label><input type="checkbox" name="catsel[]" value="'.$cat_ID.'" class="checkbox"';
			if( in_array( $cat_ID, $cat_array ) )
			{ // This category is in the current selection
				$r .= ' checked="checked"';
			}
			$r .= ' /> ';
		}

		$r .= '<a href="';

		if( $this->params['link_type'] == 'context' )
		{	// We want to preserve current browsing context:
			$r .= regenerate_url( 'cats,catsel', 'cat='.$cat_ID );
		}
		else
		{
			$r .= url_add_param( get_bloginfo('blogurl'), 'cat='.$cat_ID );
		}

		$r .= '">'.format_to_output($cat['cat_name'], 'htmlbody').'</a> <span class="notes">('.$cat['cat_postcount'].')</span>';

		if( in_array( $cat_ID, $cat_array ) )
		{ // This category is in the current selection
			$r .= '*';
		}

		if( $this->params['form'] )
		{	// We want to add form fields:
			$r .= '</label>';
		}
		return $r;
	}


	function callback_after_each( $cat_ID, $level )
	{ // callback to display sublist element
		return $this->params['line_end'];
	}


	function callback_after_last( $parent_cat_ID, $level )
	{ // callback to end sublist
		$r = '';
		if( $level > 0 ) $r .= $this->params['group_end'];
		return $r;
	}

}


/*
 * $Log$
 * Revision 1.21  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.20  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.19  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
