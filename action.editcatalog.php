<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageCatalogs()) return;

$db = cmsms()->GetDb();
$cid = isset($params['catalog_id']) ? (int)$params['catalog_id'] : 0;
$r = $cid ? $db->GetRow(
    'SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE catalog_id=?',
    array($cid)
) : array();
if (!is_array($r)) $r = array();

$name = isset($r['name']) ? (string)$r['name'] : '';
$playlist = isset($r['youtube_playlist_id']) ? (string)$r['youtube_playlist_id'] : '';
$description = isset($r['description']) ? (string)$r['description'] : '';
$sort = isset($r['sort_order']) ? (int)$r['sort_order'] : 0;
$active = !isset($r['active']) || (int)$r['active'] === 1;
$showButton = !isset($r['show_button']) || (int)$r['show_button'] === 1;

echo '<div class="ytc-admin-form-heading">';
echo '<h2>'.($cid ? 'Edit catalogue' : 'Add YouTube playlist / catalogue').'</h2>';
echo '<p>'.($cid ? 'Update the catalogue details and playlist connection.' : 'Create a manual catalogue or connect a public YouTube playlist.').'</p>';
echo '</div>';

echo $this->CreateFormStart($id,'savecatalog',$returnid);
echo '<div class="ytc-admin-card">';
echo '<div class="ytc-admin-grid ytc-admin-grid-2">';
echo '<div class="ytc-admin-field"><label for="'.$id.'name">Catalogue name</label><input id="'.$id.'name" name="'.$id.'name" value="'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'" required></div>';
echo '<div class="ytc-admin-field"><label for="'.$id.'sort_order">Sort order</label><input id="'.$id.'sort_order" type="number" name="'.$id.'sort_order" value="'.$sort.'"><small>Lower numbers appear first.</small></div>';
echo '</div>';
echo '<div class="ytc-admin-field"><label for="'.$id.'playlist_input">YouTube playlist URL or ID</label><input id="'.$id.'playlist_input" name="'.$id.'playlist_input" value="'.htmlspecialchars($playlist,ENT_QUOTES,'UTF-8').'"><small>Leave blank for a manual catalogue. If supplied, YouTube becomes the source for catalogue membership.</small></div>';

echo '<div class="ytc-admin-field"><label>Catalogue description</label>';
// Use the CMSMS-selected WYSIWYG editor (for example MicroTiny) when available.
echo $this->CreateTextArea(true,$id,$description,'description');
echo '<small>This description is displayed underneath the catalogue filter buttons on the frontend.</small></div>';

echo '<div class="ytc-admin-field ytc-admin-check-row"><label><input type="checkbox" name="'.$id.'active" value="1"'.($active?' checked':'').'> Active catalogue</label></div>';
echo '<div class="ytc-admin-field ytc-admin-check-row"><label><input type="checkbox" name="'.$id.'show_button" value="1"'.($showButton?' checked':'').'> Show catalogue filter button on frontend</label><small>Turn this off to hide this catalogue from the filter buttons. Any video belonging to this catalogue will also be excluded from All videos, but the catalogue can still be selected directly with catalog='.$cid.'.</small></div>';
if ($cid) echo '<input type="hidden" name="'.$id.'catalog_id" value="'.$cid.'">';
echo '</div>';
echo '<div class="ytc-admin-actions"><input type="submit" value="Save catalogue" class="ytc-admin-button"> '.$this->CreateLink($id,'defaultadmin',$returnid,'Cancel',array('tab'=>'catalogs'),'','','class="ytc-admin-button ytc-admin-button-secondary"').'</div>';
echo $this->CreateFormEnd();
