<?php
/**
 * This file attaches JS behaviors to item_form objects
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.

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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $js_doc_title_prefix;
?>
<script type="text/javascript">
	<?php
	// Add event to the item title field to update document title and init it (important when switching tabs/blogs):
	if( isset($js_doc_title_prefix) )
	{ // dynamic document.title handling:
		?>
		if( post_title_elt = document.getElementById('post_title') )
		{
			/**
			 * Updates document.title according to the item title field (post_title)
			 */
			function evo_update_document_title()
			{
				var posttitle = document.getElementById('post_title').value;

				document.title = document.title.replace( /(<?php echo preg_quote( trim($js_doc_title_prefix) /* e.g. FF2 trims document.title */ ) ?>).*$/, '$1 '+posttitle );
			}

			addEvent( post_title_elt, 'keyup', evo_update_document_title, false );

			// Init:
			evo_update_document_title();
		}
		<?php
	}

	// Add event to check the edit_date checkbox whenever the date is modified:
	if( $creating )
	{
		?>
		if( edit_date_elt = document.getElementById('edit_date') )
		{
			/**
			 * If user modified date, check the checkbox:
			 */
			function evo_check_edit_date()
			{
				edit_date_elt.checked = true;
			}

			if( item_issue_date_elt = document.getElementById('item_issue_date') )
			{
				addEvent( item_issue_date_elt, 'change', evo_check_edit_date, false );
			}
			if( item_issue_time_elt = document.getElementById('item_issue_time') )
			{
				addEvent( item_issue_time_elt, 'change', evo_check_edit_date, false );
			}
			if( item_issue_date_button = document.getElementById('anchor_item_issue_date') )
			{
				addEvent( item_issue_date_button, 'click', evo_check_edit_date, false );
			}
		}
		<?php
	}
	?>
</script>

<?php
/*
 * $Log$
 * Revision 1.4  2006/12/13 18:35:56  blueyed
 * Talking about behavior
 *
 * Revision 1.3  2006/12/12 23:23:30  fplanque
 * finished post editing v2.0
 *
 * Revision 1.2  2006/12/12 21:25:31  fplanque
 * UI fixes
 *
 * Revision 1.1  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 */
?>