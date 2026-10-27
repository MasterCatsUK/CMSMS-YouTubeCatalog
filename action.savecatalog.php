<?php
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageCatalogs()) return;

require_once __DIR__.'/lib/YouTubeCatalog.api.php';
$db = cmsms()->GetDb();
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
YouTubeCatalogApi::ensureUtf8mb4Schema();
$cid = (int)($params['catalog_id'] ?? 0);
$name = trim($params['name'] ?? '');
$desc = trim($params['description'] ?? '');
$sort = (int)($params['sort_order'] ?? 0);
$active = !empty($params['active']) ? 1 : 0;
$showButton = !empty($params['show_button']) ? 1 : 0;
$raw = trim($params['playlist_input'] ?? '');
$pid = $raw ? YouTubeCatalogApi::extractPlaylistId($raw) : null;

if ($raw && !$pid) {
    $this->SetError('Could not recognise that YouTube playlist URL or ID.');
    $this->RedirectToAdminTab('catalogs');
    return;
}

if ($pid) {
    try {
        $m = YouTubeCatalogApi::getPlaylistMeta($this,$pid);
        // Catalogue name/description are CMS-managed content.
        // For a brand-new catalogue we may use the YouTube title as a convenience,
        // but never replace the WYSIWYG description with YouTube metadata.
        if (!$cid && !$name) $name = $m['snippet']['title'] ?? $name;
    } catch (Exception $e) {
        // Do not prevent saving an otherwise valid catalogue if YouTube is temporarily unavailable.
        // The sync/status area will report the API problem afterwards.
    }
}

if ($cid) {
    $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs SET name=?,description=?,youtube_playlist_id=?,active=?,show_button=?,sort_order=?,modified_date=? WHERE catalog_id=?',
        array($name,$desc,$pid,$active,$showButton,$sort,date('Y-m-d H:i:s'),$cid));
} else {
    $db->Execute('INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs (name,description,youtube_playlist_id,active,show_button,sort_order,create_date,modified_date) VALUES (?,?,?,?,?,?,?,?)',
        array($name,$desc,$pid,$active,$showButton,$sort,date('Y-m-d H:i:s'),date('Y-m-d H:i:s')));
    $cid = (int)$db->Insert_ID();
}

$sync_note = '';
if ($pid) {
    try {
        $r = YouTubeCatalogApi::syncCatalog($this,$cid,true);
        $sync_note = ' Sync complete: '.(int)$r['added'].' added, '.(int)$r['updated'].' updated.';
    } catch (Exception $e) {
        $sync_note = ' Saved, but the YouTube sync reported: '.$e->getMessage();
    }
}

// Catalogue names are part of the Search text for linked videos, so refresh
// those index entries after editing the catalogue too.
try {
    $linkedVideoIds = $db->GetCol(
        'SELECT video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE catalog_id=?',
        array($cid)
    );
    foreach ((array)$linkedVideoIds as $linkedVideoId) $this->indexVideo((int)$linkedVideoId);
} catch (Exception $e) {
    audit($cid, $this->GetName(), 'Search indexing failed after catalogue save: '.$e->getMessage());
}

$this->SetMessage('Catalogue saved.'.$sync_note);
$this->RedirectToAdminTab('catalogs');
