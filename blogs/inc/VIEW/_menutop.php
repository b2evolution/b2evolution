<?php
/**
 * This file displays the first part of the page menu (before the page title).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @todo Let the {@link AdminUI_general AdminUI} object handle this. NEEDS MASSIVE CLEANUP!!!!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $io_charset, $rsc_url, $UserSettings, $Debuglog, $Plugins, $generating_static;
global $month, $month_abbrev, $weekday, $weekday_abbrev; /* for localized calendar */
global $debug, $htsrv_url;

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $this->get_html_title(); ?></title>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
	<?php
	// Include head lines, links (to CSS...), sets <base>, ..
	echo $this->get_headlines();

	global $rsc_url, $htsrv_url;
	?>
	<script type="text/javascript">
		// Paths used by JS functions:
		var imgpath_expand = '<?php echo get_icon( 'expand', 'url' ); ?>';
		var imgpath_collapse = '<?php echo get_icon( 'collapse', 'url' ); ?>';
		var htsrv_url = '<?php echo $htsrv_url ?>';
	</script>

	<!-- script allowing to check and uncheck all boxes in forms -->
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/functions.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/form_extensions.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/anchorposition.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/date.js"></script>
	<script type="text/javascript">
		// Override vars used by date.js (and calendarpopup.js, if present)
		var MONTH_NAMES=new Array( '<?php echo implode("','", array_map('T_',$month)) ?>','<?php echo implode("','", array_map('trim', array_map( 'T_', $month_abbrev ))) ?>' );
		var DAY_NAMES=new Array('<?php echo implode("','", array_map('T_', $weekday)) ?>','<?php echo implode("','", array_map('T_',$weekday_abbrev)) ?>');
	</script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/popupwindow.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/calendarpopup.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/rollovers.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/extracats.js"></script>
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/dynamic_select.js"></script>
	<!-- General admin functions: -->
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/admin.js"></script>
	<!-- include jquery JS: -->
	<script type="text/javascript" src="<?php echo $rsc_url; ?>js/<?php echo ($debug ? 'jquery.js' : 'jquery.min.js'); ?>"></script>

	<?php
	global $UserSettings;
	if( $UserSettings->get('control_form_abortions') )
	{	// Activate bozo validator
		echo '<script type="text/javascript" src="'.$rsc_url.'js/bozo_validator.js"></script>';
	}

	if( $UserSettings->get('focus_on_first_input') )
	{	// Activate focus on first form <input type="text">:
		echo '<script type="text/javascript">addEvent( window, "load", focus_on_first_input, false );</script>';
	}

	global $Debuglog;
	$Debuglog->add( 'Admin-Path: '.var_export($this->path, true) );

	if( $this->get_path(0) == 'files'
			|| ($this->get_path_range(0,1) == array('blogs', 'perm') )
			|| ($this->get_path_range(0,1) == array('blogs', 'permgroup') ) )
	{{{ // -- Inject javascript ----------------
		// gets initialized in _footer.php
		?>
		<script type="text/javascript">
		<!--
		  var allchecked = Array();
		  var idprefix;

			<?php
			switch( $this->get_path(0) )
			{
				case 'files': // {{{
				/**
				 * Toggles status of a bunch of checkboxes in a form
				 *
				 * @param string the form name
				 * @param string the checkbox(es) element(s) name
				 * @param string number/name of the checkall set to use. Defaults to 0 and is needed when there are several "checkall-sets" on one page.
				 */ ?>
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
				<?php // }}}
				break;
			}

			// --- general functions ----------------
			/**
			 * replaces the text of the checkall-html-ID for set_name
			 *
			 * @param integer|string number or name of the checkall "set" to use
			 * @param boolean force setting to true/false
			 */ ?>
			function setcheckallspan( set_name, set )
			{
				if( typeof(allchecked[set_name]) == 'undefined' || typeof(set) != 'undefined' )
				{ // init
					allchecked[set_name] = set;
				}

				if( allchecked[set_name] )
				{
					var replace = document.createTextNode('<?php echo TS_('uncheck all') ?>');
				}
				else
				{
					var replace = document.createTextNode('<?php echo TS_('check all') ?>');
				}

				if( document.getElementById( idprefix+'_'+String(set_name) ) )
				{
					document.getElementById( idprefix+'_'+String(set_name) ).replaceChild(replace, document.getElementById( idprefix+'_'+String(set_name) ).firstChild);
				}
				//else alert('no element with id '+idprefix+'_'+String(set_name));
			}

			<?php
			/**
			 * inits the checkall functionality.
			 *
			 * @param string the prefix of the IDs where the '(un)check all' text should be set
			 * @param boolean initial state of the text (if there is no checkbox with ID htmlid + '_state_' + nr)
			 */ ?>
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
		<?php
	}}}

	// CALL PLUGINS NOW:
	global $Plugins;
	$Plugins->trigger_event( 'AdminEndHtmlHead', array() );
	?>

</head>

