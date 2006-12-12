<?php
/**
 * This file attached JS bahviors to item_form objects
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>
<script type="text/javascript">
	<?php
	// Add event to the item title field to update document title and init it (important when switching tabs/blogs):
	if( isset($js_doc_title_prefix) )
	{ // dynamic document.title handling:
		?>
		alert( 'a' );
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

</script>

<?php
/*
 * $Log$
 * Revision 1.1  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 */
?>