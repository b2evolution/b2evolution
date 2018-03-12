<?php
/**
 * This file display the form to export into XML file
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _xml.form.php 505 2011-12-09 20:54:21Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $export_Module, $options;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'exportxml' );
$Form->hidden_ctrl();

$Form->begin_fieldset( 'Export to XML file' );

echo $export_Module->T_('When you click the button below XML file will be created to save to your computer.');

$BlogCache = & get_BlogCache();
$BlogCache->none_option_text = '&nbsp;';

$Form->select_input_object( 'blog_ID', param( 'blog_ID', 'integer', 0 ), $BlogCache, T_('Blog for export'), array(
		'note' => T_('The data of this blog will be used for export.'),
		'allow_none' => true, 'required' => true ) );

$Form->checkbox_input( 'options[all]', isset( $options['all'] ) || empty( $options ) ? 1 : 0, $export_Module->T_('Select what to export'), array( 'input_suffix' => '&nbsp;<label for="options_all_">'.$export_Module->T_('All content').'</label>', 'note' => $export_Module->T_('This will contain all users, categories, posts, comments and tags.' ), 'required' => true ) );

$Form->checkbox_input( 'options[user]', isset( $options['user'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_user_">'.$export_Module->T_('Users').'</label>' ) );

$Form->checkbox_input( 'options[pass]', isset( $options['pass'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_pass_">'.$export_Module->T_('Include (md5-hashed) user passwords in export').'</label>', 'input_prefix' => '&nbsp; &nbsp; ' ) );

$Form->checkbox_input( 'options[cat]', isset( $options['cat'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_cat_">'.$export_Module->T_('Categories').'</label>' ) );

$Form->checkbox_input( 'options[tag]', isset( $options['tag'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_tag_">'.$export_Module->T_('Tags').'</label>' ) );

$Form->checkbox_input( 'options[post]', isset( $options['post'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_post_">'.$export_Module->T_('Posts').'</label>' ) );

$Form->checkbox_input( 'options[comment]', isset( $options['comment'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_comment_">'.$export_Module->T_('Comments').'</label>', 'input_prefix' => '&nbsp; &nbsp; ' ) );

$Form->checkbox_input( 'options[file]', isset( $options['file'] ) ? 1 : 0, '', array( 'input_suffix' => '&nbsp;<label for="options_file_">'.$export_Module->T_('Files').'</label>', 'input_prefix' => '&nbsp; &nbsp; ' ) );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[export]', $export_Module->T_('Download XML file'), 'SaveButton' ) ) );

?>
<script type="text/javascript">
jQuery( 'input[name^=options]' ).click( function()
{	// Event of clicking on the checkbox to choose what to export
	if( jQuery( this ).attr( 'name' ) == 'options[all]' )
	{	// Click on the option 'All content'
		if( jQuery( this ).is( ':checked' ) )
		{	// Deselect all child options
			jQuery( 'input[name^=options]' ).not( '[name*=all]' ).removeAttr( 'checked' );
		}
		else
		{	// Select all child options
			jQuery( 'input[name^=options]' ).not( '[name*=all]' ).attr( 'checked', 'checked' );
		}
	}
	else if( jQuery( this ).attr( 'name' ) != 'options[all]' && jQuery( this ).is( ':checked' ) )
	{	// Deselect an option 'All content'
		jQuery( 'input#options_all_' ).removeAttr( 'checked' );
	}

	if( jQuery( this ).attr( 'name' ) == 'options[comment]' && jQuery( this ).is( ':checked' ) )
	{	// Select a post option when a comment option is selected
		jQuery( 'input#options_post_' ).attr( 'checked', 'checked' );
	}
	if( jQuery( this ).attr( 'name' ) == 'options[post]' && !jQuery( this ).is( ':checked' ) )
	{	// Deselect a comment option when a post option is deselected
		jQuery( 'input#options_comment_' ).removeAttr( 'checked' );
	}

	if( jQuery( this ).attr( 'name' ) == 'options[file]' && jQuery( this ).is( ':checked' ) )
	{	// Select a post option when a comment option is selected
		jQuery( 'input#options_post_' ).attr( 'checked', 'checked' );
	}
	if( jQuery( this ).attr( 'name' ) == 'options[post]' && !jQuery( this ).is( ':checked' ) )
	{	// Deselect a comment option when a post option is deselected
		jQuery( 'input#options_file_' ).removeAttr( 'checked' );
	}

	if( jQuery( this ).attr( 'name' ) == 'options[pass]' && jQuery( this ).is( ':checked' ) )
	{	// Select an user option when a password option is selected
		jQuery( 'input#options_user_' ).attr( 'checked', 'checked' );
	}
	if( jQuery( this ).attr( 'name' ) == 'options[user]' && !jQuery( this ).is( ':checked' ) )
	{	// Deselect a password option when an user option is deselected
		jQuery( 'input#options_pass_' ).removeAttr( 'checked' );
	}
} );
</script>
<?php
/*
 * $Log: _xml.form.php,v $
 *
 */
?>