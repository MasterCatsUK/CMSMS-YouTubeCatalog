<?php
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageVideos()) return;

require_once __DIR__.'/lib/YouTubeCatalog.api.php';
$db = cmsms()->GetDb();
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
YouTubeCatalogApi::ensureUtf8mb4Schema();

// CMSMS normalises action inputs into $params. Never read raw superglobals.
$getParam = static function($name, $default = null) use ($params) {
    return array_key_exists($name, $params) ? $params[$name] : $default;
};

$videoId = (int)$getParam('video_id', 0);
$input = trim((string)$getParam('youtube_input', ''));
$youtubeId = YouTubeCatalogApi::extractVideoId($input);

$redirectBack = function($message) use ($id, $returnid, $videoId) {
    // Return to the form, not the list, so the user immediately sees what
    // needs correcting. CMSMS will display the error at the top.
    $this->SetError($message);
    $args = array();
    if ($videoId) $args['video_id'] = $videoId;
    $this->Redirect($id, 'editvideo', $returnid, $args);
};

if ($input === '') {
    $redirectBack('Please enter a YouTube video URL or 11-character video ID.');
    return;
}
if (!$youtubeId) {
    $redirectBack('Could not recognise that YouTube URL. You can paste a youtube.com/watch, youtu.be, Shorts, Live or Embed URL, or the 11-character video ID.');
    return;
}

try {
    $details = YouTubeCatalogApi::fetchVideos($this, array($youtubeId));
} catch (Exception $e) {
    $redirectBack('Could not get the video from YouTube: '.$e->getMessage());
    return;
}

if (empty($details[$youtubeId])) {
    $redirectBack('YouTube did not return details for that video. Check that it is public or unlisted and that the URL/video ID is correct.');
    return;
}

$v = $details[$youtubeId];
$now = date('Y-m-d H:i:s');
$active = $getParam('active', null) ? 1 : 0;
$titleOverride = trim((string)$getParam('title', ''));
$descOverride = trim((string)$getParam('description', ''));
$title = ($titleOverride !== '') ? $titleOverride : (string)$v['title'];
$description = ($descOverride !== '') ? $descOverride : (string)$v['description'];

// If the video was previously imported by a playlist, reuse that row rather
// than failing against the unique youtube_id index.
$existingId = $db->GetOne(
    'SELECT video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE youtube_id=?',
    array($youtubeId)
);
if ($existingId && !$videoId) $videoId = (int)$existingId;

try {
    if ($videoId) {
        $ok = $db->Execute(
            'UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_videos SET youtube_id=?,title=?,description=?,duration_iso=?,duration_text=?,view_count=?,thumbnail_url=?,published_at=?,channel_title=?,youtube_status=?,source_type=?,active=?,modified_date=? WHERE video_id=?',
            array($youtubeId,$title,$description,$v['duration_iso'],$v['duration_text'],(int)$v['view_count'],$v['thumbnail_url'],$v['published_at'],$v['channel_title'],$v['youtube_status'],'manual',$active,$now,$videoId)
        );
    } else {
        $ok = $db->Execute(
            'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_videos (youtube_id,title,description,duration_iso,duration_text,view_count,thumbnail_url,published_at,channel_title,youtube_status,source_type,active,create_date,modified_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            array($youtubeId,$title,$description,$v['duration_iso'],$v['duration_text'],(int)$v['view_count'],$v['thumbnail_url'],$v['published_at'],$v['channel_title'],$v['youtube_status'],'manual',$active,$now,$now)
        );
        if ($ok) $videoId = (int)$db->Insert_ID();
    }
    if (!$ok || !$videoId) throw new Exception($db->ErrorMsg() ?: 'Database save failed.');
} catch (Exception $e) {
    $redirectBack('The video details were received from YouTube, but CMS could not save them: '.$e->getMessage());
    return;
}

if (!$active) $this->clearFeaturedVideoIfSelected($videoId);

try {
    YouTubeCatalogApi::ensureLocalThumbnail($this, $youtubeId, $v['thumbnail_url'], true);
} catch (Exception $e) {
    audit('', $this->GetName(), 'Thumbnail download failed for '.$youtubeId.': '.$e->getMessage());
}

$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE video_id=?', array($videoId));
$catalogIds = $getParam('catalog_ids', array());
if (!is_array($catalogIds)) $catalogIds = array($catalogIds);
$catalogIds = array_values(array_unique(array_filter(array_map('intval', $catalogIds))));

foreach ($catalogIds as $catalogId) {
    if ($catalogId < 1) continue;
    $db->Execute(
        'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos (catalog_id,video_id,sort_order,seen_in_sync) VALUES (?,?,0,0)',
        array($catalogId,$videoId)
    );
}

if (!$catalogIds) {
    $manualCatalogId = $db->GetOne(
        'SELECT catalog_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE (youtube_playlist_id IS NULL OR youtube_playlist_id=\'\') ORDER BY catalog_id ASC'
    );
    // If an older install somehow lost the built-in catalogue, recreate it.
    if (!$manualCatalogId) {
        $db->Execute(
            'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs (name,description,youtube_playlist_id,active,sort_order,create_date,modified_date) VALUES (?,?,?,?,?,?,?)',
            array('Manual Videos','Videos added manually in CMS Made Simple.','',1,0,$now,$now)
        );
        $manualCatalogId = (int)$db->Insert_ID();
    }
    if ($manualCatalogId) {
        $db->Execute(
            'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos (catalog_id,video_id,sort_order,seen_in_sync) VALUES (?,?,0,0)',
            array((int)$manualCatalogId,$videoId)
        );
    }
}

try { $this->indexVideo($videoId); } catch (Exception $e) { audit($videoId, $this->GetName(), 'Search indexing failed: '.$e->getMessage()); }

$this->SetMessage('Video saved and refreshed from YouTube.');
$this->RedirectToAdminTab('videos');
