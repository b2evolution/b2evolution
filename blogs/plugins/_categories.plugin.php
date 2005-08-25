<?php
/**
 * This file implements the Categories plugin.
 *
 * Displays a list of categories (chapters and subchapters) for the blog.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


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
	var $help_url = 'http://b2evolution.net/';


	/**
	 * Constructor
	 *
	 * {@internal categories_plugin::categories_plugin(-)}}
	 */
	function categories_plugin()
	{
		$this->short_desc = T_('This skin tag displays the list of avilable categories for the blog.');
		$this->long_desc = T_('Categories are indeed chapters and sub-chapters in the blog.');
	}


 	/**
	 * Event handler: SkinTag
	 *
	 * {@internal categories_plugin::SkinTag(-)}}
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'title' : (Default: '<h3>'.T_('Categories').'</h3>')
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 *                - 'context_isolation' : what params need override when changing date/range (Default: 'm,w,p,title,unit,dstart' )
	 *                - 'form' : true|false (default: false)
	 *                - 'list_start' : (Default '<ul>')
	 *                - 'list_end' : (Default '</ul>')
	 *                - 'line_start' : (Default '<li>')
	 *                - 'line_end' : (Default '</li>')
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
	 	global $cache_categories;
		/**
		 * @todo get rid of these globals:
		 */
		global $blog, $cat_modifier, $pagenow;

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


		// START DISPLAY:
		echo $params['block_start'];

		echo $params['title'];

		if( $blog > 1 )
		{ // We want to display cats for one blog
			echo $params['list_start'];

			if( $params['option_all'] )
			{	// We want to display a link to all cats:
				echo $this->params['line_start'].'<a href="';
				if( $this->params['link_type'] == 'context' )
				{	// We want to preserve current browsing context:
					echo regenerate_url( 'cats,catsel' );
				}
				else
				{
					echo get_bloginfo('blogurl');
				}
				echo '">'.$params['option_all'].'</a>';
				echo $this->params['line_end'];
			}

			echo cat_children( $cache_categories, $blog, NULL,
							array( $this, 'callback_before_first' ), array( $this, 'callback_before_each' ),
							array( $this, 'callback_after_each' ), array( $this, 'callback_after_last' ), 0 );
			echo $params['list_end'];
		}
		else
		{ // We want to display cats for all blogs
			echo $params['collist_start'];

			for( $curr_blog_ID=blog_list_start('stub');
						$curr_blog_ID!=false;
						 $curr_blog_ID=blog_list_next('stub') )
			{

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

				// run recursively through the cats
				echo $params['list_start'];
				echo cat_children( $cache_categories, $curr_blog_ID, NULL,
							array( $this, 'callback_before_first' ), array( $this, 'callback_before_each' ),
							array( $this, 'callback_after_each' ), array( $this, 'callback_after_last' ), 0 );
				echo $params['list_end'];
			}

			echo $params['collist_end'];
		}

		if( $params['form'] )
		{	// We want to add form fields:
		?>
			<span class="line">
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
		global $tab, $blog, $cat_array, $pagenow;
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

		$r .= '">'.$cat['cat_name'].'</a> <span class="notes">('.$cat['cat_postcount'].')</span>';

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
?>