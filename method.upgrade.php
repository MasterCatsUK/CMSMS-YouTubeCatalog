<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;

// Releases 1.0.58–1.0.60 change frontend handling and generic defaults only.
// Existing 1.0.57 installs need no schema, settings, thumbnail or template migrations.
if (isset($oldversion) && version_compare($oldversion, '1.0.57', '>=')) return;


// 1.0.11: ensure local thumbnail folder exists for installs upgraded from an older release.
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
try {
    YouTubeCatalogApi::ensureThumbnailDirectory();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create uploads/YouTubeCatalog during upgrade: '.$e->getMessage());
}


// Copy thumbnails for existing videos into the local uploads folder.
try {
    $db = cmsms()->GetDb();
    $rows = $db->GetArray('SELECT youtube_id,thumbnail_url FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos');
    foreach ((array)$rows as $row) {
        try {
            YouTubeCatalogApi::ensureLocalThumbnail($this, $row['youtube_id'], $row['thumbnail_url'], false);
        } catch (Exception $thumbError) {
            audit('', $this->GetName(), 'Could not migrate thumbnail '.$row['youtube_id'].': '.$thumbError->getMessage());
        }
    }
} catch (Exception $e) {
    audit('', $this->GetName(), 'Thumbnail migration failed: '.$e->getMessage());
}


// 1.0.22: force both the DB connection and the individual text columns to
// utf8mb4.  This also repairs sites where the 1.0.21 table conversion hook was
// skipped or the table default changed without the existing columns changing.
try {
    YouTubeCatalogApi::ensureUtf8mb4Schema();
    $db = YouTubeCatalogApi::db();
    $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not repair YouTubeCatalog utf8mb4 schema: '.$e->getMessage());
    throw $e;
}

// 1.0.23: repair manual videos that were saved without a catalogue link.
try {
    $db = cmsms()->GetDb();
    $manualCatalogId = $db->GetOne(
        'SELECT catalog_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE (youtube_playlist_id IS NULL OR youtube_playlist_id=\'\') ORDER BY catalog_id ASC'
    );
    if (!$manualCatalogId) {
        $now = date('Y-m-d H:i:s');
        $db->Execute(
            'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs (name,description,youtube_playlist_id,active,sort_order,create_date,modified_date) VALUES (?,?,?,?,?,?,?)',
            array('Manual Videos','Videos added manually in CMS Made Simple.','',1,0,$now,$now)
        );
        $manualCatalogId = (int)$db->Insert_ID();
    }
    if ($manualCatalogId) {
        $orphans = $db->GetCol(
            'SELECT v.video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos v LEFT JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos cv ON cv.video_id=v.video_id WHERE v.source_type=\'manual\' AND cv.video_id IS NULL'
        );
        foreach ((array)$orphans as $orphanVideoId) {
            $db->Execute(
                'INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos (catalog_id,video_id,sort_order,seen_in_sync) VALUES (?,?,0,0)',
                array((int)$manualCatalogId,(int)$orphanVideoId)
            );
        }
    }
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not repair manual video catalogue links: '.$e->getMessage());
}


// 1.0.37: create Design Manager copies of the frontend templates without
// overwriting templates the site owner has already customised.
try {
    $this->ensureDesignManagerTemplates();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create Design Manager templates during upgrade: '.$e->getMessage());
}

// Register all existing active videos with the standard CMSMS Search module.
try {
    $search = \cms_utils::get_search_module();
    if (is_object($search)) $this->SearchReindex($search);
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not rebuild Search index during upgrade: '.$e->getMessage());
}


// 1.0.41: per-catalogue control over whether a frontend filter button is shown.
// Existing catalogues default to visible so upgrades preserve the current UI.
try {
    $db = cmsms()->GetDb();
    $column = $db->GetOne(
        'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',
        array(CMS_DB_PREFIX.'module_youtubecatalog_catalogs','show_button')
    );
    if (!$column) {
        $db->Execute(
            'ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs ADD COLUMN show_button TINYINT(1) NOT NULL DEFAULT 1 AFTER active'
        );
    }
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not add catalogue show_button setting: '.$e->getMessage());
    throw $e;
}


// 1.0.51: split backend access into Videos, Catalogues and Settings permissions.
// The original permission remains supported as a legacy umbrella permission.
foreach (array(
    array('Manage YouTubeCatalog Videos','Manage YouTubeCatalog Videos'),
    array('Manage YouTubeCatalog Catalogues','Manage YouTubeCatalog Catalogues'),
    array('Manage YouTubeCatalog Settings','Manage YouTubeCatalog Settings')
) as $ytcPerm) {
    try {
        $this->CreatePermission($ytcPerm[0], $ytcPerm[1]);
    } catch (Exception $e) {
        // Permission may already exist; record unexpected failures.
        audit('', $this->GetName(), 'Permission registration skipped: '.$e->getMessage());
    }
}


// 1.0.51: backend list preferences. Existing sites receive safe defaults.
try {
    if (trim((string)$this->GetPreference('backend_module_name','')) === '') {
        $this->SetPreference('backend_module_name','YouTube Video Catalog');
    }
    $ytcPageLimit = (int)$this->GetPreference('backend_page_limit',0);
    if ($ytcPageLimit < 5 || $ytcPageLimit > 200) {
        $this->SetPreference('backend_page_limit',20);
    }
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not initialise YouTubeCatalog backend preferences: '.$e->getMessage());
}


// 1.0.52: ensure the Single Video Design Manager template/type exists.
// ensureDesignManagerTemplates() is intentionally safe and does not overwrite
// templates the site owner has already customised.
try {
    $this->ensureDesignManagerTemplates();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create Single Video Design Manager template: '.$e->getMessage());
}


// 1.0.53: ensure the Video Only Design Manager template/type exists.
try {
    $this->ensureDesignManagerTemplates();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create Video Only Design Manager template: '.$e->getMessage());
}


// 1.0.54: create the internal Single Videos cache catalogue on upgraded sites.
try {
    $db = cmsms()->GetDb();
    $singleCatalogId = (int)$db->GetOne(
        'SELECT catalog_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs '
      . 'WHERE name=? AND (youtube_playlist_id IS NULL OR youtube_playlist_id=\'\') '
      . 'ORDER BY catalog_id ASC',
        array('Single Videos')
    );

    if (!$singleCatalogId) {
        $now = date('Y-m-d H:i:s');
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
                $now,
                $now
            )
        );
    }
} catch (Exception $e) {
    audit('', $this->GetName(), 'Could not create Single Videos cache catalogue: '.$e->getMessage());
}
