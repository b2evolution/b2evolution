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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


class AdminUI_general extends Widget
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
	}


	/**
	 *
	 *
	 * @return
	 */
	function addMenuEntries( $node, $entries, $template = 'default' )
	{
		$node =& $this->getNode( $node, true );

		foreach( $entries as $lKey => $lMenuProps )
		{
			if( 1 ) // TODO: check perms/user settings, ...
			{
				$node['entries'][$lKey] = $lMenuProps;
			}
		}

		$node['template'] = $template;
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
	 * Get the key of a selected entry for a node.
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
	 * Get a node (by reference) from the menu entries.
	 *
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
	 * Get a template by name.
	 *
	 * @param string Name of the template
	 * @return
	 */
	function getTemplate($name)
	{
		global $app_shortname, $app_version;

		switch( $name )
		{
			case 'menu_main':
				return array(
						'before' => '<div id="mainmenu"><ul>',
						'after' => '</ul>
												<p class="center">'.$app_shortname.' v <strong>'.$app_version.'</strong></p>
												</div>',
					);

			default:
				return array(
						'before' => '<div class="pt">'
												."\n".'<ul class="hack">'
												."\n<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>"
												."\n</ul>"
												."\n".'<div class="panelblocktabs">'
												."\n".'<ul class="tabs">',
						'after' => "</ul>\n</div>\n</div>"
												."\n".'<div class="tabbedpanelblock">',
						'empty' => '<div class="panelblock">',
					);
		}
	}


	/**
	 *
	 *
	 * @return
	 */
	function getTemplateForNode( $node )
	{
		$node =& $this->getNode($node);

		if( isset($node['template']) )
		{
			return $this->getTemplate($node['template']);
		}

		return $this->getTemplate('default');
	}


	/**
	 *
	 *
	 * @return
	 */
	function dispMenu( $node )
	{
		$template = $this->getTemplateForNode( $node );

		if( !$this->getMenuEntries($node) )
		{
			echo $template['empty'];
		}
		else
		{
			echo $template['before'];
			$this->dispMenuEntries( $node );
			echo $template['after'];
		}
	}


	/**
	 *
	 *
	 * @return
	 */
	function dispSubmenu( $node = NULL )
	{
		global $admin_tab;

		if( is_null($node) )
		{
			$node = array( $this->getPath(0) );
		}

		$this->dispMenu($node);
	}


	/**
	 *
	 *
	 * @return
	 */
	function dispMenuEntries( $node, $template = '<li>%s</li>', $templateSelected = '<li class="current">%s</li>' )
	{
		global $current_User;

		foreach( $this->getMenuEntries($node) as $loop_tab => $loop_details )
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

				#pre_dump( $node, $loop_tab, $this->getSelected($node) );
				printf( ( $loop_tab == $this->getSelected($node)
										? $templateSelected
										: $template ),
								$anchor );
			}
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
}

?>
