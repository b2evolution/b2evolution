<?php
/**
 * This file implements the Admin UI class.
 * Admin skins should derive from this class and override {@link get_menu_template()}
 * for example.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE.
 *
 * @todo Refactor to allow easier contributions! (blueyed)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The general Admin UI class. It provides functions to handle the UI part of the
 * Backoffice.
 *
 * Admin skins should derive from this class and override {@link get_menu_template()}
 * for example.
 *
 * @package admin
 * @todo CODE DOCUMENTATION!!!
 */
class AdminUI_general
{
	/**
	 * The menus.
	 * @var array
	 */
	var $menus = array();

	/**
	 * List of the headlines to output.
	 * @var array
	 */
	var $headlines = array();

	/**
	 * The path of the selected menu.
	 * Use {@link get_path()} or {@link get_path_range()} to access it.
	 * Use {@link set_path()}, {@link add_path()} or {@link set_path_by_nr()} to set it.
	 * @var array
	 */
	var $path = array();

	/**
	 * The properties of the path entries.
	 * Use {@link get_prop_for_path()} or {@link get_properties_for_path()} to access it
	 * Use {@link set_path()}, {@link add_path()} or {@link set_path_by_nr()} to set it.
	 * @var array
	 */
	var $pathProps = array();

	/**
	 * Visual path seperator (used in html title, ..)
	 * @var string
	 */
	var $pathSeperator;

	/**
	 * The Logo for the admin (build in constructor).
	 * @var string
	 */
	var $admin_logo;

	/**
	 * The Logout/Exit-to-blogs links (build in constructor).
	 * @var string
	 */
	var $exit_links;

	/**
	 * The explicit title for the page.
	 * @var string
	 */
	var $title;

	/**
	 * The explicit title for the titlearea (<h1>).
	 * @var string
	 */
	var $title_titlearea;
	var $title_titlearea_appendix = '';


	/**
	 * Constructor.
	 */
	function AdminUI_general()
	{
		global $mode; // TODO: make it a real property
		global $htsrv_url, $baseurl;

		$this->mode = $mode;

		if( is_null($this->admin_logo) )
		{
			global $app_admin_logo;
			$this->admin_logo = $app_admin_logo;
		}

		if( is_null($this->exit_links) )
		{
			global $app_exit_links;
			$this->exit_links = $app_exit_links;
		}

		if( is_null($this->pathSeperator) )
		{
			global $admin_path_seprator;
			$this->pathSeperator = $admin_path_seprator;
		}

		$this->init_templates();
	}


	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
	}


	/**
	 * Get the <title> of the page.
	 *
	 * This is either {@link $title} or will be constructed from title/text properties
	 * of the path entries.
	 *
	 * @param boolean If true, the fallback will be in reversed order
	 * @return string
	 */
	function get_title( $reversedDefault = false )
	{
		if( isset($this->title) )
		{ // Explicit title has been set:
			return $this->title;
		}
		else
		{ // Fallback: implode title/text properties of the path
			$titles = $this->get_properties_for_path( $this->path, array( 'title', 'text' ) );
			if( $reversedDefault )
			{ // We have asked for reverse order of the path elements:
				$titles = array_reverse($titles);
			}
			return implode( $this->pathSeperator, $titles );
		}
	}


	/**
	 * Get the title for the titlearea (<h1>).
	 *
	 * @return string
	 */
	function get_title_for_titlearea()
	{
		if( ! isset( $this->title_titlearea ) )
		{ // Construct path:
			$titles = array();
			foreach( $this->path as $i => $lPath )
			{
				if( false !== ($title_text = $this->get_prop_for_path( $i, array( 'title', 'text' ) )) )
				{
					$titles[] = '<a href="'.$this->get_prop_for_path( $i, array( 'href' ) ).'">'.$title_text.'</a>';
				}
			}

			$this->title_titlearea = implode( $this->pathSeperator, $titles );
		}

		return $this->title_titlearea.$this->title_titlearea_appendix;
	}


	/**
	 * Append a string at the end of the existing titlearea.
	 *
	 * We actually keep the appended stuff separate from the main title, because the main title
	 * might in some occasions not be known immediately.
	 *
	 * @param string What to append to the titlearea
	 */
	function append_to_titlearea( $string )
	{
		$this->title_titlearea_appendix .= $this->pathSeperator.$string;
	}


	/**
	 * Get the title for HTML <title> tag.
	 *
	 * If no explicit title has been specified, auto construct one from path.
	 *
	 * @return string
	 */
	function get_html_title()
	{
		global $app_shortname;

		$r = $app_shortname.$this->pathSeperator;

		if( $htmltitle = $this->get_prop_for_node( $this->path, array( 'htmltitle' ) ) )
		{ // Explicit htmltitle set:
			$r .= $htmltitle;
		}
		else
		{	// No explicit title set, construct Title from path
			$r .= #preg_replace( '/:$/', '',
						$this->get_title()
						#)
						;
		}

		return $r;
	}


	/**
	 * Get a list of properties for a given path for a set of property names to check.
	 * The result is a list of properties for each node down the path.
	 *
	 * The property names must be given in $prop_by_ref, ordered by preference.
	 *
	 * @param string|array The path. See {@link get_node_by_path()}.
	 * @param array Alternative names of the property to receive (ordered by priority).
	 * @return array List of the properties.
	 */
	function get_properties_for_path( $path, $prop_by_ref )
	{
		if( !is_array($path) )
		{
			$path = array( $path );
		}
		$r = array();

		$prevPath = array();
		foreach( $path as $i => $lPath )
		{
			if( false !== ($prop = $this->get_prop_for_path( $i, $prop_by_ref )) )
			{
				$r[] = $prop;
			}

			$prevPath[] = $lPath;
		}

		return $r;
	}


	/**
	 * Get a property of a node, given by path.
	 *
	 * @param string|array The path. See {@link get_node_by_path()}.
	 * @param array Alternative names of the property to receive (ordered by priority).
	 * @return mixed|false False if property is not set for the node, otherwise its value.
	 */
	function get_prop_for_node( $path, $prop_by_ref )
	{
		$node =& $this->get_node_by_path( $path );

		foreach( $prop_by_ref as $lProp )
		{
			if( isset($node[$lProp]) )
			{
				return $node[$lProp];
			}
		}

		return false;
	}


	/**
	 * Get a property for a specific path entry.
	 *
	 * @param int The numeric index of the path entry to query (0 is first).
	 * @param array A list of properties to check, ordered by priority.
	 * @return mixed|false The first found property or false if it does not exist
	 */
	function get_prop_for_path( $nr, $prop_by_ref )
	{
		if( $pathWithProps = $this->get_path( $nr, true ) )
		{
			foreach( $prop_by_ref as $lProp )
			{
				if( isset($pathWithProps['props'][$lProp]) )
				{
					return $pathWithProps['props'][$lProp];
				}
			}
		}

		return false;
	}


	/**
	 * Get a menu, any level.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @param string The template name, see {@link get_menu_template()}.
	 */
	function get_html_menu( $path = NULL, $template = 'main' )
	{
		/* debug:
		$r = ' dispMenu-BEGIN ';
		$r .= $this->get_html_menu_entries( $path, $template );
		$r .= ' dispMenu-END ';
		return $r;
		*/
		return $this->get_html_menu_entries( $path, $template );
	}


	/**
	 * Display a submenu (1st sublevel).
	 *
	 * Should be called from within the AdminUI class.
	 *
	 * @access protected
	 *
	 * @param NULL|string|array The path (NULL defaults to first path entry). See {@link get_node_by_path()}.
	 */
	function disp_submenu( $path = NULL )
	{
		//echo ' disp_submenu-BEGIN ';

		if( is_null($path) )
		{
			$path = array( $this->get_path(0) );
		}

		echo $this->get_html_menu( $path, 'sub' );

		//echo ' disp_submenu-END ';
	}


	/**
	 * Display the start of the payload block
	 */
	function disp_payload_begin()
	{
		// Display submenu (this also opens a div class="panelblock" or class="panelblocktabs")
		$this->disp_submenu();
	}


	/**
	 * Display the end of the payload block
	 *
	 * Was: _sub_end.inc.php
	 */
	function disp_payload_end()
	{
		echo "</div>\n";	// class="panelblock*"
	}


	/**
	 * Returns the list of available Collections (aka Blogs) to work on.
	 *
	 * @todo Use BlogCache(?)
	 * @todo Use a template (i wanna make an UL/LI/A list structure in newer skins)
	 *
	 * @param string name of required permission needed to display the blog in the list
 	 * @param string level of required permission needed to display the blog in the list
	 * @param string Url format string for elements, with %d for blog number.
	 * @param string Title for "all" button
	 * @param string URL for "all" button
	 * @param string onclick attribute format string, with %d for blog number.
	 * @return string HTML
	 */
	function get_html_collection_list( $permname = 'blog_ismember', $permlevel = 1, $url_format = '?blog=%d', $all_title = NULL, $all_url = '', $onclick = NULL )
	{
		global $current_User, $blog;

		$template = $this->get_menu_template( 'CollectionList' );

		$r = $template['before'];

		if( !is_null($all_title) )
		{	// We want to add an "all" button
			$r .= $template[ $blog == 0 ? 'beforeEachSel' : 'beforeEach' ];
			$r .= '<a href="'.$all_url
						.'" class="'.( $blog == 0 ? 'CurrentBlog' : 'OtherBlog' ).'">'
						.$all_title.'</a> ';
			$r .= $template[ $blog == 0 ? 'afterEachSel' : 'afterEach' ];
		}

		for( $curr_blog_ID = blog_list_start();
					$curr_blog_ID != false;
					$curr_blog_ID = blog_list_next() )
		{
			if( ! $current_User->check_perm( $permname, $permlevel, false, $curr_blog_ID ) )
			{ // Current user doesn't have required permission on this blog...
				continue;
			}

			$r .= $template[ $curr_blog_ID == $blog ? 'beforeEachSel' : 'beforeEach' ];

			$r .= '<a href="'.sprintf( $url_format, $curr_blog_ID )
						.'" class="'.( $curr_blog_ID == $blog ? 'CurrentBlog' : 'OtherBlog' ).'"';

			if( ! is_null($onclick) )
			{	// We want to include an onclick attribute:
				$r .= ' onclick="'.sprintf( $onclick, $curr_blog_ID ).'"';
			}

			$r .= '>'.blog_list_iteminfo( 'shortname', false ).'</a> ';

			$r .= $template[ $curr_blog_ID == $blog ? 'afterEachSel' : 'afterEach' ];
		}

		$r .= $template['after'];

		return $r;
	}


	/**
	 * Get the HTML for the menu entries of a specific path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @param string Template name, see {@link get_menu_template()}.
	 * @return string The HTML for the menu.
	 */
	function get_html_menu_entries( $path, $template, $depth = 0 )
	{
		global $current_User;

		$r = '';

		$templateForLevel = $this->get_menu_template( $template, $depth );

		if( !( $menuEntries = $this->get_menu_entries($path) ) )
		{
			if( isset($templateForLevel['empty']) )
			{
				$r .= $templateForLevel['empty'];
			}
		}
		else
		{
			$r .= $templateForLevel['before'];

			$selected = $this->get_selected($path);

			foreach( $menuEntries as $loop_tab => $loop_details )
			{
				$perm = true; // By default

				if( ( ( !isset($loop_details['perm_name'])
								|| ($perm = $current_User->check_perm( $loop_details['perm_name'], $loop_details['perm_level'] ) ) )
							&& ( !isset($loop_details['perm_eval'])
										|| $perm = eval($loop_details['perm_eval']) )
						)
						|| isset($loop_details['text_noperm']) )
				{ // If no permission requested or if perm granted or if we have an alt text, display tab:
					$anchor = '<a href="';
					if( isset( $loop_details['href'] ) )
						$anchor .= $loop_details['href'];
					else
						$anchor .= eval( $loop_details['href_eval'] );
					$anchor .= '"';
					if( isset($loop_details['style']) )
					{
						$anchor .= ' style="'.$loop_details['style'].'"';
					}

					$anchor .= '>'.format_to_output( $perm ? $loop_details['text'] : $loop_details['text_noperm'], 'htmlbody' )
											."</a>";


					if( $loop_tab == $selected )
					{ // Highlight selected entry
						if( !empty( $templateForLevel['_props']['recurseSelected'] )
								&& ( $recursePath = array_merge( $path, $loop_tab ) )
								&& ($this->get_menu_entries($recursePath) ) )
						{
							$r .= isset($templateForLevel['beforeEachSelWithSub'])
								? $templateForLevel['beforeEachSelWithSub']
								: $templateForLevel['beforeEachSel'];
							$r .= $anchor;

							$r .= $this->get_html_menu_entries( $recursePath, $template, $depth+1 );

							$r .= isset($templateForLevel['afterEachSelWithSub'])
								? $templateForLevel['afterEachSelWithSub']
								: $templateForLevel['afterEachSel'];
						}
						else
						{
							$r .= $templateForLevel['beforeEachSel'];
							$r .= $anchor;
							$r .= $templateForLevel['afterEachSel'];
						}
					}
					else
					{
						$r .= $templateForLevel['beforeEach'];
						$r .= $anchor;
						$r .= $templateForLevel['afterEach'];
					}
				}
			}
			$r .= $templateForLevel['after'];
		}

		return $r;
	}


	/**
	 * Add menu entries to a given path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @param array Menu entries to add.
	 */
	function add_menu_entries( $path, $entries )
	{
		$node =& $this->get_node_by_path( $path, true );

		foreach( $entries as $lKey => $lMenuProps )
		{
			if( 1 ) // TODO: check perms/user settings, ... (this gets mainly done in get_html_menu_entries() for now)
			{
				$node['entries'][$lKey] = $lMenuProps;
			}
		}
	}


	/**
	 * Get menu entries for a given path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @return array The menu entries (may be empty).
	 */
	function get_menu_entries( $path )
	{
		$node =& $this->get_node_by_path( $path );

		return isset( $node['entries'] ) ? $node['entries'] : array();
	}


	/**
	 * Get the key of a selected entry for a path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @return string|false
	 */
	function get_selected( $path )
	{
		$node =& $this->get_node_by_path($path);

		if( isset($node['selected']) )
		{
			return $node['selected'];
		}

		return false;
	}


	/**
	 * Get the reference of a node from the menu entries using a path.
	 *
	 * @param array|string|NULL The path. NULL means root, string means child of root,
	 *                          array means path below root.
	 *                          (eg <code>array('options', 'general')</code>).
	 * @param boolean Should the node be created if it does not exist already?
	 * @return array
	 */
	function & get_node_by_path( $path, $createIfNotExisting = false )
	{
		if( is_null($path) )
		{ // root element
			$path = array();
		}
		elseif( !is_array($path) )
		{
			$path = array($path);
		}

		$nodes =& $this->menus;
		foreach( $path as $lStep )
		{
			if( !isset($nodes['entries'][$lStep]) )
			{
				if( $createIfNotExisting )
				{
					$nodes['entries'][$lStep] = array();
				}
				else
				{
					return false;
				}
			}
			$nodes =& $nodes['entries'][$lStep];
		}

		return $nodes;
	}


	/**
	 * Get a template by name and depth.
	 *
	 * Templates can handle multiple depth levels
	 *
	 * This is a method (and not a member array) to allow dynamic generation.
	 * fp>>I'm not so sure about this... feels a little bloated... gotta think about it..
	 *
	 * @param string Name of the template ('main', 'sub')
	 * @param integer Nesting level (start at 0)
	 * @return array Associative array which defines layout and optionally properties.
	 */
	function get_menu_template( $name, $depth = 0 )
	{
		switch( $name )
		{
			case 'main':
				switch( $depth )
				{
					case 0:
						// main level
						global $app_shortname, $app_version;

						return array(
							'before' => '<div id="mainmenu"><ul>',
							'after' => "</ul>\n<p class=\"center\">$app_shortname v <strong>$app_version</strong></p>\n</div>",
							'beforeEach' => '<li>',
							'afterEach' => '</li>',
							'beforeEachSel' => '<li class="current">',
							'afterEachSel' => '</li>',
							'beforeEachSelWithSub' => '<li class="parent">',
							'afterEachSelWithSub' => '</li>',
							'_props' => array(
								/**
								 * @todo Move to new skin (recurse for subentries if an entry is selected)
								'recurseSelected' => true,
								*/
							),
						);

					default:
						// any sublevel
						return array(
							'before' => '<ul class="submenu">',
							'after' => '</ul>',
							'beforeEach' => '<li>',
							'afterEach' => '</li>',
							'beforeEachSel' => '<li class="current">',
							'afterEachSel' => '</li>',
						);
				}

				break;


			case 'sub':
				// submenu, we support just one sub-level (by default)
				return array(
						'before' => '<div class="pt">'
							."\n".'<ul class="hack">'
							."\n<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>"
							// TODO: this hack MAY NOT be needed when not using pixels instead of decimal ems or exs in the CSS
							."\n</ul>"
							."\n".'<div class="panelblocktabs">'
							."\n".'<ul class="tabs">',
						'after' => "</ul>\n</div>\n</div>"
							."\n".'<div class="tabbedpanelblock">',
						'empty' => '<div class="panelblock">',
						'beforeEach' => '<li>',
						'afterEach'  => '</li>',
						'beforeEachSel' => '<li class="current">',
						'afterEachSel' => '</li>',
					);


			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '',
						'after' => '',
						'beforeEach' => '',
						'afterEach' => '',
						'beforeEachSel' => '',
						'afterEachSel' => '',
					);
				// fp>> I'll use the following as soon as I have time to play with the CSS:
				return array(
						'before' => '<ul class="submenu">',
						'after' => '</ul>',
						'beforeEach' => '<li>',
						'afterEach' => '</li>',
						'beforeEachSel' => '<li class="current">',
						'afterEachSel' => '</li>',
					);


			case 'Results':
				// Results list:
				return array(
				'before' => '<div class="results">',
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>Pages</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'list_start' => '<table class="grouped" cellspacing="0">'."\n\n",
						'head_start' => "<thead><tr>\n",
							'head_title' => '<th colspan="$nb_cols$"><span style="float:right">$global_icons$</span>$title$</th></tr>'
															."\n\n<tr>\n",
							'colhead_start' => '<th>',
							'colhead_start_first' => '<th class="firstcol">',
							'colhead_start_last' => '<th class="lastcol">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => '<img src="../admin/img/grey_arrow_up.gif" alt="A" title="'.T_('Ascending order')
																	.'" height="12" width="11" />',
							'sort_asc_on' => '<img src="../admin/img/black_arrow_up.gif" alt="A" title="'.T_('Ascending order')
																	.'" height="12" width="11" />',
							'sort_desc_off' => '<img src="../admin/img/grey_arrow_down.gif" alt="D" title="'.T_('Descending order')
																	.'" height="12" width="11" />',
							'sort_desc_on' => '<img src="../admin/img/black_arrow_down.gif" alt="D" title="'.T_('Descending order')
																	.'" height="12" width="11" />',
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</tr></thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => "<tr>\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td>',
								'col_start_first' => '<td class="firstcol">',
								'col_start_last' => '<td class="lastcol">',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'grp_col_start' => '<td>',
								'grp_col_start_first' => '<td class="firstcol">',
								'grp_col_start_last' => '<td class="lastcol">',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
					'list_end' => "</table>\n\n",
					'footer_start' => '<div class="results_nav">',
					'footer_text' => '<strong>Pages</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$'
														/* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
														/* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
														/* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => T_('1 page'),
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results' => '<table class="grouped clear" cellspacing="0">'."\n\n"
																.'<th><span style="float:right">$global_icons$</span>'
																.'$title$</th></tr>'."\n\n<tr>\n"
																.'<tr class="lastline"><td class="firstcol lastcol">'.T_('No results.').'</td></tr>'
																.'</table>'."\n\n",
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'Form':
				// Default Form settings:
				return array(
					'layout' => 'fieldset',
				);

			// TODO: add default settings for 'table', 'fieldset', etc...

			default:
				die( 'Unknown $name for AdminUI::get_menu_template(): '.var_export($name, true) /* PHP 4.2 ! */ );
		}
	}


	/**
	 * Add a headline for HTML <head>.
	 *
	 * @param string The line that should be added to <head>.
	 */
	function add_headline( $headline )
	{
		$this->headlines[] = $headline;
	}


	/**
	 * Get the headlines for HTML <head> (CSS files especially).
	 *
	 * To define headlines for a derived skin, add entries to
	 * {@link $headlines} and "return parent::get_headlines();".
	 *
	 * @return string The concatenated headlines to output in HTML <head>.
	 */
	function get_headlines()
	{
		$r = implode( "\n\t", $this->headlines );

		return $r;
	}


	/**
	 *
	 * @todo Move generation of blog list to this class!
	 *
	 * @return string
	 */
	function get_bloglist_buttons( $before = '', $after = '' )
	{
		global $blogListButtons;

		if( !empty($blogListButtons) )
		{
			return $before.$blogListButtons.$after;
		}
	}


	/**
	 * Get a path key by numeric key. Starts with 0.
	 *
	 * @param integer The numeric index of the path (0 is first).
	 * @param boolean Also return properties?
	 * @return string|array|false (depends on $withProps)
	 */
	function get_path( $which, $withProps = false )
	{
		if( $which === 'last' )
		{
			$which = count($this->path)-1;
		}
		if( !isset($this->path[$which]) )
		{
			return false;
		}

		if( $withProps )
		{
			return array(
				'path' => $this->path[$which],
				'props' => isset( $this->pathProps[$which] ) ? $this->pathProps[$which] : array(),
			);
		}

		return $this->path[$which];
	}


	/**
	 * Get tghe list of path keys in a given range.
	 *
	 * @param integer start index
	 * @param integer|NULL end index (NULL means same as start index)
	 * @return array List of path keys.
	 */
	function get_path_range( $start, $end = NULL )
	{
		if( is_null($end) )
		{
			$end = $start;
		}

		$r = array();
		for( $i = $start; $i <= $end; $i++ )
		{
			$r[] = isset($this->path[$i]) ? $this->path[$i] : NULL;
		}

		return $r;
	}


	/**
	 * Set $pathKey as the $nr'th path key.
	 *
	 * Also marks the parent node as selected.
	 *
	 * @param integer|NULL Numerical index of the path, NULL means 'append'.
	 * @param array Either the key of the path or an array(keyname, propsArray).
	 * @param array Properties for this path entry.
	 */
	function set_path_by_nr( $nr, $pathKey, $pathProps = array() )
	{
		if( is_null($nr) )
		{ // append
			$nr = count($this->path);
		}
		if( $nr === 0 )
		{
			$parentNode =& $this->get_node_by_path(NULL);
		}
		else
		{
			$parentNode =& $this->get_node_by_path($this->get_path_range( 0, $nr-1 ));
		}
		$parentNode['selected'] = $pathKey;

		$this->path[$nr] = $pathKey;
		$this->pathProps[$nr] = $pathProps;

		#pre_dump( 'setPathByNr: ', $nr, $pathKey, $pathProps );
	}


	/**
	 * Add a path at the end of the path list.
	 *
	 * @param string|array Either the key of the path or an array(keyname, propsArray).
	 */
	function add_path( $path, $pathProps = array() )
	{
		$search_path = $this->path;
		$search_path[] = $path;
		// auto-detect path props from menu entries
		if( $node =& $this->get_node_by_path( $search_path ) )
		{
			$pathProps = array_merge( $pathProps, $node );
		}

		$this->set_path_by_nr( NULL, $path, $pathProps );
	}


	/**
	 * Set the full selected path.
	 *
	 * For example, this selects the tab/submenu 'plugins' in the main menu 'options':
	 * <code>
	 * set_path( 'options', 'plugins' );
	 * </code>
	 *
	 * Use {@link add_path()} to append a single path.
	 *
	 * This is an easy stub for {@link set_path_by_nr()}.
	 *
	 * @param string|array,... Either the key of the path or an array(keyname, propsArray).
	 * @uses set_path_by_nr()
	 */
	function set_path()
	{
		$args = func_get_args();

		$i = 0;
		$prevPath = array();  // Remember the path we have walked through

		foreach( $args as $arg )
		{
			if( is_array($arg) )
			{ // Path name and properties given
				list( $pathName, $pathProps ) = $arg;
			}
			else
			{ // Just the path name
				$pathName = $arg;
				$pathProps = array();
			}

			if( $node =& $this->get_node_by_path( array_merge( $prevPath, array($pathName) ) ) )
			{ // the node exists in the menu entries: merge the properties
				$pathProps = array_merge( $node, $pathProps );
			}

			$this->set_path_by_nr( $i++, $pathName, $pathProps );

			$prevPath[] = $pathName;
		}
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @return string
	 */
	function get_body_top()
	{
		return '';
	}


	/**
	 * Get the end of the HTML <body>. Close open divs, etc...
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		return '';
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $app_version, $current_User;

		$r = '
		<div id="header">
			'.$this->admin_logo.'

			<div id="headinfo">
				<span id="headfunctions">
					'.$this->exit_links.'
				</span>

				b2evo v <strong>'.$app_version.'</strong>
				&middot; '.$this->get_head_info().'
			</div>

			<h1>'.$this->get_title_for_titlearea().'</h1>
		</div>
		';

		return $r;
	}


	/**
	 * Get default head info (local time, GMT, Login).
	 *
	 * @return string
	 */
	function get_head_info()
	{
		global $obhandler_debug, $localtimenow, $servertimenow, $current_User;

		$r = '';

		if( !$obhandler_debug )
		{ // don't display changing time when we want to test obhandler
			$r .= "\n".T_('Time:').' <strong>'.date_i18n( locale_timefmt(), $localtimenow ).'</strong>'
				.' &middot; <acronym title="'.T_('Greenwich Mean Time ').'">'
				./* TRANS: short for Greenwich Mean Time */ T_('GMT:').'</acronym> <strong>'.gmdate( locale_timefmt(), $servertimenow).'</strong>'
				.' &middot; '.T_('Logged in as:').' <strong><a href="b2users.php?user_ID='.$current_User->ID.'">'.$current_User->dget('login').'</a></strong>'
				."\n";
		}

		return $r;
	}

}

/*
 * $Log$
 * Revision 1.37  2005/10/30 23:42:46  blueyed
 * Refactored get_head_links() into existing get_headlines(); doc
 *
 * Revision 1.36  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.35  2005/10/26 23:08:28  blueyed
 * doc; todo
 *
 * Revision 1.34  2005/10/12 18:24:37  fplanque
 * bugfixes
 *
 * Revision 1.33  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.32  2005/08/31 19:06:41  fplanque
 * minor
 *
 * Revision 1.31  2005/08/01 19:36:54  fplanque
 * no message
 *
 * Revision 1.30  2005/07/18 14:21:37  fplanque
 * Use a default Form layout which can be skin dependant
 *
 * Revision 1.29  2005/07/15 16:41:50  fplanque
 * fixed typo
 *
 * Revision 1.28  2005/07/14 21:03:31  blueyed
 * Fixed notice with array_merge() again.
 *
 * Revision 1.27  2005/07/10 00:09:06  blueyed
 * renamed getNode() to get_node_by_path(), fixed array_merge() notice for PHP5
 *
 * Revision 1.26  2005/06/23 18:43:06  blueyed
 * Fixed constructor's name.
 *
 * Revision 1.25  2005/06/22 17:21:39  blueyed
 * css fix for khtml
 *
 * Revision 1.24  2005/06/03 20:14:37  fplanque
 * started input validation framework
 *
 * Revision 1.23  2005/06/02 18:50:52  fplanque
 * no message
 *
 * Revision 1.22  2005/05/02 19:06:44  fplanque
 * started paging of user list..
 *
 * Revision 1.21  2005/04/28 20:44:17  fplanque
 * normalizing, doc
 *
 * Revision 1.20  2005/04/21 18:01:28  fplanque
 * CSS styles refactoring
 *
 * Revision 1.19  2005/04/15 18:02:57  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.18  2005/03/21 17:37:47  fplanque
 * results/table layout refactoring
 *
 * Revision 1.17  2005/03/18 00:24:04  blueyed
 * doc
 *
 * Revision 1.16  2005/03/17 14:06:37  fplanque
 * put back page titles in logical order
 *
 * Revision 1.15  2005/03/16 19:58:13  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.14  2005/03/16 16:05:09  fplanque
 * $app_footer_text
 *
 * Revision 1.13  2005/03/13 19:46:53  blueyed
 * application config layer
 *
 * Revision 1.12  2005/03/11 12:40:15  fplanque
 * multiple browsing views, part ONE
 *
 */
?>