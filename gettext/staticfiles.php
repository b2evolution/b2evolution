<?php
/**
 * File to handle the maintaining of the static html files of this package
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package internal
 * @author blueyed: Daniel HAHLER
 */

/**
 * Load config
 */
require( '../blogs/conf/_config.php' );

define( 'EVO_MAIN_INIT', true );

/**#@+
 * Load required functions
 */
require $misc_inc_path.'_log.class.php';
require $misc_inc_path.'_misc.funcs.php';
require $misc_inc_path.'_form.funcs.php';
/**#@-*/

$Debuglog = new Log();


$pofilepath = dirname(__FILE__).'/langfiles';

// ------------------- CONFIG ------------------------
define( 'TRANSTAG_OPEN', '{{{' );
define( 'TRANSTAG_CLOSE', '}}}' );
define( 'CHDIR_TO_BLOGS', '..' );
define( 'STATIC_POT', $pofilepath.'\static.POT' );
define( 'DEFAULT_TARGET', 'en-EU' );
define( 'DEFAULT_CHARSET', 'iso-8859-1' );

param('highlight_untranslated', 'integer', 0 );
param('action', 'string', '' );


// look what translations we have
$pofiles = glob( $pofilepath.'/*.static.po' );
$targets[ DEFAULT_TARGET ] = '';

// add targets that use same message file
foreach( $locales as $key => $value )
{
	if( substr($value['messages'], 0, 2 ) == substr( $locales[ DEFAULT_TARGET ]['messages'], 0, 2 ) )
	{
		$targets[ $key ] = '';
	}
}

// Discover targets from *.static.po files
foreach( $pofiles as $po )
{
	$target = basename( $po, '.static.po' );
	$targets[ $target ] = $po;

	// add targets that use same message file
	foreach( $locales as $key => $value ) if( $key != $target )
	{
		if( $value['messages'] == $locales[ $target ]['messages'] )
		{
			$targets[ $key ] = $po;
		}
	}
}


if( !isset($argv) )
{ // html head
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>b2evo :: static page generation</title>
		<link href="../blogs/skins_adm/legacy/rsc/css/desert.css" rel="stylesheet" type="text/css" title="Desert" />
	</head>
	<body>
	<div class="center" style="margin:auto;width:75%">

	<img src="<?php echo $rsc_url ?>img/b2evolution_logo_360.gif" /><br />
<?php
}

log_('<hr />');
log_('This script maintains the static html files of the b2evolution project.');
log_('written by <a href="http://thequod.de/contact">daniel hahler</a>, 2004');

if( isset($argv) )
{ // commandline mode
	log_('<hr />');
	if( isset($argv[1]) && in_array($argv[1], array('extract', 'merge')) )
	{
		$action = $argv[1];
	}
	else
	{
		echo "
Usage: $argv[0] <extract|merge>
extract: extracts all translatable strings into ".STATIC_POT.".
merge: creates all static files for which there are .po files in the current directory.
\ntargets: ".implode(', ', array_keys( $targets ))."
";
		exit;
	}
}
elseif( $action == '' )
{
	htmlmenu();
}

function htmlmenu()
{
	global $targets, $highlight_untranslated;
	echo '
	<hr />
	<br />
	<div style="width:75%;margin:auto">
	<form method="get" class="fform">
	<fieldset>
		<legend>Create translated files</legend>
		<input type="hidden" name="action" value="merge" />
		<input type="checkbox" value="1" name="highlight_untranslated" '.( ($highlight_untranslated) ? 'checked="checked"' : '' ).' />
		highlight untranslated strings
		<br /><br />(targets: '.implode(', ', array_keys( $targets )).')
		<br /><br /><input type="submit" value="create static files from locales .po files" class="search" />
	</fieldset>
	</form>

	<form method="get" class="fform">
	<fieldset>
		<legend>Extract translatable strings into .POT file</legend>
		<input type="hidden" name="action" value="extract" />
		';
		form_info( 'static POT file', str_replace( '\\', '/', STATIC_POT ) );
		echo '
		<input type="submit" value="extract" class="search" />
	</fieldset>
	</form>
	</div>
	<br /><br />
	';
};

/**
 * A quick and dirty class for PO/POT files
 *
 * @package internal
 */
class POFile // {{{
{
	var $msgids = array();

	function POFile($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * adds a MSGID for a specific source file
	 */
	function addmsgid( $msgid, $sourcefile = '', $trans = '' )
	{
		if( in_array($msgid, array('trans_locale', 'trans_charset', 'trans_available')) )
		{ // don't put those into POT file
			return;
		}

		// replace links
		$msgid = preg_replace('/<a\s+([^>]*)>/', '<a %s>', $msgid);

		// we don't want tabs and returns in the msgid, but we must escape '"'
		$search = array("\r", "\n", "\t", '"');
		$replace = array('', ' ', '', '\"');
		$msgid = str_replace($search, $replace, $msgid);

		if( !isset($this->msgids[ $msgid ]) )
		{
			$this->msgids[ $msgid ] = '';
		}
		if( !empty($sourcefile) )
		{
			$this->msgids[ $msgid ]['source'][] = $sourcefile;
		}
		$this->msgids[ $msgid ]['trans'][] = $trans;
	}

	/**
	 * translates msgid
	 */
	function translate( $msgid )
	{
		$omsgid = $msgid;  // remember

		if( preg_match_all('/<a\s+([^>]*)>/', $msgid, $matches) )
		{ // we have to replace links
			// remember a-tag params
			$aparams = $matches[1];

			// generate clean msgid like in .po files
			$msgid = preg_replace('/<a\s+([^>]*)>/', '<a %s>', $msgid);
		}

		// we don't have formatting in the .po files, but escaped '"'
		$msgid = str_replace( array("\r", "\n", "\t", '"'), array('', ' ', '', '\"'), $msgid);

		if( isset($this->msgids[ $msgid ]) )
		{
			$trans = $this->msgids[ $msgid ]['trans'];

			if( isset($aparams) )
			{
				$trans = vsprintf($trans, $aparams);
			}

			return $trans;
		}
		else
		{
			#pre_dump( $msgid, 'not translated!' );
			return TRANSTAG_OPEN.$omsgid.TRANSTAG_CLOSE;
		}
	}

	/**
	 * reads .po file
	 *
	 * this is quite the same as in locales.php for the extract part.
	 *
	 * @return array with msgids
	 */
	function read()
	{
		$lines = file( $this->filename );
		$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
		$all = 0;
		$fuzzy = 0;
		$untranslated = 0;
		$translated = 0;
		$status = '-';
		$matches = array();
		$sources = array();
		$loc_vars = array();
		$this->msgids = array();
		foreach ($lines as $line)
		{
			// echo 'LINE:', $line, '<br />';
			if(trim($line) == '' )
			{	// Blank line, go back to base status:
				if( $status == 't' )
				{	// ** End of a translation **:
					if( $msgstr == '' )
					{
						$untranslated++;
						// echo 'untranslated: ', $msgid, '<br />';
					}
					else
					{
						$translated++;

						// Inspect where the string is used
						$sources = array_unique( $sources );
						// echo '<p>sources: ', implode( ', ', $sources ), '</p>';
						foreach( $sources as $source )
						{
							if( !isset( $loc_vars[$source]  ) ) $loc_vars[$source] = 1;
							else $loc_vars[$source] ++;
						}

						// Save the string
						// $ttrans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => '".str_replace( "'", "\'", str_replace( '\"', '"', $msgstr ))."',";
						// $ttrans[] = "\n\t\"$msgid\" => \"$msgstr\",";
						#$ttrans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => \"".str_replace( '$', '\$', $msgstr)."\",";
						$this->msgids[$msgid]['trans']
							= str_replace( array('\t', '\r', '\n', '\"'), array("\t", "\r", "\n", '"'), $msgstr);

					}
				}
				$status = '-';
				$msgid = '';
				$msgstr = '';
				$sources = array();
			}
			elseif( ($status=='-') && preg_match( '#^msgid "(.*)"#', $line, $matches))
			{	// Encountered an original text
				$status = 'o';
				$msgid = $matches[1];
				// echo 'original: "', $msgid, '"<br />';
				$all++;
			}
			elseif( ($status=='o') && preg_match( '#^msgstr "(.*)"#', $line, $matches))
			{	// Encountered a translated text
				$status = 't';
				$msgstr = $matches[1];
				// echo 'translated: "', $msgstr, '"<br />';
			}
			elseif( preg_match( '#^"(.*)"#', $line, $matches))
			{	// Encountered a followup line
				if ($status=='o')
					$msgid .= $matches[1];
				elseif ($status=='t')
					$msgstr .= $matches[1];
			}
			elseif( ($status=='-') && preg_match( '@^#:(.*)@', $line, $matches))
			{	// Encountered a source code location comment
				// echo $matches[0],'<br />';
				$sourcefiles = preg_replace( '@\\\\@', '/', $matches[1] );
				// $c = preg_match_all( '@ ../../../([^:]*):@', $sourcefiles, $matches);
				$c = preg_match_all( '@ ../../../([^/:]*)@', $sourcefiles, $matches);
				for( $i = 0; $i < $c; $i++ )
				{
					$sources[] = $matches[1][$i];
				}
				// echo '<br />';
			}
			elseif(strpos($line,'#, fuzzy') === 0)
				$fuzzy++;
		}

		ksort( $loc_vars );
		foreach( $loc_vars as $source => $c )
		{
			echo $source, ' = ', $c, '<br />';
		}

		return( $this->msgids );
	}
} // }}}


/**
 * A class build upon class POFile to provide specific POT actions (write)
 *
 * @package internal
 */
class POTFile extends POFile  // {{{
{
	function write()
	{
		global $targets, $locales;

		log_('Writing POTFile '.$this->filename.'..');
		$fh = fopen( $this->filename, 'w' );
		fwrite($fh, '# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR Francois PLANQUE
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: PACKAGE VERSION\n"
"Report-Msgid-Bugs-To: http://fplanque.net/\n"
"POT-Creation-Date: 2004-04-26 03:00+0200\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=CHARSET\n"
"Content-Transfer-Encoding: 8bit\n"

');

		$count = 0;

		foreach( $this->msgids as $msgid => $arr )
		{
			if( isset($arr['source']) )
			{ // write sources of string
				foreach( $arr['source'] as $source )
				{
					fwrite( $fh, '#: ../../'.$source."\n" );
				}
			}
			fwrite( $fh, 'msgid "'.$msgid.'"'."\nmsgstr ".'""'."\n\n" );

			$count++;

		}

		fclose( $fh );

		log_($count.' msgids written.');
	}

} // }}}


/**
 * output, respects commandline mode
 */
function log_( $string )
{
	global $argv;
	if( isset($argv) )
	{ // command line mode
		if( $string == '<hr />' )
			echo '------------------------------------------------------------------------------';
		else
		{ // remove tags
			$string = strip_tags($string);
			echo $string;
		}
		echo "\n";
	}
	else
	{
		echo $string."<br />\n";
	}
}


// HERE WE GO -------------------------------

if( $action )
{
	log_('<div class="panelinfo"><p><strong>action: '.$action.'</strong></p>'
				.( ( $highlight_untranslated && $action == 'merge' ) ? 'note: untranslated strings will get highlighted!' : '' )
				.'</div>'
			);
}

// change to /blogs folder
chdir( CHDIR_TO_BLOGS );
#pre_dump( getcwd(), 'cwd' );

// get the source files
$srcfiles = array();

foreach( array( '.', 'doc' ) as $dir )
{
	if( $fp = opendir($dir) )
	{
		while( ($file = readdir($fp) ) !== false )
		{
			if( $dir != '.' )
			{
				$file = $dir.'/'.$file;
			}
			if( is_file($file) && preg_match('/\.src\.html$/', $file))
			{
				$srcfiles[] = $file;
			}
		}
		closedir($fp);
	}
	else log( 'could not open directory '.$dir );
}

// echo '<hr />'; pre_dump( $srcfiles, 'source files' ); echo '<hr />';


switch( $action )
{
	case 'extract':

		$POTFile = new POTFile( STATIC_POT );

		foreach( $srcfiles as $srcfile )
		{
			log_( 'Extracting '.$srcfile.'..' );

			// get source file content
			$text = implode( '', file( $srcfile ) );
			// get all strings to translate
			preg_match_all('/'.TRANSTAG_OPEN.'(.*?)'.TRANSTAG_CLOSE.'/s', $text, $matches_msgids, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
			// get all newlines (to assign source file line numbers to msgids later)
			preg_match_all('/\n/', $text, $matches_line, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);

			$lm = 0;  // represents line numer - 1
			foreach( $matches_msgids[1] as $match )
			{
				while( isset($matches_line[0][$lm]) && ($match[1] > $matches_line[0][ $lm ][1]) )
				{ // assign line numbers
					$lm++;
				}
				$POTFile->addmsgid( $match[0], $srcfile.':'.($lm + 1) );
				#log_(' ['.$srcfile.':'.($lm + 1).']<br />');
			}
		}

		// write POT file
		$POTFile->write();
	break;

	case 'merge':
		foreach( $targets as $target => $targetmessagefile )
		{ // loop targets/locales
			log_('<h2 style="margin-bottom:0">TARGET: '.$target.'</h2>');
			if( $targetmessagefile != '' )
			{ // only translate when not DEFAULT_TARGET
				log_( 'reading .po file: '.basename( $targetmessagefile ) );

				$POFile = new POFile( $targetmessagefile );
				$POFile->read();

				// get charset out of .PO file header
				if( preg_match( '/; charset=(.*?)\n/', $POFile->translate(''), $matches ) )
				{
					$charset = $matches[1];
					if( $charset == 'CHARSET' )
					{
						log_('Invalid charset "'.$charset.'". Will use default, '.DEFAULT_CHARSET);
						$charset = DEFAULT_CHARSET;
					}
					log_('Charset: '.$charset);
				}
				else
				{
					log_('<span style="color:red">WARNING: no charset found. Will use '.DEFAULT_CHARSET.'.</span>');
					$charset = DEFAULT_CHARSET;
				}
			}
			else
			{ // no $targetmessagefile, so we don't translate
				$charset = DEFAULT_CHARSET;
				log_( 'building default files');
				$POFile = new POFile('');
			}
			$replacesrc = $target != DEFAULT_TARGET ?
											'.'.$target.'.' :
											'.';


			foreach( $srcfiles as $srcfile )
			{ // loop through sourcefiles
				// the file to create
				$newfilename = str_replace('.src.', $replacesrc, $srcfile);

				log_( 'Merging '.$srcfile.' into '.$newfilename );
				$text = implode( '', file( $srcfile ) );

				$path_to_root = '';
				for($i = 1; $i < count(split('/', $srcfile)); $i++)
				{
					$path_to_root = '../'.$path_to_root;
				}


				// --- build "available translations" list -------------
				locale_activate( $target );  // activate locale to translate locale names

				// Sort the targets by their translated name
				$sortedTargets = $targets;
				uksort( $sortedTargets, create_function( '$a,$b', 'return strcasecmp( T_( $GLOBALS[\'locales\'][$a][\'name\'] ), T_( $GLOBALS[\'locales\'][$b][\'name\'] ) );' ) );

				$trans_available = "\t".'<ul>'."\n";
				foreach( $sortedTargets as $ttarget => $ttargetmessagefile )
				{ // the link to the static html file for that target message file
					$linkto = str_replace('.src.', ( $ttarget != DEFAULT_TARGET ) ? ".$ttarget." : '.', basename($srcfile) );

					$trans_available .=
					"\t\t".'<li><a href="'.$linkto.'">'.locale_flag($ttarget, 'w16px', 'flag', '', false, $path_to_root.'blogs/rsc/flags').T_( $locales[$ttarget]['name'] ).'</a></li>'."\n";
				}
				$trans_available .= "\t</ul>";

				$text = str_replace( TRANSTAG_OPEN.'trans_available'.TRANSTAG_CLOSE, $trans_available, $text );


				// standard replacements
				$search = array(
					// internal replacements
					TRANSTAG_OPEN.'trans_locale'.TRANSTAG_CLOSE,
					urlencode( TRANSTAG_OPEN.'trans_locale'.TRANSTAG_CLOSE ), // DW fix
					TRANSTAG_OPEN.'trans_charset'.TRANSTAG_CLOSE,
					'<html', // add note about generator
				);
				$replace = array(
					$target,
					$target,
					$charset,
					'<!-- This file was generated automatically by /gettext/staticfiles.php - Do not edit this file manually -->'."\n".'<html'
				);
				$text = str_replace( $search, $replace, $text);


				// emphasize links to start page (small-caps)
				#pre_dump('/index.([a-z]{2}-[A-Z]{2}(-.{1,14})?.)?html/', $newfilename );
				/*if( preg_match( '/index.([a-z]{2}-[A-Z]{2}(-.{1,14})?.)?html/', $newfilename ) )
				{ // start page is current
					$text = preg_replace( '/(<a .*?>)({{{Start page}}})(<\/a>)/s', '$1<span style="font-variant:small-caps">$2</span>$3', $text );
				}*/

				// emphasize current flag link (<strong>)
				$text = preg_replace( '/(<a[^>]+href="(..\/)?'.basename($newfilename).'(\?.*?)?"[^>]*><img .*?>)(.+?)(<\/a>)/s',
															'$1<strong>$4</strong>$5',
															$text);


				if( $targetmessagefile != '' )
				{ // translate everything
					$text = preg_replace( '/'.TRANSTAG_OPEN.'(.*?)'.TRANSTAG_CLOSE.'/es', '$POFile->translate(stripslashes(\'$1\'))', $text );

					if( strpos( $text, TRANSTAG_OPEN ) !== false )
					{ // there are still tags.
						#pre_dump( $text, substr( $text, strpos( $text, TRANSTAG_OPEN ), 30 ) );
						log_('<span style="color:blue">WARNING: some strings have not been translated!</span>');
					}
				}


				// handle left TRANSTAGs
				if( $highlight_untranslated && $targetmessagefile != '' )
				{ // we want to highlight untranslated strings
					$text = str_replace( array(TRANSTAG_OPEN, TRANSTAG_CLOSE), array('<span style="color:red" title="not translated">', '</span>'), $text );
				}
				else
				{ // just remove tags
					$text = str_replace( array(TRANSTAG_OPEN, TRANSTAG_CLOSE), '', $text );
				}


				// replace links
				$text = preg_replace( '/\.src\.(html)/', $replacesrc."$1", $text );


				// remove DW tags
				$text = preg_replace( '/<!-- Instance(Begin|End|Param).*? -->/', '', $text );

				$fh = fopen( $newfilename, 'w' );
				fwrite( $fh, $text );
				fclose( $fh );
			}
			log_('');
		}

	break;
}

log_('');
log_('Finito.');

if( !isset($argv) )
{
	if( !empty($action) )
		htmlmenu();
	echo '</div></body></html>';
}

?>