<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageVideos()) return;

$db = cmsms()->GetDb();
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
$videoId = isset($params['video_id']) ? (int)$params['video_id'] : 0;
$row = array();
if ($videoId) {
    $tmp = $db->GetRow(
        'SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=?',
        array($videoId)
    );
    if (is_array($tmp)) $row = $tmp;
}

$catalogs = $db->GetArray(
    'SELECT catalog_id,name FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE COALESCE(active,1)<>0 ORDER BY sort_order,name'
);
$selected = $videoId ? $db->GetCol(
    'SELECT catalog_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE video_id=?',
    array($videoId)
) : array();
if (!is_array($selected)) $selected = array();

$youtubeId = isset($row['youtube_id']) ? (string)$row['youtube_id'] : '';
$title = isset($row['title']) ? (string)$row['title'] : '';
$description = isset($row['description']) ? (string)$row['description'] : '';
$active = !isset($row['active']) || (int)$row['active'] === 1;
$thumb = '';
if ($videoId && $youtubeId !== '') {
    try { $thumb = YouTubeCatalogApi::ensureLocalThumbnail($this, $youtubeId, isset($row['thumbnail_url']) ? $row['thumbnail_url'] : '', false); }
    catch (Exception $e) { $thumb = ''; }
}

echo '<style>
.ytc-video-edit-page{max-width:1120px;margin:0;padding:4px 2px 28px;font-family:inherit;box-sizing:border-box}
.ytc-video-edit-page *{box-sizing:border-box}
.ytc-video-edit-page .ytc-admin-form-heading{margin:0 0 20px}
.ytc-video-edit-page .ytc-admin-form-heading h2{margin:0 0 6px;font-size:1.55rem;line-height:1.25}
.ytc-video-edit-page .ytc-admin-form-heading p{margin:0;max-width:850px;line-height:1.55}
.ytc-video-edit-page .ytc-admin-card{border:1px solid;border-radius:12px;padding:24px;margin:0 0 18px}
.ytc-video-edit-page .ytc-admin-video-layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(280px,.75fr);gap:28px;align-items:start}
.ytc-video-edit-page .ytc-admin-field{margin:0 0 22px}
.ytc-video-edit-page .ytc-admin-field:last-child{margin-bottom:0}
.ytc-video-edit-page .ytc-admin-field>label{display:block;margin:0 0 7px;font-weight:700}
.ytc-video-edit-page .ytc-admin-field input[type=text],
.ytc-video-edit-page .ytc-admin-field textarea{display:block;width:100%;max-width:none;padding:10px 12px;border-radius:7px;font:inherit;line-height:1.45}
.ytc-video-edit-page .ytc-admin-field textarea{min-height:180px;resize:vertical}
.ytc-video-edit-page .ytc-admin-field small{display:block;margin-top:7px;line-height:1.45}
.ytc-video-edit-page .ytc-admin-optional{font-size:.86em;font-weight:400}
.ytc-video-edit-page .ytc-admin-video-side{display:grid;gap:18px}
.ytc-video-edit-page .ytc-admin-panel{border:1px solid;border-radius:9px;padding:15px}
.ytc-video-edit-page .ytc-admin-panel-title{margin:0 0 11px;font-weight:700}
.ytc-video-edit-page .ytc-admin-thumb-card,
.ytc-video-edit-page .ytc-admin-thumb-placeholder{border:1px solid;border-radius:9px;padding:12px}
.ytc-video-edit-page .ytc-admin-thumb-title{font-weight:700;margin:0 0 10px}
.ytc-video-edit-page .ytc-admin-thumb-card img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:7px;margin:0 0 8px}
.ytc-video-edit-page .ytc-admin-thumb-placeholder{min-height:170px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:6px}
.ytc-video-edit-page .ytc-admin-checks{display:grid;grid-template-columns:1fr;gap:8px}
.ytc-video-edit-page .ytc-admin-checks label,
.ytc-video-edit-page .ytc-admin-check-row label{display:flex!important;align-items:flex-start;gap:9px;margin:0!important;font-weight:400!important;line-height:1.4;cursor:pointer}
.ytc-video-edit-page input[type=checkbox]{margin-top:.18rem;flex:0 0 auto}
.ytc-video-edit-page .ytc-admin-check-row{padding-top:2px}
.ytc-video-edit-page .ytc-admin-check-row label{font-weight:700!important}
.ytc-video-edit-page .ytc-admin-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:2px}
.ytc-video-edit-page .ytc-admin-button{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:40px;border:1px solid!important;border-radius:7px!important;padding:9px 16px!important;text-decoration:none!important;cursor:pointer;font:inherit;font-weight:700!important;line-height:1.2}
@media(max-width:850px){.ytc-video-edit-page .ytc-admin-video-layout{grid-template-columns:1fr}.ytc-video-edit-page .ytc-admin-card{padding:18px}.ytc-video-edit-page .ytc-admin-video-side{grid-template-columns:1fr}}
</style>';
echo '<div class="ytc-video-edit-page">';
echo '<div class="ytc-admin-form-heading">';
echo '<h2>'.($videoId ? 'Edit video' : 'Add video').'</h2>';
echo '<p>'.($videoId ? 'Update this video or refresh its details from YouTube.' : 'Paste a YouTube URL or video ID. The title, description, duration, views and thumbnail will be pulled from YouTube automatically.').'</p>';
echo '</div>';

echo $this->CreateFormStart($id, 'savevideo', $returnid);
echo '<div class="ytc-admin-card">';
echo '<div class="ytc-admin-video-layout">';
echo '<div>';
echo '<div class="ytc-admin-field"><label for="'.$id.'youtube_input">YouTube URL or video ID</label><input id="'.$id.'youtube_input" type="text" name="'.$id.'youtube_input" value="'.htmlspecialchars($youtubeId,ENT_QUOTES,'UTF-8').'" required><small>Supports normal watch URLs, youtu.be links, Shorts, Live, Embed URLs, or an 11-character video ID.</small></div>';
echo '<div class="ytc-admin-field"><label for="'.$id.'title">Title override <span class="ytc-admin-optional">Optional</span></label><input id="'.$id.'title" type="text" name="'.$id.'title" value="'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'"><small>Leave blank when adding a video to use the title from YouTube.</small></div>';
echo '<div class="ytc-admin-field"><label for="'.$id.'description">Description override <span class="ytc-admin-optional">Optional</span></label><textarea id="'.$id.'description" name="'.$id.'description" rows="8">'.htmlspecialchars($description,ENT_QUOTES,'UTF-8').'</textarea><small>Leave blank when adding a video to use the description from YouTube.</small></div>';
echo '</div>';

echo '<aside class="ytc-admin-video-side">';
if ($thumb) {
    echo '<div class="ytc-admin-thumb-card"><div class="ytc-admin-thumb-title">YouTube thumbnail</div><img src="'.htmlspecialchars($thumb,ENT_QUOTES,'UTF-8').'" alt="Thumbnail for '.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'"><small>Stored locally in <code>/uploads/YouTubeCatalog/</code>.</small></div>';
} else {
    echo '<div class="ytc-admin-thumb-placeholder"><strong>Thumbnail</strong><span>The YouTube thumbnail will appear here after the video is saved.</span></div>';
}
echo '<div class="ytc-admin-panel"><div class="ytc-admin-panel-title">Catalogues</div><div class="ytc-admin-checks">';
foreach ((array)$catalogs as $c) {
    $cid = (int)$c['catalog_id'];
    $checked = in_array($cid,array_map('intval',$selected),true) ? ' checked' : '';
    echo '<label><input type="checkbox" name="'.$id.'catalog_ids[]" value="'.$cid.'"'.$checked.'> <span>'.htmlspecialchars($c['name'],ENT_QUOTES,'UTF-8').'</span></label>';
}
echo '</div><small>If none is selected, a manually added video is placed in the built-in Manual Videos catalogue.</small></div>';
echo '<div class="ytc-admin-panel ytc-admin-check-row"><label><input type="checkbox" name="'.$id.'active" value="1"'.($active?' checked':'').'> <span>Active video</span></label></div>';
echo '</aside>';
echo '</div>';
if ($videoId) echo '<input type="hidden" name="'.$id.'video_id" value="'.$videoId.'">';
echo '</div>';
echo '<div class="ytc-admin-actions"><input type="submit" name="'.$id.'submit" value="'.($videoId?'Save & refresh from YouTube':'Add video from YouTube').'" class="ytc-admin-button"> '.$this->CreateLink($id,'defaultadmin',$returnid,'Cancel',array('tab'=>'videos'),'','','class="ytc-admin-button ytc-admin-button-secondary"').'</div>';
echo $this->CreateFormEnd();
echo '</div>';
