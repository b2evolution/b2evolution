<?php
/**
 * File to handle the maintaining of the static html files of this package
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package internal
 * @author blueyed
 */

require( '../blogs/b2evocore/_functions.php' );
require( '../blogs/conf/_config.php' );
#require( '../blogs/conf/_locales.php' );
#require( '../blogs/b2evocore/_functions_locale.php' );


$pofilepath = dirname(__FILE__).'/langfiles';

// ------------------- CONFIG ------------------------
define( 'TRANSTAG_OPEN', '{{{' );
define( 'TRANSTAG_CLOSE', '}}}' );
define( 'CHDIR_TO_BLOGS', '..' );
define( 'STATIC_POT', $pofilepath.'\static.POT' );
define( 'DEFAULT_TARGET', 'en-EU' );
define( 'DEFAULT_CHARSET', 'iso-8859-1' );
define( 'HIGHLIGHT_UNTRANSLATED', '1' );


// look what translations we have
$pofiles = glob( $pofilepath.'/*.static.po' );
$targets = array( DEFAULT_TARGET );
foreach( $pofiles as $po )
{
	$targets[] = basename( $po, '.static.po' );
}


if( !isset($argv) )
{ // html head
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>b2evo :: static page generation</title>
		<link href="../blogs/admin/admin.css" rel="stylesheet" type="text/css" />
	</head>
	<body class="center">
	<div style="width:75%">
	
	<img src="'.$img_url.'/b2evolution_logo_360.gif" /><br />
	';
}

log_('<hr>');
log_('This script maintains the static html files of the b2evolution project.');
log_('written by <a href="http://thequod.de">daniel hahler</a>, 2004');

if( isset($argv) )
{ // commandline mode
	log_('<hr>');
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
\n.po files are available for: ".implode(', ', $targets)."
";
		exit;
	}
}
else
{
	echo('
	<ul>
	<li><a href="?action=extract">extract to '.STATIC_POT.'</a></li>
	<li><a href="?action=merge">create static files from locales .po files</a> (which are: '.implode(', ', $targets).')</li>
	</ul>');
	log_('<hr>');
	param( 'action', 'string', '' );
	if( empty($action) )
	{
		echo('Please choose an action..<br />');
		exit;
	}
};


/**
 * a quick and dirty class for PO/POT files
 */
class POFile
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
		$msgid = preg_replace('/<a(\s+.*?)href=".*?"(.*?)>/', '<a$1%href$2>', $msgid);
		
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
		if( preg_match('/<a(.*?)(href=".*?")(,*?)>/', $msgid, $matches) )
		{	// we have to replace links
			// remember urls
			$urls = $matches[2];
			
			// generate clean msgid
			$msgid = preg_replace('/<a(.*?)href="(.*?)"(,*?)>/', '<a$1%href$3>', $msgid);
		}
		
		// we don't have formatting in the .po files, but escaped '"'
		$msgid = str_replace( array("\r", "\n", "\t", '"'), array('', ' ', '', '\"'), $msgid);
		
		#pre_dump($msgid);
		
		if( isset($this->msgids[ $msgid ]) )
		{
			$trans = $this->msgids[ $msgid ]['trans'];
			
			if( isset($urls) )
			{
				$trans = str_replace('%href', $urls, $trans);
			}
			
			return $trans;
		}
		else
		{
			return TRANSTAG_OPEN.$msgid.TRANSTAG_CLOSE;
		}
	}
	
	/**
	 * reads .po file
	 *
	 * this is quite the same as in b2options for the extract part.
	 *
	 * @return array with msgids
	 */
	function read()
	{
		$lines = file( $this->filename );
		$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
		$all = 0;
		$fuzzy=0;
		$untranslated=0;
		$translated=0;
		$status='-';
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
}
	
/**
 * a class build upon class POFile to provide specific POT actions (write)
 */
class POTFile extends POFile
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
		
		// add strings used by this script that must also be translated
		foreach( $targets as $target )
		{ // the available locale names
			$this->addmsgid( $locales[$target]['name'] );
		}
		
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
	
}

/**
 * output, respects commandline mode
 */
function log_( $string )
{
	global $argv;
	if( isset($argv) )
	{ // command line mode
		if( $string == '<hr>' )
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

log_('<h1>action: '.$action.'</h1>');

// change to /blogs folder
chdir( CHDIR_TO_BLOGS );

// get the source files
$srcfiles = array();
foreach( glob('{*.src.html,doc/*.src.html}', GLOB_BRACE) as $filename )
{
	$srcfiles[] = $filename;
}

// echo '<hr>'; pre_dump( $srcfiles, 'source files' ); echo '<hr>';


switch( $action )
{
	case 'extract':
		
		$POTFile = new POTFile( STATIC_POT );

		foreach( $srcfiles as $srcfile )
		{
			log_( 'Extracting '.$srcfile.'..' );
			
			$text = implode( '', file( $srcfile ) );
			preg_match_all('/{{{(.*?)}}}/s', $text, $matches_msgids, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
			preg_match_all('/\n/', $text, $matches_line, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
			
			$lm = 0;
			foreach( $matches_msgids[1] as $match )
			{
				#echo $lm.': '.$match[1].' / '.($matches_line[0][$lm][1]).'<br>';
				while( $match[1] > $matches_line[0][ $lm ][1] )
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
		foreach( $targets as $target )
		{
			log_('<h2 style="margin-bottom:0">TARGET: '.$target.'</h2>');
			if( $target != DEFAULT_TARGET )
			{
				$replacesrc = '.'.$target.'.';
			
				log_( 'reading locale: '.$target );
			
				$POFile = new POFile($pofilepath.'/'.$target.'.static.po');
				$POFile->read();
				
				// get charset out of first msgstr
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
			{ // target == DEFAULT_TARGET, so we don't translate
				$replacesrc = '.';
				$charset = DEFAULT_CHARSET;
				log_( 'building default files');
				$POFile = new POFile('');
			}
			
			foreach( $srcfiles as $srcfile )
			{
				$newfilename = str_replace('.src.', $replacesrc, $srcfile);
				
				log_( 'Merging '.$srcfile.' into '.$newfilename );
				$text = implode( '', file( $srcfile ) );
				
				// build "available translations" list
				$list_avail = "\t".'<ul style="margin-left: 2ex;list-style:none;">'."\n";
				
				$flagspath = 'blogs/img/flags';
				for($i = 1; $i < count(split('/', $srcfile)); $i++)
				{
					$flagspath = '../'.$flagspath;
				}
				
				foreach( $targets as $ttarget )
				{
					$linkto = str_replace('.src.', ( $ttarget != DEFAULT_TARGET ) ? ".$ttarget." : '.', basename($srcfile) );
					$list_avail .=
					"\t\t".'<li><a href="'.$linkto.'">'.locale_flag($ttarget, 'w16px', 'flag', '', false, $flagspath).$POFile->translate( $locales[$ttarget]['name'] ).'</a></li>'."\n";
				}
				$list_avail .= "\t</ul>";
				$text = str_replace( TRANSTAG_OPEN.'trans_available'.TRANSTAG_CLOSE, $list_avail, $text );
				
				if( $target != DEFAULT_TARGET )
				{
					$text = preg_replace( '/'.TRANSTAG_OPEN.'(.*?)'.TRANSTAG_CLOSE.'/es', '$POFile->translate(stripslashes(\'$1\'))', $text );
					
					if( strpos( $text, TRANSTAG_OPEN ) !== false )
					{ // there are still tags.
						log_('<span style="color:blue">WARNING: some strings have not been translated!</span>');
					}
				}
				
				// standard replacements
				$search = array(
					// internal replacements
					TRANSTAG_OPEN.'trans_locale'.TRANSTAG_CLOSE, TRANSTAG_OPEN.'trans_charset'.TRANSTAG_CLOSE,
					'<html',                          // add note about generator
				);
				$replace = array(
					$target, $charset,
					'<!-- This file was generated automatically by /gettext/staticfiles.php - Do not edit this file manually -->'."\n".'<html'
				);
				// left TAGs
				array_push( $search, TRANSTAG_OPEN, TRANSTAG_CLOSE );
				if( HIGHLIGHT_UNTRANSLATED && !($target == DEFAULT_TARGET) )
				{ // we want to highlight untranslated strings
					array_push( $replace, '<span style="color:red" title="not translated">', '</span>' );
				}
				else
				{ // just remove tags
					array_push( $replace, '', '' );
				}
				$text = str_replace( $search, $replace, $text);
				
				// replace links
				$text = preg_replace( '/\.src\.(html)/', $replacesrc."$1", $text );
				
				// emphasize links to the file itself
				$text = preg_replace( ':(<a[^>]+href="'.basename($newfilename).'"[^>]*>)(.+?)(<\/a>):s', '$1<strong>$2</strong>$3', $text);
				
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
	echo '</body></html>';
}

?>