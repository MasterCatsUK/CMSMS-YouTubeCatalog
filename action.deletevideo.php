<?php
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageVideos()) return;
$db=cmsms()->GetDb();
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
$vid=(int)($params['video_id']??0);
$search = \cms_utils::get_search_module();
if (is_object($search) && $vid) $search->DeleteWords($this->GetName(), $vid, 'video');
$youtubeId=$db->GetOne('SELECT youtube_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=?',array($vid));
$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE video_id=?',array($vid));
$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=?',array($vid));
if ($youtubeId) { $thumbPath=YouTubeCatalogApi::getLocalThumbnailPath($youtubeId); if (is_file($thumbPath)) @unlink($thumbPath); }
$this->clearFeaturedVideoIfSelected($vid);
$this->SetMessage('Video deleted.');
$this->RedirectToAdminTab('videos');
