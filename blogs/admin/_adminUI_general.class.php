<?php
/**
 * This file implements the Admin UI class.
 * Admin skins should derive from this class and override {@link getTemplate()}
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
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// fp>>daniel: I don't think this is a widget!
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
	 */
	var $path = array();


	/**
	 * Constructor.
	 *
	 * @return
	 */
	function AdminUI()
	{
		global $mode; // TODO: make it a real property
		$this->mode = $mode;

		$this->initTemplates();
	}


	/**
	 * This function should init the templates - like adding Javascript through the {@link addHeadline()} method.
	 */
	function initTemplates()
	{
	}


	/**
	 * Displays a menu, any level.
	 *
	 * @param f*g undocumented, NULL will display main menu
	 * @param f*g undocumented {@link what?}
	 * @return
	 */
	function dispMenu( $path = NULL, $template = 'main' )
	{
		//echo ' dispMenu-BEGIN ';
		echo $this->getHtmlMenuEntries( $path, $template );
		//echo ' dispMenu-END ';
	}


	/**
	 * Display a submenu (1st sublevel).
	 *
	 * @param array|NULL Path of the menu to display.
	 * @return
	 */
	function dispSubmenu( $path = NULL )
	{
		//echo ' dispSubmenu-BEGIN ';

		if( is_null($path) )
		{
			$path = array( $this->getPath(0) );
		}

		$this->dispMenu( $path, 'sub' );

  	//echo ' dispSubmenu-END ';
	}


	/**
	 * Display the end of the payload block
	 */
	function dispPayloadBegin()
	{
		// fp>> In current skins this merged with the SubMenu (tabs)
		// fp>> I don't understand the templating stuff well enough to implement it right now...
	}


	/**
	 * Display the end of the payload block
	 *
	 * Was: _sub_end.inc.php
	 */
	function dispPayloadEnd()
	{
		// fp>> I don't understand the templating stuff well enough to implement it right now...
		echo "</div>\n";
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
				$html .= ' onclick="'.sprintf( $onclick, $curr_blog_ID ).'"';
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
	 * @return string
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
					$anchor = '<a href="'.$loop_details['href'].'"';
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
	 *
	 *
	 * @return
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
	 *
	 *
	 * @return array
	 */
	function getMenuEntries( $node )
	{
		$node =& $this->getNode( $node );

		return isset( $node['entries'] ) ? $node['entries'] : array();
	}


	/**
	 * Get the key of a selected entry for a path.
	 *
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
	 * @param array|string|NULL The path.
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
	 * @todo fp>>daniel does this work for menus only? Can we generalize it to getTemplate?
	 *
	 * @param string Name of the template ('main', 'sub')
	 * @param integer nesting level (start at 0)
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
															'recurseSelected' => true,  // recurse for subentries if an entry is selected
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

			default:
				die( 'Unknown $name for AdminUI::getMenuTemplate(): '.var_export($name, true) /* PHP 4.2 ! */ );
		}
	}


	/**
	 * Set a headline for HTML head.
	 *
	 * @return
	 */
	function addHeadline( $headline )
	{
		$this->headlines[] = $headline;
	}


	/**
	 * Output the headlines.
	 */
	function getHeadlines()
	{
		$r = '';

		$r .= implode( "\n", $this->headlines );

		return $r;
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
	 *
	 *
	 * @return string|false
	 */
	function getPath( $which )
	{
		return isset($this->path[$which]) ? $this->path[$which] : false;
	}


	/**
	 *
	 *
	 * @return array
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
	 *
	 *
	 * @return
	 */
	function setPathByNr( $key, $nr = 0 )
	{
		if( $nr == 0 )
		{
			$parentNode =& $this->getNode(NULL);
		}
		else
		{
			$parentNode =& $this->getNode($this->getPathRange( 0, $nr-1 ));
		}
		$parentNode['selected'] = $key;

		$this->path[$nr] = $key;
	}


	/**
	 *
	 *
	 * @return
	 */
	function setPathArray( $pathArray )
	{
		foreach( $pathArray as $lKey => $lPath )
		{
			$this->setPathByNr( $value, $lKey );
		}
	}


	/**
	 *
	 * @param string,... the keys for the path
	 * @return
	 */
	function setPath()
	{
		$args = func_get_args();

		$i = 0;
		foreach( $args as $arg )
		{
			$this->setPathByNr( $arg, $i++ );
		}
	}


	/**
	 * Close open divs, etc...
	 *
	 * @return string
	 */
	function getBodyBottom()
	{
		return '';
	}


	/**
	 * Get the footer of the admin page.
	 *
	 * @return string
	 */
	function getPageFooter()
	{
		return "\n\n</body>\n</html>";
	}
}

?>
