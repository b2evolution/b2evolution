<?php
/**
 * This file displays the first part of the page menu (before the page title).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * @author blueyed
 * @author fplanque
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _html_header.inc.php 6268 2014-03-19 14:15:42Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $io_charset, $rsc_url, $UserSettings, $Debuglog, $Plugins;
global $month, $month_abbrev, $weekday, $weekday_abbrev; /* for localized calendar */
global $debug, $Hit, $AdminUI;

headers_content_mightcache( 'text/html', 0 );		// Make extra sure we don't cache the admin pages!
require_js( 'functions.js' ); // General functions
require_js( 'ajax.js' );	// Functions to work with AJAX response data
?>
<!DOCTYPE html>
<html lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $this->get_html_title(); ?></title>
	<?php
	global $robots_index, $robots_follow;
	$robots_index = false;
	$robots_follow = false;
	robots_tag();

	global $rsc_path, $rsc_url, $htsrv_url;

	// var htsrv_url is used for AJAX callbacks
	add_js_headline( "// Paths used by JS functions:
		var htsrv_url = '$htsrv_url';
		var blog_id = '".param( 'blog', 'integer' )."';
		var is_backoffice = true;" );

	add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu
	init_bubbletip_js( 'rsc_url', $AdminUI->get_template( 'tooltip_plugin' ) ); // Init popover windows for usernames
	init_results_js(); // Add functions to work with Results tables

	require_js( 'form_extensions.js'); // script allowing to check and uncheck all boxes in forms -- TODO: jQueryfy

	require_js( 'extracats.js' );
	require_js( 'dynamic_select.js' );
	require_js( 'backoffice.js' );


	global $UserSettings;
	if( $UserSettings->get('control_form_abortions') )
	{	// Activate bozo validator
		require_js( 'bozo_validator.js' );
	}

	if( $UserSettings->get('focus_on_first_input') )
	{	// Activate focus on first form <input type="text">:
		add_js_headline( 'jQuery( function() { focus_on_first_input() } )' );
	}

	global $Debuglog;
	$Debuglog->add( 'Admin-Path: '.var_export($this->path, true), 'skins' );

	if( $this->get_path(0) == 'files'
			|| ($this->get_path_range(0,1) == array('blogs', 'perm') )
			|| ($this->get_path_range(0,1) == array('blogs', 'permgroup') ) )
	{{{ // -- Inject javascript ----------------
		// gets initialized in _footer.php

		$begin_script = <<<JS
		<script type="text/javascript">
		<!--
		  var allchecked = Array();
		  var idprefix;
JS;
			add_headline( $begin_script );

			switch( $this->get_path(0) )
			{
				case 'files':
				/**
				 * Toggles status of a bunch of checkboxes in a form
				 *
				 * @param string the form name
				 * @param string the checkbox(es) element(s) name
				 * @param string number/name of the checkall set to use. Defaults to 0 and is needed when there are several "checkall-sets" on one page.
				 */
				$toggleCheckboxes_script = "
				function toggleCheckboxes(the_form, the_elements, set_name )
				{
					if( typeof set_name == 'undefined' )
					{
						set_name = 0;
					}
					if( allchecked[set_name] ) allchecked[set_name] = false;
					else allchecked[set_name] = true;

					var elems = document.forms[the_form].elements[the_elements];
					if( !elems )
					{
						return;
					}
					var elems_cnt = (typeof(elems.length) != 'undefined') ? elems.length : 0;
					if (elems_cnt)
					{
						for (var i = 0; i < elems_cnt; i++)
						{
							elems[i].checked = allchecked[nr];
						} // end for
					}
					else
					{
						elems.checked = allchecked[nr];
					}
					setcheckallspan( set_name );
				}
";
				add_headline( $toggleCheckboxes_script );
				break;
			}

			// --- general functions ----------------
			/**
			 * replaces the text of the checkall-html-ID for set_name
			 *
			 * @param integer|string number or name of the checkall "set" to use
			 * @param boolean force setting to true/false
			 */
			$setcheckallspan_script = "
			function setcheckallspan( set_name, set )
			{
				if( typeof(allchecked[set_name]) == 'undefined' || typeof(set) != 'undefined' )
				{ // init
					allchecked[set_name] = set;
				}

				if( allchecked[set_name] )
				{
					var replace = document.createTextNode('" . TS_('uncheck all') . "');
				}
				else
				{
					var replace = document.createTextNode('" . TS_('check all') . "');
				}

				if( document.getElementById( idprefix+'_'+String(set_name) ) )
				{
					document.getElementById( idprefix+'_'+String(set_name) ).replaceChild(replace, document.getElementById( idprefix+'_'+String(set_name) ).firstChild);
				}
				//else alert('no element with id '+idprefix+'_'+String(set_name));
			}
";
			add_headline( $setcheckallspan_script );
			/**
			 * inits the checkall functionality.
			 *
			 * @param string the prefix of the IDs where the '(un)check all' text should be set
			 * @param boolean initial state of the text (if there is no checkbox with ID htmlid + '_state_' + nr)
			 */ $initcheckall_script = <<<JS
			function initcheckall( htmlid, init )
			{
				// initialize array
				allchecked = Array();
				idprefix = typeof(htmlid) == 'undefined' ? 'checkallspan' : htmlid;

				for( var lform = 0; lform < document.forms.length; lform++ )
				{
					for( var lelem = 0; lelem < document.forms[lform].elements.length; lelem++ )
					{
						if( document.forms[lform].elements[lelem].id.indexOf( idprefix ) == 0 )
						{
							var index = document.forms[lform].elements[lelem].name.substring( idprefix.length+2, document.forms[lform].elements[lelem].name.length );
							if( document.getElementById( idprefix+'_state_'+String(index)) )
							{
								setcheckallspan( index, document.getElementById( idprefix+'_state_'+String(index)).checked );
							}
							else
							{
								setcheckallspan( index, init );
							}
						}
					}
				}
			}
			//-->
		</script>
JS;
		add_headline( $initcheckall_script );
	}}}

	// Add event to the item title field to update document title and init it (important when switching tabs/blogs):
	global $js_doc_title_prefix;
	if( isset($js_doc_title_prefix) )
	{ // dynamic document.title handling:
		$base_title = preg_quote( trim($js_doc_title_prefix) /* e.g. FF2 trims document.title */ );
		add_js_headline( 'jQuery(function(){
			var generateTitle = function()
			{
				currentPostTitle = jQuery(\'#post_title\').val()
				document.title = document.title.replace(/(' . $base_title . ').*$/, \'$1 \'+currentPostTitle)
			}
			generateTitle()
			jQuery(\'#post_title\').keyup(generateTitle)
		})' );
	}

	// CALL PLUGINS NOW:
	global $Plugins;
	$Plugins->trigger_event( 'AdminEndHtmlHead', array() );

	include_headlines(); // Add javascript and css files included by plugins and skin
?>
</head>
