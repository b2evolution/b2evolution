<?php
/**
 * This file implements the Admin UI class.
 * Admin skins should derive from this class and override {@link getMenuTemplate()}
 * for example.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * The general Admin UI class. It provides functions to handle the UI part of the
 * Backoffice.
 *
 * Admin skins should derive from this class and override {@link getMenuTemplate()}
 * for example.
 *
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
	 * Use {@link setPath()}, {@link setPathArray()} or
	 * {@link setPathByNr()} to set it.
	 * @var array
	 */
	var $path = array();

	/**
	 * The properties of the path entries.
	 * Use {@link setPath()}, {@link setPathArray()} or
	 * {@link setPathByNr()} to set it.
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


	/**
	 * Constructor.
	 *
	 * @return
	 */
	function AdminUI()
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

		$this->initTemplates();
	}


	/**
	 * This function should init the templates - like adding Javascript through the {@link addHeadline()} method.
	 */
	function initTemplates()
	{
	}


	/**
	 * Get the title of the page.
	 *
	 * @return
	 */
	function getTitle( $reversedDefault = false )
	{
		if( isset($this->title) )
		{ // Explicit title has been set:
			return $this->title;
		}
		elseif( $title = $this->getPathProperty( 'last', array( 'title' ) ) )
		{ // Title property of the path
			return $title;
		}
		elseif( $title = $this->getPropertyForNode( $this->path, array( 'title' ) ) )
		{ // Title property for the node of the current path
			return $title;
		}
		else
		{ // Fallback: implode title/text properties of the path
			$titles = $this->getPropertiesForPath( $this->path, array( 'title', 'text' ) );
			if( $reversedDefault )
			{	// We have asked for reverse order of the path elements:
				$titles = array_reverse($titles);
			}
			return implode( $this->pathSeperator, $titles );
		}
	}


	/**
	 * Get the title for the titlearea (<h1>). Falls back to {@link getTitle()}.
	 *
	 * @return
	 */
	function getTitleForTitlearea()
	{
		$r = $this->pathSeperator;

		if( isset( $this->title_titlearea ) )
		{ // Explicit title has been set:
			$r .= $this->title_titlearea;
		}
		elseif( $titleForTitlearea = $this->getPropertyForNode( $this->path, array( 'title' ) ) )
		{ // Title property for the node of the current path
			$r .= $titleForTitlearea;
		}
		else
		{ // Fallback to {@link getTitle()}
			$r .= $this->getTitle();
		}

		return $r;
	}


	/**
	 * Get the title for HTML <title> tag.
	 *
	 * If no explicit title has been specified, auto construct one from path.
	 *
	 * @return string
	 */
	function getHtmlTitle()
	{
		global $app_shortname;

		$r = $app_shortname.$this->pathSeperator;

		if( $htmltitle = $this->getPropertyForNode( $this->path, array( 'htmltitle' ) ) )
		{ // Explicit htmltitle set:
			$r .= $htmltitle;
		}
		else
		{	// No explicit title set, construct Title from path
			$r .= #preg_replace( '/:$/', '',
						$this->getTitle()
						#)
						;
		}

		return $r;
	}


	/**
	 * Get a list of properties for a given path for a set of property names to check.
	 * The result is a list of properties for each node down the path.
	 *
	 * The property names must be given in $propertyByPreference, ordered by preference.
	 *
	 * @param string|array The path. See {@link getNode()}.
	 * @param array Alternative names of the property to receive (ordered by priority).
	 * @return array List of the properties.
	 */
	function getPropertiesForPath( $path, $propertyByPreference )
	{
		if( !is_array($path) )
		{
			$path = array( $path );
		}
		$r = array();

		$prevPath = array();
		foreach( $path as $i => $lPath )
		{
			if( false !== ($prop = $this->getPathProperty( $i, $propertyByPreference )) )
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
	 * @param string|array The path. See {@link getNode()}.
	 * @param array Alternative names of the property to receive (ordered by priority).
	 * @return mixed|false False if property is not set for the node, otherwise its value.
	 */
	function getPropertyForNode( $path, $propertyByPreference )
	{
		$node =& $this->getNode( $path );

		foreach( $propertyByPreference as $lProp )
		{
			if( isset($node[$lProp]) )
			{
				return $node[$lProp];
			}
		}

		return false;
	}


	/**
	 *
	 *
	 * @return
	 */
	function getPathProperty( $nr, $propertyByPreference )
	{
		if( $pathWithProps = $this->getPath( $nr, true ) )
		{
			foreach( $propertyByPreference as $lProp )
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
	 * Displays a menu, any level.
	 *
	 * @param NULL|string|array The path. See {@link getNode()}.
	 * @param string The template name, see {@link getMenuTemplate()}.
	 */
	function getMenu( $path = NULL, $template = 'main' )
	{
		/* debug:
		$r = ' dispMenu-BEGIN ';
		$r .= $this->getHtmlMenuEntries( $path, $template );
		$r .= ' dispMenu-END ';
		return $r;
		*/
		return $this->getHtmlMenuEntries( $path, $template );
	}


	/**
	 * Display a submenu (1st sublevel).
	 *
	 * Should be called from within the AdminUI class.
	 *
	 * @access protected
	 *
	 * @param NULL|string|array The path (NULL defaults to first path entry). See {@link getNode()}.
	 */
	function dispSubmenu( $path = NULL )
	{
		//echo ' dispSubmenu-BEGIN ';

		if( is_null($path) )
		{
			$path = array( $this->getPath(0) );
		}

		echo $this->getMenu( $path, 'sub' );

		//echo ' dispSubmenu-END ';
	}


	/**
	 * Display the start of the payload block
	 */
	function dispPayloadBegin()
	{
		// Display submenu (this also opens a div class="panelblock" or class="panelblocktabs")
		$this->dispSubmenu();
	}


	/**
	 * Display the end of the payload block
	 *
	 * Was: _sub_end.inc.php
	 */
	function dispPayloadEnd()
	{
		echo "</div>\n";	// class="panelblock*"
	}


	/**
	 * Returns the list of available Collections (aka Blogs) to work on.
	 *
	 * fplanque>>I'm trying to hack this in and get a feeling of the AdminUI stuff at the same time :/
	 *
	 * @todo Use BlogCache(?)
	 * @todo Use a template (i wanna make an UL/LI/A list structure in newer skins)
	 * @todo maybe rename to getHtmlCollectionList
	 *
	 * @param string name of required permission needed to display the blog in the list
 	 * @param string level of required permission needed to display the blog in the list
	 * @param string Url format string for elements, with %d for blog number.
	 * @param string Title for "all" button
	 * @param string URL for "all" button
	 * @param string onclick attribute format string, with %d for blog number.
	 * @return string HTML
	 */
	function getCollectionList( $permname = 'blog_ismember', $permlevel = 1, $url_format = '?blog=%d',
															$all_title = NULL, $all_url = '', $onclick = NULL )
	{
		global $current_User, $blog;

		$template = $this->getMenuTemplate( 'CollectionList' );

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
	 * @param NULL|string|array The path. See {@link getNode()}.
	 * @param string Template name, see {@link getMenuTemplate()}.
	 * @return string The HTML for the menu.
	 */
	function getHtmlMenuEntries( $path, $template, $depth = 0 )
	{
		global $current_User;

		$r = '';

		$templateForLevel = $this->getMenuTemplate( $template, $depth );

		if( !( $menuEntries = $this->getMenuEntries($path) ) )
		{
			if( isset($templateForLevel['empty']) )
			{
				$r .= $templateForLevel['empty'];
			}
		}
		else
		{
			$r .= $templateForLevel['before'];

			$selected = $this->getSelected($path);

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
					{
						if( !empty( $templateForLevel['_props']['recurseSelected'] )
								&& ( $recursePath = array_merge( $path, $loop_tab ) )
								&& ($this->getMenuEntries($recursePath) ) )
						{
							$r .= isset($templateForLevel['beforeEachSelWithSub'])
										? $templateForLevel['beforeEachSelWithSub']
										: $templateForLevel['beforeEachSel'];
							$r .= $anchor;

							$r .= $this->getHtmlMenuEntries( $recursePath, $template, $depth+1 );

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
	 * @param NULL|string|array The path. See {@link getNode()}.
	 * @param array Menu entries to add.
	 */
	function addMenuEntries( $path, $entries )
	{
		$node =& $this->getNode( $path, true );

		foreach( $entries as $lKey => $lMenuProps )
		{
			if( 1 ) // TODO: check perms/user settings, ...
			{
				$node['entries'][$lKey] = $lMenuProps;
			}
		}
	}


	/**
	 * Get menu entries for a given path.
	 *
	 * @param NULL|string|array The path. See {@link getNode()}.
	 * @return array The menu entries (may be empty).
	 */
	function getMenuEntries( $path )
	{
		$node =& $this->getNode( $path );

		return isset( $node['entries'] ) ? $node['entries'] : array();
	}


	/**
	 * Get the key of a selected entry for a path.
	 *
	 * @param NULL|string|array The path. See {@link getNode()}.
	 * @return string|false
	 */
	function getSelected( $path )
	{
		$node =& $this->getNode($path);

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
	 * @return array
	 */
	function & getNode( $path, $createIfNotExisting = false )
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
	function getMenuTemplate( $name, $depth = 0 )
	{
		switch( $name )
		{
			case 'main':
				switch( $depth )
				{
					case 0:
						// main level
						global $app_shortname, $app_version;

						return array( 'before' => '<div id="mainmenu"><ul>',
													'after' => '</ul>
																			<p class="center">'.$app_shortname.' v <strong>'.$app_version.'</strong></p>
																			</div>',
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
						return array( 'before' => '<ul class="submenu">',
													'after' => '</ul>',
													'beforeEach' => '<li>',
													'afterEach' => '</li>',
													'beforeEachSel' => '<li class="current">',
													'afterEachSel' => '</li>',
												);
				}

				break;


			case 'sub':
				// submenu, we support just one sub-level
				return array(
						'before' => '<div class="pt">'
												."\n".'<ul class="hack">'
												."\n<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>"
												// Note: this hack MAY NOT be needed when not using pixels instead of decimal ems or exs in the CSS
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
				// fp>>daniel: is it a bad idea to put this here??
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
					'header_text' => '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$',
					'header_text_single' => '',
					'header_end' => '</div>',
					'title_start' => "<div>\n",
					'title_end' => "</div>\n",
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
							'basic_sort_asc' => getIcon( 'ascending' ),
							'basic_sort_desc' => getIcon( 'descending' ),
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
						'body_end' => "</tbody>\n\n",
					'list_end' => "</table>\n\n",
					'footer_start' => '<div class="results_nav">',
					'footer_text' => /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
														'<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$'
														/* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => T_('1 page'),
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results' => '<table class="grouped" cellspacing="0">'."\n\n"
																.'<th><span style="float:right">$global_icons$</span>'
																.'$title$</th></tr>'."\n\n<tr>\n"
																.'<tr class="lastline"><td class="firstcol lastcol">'.T_('No results.').'</td></tr>'
																.'</table>'."\n\n",
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			default:
				die( 'Unknown $name for AdminUI::getMenuTemplate(): '.var_export($name, true) /* PHP 4.2 ! */ );
		}
	}


	/**
	 * Add a headline for HTML <head>.
	 */
	function addHeadline( $headline )
	{
		$this->headlines[] = $headline;
	}


	/**
	 * Get the headlines for HTML <head>.
	 */
	function getHeadlines()
	{
		$r = '';

		$r .= implode( "\n", $this->headlines );

		return $r;
	}


	/**
	 * Get links (to CSS files especially).
	 */
	function getHeadlinks()
	{
		return '';
	}


	/**
	 *
	 * @todo Move generation of blog list to this class!
	 *
	 * @return string
	 */
	function getBloglistButtons( $before = '', $after = '' )
	{
		global $blogListButtons;

		if( !empty($blogListButtons) )
		{
			return $before.$blogListButtons.$after;
		}
	}


	/**
	 * Get a path key by numeric key.
	 *
	 * @param integer The numeric index of the path.
	 * @param boolean Also return properties?
	 * @return string|array|false (depends on $withProps)
	 */
	function getPath( $which, $withProps = false )
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
					'props' => isset( $this->pathProps[$which] )
											? $this->pathProps[$which]
											: array(),
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
	function getPathRange( $start, $end = NULL )
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
	function setPathByNr( $nr, $pathKey, $pathProps = array() )
	{
		if( is_null($nr) )
		{ // append
			$nr = count($this->path);
		}
		if( $nr === 0 )
		{
			$parentNode =& $this->getNode(NULL);
		}
		else
		{
			$parentNode =& $this->getNode($this->getPathRange( 0, $nr-1 ));
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
	function addPath( $path, $pathProps = array() )
	{
		// auto-detect path props from menu entries
		if( $node =& $this->getNode( array_merge( $this->path, $path ) ) )
		{
			$pathProps = array_merge( $pathProps, $node );
		}

		$this->setPathByNr( NULL, $path, $pathProps );
	}


	/**
	 * Set the paths beginning at all the paths passed as arguments.
	 *
	 * This is an easy stub for {@link setPathByNr()}.
	 *
	 * @todo DOCUMENTATION!!
	 *
	 * @param string|array,... Either the key of the path or an array(keyname, propsArray).
	 * @uses setPathByNr()
	 */
	function setPath()
	{
		$args = func_get_args();

		$i = 0;
		$prevPath = array();

		foreach( $args as $arg )
		{
			if( is_array($arg) )
			{
				list( $pathName, $pathProps ) = $arg;
			}
			else
			{
				$pathName = $arg;
				$pathProps = array();
			}

			if( $node =& $this->getNode( array_merge($prevPath, $pathName) ) )
			{
				$pathProps = $node;
			}

			$this->setPathByNr( $i++, $pathName, $pathProps );

			$prevPath[] = $pathName;
		}
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @return string
	 */
	function getBodyTop()
	{
		return '';
	}


	/**
	 * Get the end of the HTML <body>. Close open divs, etc...
	 *
	 * @return string
	 */
	function getBodyBottom()
	{
		return '';
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return
	 */
	function getPageHead()
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
				&middot; '.$this->getHeadInfo().'
			</div>

			<h1>'.$this->getTitleForTitlearea().'</h1>
		</div>
		';

		return $r;
	}


	/**
	 * Get default head info (local time, GMT, Login).
	 *
	 * @return string
	 */
	function getHeadInfo()
	{
		global $obhandler_debug, $localtimenow, $servertimenow, $current_User;

		$r = '';

		if( !$obhandler_debug )
		{ // don't display changing time when we want to test obhandler
			$r .= "\n".T_('Time:').' <strong>'.date_i18n( locale_timefmt(), $localtimenow ).'</strong>'
						.' &middot; <acronym title="'.T_('Greenwich Mean Time ').'">'
						./* TRANS: short for Greenwich Mean Time */ T_('GMT:').'</acronym> <strong>'.gmdate( locale_timefmt(), $servertimenow).'</strong>'
						.' &middot; '.T_('Logged in as:').' <strong>'.$current_User->dget('login').'</strong>'
						."\n";
		}

		return $r;
	}

}

/*
 * $Log$
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
