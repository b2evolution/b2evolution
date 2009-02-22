<?php
/**
 * This file displays the first part of the page menu (before the page title).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $io_charset, $rsc_url, $UserSettings, $Debuglog, $Plugins, $generating_static;
global $month, $month_abbrev, $weekday, $weekday_abbrev; /* for localized calendar */
global $debug, $Hit;

header_content_type( 'text/html' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $this->get_html_title(); ?></title>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
	<?php
	global $rsc_path, $rsc_url, $htsrv_url;

	add_js_headline( "// Paths used by JS functions:
		var imgpath_expand = '" . get_icon( 'expand', 'url' ) . "';
		var imgpath_collapse = '" . get_icon( 'collapse', 'url' ) . "';
		var htsrv_url = '$htsrv_url';" );

 	require_js( '#jquery#' );
 	require_js( '#jqueryUI#' );
 	// script allowing to check and uncheck all boxes in forms
 	require_js( 'functions.js');
	require_js( 'form_extensions.js');
	// Afwas > are these two used or part of the javaScript popup calendar?
	// require_js( 'anchorposition.js');
	//	require_js( 'popupwindow.js' );
	require_js( 'rollovers.js' );
	require_js( 'extracats.js' );
	require_js( 'dynamic_select.js' );
	require_js( 'admin.js' );


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
	$Debuglog->add( 'Admin-Path: '.var_export($this->path, true) );

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

	if( $Hit->is_winIE )
	{
		add_headline( '<!--[if lt IE 7]>
<style type="text/css">
/* IE: fix extra space */
div.skin_wrapper_loggedin {
	margin-top: 0;
	padding-top: 0;
}
</style>
<![endif]-->' );
	}

	// fp> TODO: ideally all this should only be included when the datepicker will be needed
	// dh> The Datepicker could dynamically load this CSS in document.ready?!
	// Afwas> Done. Keeping this conversation for reference. The performance may be an issue.
	// require_css( 'ui.datepicker.css' );
	
	add_js_headline( 'jQuery(function(){
			jQuery(\'#item_issue_date, #item_issue_time\').change(function(){
				jQuery(\'#set_issue_date_to\').attr("checked", "checked")
			})
		})' );
	
	// Add event to the item title field to update document title and init it (important when switching tabs/blogs):
	global $js_doc_title_prefix;
	if( isset($js_doc_title_prefix) )
	{ // dynamic document.title handling:
		$base_title = preg_quote( trim($js_doc_title_prefix) /* e.g. FF2 trims document.title */ );
		add_js_headline( 'jQuery(function(){
			var generateTitle = function()
			{
				currentPostTitle = jQuery(\'#post_title\').val()
				if ( currentPostTitle != undefined )
				{	// Tblue> Dirty workaround! This script should be only used when editing/creating a post...
					document.title = document.title.replace(/(' . $base_title . ').*$/, \'$1 \'+currentPostTitle)
				}
			}
			generateTitle()
			jQuery(\'#post_title\').keyup(generateTitle)
		})' );
	}


	$datefmt = locale_datefmt();
	$datefmt = str_replace( array( 'd', 'm', 'Y' ), array( 'dd', 'mm', 'yy' ), $datefmt );
	add_js_headline( 'jQuery(function(){
			var monthNames = [\'' . T_( 'January' ) . '\',\'' . T_( 'February' ) . '\', \'' . T_( 'March' ) . '\', \'' . T_( 'April' ) . '\', \'' . T_( 'May' ) . '\', \'' . T_( 'June' ) . '\', \'' . T_( 'July' ) . '\', \'' . T_( 'August' ) . '\', \'' . T_( 'September' ) . '\', \'' . T_( 'October' ) . '\', \'' . T_( 'November' ) . '\', \'' . T_( 'December' ) . '\']
			var dayNamesMin = [\'' . T_( 'Sun' ) . '\', \'' . T_( 'Mon' ) . '\', \'' . T_( 'Tue' ) . '\', \'' . T_( 'Wed' ) . '\', \'' . T_( 'Thu' ) . '\', \'' . T_( 'Fri' ) . '\', \'' . T_( 'Sat' ) . '\']
			var docHead = document.getElementsByTagName(\'head\')[0];
			for (i=0;i<dayNamesMin.length;i++)
				dayNamesMin[i] = dayNamesMin[i].substr(0, 2)

			jQuery("#item_issue_date, #item_deadline").datepicker({
				beforeShow: function(){ // Dynamically add stylesheet just before display
					jQuery(document.createElement(\'link\'))
						.attr({type: \'text/css\', href: \'rsc/css/ui.datepicker.css\', rel: \'stylesheet\', media: \'screen\'})
						.appendTo(docHead)
				},
				dateFormat: \'' . $datefmt . '\',
				monthNames: monthNames,
				dayNamesMin: dayNamesMin,
				onClose: function(){ // Dynamically removing stylesheet, prevents duplicates
					jQuery(docHead).find("link[href=\'rsc/css/ui.datepicker.css\']").remove();
				}
			})
		})' );

	// CALL PLUGINS NOW:
	global $Plugins;
	$Plugins->trigger_event( 'AdminEndHtmlHead', array() );

	include_headlines(); // Add javascript and css files included by plugins and skin
?>
</head>

<?php
/*
 * $Log$
 * Revision 1.19  2009/02/22 18:46:56  afwas
 * - Reverted 1.14 && 1.15 because that didn't work for edited posts.
 * - Cut one of the functions for datepicker (handles change of radiobutton in other jQuery because the time can also change)
 * - Added dynamically loaded (and removed!) stylesheet for datepicker.
 *
 * Revision 1.18  2009/02/22 18:09:40  tblue246
 * Bugfix
 *
 * Revision 1.17  2009/02/22 16:35:15  blueyed
 * TODO comment
 *
 * Revision 1.16  2009/02/22 07:43:08  afwas
 * Minor: simplification of javaScript function generateTitle()
 *
 * Revision 1.15  2009/02/22 06:53:39  afwas
 * Minor: simplification of javaScript function generateTitle()
 *
 * Revision 1.14  2009/02/21 23:10:43  fplanque
 * Minor
 *
 * Revision 1.13  2009/02/01 00:11:02  blueyed
 * Use jQuery document.ready for focus_on_first_input
 *
 * Revision 1.12  2009/01/24 03:10:20  afwas
 * - added jQuery that sets 'Set to' radiobutton (#set_issue_date_to) after time or date have been manually modified.
 * - Recoded javaScript in jQuery: changes to #post_title are added to document.head. This jS originated from /inc/items/views/inc/_item_form_behaviors.
 *
 * Revision 1.11  2009/01/23 23:19:54  afwas
 * Set the radiobutton to 'Set to' if a date is picked
 *
 * Revision 1.10  2009/01/23 22:14:39  afwas
 * Added jQuery datepicker, removed javaScript popup calendar
 *
 * Revision 1.9  2008/10/02 23:33:08  blueyed
 * - require_js(): remove dirty dependency handling for communication.js.
 * - Add add_js_headline() for adding inline JS and use it for admin already.
 *
 * Revision 1.8  2008/09/28 08:06:13  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.7  2008/04/03 14:54:34  fplanque
 * date fixes
 *
 * Revision 1.6  2008/02/08 22:24:46  fplanque
 * bugfixes
 *
 * Revision 1.5  2008/01/21 15:02:01  fplanque
 * fixed evobar
 *
 * Revision 1.4  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/07/04 23:36:10  blueyed
 * Fixed folding
 *
 * Revision 1.2  2007/06/30 22:03:34  fplanque
 * cleanup
 *
 * Revision 1.1  2007/06/25 11:02:35  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.32  2007/06/24 22:35:57  fplanque
 * cleanup
 *
 * Revision 1.30  2007/06/24 20:09:06  personman2
 * switching to require_css and require_js in admin skins
 *
 * Revision 1.28  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.27  2007/03/12 22:59:32  blueyed
 * Fixed inclusion of jQuery
 *
 * Revision 1.26  2007/03/11 18:04:30  blueyed
 * Updated jQuery; now uncompressed jquery.js gets used in backoffice if $debug is true and jquery.js exists - otherwise the compressed jquery.min.js gets used.
 * jquery.js is not meant to get shipped in releases!
 *
 * Revision 1.25  2006/11/26 23:25:20  blueyed
 * Newline at the end, so "view-source" is nicer
 *
 * Revision 1.24  2006/11/26 02:30:39  fplanque
 * doc / todo
 */
?>
