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
	var $version = '1.9-dev';
	var $author = 'The b2evo Group';


	/**
	 * Init
	 */
	function PluginInit( & $params )
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
    // smpdawg - This change doesn't (shouldn't) affect appearance but it does fix an HTML validation issue with category lists
    // that occurs when you have sub-categories.  If a ul is to be nested in another ul it must be inside an li.
		if(!isset($params['group_start'])) $params['group_start'] = '<li style="list-style: none;"><ul>';
		if(!isset($params['group_end'])) $params['group_end'] = "</ul></li>\n";

		// This is what will enclose the global list if several blogs are listed on the same page:
		if(!isset($params['collist_start'])) $params['collist_start'] = '';
		if(!isset($params['collist_end'])) $params['collist_end'] = "\n";

		// This is what will separate blogs/collections when several of them are listed on the same page:
		if(!isset($params['coll_start'])) $params['coll_start'] = '<h4>';
		if(!isset($params['coll_end'])) $params['coll_end'] = "</h4>\n";


		if(!isset($params['option_all'])) $params['option_all'] = T_('All');


		// Save params for others functions:
		$this->params = $params;


		/**
		 * @var ChapterCache
		 */
		$ChapterCache = & get_Cache( 'ChapterCache' );

		$callbacks = array(
			'line' 			 	 => array( $this, 'cat_line' ),
			'no_children'  => array( $this, 'cat_no_children' ),
			'before_level' => array( $this, 'cat_before_level' ),
			'after_level'	 => array( $this, 'cat_after_level' )
		);


		// START DISPLAY:
		echo $params['block_start'];

		echo $params['title'];

		if( $blog > 1 )
		{ // ____________________ We want to display cats for ONE blog ____________________
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

			$r = $tmp_disp . $ChapterCache->recurse( $callbacks, $blog );

			if( ! empty($r) )
			{
				echo $params['list_start'];
				echo $r;
				echo $params['list_end'];
			}
		}
		else
		{ // ____________________ We want to display cats for ALL blogs ____________________

			// Make sure everything is loaded at once (vs multiple queries)
			$ChapterCache->load_all();

			echo $params['collist_start'];

			for( $curr_blog_ID=blog_list_start();
						$curr_blog_ID!=false;
						 $curr_blog_ID=blog_list_next() )
			{
				if( ! blog_list_iteminfo('disp_bloglist', false) )
				{ // Skip Blogs that should not get displayed in public blog list
					continue;
				}

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

				$r = $ChapterCache->recurse( $callbacks, $curr_blog_ID );

				if( ! empty($r) )
				{
					echo $params['list_start'];
					echo $r;
					echo $params['list_end'];
				}
			}

			echo $params['collist_end'];
		}

		if( $params['form'] )
		{	// We want to add form fields:
		?>
			<div class="line">
				<input type="radio" name="cat" value="" id="catANY" class="radio" <?php if( $cat_modifier != '-' && $cat_modifier != '*' ) echo 'checked="checked" '?> />
				<label for="catANY"><?php echo T_('ANY') ?></label>
			</div>
			<div class="line">
				<input type="radio" name="cat" value="-" id="catANYBUT" class="radio" <?php if( $cat_modifier == '-' ) echo 'checked="checked" '?> />
				<label for="catANYBUT"><?php echo T_('ANY BUT') ?></label>
			</div>
			<div class="line">
				<input type="radio" name="cat" value="*" id="catALL" class="radio" <?php if( $cat_modifier == '*' ) echo 'checked="checked" '?> />
				<label for="catALL"><?php echo T_('ALL') ?></label>
			</div>
		<?php
		}

		echo $params['block_end'];

		return true;
	}


	/**
	 * Generate category line when it has children
	 *
	 * @param Chapter generic category we want to display
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_line( $Chapter, $level )
	{
		$r = $this->params['line_start'];

		if( $this->params['form'] )
		{	// We want to add form fields:
			global $cat_array;
			$r .= '<label><input type="checkbox" name="catsel[]" value="'.$Chapter->ID.'" class="checkbox"';
			if( in_array( $Chapter->ID, $cat_array ) )
			{ // This category is in the current selection
				$r .= ' checked="checked"';
			}
			$r .= ' /> ';
		}

		$r .= '<a href="';

		if( $this->params['link_type'] == 'context' )
		{	// We want to preserve current browsing context:
			$r .= regenerate_url( 'cats,catsel', 'cat='.$Chapter->ID );
		}
		else
		{
			$r .= $Chapter->get_permanent_url();
		}

		$r .= '">'.$Chapter->dget('name').'</a>';

		if( $this->params['form'] )
		{	// We want to add form fields:
			$r .= '</label>';
		}

		$r .= $this->params['line_end'];

		return $r;
	}


	/**
	 * Generate category line when it has no children
	 *
	 * @param Chapter generic category we want to display
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_no_children( $Chapter, $level )
	{
		return '';
	}


	/**
	 * Generate code when entering a new level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_before_level( $level )
	{
		$r = '';
		if( $level > 0 ) $r .= $this->params['group_start'];
		return $r;
	}

	/**
	 * Generate code when exiting from a level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_after_level( $level )
	{
		$r = '';
		if( $level > 0 ) $r .= $this->params['group_end'];
		return $r;
	}
}


/*
 * $Log$
 * Revision 1.28  2006/11/16 23:48:56  blueyed
 * Use div.line instead of span.line as element wrapper for XHTML validity
 *
 * Revision 1.27  2006/09/13 15:48:41  smpdawg
 * Minor change
 *
 * Revision 1.26  2006/09/11 20:53:33  fplanque
 * clean chapter paths with decoding, finally :)
 *
 * Revision 1.25  2006/09/11 19:34:34  fplanque
 * fully powered the ChapterCache
 *
 * Revision 1.24  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.23  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.22  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
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