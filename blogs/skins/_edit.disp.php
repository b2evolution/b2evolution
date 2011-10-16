<?php
/**
 * This is the template that displays the edit item form. It gets POSTed to /htsrv/item_edit.php.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)

 *
 * @package evoskins
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $Session, $inc_path;
global $action, $form_action;

/**
 * @var User
 */
global $current_User;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;

global $trackback_url;
global $bozo_start_modified, $creating;
global $edited_Item, $item_tags, $item_title, $item_content;
global $post_category, $post_extracats;
global $admin_url, $redirect_to, $advanced_edit_link, $form_action;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( $form_action, 'item_checkchanges', 'post' );

// ================================ START OF EDIT FORM ================================

$iframe_name = NULL;
$params = array();
if( !empty( $bozo_start_modified ) )
{
  $params['bozo_start_modified'] = true;
}

$Form->begin_form( 'inskin', '', $params );

  $Form->add_crumb( 'item' );
  $Form->hidden( 'ctrl', 'items' );
  $Form->hidden( 'blog', $Blog->ID );
  if( isset( $edited_Item ) )
  {
    $Form->hidden( 'post_ID', $edited_Item->ID );

    // Here we add js code for attaching file popup window: (Yury)
    if( !empty( $edited_Item->ID ) && ( $Session->get('create_edit_attachment') === true ) )
    { // item also created => we have $edited_Item->ID for popup window:
      echo_attaching_files_button_js( $iframe_name );
      // clear session variable:
      $Session->delete('create_edit_attachment');
    }
  }
  $Form->hidden( 'redirect_to', $redirect_to );

  // In case we send this to the blog for a preview :
  $Form->hidden( 'preview', 0 );
  $Form->hidden( 'more', 1 );
  $Form->hidden( 'preview_userid', $current_User->ID );


  // Fields used in "advanced" form, but not here:
  $Form->hidden( 'post_status', $edited_Item->get( 'status' ) );
  $Form->hidden( 'post_comment_status', $edited_Item->get( 'comment_status' ) );
  $Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
  $Form->hidden( 'item_typ_ID', $edited_Item->ptyp_ID );
  $Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
  $Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
  $Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
  $Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
  $Form->hidden( 'metadesc', $edited_Item->get( 'metadesc' ) );
  $Form->hidden( 'metakeywords', $edited_Item->get( 'metakeywords' ) );


  if( $Blog->get_setting( 'use_workflow' ) )
  {  // We want to use workflow properties for this blog:
    $Form->hidden( 'item_priority', $edited_Item->priority );
    $Form->hidden( 'item_assigned_user_ID', $edited_Item->assigned_user_ID );
    $Form->hidden( 'item_st_ID', $edited_Item->pst_ID );
    $Form->hidden( 'item_deadline', $edited_Item->datedeadline );
  }
  $Form->hidden( 'trackback_url', $trackback_url );
  $Form->hidden( 'renderers_displayed', 1 );
  $Form->hidden( 'renderers', $edited_Item->get_renderers_validated() );
  $Form->hidden( 'item_featured', $edited_Item->featured );
  $Form->hidden( 'item_hideteaser', $edited_Item->hideteaser );
  $Form->hidden( 'item_order', $edited_Item->order );

  $creator_User = $edited_Item->get_creator_User();
  $Form->hidden( 'item_owner_login', $creator_User->login );
  $Form->hidden( 'item_owner_login_displayed', 1 );

  // CUSTOM FIELDS double
  for( $i = 1 ; $i <= 5; $i++ )
  {  // For each custom double field:
    $Form->hidden( 'item_double'.$i, $edited_Item->{'double'.$i} );
  }
  // CUSTOM FIELDS varchar
  for( $i = 1 ; $i <= 3; $i++ )
  {  // For each custom varchar field:
    $Form->hidden( 'item_varchar'.$i, $edited_Item->{'varchar'.$i} );
  }

  // TODO: Form::hidden() do not add, if NULL?!

?>


  <?php
  // ############################ POST CONTENTS #############################
  // Title input:
  $require_title = $Blog->get_setting('require_title');
  if( $require_title != 'none' )
  {
    echo '<table width="100%"><tr>';
    $Form->labelstart = '<th width="10%">';
    $Form->labelend = '</th>';
    $Form->inputstart = '<td>';
    $Form->inputend = '</td>';
    $Form->text_input( 'post_title', $item_title, 20, T_('Title'), '', array('maxlength'=>255, 'style'=>'width: 100%;', 'required'=>($require_title=='required')) );
    echo '</tr></table>';
    echo '<br />';
  }

  // ---------------------------- TEXTAREA -------------------------------------
  $Form->fieldstart = '<div class="edit_area">';
  $Form->fieldend = "</div>\n";
  $Form->labelstart = '';
  $Form->labelend = '';
  $Form->labelempty = '';
  $Form->textarea_input( 'content', $item_content, 16, NULL, array( 'cols' => 50 , 'id' => 'itemform_post_content' ) );
  ?>
  <script type="text/javascript" language="JavaScript">
    <!--
    // This is for toolbar plugins
    var b2evoCanvas = document.getElementById('itemform_post_content');
    //-->
  </script>

  <?php
  // CALL PLUGINS NOW:
  $Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'inskin' ) );

$Form->end_fieldset();

  if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
  { // ------------------------------------ TIME STAMP -------------------------------------
    $Form->begin_fieldset();

    $Form->fieldstart = '';
    $Form->fieldend = '';
    $Form->labelstart = '';
    $Form->labelend = '';
    $Form->inputstart = '';
    $Form->inputend = '';
    echo '<br /><div id="itemform_edit_timestamp" class="edit_fieldgroup">';
    issue_date_control( $Form, false, '<strong>'.T_('Issue date').'</strong>' );
    echo '</div>';

    $Form->end_fieldset();
  }

  cat_select( $Form, true, false );
  echo '<br />';

  echo '<table cellspacing="0" width="100%">';
  echo '<tr><td class="label"><label for="item_tags"><strong>'.T_('Tags').':</strong> <span class="notes">'.T_('sep by ,').'</span></label></td>';
  echo '<td class="input">';
  $Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
  echo '</td><td width="1"><!-- for IE7 --></td></tr>';
  echo '</table><br />';
?>

<div class="clear"></div>

<?php
// ####################### PLUGIN FIELDSETS #########################
$Plugins->trigger_event( 'DisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'inskin' ) );
?>

<div class="clear"></div>

<div class="right">
<?php // ------------------------------- ACTIONS ----------------------------------
  if( $creating )
  {  // Create new post
    $Form->submit( array( 'actionArray[create]', T_('Publish!'), 'SaveButton', '' ) );
  }
  else
  {  // Edit existed post
    $Form->submit( array( 'actionArray[update]', T_('Save changes'), 'SaveButton', '' ) );
  }

  if( $current_User->check_perm( 'admin', 'normal' ) )
  {  // If current user has an access to the Back-office
    echo '<br /><br /><a href="'.$advanced_edit_link['href'].'" onclick="'.$advanced_edit_link['onclick'].'" class="small">'.T_('Go to advanced edit screen').'</a>';
  }
?>
</div>
<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();


// ####################### JS BEHAVIORS #########################
// New category input box:
echo_onchange_newcat();
echo_autocomplete_tags();

/*
 * $Log$
 * Revision 1.4  2011/10/16 19:39:06  fplanque
 * Moved inskin.form to _edit.disp because there is no use for inskin.from in the backoffice.
 *
 * Revision 1.3  2011/10/12 11:23:32  efy-yurybakh
 * In skin posting (beta)
 *
 * Revision 1.2  2011/10/11 19:04:29  efy-yurybakh
 * In skin posting (beta)
 *
 * Revision 1.1  2011/10/11 18:26:11  efy-yurybakh
 * In skin posting (beta)
 */
?>