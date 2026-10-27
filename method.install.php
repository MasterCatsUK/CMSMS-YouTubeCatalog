<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
$db = cmsms()->GetDb();
$db->Execute('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
$dict = NewDataDictionary($db);

$sql = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_youtubecatalog_catalogs', "
 catalog_id I KEY AUTO,
 name C(255) NOTNULL,
 description X,
 youtube_playlist_id C(128),
 active I1 DEFAULT 1,
 show_button I1 DEFAULT 1,
 sort_order I DEFAULT 0,
 last_sync DT,
 last_error X,
 create_date DT,
 modified_date DT
");
$dict->ExecuteSQLArray($sql);

$sql = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_youtubecatalog_videos', "
 video_id I KEY AUTO,
 youtube_id C(32) NOTNULL,
 title C(255) NOTNULL,
 description X,
 duration_iso C(64),
 duration_text C(32),
 view_count I8 DEFAULT 0,
 thumbnail_url C(512),
 published_at DT,
 channel_title C(255),
 youtube_status C(32),
 source_type C(32) DEFAULT 'manual',
 active I1 DEFAULT 1,
 create_date DT,
 modified_date DT
");
$dict->ExecuteSQLArray($sql);
$db->Execute('CREATE UNIQUE INDEX '.CMS_DB_PREFIX.'ytc_youtube_id ON '.CMS_DB_PREFIX.'module_youtubecatalog_videos (youtube_id)');

$sql = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos', "
 link_id I KEY AUTO,
 catalog_id I NOTNULL,
 video_id I NOTNULL,
 sort_order I DEFAULT 0,
 seen_in_sync I1 DEFAULT 0
");
$dict->ExecuteSQLArray($sql);
$db->Execute('CREATE UNIQUE INDEX '.CMS_DB_PREFIX.'ytc_cat_video ON '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos (catalog_id,video_id)');

// YouTube titles/descriptions can contain emoji and other 4-byte Unicode characters.
// Force all module tables to utf8mb4 so those values can be stored safely.
// Identifier names are defined solely by CMSMS constants, not user input.
// SQL parameter placeholders cannot bind SQL table identifiers.
$db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_videos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$this->SetPreference('youtube_api_key','');
$this->SetPreference('sync_interval_hours',6);
$this->SetPreference('last_global_sync',0);
$this->SetPreference('cron_token',bin2hex(random_bytes(24)));
$this->SetPreference('frontend_heading','Our Videos');
$this->SetPreference('frontend_intro','Browse our video collection.');
$this->SetPreference('all_videos_description','');
$this->SetPreference('backend_module_name','YouTube Video Catalog');
$this->SetPreference('backend_page_limit',20);
$this->CreatePermission('Manage YouTube Catalog','Manage YouTube Video Catalog (legacy/full access)');
$this->CreatePermission('Manage YouTubeCatalog Videos','Manage YouTubeCatalog Videos');
$this->CreatePermission('Manage YouTubeCatalog Catalogues','Manage YouTubeCatalog Catalogues');
$this->CreatePermission('Manage YouTubeCatalog Settings','Manage YouTubeCatalog Settings');

$db->Execute('INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs (name,description,active,sort_order,create_date,modified_date) VALUES (?,?,?,?,?,?)',
 array('Manual Videos','Videos added manually in CMS Made Simple.',1,0,date('Y-m-d H:i:s'),date('Y-m-d H:i:s')));

// Internal catalogue used to cache videos referenced by the single-video tag.
// It is inactive/hidden by default so it does not alter public catalogue filters.
$db->Execute(
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
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s')
    )
);


// Create the local thumbnail storage folder used by the frontend/admin.
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
try {
    YouTubeCatalogApi::ensureThumbnailDirectory();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create uploads/YouTubeCatalog: '.$e->getMessage());
}


// Create editable frontend templates for Design Manager. File templates remain
// as factory/fallback copies and are not removed.
try {
    $this->ensureDesignManagerTemplates();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create Design Manager templates: '.$e->getMessage());
}
