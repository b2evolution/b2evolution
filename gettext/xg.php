<?php
/**
 * Create a new messages.POT file and update specified .po files.
 *
 * Uses find, xargs, sed, xgettext and msgmerge tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @package internal
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */

/**
 * @global array These locales messages.PO files will be merged after
 *               generating the new messages.POT file.
 */
$localesToMerge = array( 'de_DE' );


// check that all external tools are available:
foreach( array( 'xgettext', 'msgmerge', 'find', 'xargs', 'sed' ) as $testtool )
{
	exec( $testtool.' --version', $output, $return );
	if( $return !== 0 )
	{
		die( "This script needs the $testtool tool.\n" );
	}
}

# extract T_() and NT_() strings from all .php files below ../blogs
system( 'find ../blogs/ -iname "*.php"'
				.' | xargs xgettext -D ../blogs/ -o ../blogs/locales/messages.pot --no-wrap --add-comments=TRANS --copyright-holder="Francois PLANQUE" --msgid-bugs-address=http://fplanque.net/ --keyword=T_ --keyword=NT_ -F' );


# replace various things (see comments)
system( 'sed -i "'
				# remove \r:
				.'s!\\\\\\\\r!!g;'
				# make paths relative to the .po files:\
				.'s! ../blogs/! ../../../!g;'
				.'" ../blogs/locales/messages.pot' );

# Replace header "vars" in first 20 lines:
system( 'sed -i 1,20"'
				.'s/PACKAGE/b2evolution/;'
				.'s/VERSION/0.9.2-CVS/;'
				.'s/# SOME DESCRIPTIVE TITLE./# b2evolution - Language file/;'
				.'s/YEAR/2004/;'
				.'s/CHARSET/iso-8859-1/;'
				.'" ../blogs/locales/messages.pot' );


# Merge with existing .po files:
foreach( $localesToMerge as $llocale )
{
	$pofile = '../blogs/locales/'.$llocale.'/LC_MESSAGES/messages.po';

	echo 'Merging with '.$llocale;

	if( !file_exists( $pofile ) )
	{
		echo "PO file $pofile not found!\n";
		continue;
	}

	system( "msgmerge -U -F --no-wrap $pofile ../blogs/locales/messages.pot" );
	# delete old TRANS comments and make automatic ones valid comments:
	system( 'sed -i -r "/^#\\s+TRANS:/d; s/^#\\. TRANS:/# TRANS:/;" '.$pofile );
	echo "\n";
}

?>
