<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;

$this->lazySyncIfDue();
$db = cmsms()->GetDb();

$catalogId = isset($params['catalog']) ? (int)$params['catalog'] : 0;
$limit = isset($params['limit']) ? max(0, (int)$params['limit']) : 0;
$requestedVideoId = isset($params['video_id']) ? max(0, (int)$params['video_id']) : 0;
$singleVideoInput = isset($params['video']) ? trim((string)$params['video']) : '';
// featured=1 reuses the single-video renderer and its Design Manager template.
// Resolve only an active local record: an empty/inactive selection must never
// trigger a YouTube lookup or silently choose another video.
$featuredRequested = isset($params['featured']) && (int)$params['featured'] === 1;
if ($featuredRequested) {
    $singleVideoInput = (string)$db->GetOne(
        'SELECT youtube_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=? AND COALESCE(active,1)<>0',
        array($this->getFeaturedVideoId())
    );
}
$templateWasSpecified = !empty($params['template']);
$template = $templateWasSpecified ? trim((string)$params['template']) : (($singleVideoInput !== '' || $featuredRequested) ? 'single' : 'default');

// CMSMS may supply module parameters as strings, integers or booleans.
// Normalise them so values such as 0, "0", false, "off" and "no" all
// behave consistently.
$paramBool = static function($value, $default = true) {
    if ($value === null) return (bool)$default;
    if (is_bool($value)) return $value;
    if (is_int($value) || is_float($value)) return ((int)$value) !== 0;
    $value = strtolower(trim((string)$value));
    if ($value === '') return false;
    if (in_array($value, array('0','false','off','no','none'), true)) return false;
    if (in_array($value, array('1','true','on','yes'), true)) return true;
    return (bool)$value;
};

$showCatalogs = isset($params['show_catalogs'])
    ? $paramBool($params['show_catalogs'], true)
    : true;

// show_buttons is the preferred parameter. show_catalogs remains a
// backwards-compatible fallback from earlier versions.
$showButtons = isset($params['show_buttons'])
    ? $paramBool($params['show_buttons'], true)
    : $showCatalogs;

// show_all_button controls only the "All videos" filter button.
// The default remains visible for backwards compatibility.
$showAllButton = isset($params['show_all_button'])
    ? $paramBool($params['show_all_button'], true)
    : true;

// show_description controls the catalogue description requested for the
// frontend and, for backwards compatibility, the individual video
// descriptions too. show_catalog_description/show_video_description allow
// either one to be controlled separately when needed.
$showDescription = isset($params['show_description'])
    ? $paramBool($params['show_description'], true)
    : true;
$showCatalogDescription = isset($params['show_catalog_description'])
    ? $paramBool($params['show_catalog_description'], true)
    : $showDescription;
$showVideoDescription = isset($params['show_video_description'])
    ? $paramBool($params['show_video_description'], true)
    : $showDescription;

$showStats = isset($params['show_stats'])
    ? $paramBool($params['show_stats'], true)
    : true;


/*
 * Single video mode.
 *
 * Usage:
 *   {YouTubeCatalog video='VIDEO_ID'}
 *   {YouTubeCatalog video='https://www.youtube.com/watch?v=VIDEO_ID'}
 *
 * Cache-first behaviour:
 *  1. Check the YouTubeCatalog database first.
 *  2. Check /uploads/YouTubeCatalog for the local thumbnail first.
 *  3. Only if the video is not already stored do we ask the YouTube Data API.
 *  4. A successfully fetched single video is stored and linked to the
 *     internal "Single Videos" catalogue, so later page views do not need
 *     another metadata API request.
 */
if ($singleVideoInput !== '' || $featuredRequested) {
    require_once __DIR__.'/lib/YouTubeCatalog.api.php';

    // Use the module DB helper so emoji/4-byte Unicode from YouTube metadata
    // is safe when a new single video is cached.
    $db = YouTubeCatalogApi::db();

    $youtubeId = YouTubeCatalogApi::extractVideoId($singleVideoInput);
    $singleVideo = null;
    $singleError = '';

    // The Single Videos catalogue is an internal cache/group by default.
    // It is deliberately inactive and has no frontend filter button so adding
    // a single embed cannot change the public All Videos/filter behaviour.
    $ensureSingleCatalog = static function($db) {
        $catalogId = (int)$db->GetOne(
            'SELECT catalog_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs '
          . 'WHERE name=? AND (youtube_playlist_id IS NULL OR youtube_playlist_id=\'\') '
          . 'ORDER BY catalog_id ASC',
            array('Single Videos')
        );

        if ($catalogId > 0) return $catalogId;

        $now = date('Y-m-d H:i:s');
        $ok = $db->Execute(
            'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs '
          . '(name,description,youtube_playlist_id,active,show_button,sort_order,create_date,modified_date) '
          . 'VALUES (?,?,?,?,?,?,?,?)',
            array(
                'Single Videos',
                'Videos automatically cached when they are used with the YouTubeCatalog single-video tag.',
                '',
                0,
                0,
                9999,
                $now,
                $now
            )
        );

        return $ok ? (int)$db->Insert_ID() : 0;
    };

    $linkToSingleCatalog = static function($db, $catalogId, $videoId) {
        $catalogId = (int)$catalogId;
        $videoId = (int)$videoId;
        if ($catalogId < 1 || $videoId < 1) return;

        $linkId = (int)$db->GetOne(
            'SELECT link_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos '
          . 'WHERE catalog_id=? AND video_id=?',
            array($catalogId, $videoId)
        );

        if (!$linkId) {
            $db->Execute(
                'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos '
              . '(catalog_id,video_id,sort_order,seen_in_sync) VALUES (?,?,0,0)',
                array($catalogId, $videoId)
            );
        }
    };

    if (!$youtubeId) {
        $singleError = $featuredRequested ? 'No active featured video is selected.' : 'The YouTube URL or video ID is not valid.';
    } else {
        // FIRST: local module database. If this row exists, no YouTube Data
        // API metadata request is made for this page view.
        $stored = $db->GetRow(
            'SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE youtube_id=?',
            array($youtubeId)
        );

        if ($stored) {
            if (!empty($stored['active'])) {
                $singleVideo = $stored;

                // Any video used by a single-video tag is also grouped under
                // Single Videos in the backend. The catalogue itself is
                // inactive by default, so this does not alter public filters.
                $singleCatalogId = $ensureSingleCatalog($db);
                $linkToSingleCatalog($db, $singleCatalogId, (int)$stored['video_id']);

                $catalogRowsForVideo = $db->GetArray(
                    'SELECT c.* FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs c '
                  . 'INNER JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos cv ON cv.catalog_id=c.catalog_id '
                  . 'WHERE cv.video_id=? AND COALESCE(c.active,1)<>0 '
                  . 'ORDER BY c.sort_order,c.name,c.catalog_id',
                    array((int)$stored['video_id'])
                );
                if (!is_array($catalogRowsForVideo)) $catalogRowsForVideo = array();

                $singleVideo['catalog_ids'] = array();
                $singleVideo['catalog_names'] = array();
                foreach ($catalogRowsForVideo as $cat) {
                    $singleVideo['catalog_ids'][] = (int)$cat['catalog_id'];
                    $singleVideo['catalog_names'][] = (string)$cat['name'];
                }
                $singleVideo['catalog_ids_csv'] = implode(',', $singleVideo['catalog_ids']);
                $singleVideo['catalog_name'] = implode(', ', $singleVideo['catalog_names']);
            } else {
                $singleError = 'This video is not active.';
            }
        } else {
            // SECOND: only a video that is not stored locally reaches YouTube.
            try {
                $details = YouTubeCatalogApi::fetchVideos($this, array($youtubeId));
                if (!empty($details[$youtubeId])) {
                    $fetched = $details[$youtubeId];
                    $now = date('Y-m-d H:i:s');

                    $ok = $db->Execute(
                        'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_videos '
                      . '(youtube_id,title,description,duration_iso,duration_text,view_count,thumbnail_url,'
                      . 'published_at,channel_title,youtube_status,source_type,active,create_date,modified_date) '
                      . 'VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                        array(
                            $youtubeId,
                            $fetched['title'],
                            $fetched['description'],
                            $fetched['duration_iso'],
                            $fetched['duration_text'],
                            $fetched['view_count'],
                            $fetched['thumbnail_url'],
                            $fetched['published_at'],
                            $fetched['channel_title'],
                            $fetched['youtube_status'],
                            'single',
                            1,
                            $now,
                            $now
                        )
                    );

                    if ($ok) {
                        $videoId = (int)$db->Insert_ID();
                        $singleCatalogId = $ensureSingleCatalog($db);
                        $linkToSingleCatalog($db, $singleCatalogId, $videoId);

                        // Re-read exactly what was stored so templates use the
                        // same structure on the first and later page views.
                        $singleVideo = $db->GetRow(
                            'SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=?',
                            array($videoId)
                        );

                        if ($singleVideo) {
                            $singleVideo['catalog_ids'] = array();
                            $singleVideo['catalog_names'] = array();
                            $singleVideo['catalog_ids_csv'] = '';
                            $singleVideo['catalog_name'] = '';
                        }

                        try {
                            $this->indexVideo($videoId);
                        } catch (Exception $searchError) {
                            audit($videoId, $this->GetName(), 'Search indexing failed for cached single video: '.$searchError->getMessage());
                        }
                    } else {
                        // Still render the fetched data if the DB insert fails;
                        // the next request can try to cache it again.
                        $singleVideo = $fetched;
                        $singleVideo['video_id'] = 0;
                        $singleVideo['source_type'] = 'single';
                        $singleVideo['active'] = 1;
                        $singleVideo['catalog_ids'] = array();
                        $singleVideo['catalog_names'] = array();
                        $singleVideo['catalog_ids_csv'] = '';
                        $singleVideo['catalog_name'] = '';
                    }
                } else {
                    $singleError = 'The video could not be found on YouTube.';
                }
            } catch (Exception $e) {
                $singleError = 'The video is not cached yet and YouTube could not be contacted.';
                audit('', $this->GetName(), 'Single video metadata lookup failed for '.$youtubeId.': '.$e->getMessage());
            }
        }

        if ($singleVideo) {
            /*
             * Thumbnail cache is also local-first:
             * ensureLocalThumbnail() immediately returns the existing
             * /uploads/YouTubeCatalog/<video-id>.jpg file when present.
             * It only contacts YouTube for the image when the local file is
             * missing.
             */
            $remoteThumb = isset($singleVideo['thumbnail_url']) ? trim((string)$singleVideo['thumbnail_url']) : '';
            try {
                $singleVideo['local_thumbnail_url'] = YouTubeCatalogApi::ensureLocalThumbnail(
                    $this,
                    $youtubeId,
                    $remoteThumb,
                    false
                );
            } catch (Exception $e) {
                $singleVideo['local_thumbnail_url'] = '';
                audit('', $this->GetName(), 'Single video thumbnail cache failed for '.$youtubeId.': '.$e->getMessage());
            }
            $singleVideo['all_visible'] = 1;
        }
    }

    $config = cmsms()->GetConfig();
    $rootUrl = isset($config['root_url']) ? rtrim($config['root_url'],'/') : '';

    $smarty->assign('ytc_video', $singleVideo);
    $smarty->assign('ytc_videos', $singleVideo ? array($singleVideo) : array());
    $smarty->assign('ytc_single_video_input', $singleVideoInput);
    $smarty->assign('ytc_single_error', $singleError);
    $smarty->assign('ytc_show_description', $showVideoDescription ? 1 : 0);
    $smarty->assign('ytc_show_stats', $showStats ? 1 : 0);
    $smarty->assign('ytc_module_url', $rootUrl.'/modules/'.$this->GetName());

    $dbTemplate = trim(preg_replace('/\.tpl$/i', '', (string)$template));
    if ($dbTemplate === '') $dbTemplate = 'single';
    $dmTemplate = $this->getDesignManagerTemplate($dbTemplate);

    if (is_object($dmTemplate) && $this->renderDesignManagerTemplate($dmTemplate, $smarty)) {
        // rendered from Design Manager
    } else {
        $fileKey = preg_replace('/[^A-Za-z0-9_-]/', '', basename($dbTemplate));
        $fileTemplate = $fileKey.'.tpl';
        if ($fileKey === '' || !is_file(__DIR__.'/templates/'.$fileTemplate)) $fileTemplate = 'single.tpl';
        echo $this->ProcessTemplate($fileTemplate);
    }
    return;
}


/*
 * Load videos independently of the catalogue link table.
 * This is deliberately video-first: a missing/broken catalogue relationship
 * must never make an otherwise active video disappear from the frontend.
 */
$sql = 'SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE COALESCE(active,1)<>0';
$sqlParams = array();
if ($requestedVideoId) {
    $sql .= ' AND video_id=?';
    $sqlParams[] = $requestedVideoId;
}
$sql .= ' ORDER BY published_at DESC, video_id DESC';
$videoRows = $db->GetArray($sql, $sqlParams);
if (!is_array($videoRows)) $videoRows = array();

// Active catalogues, keyed by ID.
$catalogRows = $db->GetArray(
    'SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs '
  . 'WHERE COALESCE(active,1)<>0 ORDER BY sort_order,name,catalog_id'
);
if (!is_array($catalogRows)) $catalogRows = array();
$catalogMap = array();
foreach ($catalogRows as $cat) $catalogMap[(int)$cat['catalog_id']] = $cat;

// Attach all active catalogue memberships to each video.
$links = $db->GetArray(
    'SELECT cv.video_id,cv.catalog_id,cv.sort_order '
  . 'FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos cv '
  . 'ORDER BY cv.sort_order,cv.link_id'
);
if (!is_array($links)) $links = array();
$videoCatalogs = array();
foreach ($links as $link) {
    $vid = (int)$link['video_id'];
    $cid = (int)$link['catalog_id'];
    if (!$vid || !$cid || !isset($catalogMap[$cid])) continue;
    if (!isset($videoCatalogs[$vid])) $videoCatalogs[$vid] = array();
    $videoCatalogs[$vid][$cid] = $catalogMap[$cid];
}

// Ensure a Manual Videos catalogue exists for genuinely orphaned manual items.
$manualCatalogId = 0;
foreach ($catalogMap as $cid => $cat) {
    if (empty($cat['youtube_playlist_id'])) { $manualCatalogId = (int)$cid; break; }
}
if (!$manualCatalogId) {
    $now = date('Y-m-d H:i:s');
    $ok = $db->Execute(
        'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs '
      . '(name,description,youtube_playlist_id,active,sort_order,create_date,modified_date) '
      . 'VALUES (?,?,?,?,?,?,?)',
        array('Manual Videos','Videos added manually in CMS Made Simple.','',1,0,$now,$now)
    );
    if ($ok) {
        $manualCatalogId = (int)$db->Insert_ID();
        $manual = $db->GetRow('SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE catalog_id=?', array($manualCatalogId));
        if ($manual) {
            $catalogMap[$manualCatalogId] = $manual;
            $catalogRows[] = $manual;
        }
    }
}

require_once __DIR__.'/lib/YouTubeCatalog.api.php';
$rows = array();
foreach ($videoRows as $row) {
    $vid = (int)$row['video_id'];
    $memberships = isset($videoCatalogs[$vid]) ? $videoCatalogs[$vid] : array();

    // Repair orphaned manual videos, but do not make rendering depend on repair succeeding.
    if (!$memberships && isset($row['source_type']) && $row['source_type'] === 'manual' && $manualCatalogId) {
        $db->Execute(
            'INSERT IGNORE INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos '
          . '(catalog_id,video_id,sort_order,seen_in_sync) VALUES (?,?,0,0)',
            array($manualCatalogId,$vid)
        );
        if (isset($catalogMap[$manualCatalogId])) {
            $memberships[$manualCatalogId] = $catalogMap[$manualCatalogId];
            $videoCatalogs[$vid] = $memberships;
        }
    }

    $catalogIds = array_keys($memberships);
    $catalogNames = array();
    foreach ($memberships as $cat) $catalogNames[] = $cat['name'];

    $row['catalog_ids'] = array_map('intval', $catalogIds);
    $row['catalog_ids_csv'] = implode(',', $row['catalog_ids']);
    $row['catalog_name'] = $catalogNames ? implode(', ', $catalogNames) : 'Other Videos';

    // Strict public "All videos" rule:
    // if a video belongs to ANY catalogue whose frontend filter is disabled,
    // exclude that video from All videos. It can still be shown by a direct
    // catalog=ID request. Orphaned videos remain visible.
    $allVisible = true;
    foreach ($memberships as $cat) {
        if (isset($cat['show_button']) && (int)$cat['show_button'] === 0) {
            $allVisible = false;
            break;
        }
    }
    $row['all_visible'] = $allVisible ? 1 : 0;

    $remote = isset($row['thumbnail_url']) ? trim((string)$row['thumbnail_url']) : '';
    try {
        $row['local_thumbnail_url'] = YouTubeCatalogApi::ensureLocalThumbnail($this, $row['youtube_id'], $remote, false);
    } catch (Exception $e) {
        $row['local_thumbnail_url'] = '';
        audit('', $this->GetName(), 'Thumbnail cache failed for '.$row['youtube_id'].': '.$e->getMessage());
    }

    $rows[] = $row;
    if ($limit && count($rows) >= $limit) break;
}

// Only expose catalogue filter buttons that actually have at least one
// video in the current frontend result set. This prevents empty filters.
$usedCatalogIds = array();
foreach ($rows as $video) {
    if (empty($video['catalog_ids']) || !is_array($video['catalog_ids'])) continue;
    foreach ($video['catalog_ids'] as $cid) $usedCatalogIds[(int)$cid] = true;
}

// Keep two maps: every used active catalogue can still be selected with
// catalog=ID, while only catalogues with show_button enabled are exposed as
// frontend filter buttons.
$usedFrontendCatalogMap = array();
$frontendCatalogs = array();
foreach ($catalogRows as $cat) {
    $cid = (int)$cat['catalog_id'];
    if (!$cid || !isset($usedCatalogIds[$cid])) continue;
    $usedFrontendCatalogMap[$cid] = $cat;
    if (!isset($cat['show_button']) || (int)$cat['show_button'] !== 0) {
        $frontendCatalogs[] = $cat;
    }
}

// Keep button visibility completely separate from catalogue/video data.
// ytc_catalogs remains the list used by existing templates for filter buttons,
// while ytc_all_catalogs contains every active catalogue represented by the
// current video result set.  Hiding a button must never hide its videos.
$allUsedCatalogs = array();
foreach ($catalogRows as $cat) {
    $cid = (int)$cat['catalog_id'];
    if ($cid && isset($usedCatalogIds[$cid])) $allUsedCatalogs[] = $cat;
}

$smarty->assign('ytc_catalogs', $frontendCatalogs);
$smarty->assign('ytc_filter_catalogs', $frontendCatalogs);
$smarty->assign('ytc_all_catalogs', $allUsedCatalogs);
$smarty->assign('ytc_videos', $rows);
$smarty->assign('ytc_all_videos', $rows);
// catalog=ID remains valid even when that catalogue's filter button is hidden.
$validInitialCatalog = ($catalogId && isset($usedFrontendCatalogMap[$catalogId])) ? $catalogId : 0;
$smarty->assign('ytc_catalog_id', $validInitialCatalog);
$smarty->assign('ytc_show_catalogs', $showCatalogs ? 1 : 0);
$smarty->assign('ytc_show_buttons', $showButtons ? 1 : 0);
$smarty->assign('ytc_show_all_button', $showAllButton ? 1 : 0);
$smarty->assign('ytc_show_description', $showVideoDescription ? 1 : 0);
$smarty->assign('ytc_show_catalog_description', $showCatalogDescription ? 1 : 0);
$smarty->assign('ytc_show_stats', $showStats ? 1 : 0);
$smarty->assign('ytc_heading', $this->GetPreference('frontend_heading','Our Videos'));
$smarty->assign('ytc_intro', $this->GetPreference('frontend_intro','Browse our video collection.'));
$smarty->assign('ytc_all_videos_description', $this->GetPreference('all_videos_description',''));

$config = cmsms()->GetConfig();
$rootUrl = isset($config['root_url']) ? rtrim($config['root_url'],'/') : '';
$smarty->assign('ytc_module_url', $rootUrl.'/modules/'.$this->GetName());

// CMSMS 2.x Design Manager templates take priority.  template='TestVideo'
// means a Design Manager template actually named TestVideo, as long as its
// template type belongs to YouTubeCatalog.  The shipped .tpl files remain
// factory fallbacks.
$dbTemplate = trim(preg_replace('/\.tpl$/i', '', (string)$template));
if ($dbTemplate === '') $dbTemplate = 'default';
$dmTemplate = $this->getDesignManagerTemplate($dbTemplate);

// Wrap this module instance so frontend parameters can be applied safely even
// to an existing Design Manager template that predates the new parameter.
$ytcInstanceId = 'ytc-instance-'.substr(md5($id.'|'.microtime(true).'|'.mt_rand()), 0, 12);
echo '<div id="'.htmlspecialchars($ytcInstanceId, ENT_QUOTES, 'UTF-8').'" class="ytc-module-instance">';
if (!$showAllButton) {
    echo '<style>#'.htmlspecialchars($ytcInstanceId, ENT_QUOTES, 'UTF-8').' [data-ytc-catalog="all"]{display:none!important;}</style>';
}

if (is_object($dmTemplate) && $this->renderDesignManagerTemplate($dmTemplate, $smarty)) {
    // rendered from Design Manager
} else {
    // File fallback only accepts a simple filename.  Custom Design Manager
    // names never silently map to unrelated files.
    $fileKey = preg_replace('/[^A-Za-z0-9_-]/', '', basename($dbTemplate));
    $fileTemplate = $fileKey.'.tpl';
    if ($fileKey === '' || !is_file(__DIR__.'/templates/'.$fileTemplate)) $fileTemplate = 'default.tpl';
    echo $this->ProcessTemplate($fileTemplate);
}
echo '</div>';
