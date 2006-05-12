<?php
/**
 * This file implements the {@link POFile} and {@link POTFile} classes, used to handle gettext style
 * .PO and .POT files.
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
 * A quick and dirty class for PO/POT files
 *
 * @package internal
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
	 * @return array with msgids => array( 'trans' => msgstr )
	 */
	function read( $echo_source_info = true )
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

		if( $echo_source_info )
		{
			ksort( $loc_vars );
			foreach( $loc_vars as $source => $c )
			{
				echo $source, ' = ', $c, '<br />';
			}
		}

		return( $this->msgids );
	}
}


/**
 * A class build upon class POFile to provide specific POT actions (write)
 *
 * @package internal
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

?>
