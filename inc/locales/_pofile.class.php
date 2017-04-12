<?php
/**
 * This file implements the {@link POFile} and {@link POTFile} classes, used to handle gettext style
 * .PO and .POT files.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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

	function __construct($filename=null)
	{
		// We probably don't need the windows backslashes replacing any more but leave it for safety because it doesn't hurt:
		$this->filename = str_replace( '\\', '/', $filename );
	}

	/**
	 * Add a MSGID for a specific source file
	 */
	function addmsgid( $msgid, $sourcefile = '', $trans = '' )
	{
		if( in_array($msgid, array('trans_locale', 'trans_charset', 'trans_available')) )
		{ // don't put those into POT file
			return;
		}

		// replace links
		$msgid = preg_replace('/<a\s+([^>]*)>/', '<a %s>', $msgid);
		// replace newlines, tabs, carriage returns and double quotes:
		$msgid = str_replace( array( "\n", "\t", "\r", '"' ),
							  array( '\n', '\t', '\r', '\"' ), $msgid );

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
	 * Translate msgid
	 * @param string MSGID
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
		// replace newlines, tabs, carriage returns and double quotes:
		$msgid = str_replace( array( "\n", "\t", "\r", '"' ),
							  array( '\n', '\t', '\r', '\"' ), $msgid );

		if( isset($this->msgids[ $msgid ]) )
		{
			$trans = $this->msgids[ $msgid ]['trans'];

			if( isset($aparams) )
			{
				$trans = vsprintf($trans, $aparams);
			}

			return str_replace( array( '\n', '\t', '\r', '\"' ),
								array( "\n", "\t", "\r", '"' ), $trans );
		}
		else
		{
			#pre_dump( $msgid, 'not translated!' );
			return TRANSTAG_OPEN.$omsgid.TRANSTAG_CLOSE;
		}
	}


	/**
	 * Read a .po file
	 *
	 * @param boolean Log source info to {@link $Messages}?
	 * @return array with msgids => array( 'trans' => msgstr )
	 */
	function read( $log_source_info = true )
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
		$is_fuzzy = false;
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
					elseif( $is_fuzzy )
					{
						$fuzzy++;
						// echo 'fuzzy: ', $msgid, "<br />\n";
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
						$this->msgids[$msgid]['trans'] = $msgstr;
					}
				}
				$status = '-';
				$msgid = '';
				$msgstr = '';
				$sources = array();
				$is_fuzzy = false;
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
				$c = preg_match_all( '@ ../../../([^/:]*/?)@', $sourcefiles, $matches);
				for( $i = 0; $i < $c; $i++ )
				{
					$sources[] = $matches[1][$i];
				}
				// echo '<br />';
			}
			elseif(strpos($line,'#, fuzzy') === 0)
			{
				$is_fuzzy = true;
			}
		}

		if( $loc_vars && $log_source_info )
		{
			global $Messages;
			ksort( $loc_vars );

			$list_counts = '';
			foreach( $loc_vars as $source => $c )
			{
				$list_counts .= "\n<li>$source = $c</li>";
			}
			$Messages->add( 'Sources and number of strings: <ul>'.$list_counts.'</ul>', 'note' );
		}

		return $this->msgids;
	}


	/**
	 * Write POFile::$msgids into $file_path.
	 *
	 * @return true|string True on success, string with error on failure
	 */
	function write_evo_trans($file_path, $locale)
	{
		$fp = fopen( $file_path, 'w+' );

		if( ! $fp )
		{
			return "Could not open $file_path for writing!";
		}

		fwrite( $fp, "<?php\n" );
		fwrite( $fp, "/*\n" );
		fwrite( $fp, " * Global lang file\n" );
		fwrite( $fp, " * This file was generated automatically from messages.po\n" );
		fwrite( $fp, " */\n" );
		fwrite( $fp, "if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );" );
		fwrite( $fp, "\n\n" );


		fwrite( $fp, '$trans[\''.$locale."'] = array(\n" );

		// Write meta/format info:
		$charset = 'utf-8'; // default
		if( isset($this->msgids['']) )
		{
			if( preg_match( '~\\\nContent-Type: text/plain; charset=(.*?);?\\\n~', $this->msgids['']['trans'], $match ) )
			{
				$charset = strtolower($match[1]);
			}
		}
		fwrite( $fp, "'__meta__' => array('format_version'=>1, 'charset'=>'$charset'),\n" );

		foreach( $this->msgids as $msgid => $msginfo )
		{
			$msgstr = $msginfo['trans'];

			fwrite( $fp, POFile::quote($msgid).' => '.POFile::quote($msgstr).",\n" );
		}
		fwrite( $fp, "\n);\n?>" );
		fclose( $fp );

		return true;
	}


	/**
	 * Quote a msgid/msgstr, preferrable with single quotes.
	 *
	 * Single quotes are preferred, as PHP just handles them as strings and
	 * does no extra parsing.
	 * Double quotes are used, if there's \n, \r or \t in the string.
	 *
	 * @param string
	 * @return string Quoted string (either using double or single quotes (preferred))
	 */
	function quote($s)
	{
		if( preg_match('~\\\\[nrt]~', $s) ) // \r, \n or \t in there
		{
			// NOTE: no need to escape '"', as its escaped in .po files already
			return '"'.str_replace( '$', '\$', $s ).'"';
		}
		else
		{
			return "'".str_replace( array("'", '\"'), array("\'", '"'), $s )."'";
		}
	}

}


/**
 * A class build upon class POFile to provide specific POT actions (write)
 *
 * @package internal
 */
class POTFile extends POFile
{
	/**
	 * @return boolean
	 */
	function write()
	{
		global $targets, $locales;

		log_('Writing POTFile '.$this->filename.'..');
		$fh = @fopen( $this->filename, 'w' );
		if( ! $fh )
		{
			log_( sprintf('<p class="error">Could not open %s for writing.</p>', $this->filename) );
			return false;
		}
		fwrite($fh, '# SOME DESCRIPTIVE TITLE.'."\n"
			.'# Copyright (C) YEAR Francois PLANQUE'."\n"
			.'# This file is distributed under the same license as the PACKAGE package.'."\n"
			.'# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.'."\n"
			.'#'."\n"
			.'#, fuzzy'."\n"
			.'msgid ""'."\n"
			.'msgstr ""'."\n"
			.'"Project-Id-Version: PACKAGE VERSION\n"'."\n"
			.'"Report-Msgid-Bugs-To: http://fplanque.net/\n"'."\n"
			.'"POT-Creation-Date: 2004-04-26 03:00+0200\n"'."\n"
			.'"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"'."\n"
			.'"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"'."\n"
			.'"Language-Team: LANGUAGE <LL@li.org>\n"'."\n"
			.'"MIME-Version: 1.0\n"'."\n"
			.'"Content-Type: text/plain; charset=CHARSET\n"'."\n"
			.'"Content-Transfer-Encoding: 8bit\n"'."\n"
		);

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
		return true;
	}


}

?>