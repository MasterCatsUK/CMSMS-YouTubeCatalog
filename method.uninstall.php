<?php
if (!defined('CMS_VERSION')) exit;
$db = cmsms()->GetDb();
$dict = NewDataDictionary($db);
try {
    $search = \cms_utils::get_search_module();
    if (is_object($search)) {
        $ids = $db->GetCol('SELECT video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos');
        foreach ((array)$ids as $videoId) $search->DeleteWords($this->GetName(), (int)$videoId, 'video');
    }
} catch (Exception $e) { audit('', $this->GetName(), 'Search cleanup during uninstall failed: '.$e->getMessage()); }
$dict->ExecuteSQLArray($dict->DropTableSQL(CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos'));
$dict->ExecuteSQLArray($dict->DropTableSQL(CMS_DB_PREFIX.'module_youtubecatalog_videos'));
$dict->ExecuteSQLArray($dict->DropTableSQL(CMS_DB_PREFIX.'module_youtubecatalog_catalogs'));
// Remove YouTubeCatalog templates and template types from Design Manager.
foreach (array('YouTubeCatalog Default','YouTubeCatalog Catalog') as $tplName) {
    try { $tpl = CmsLayoutTemplate::load($tplName); $tpl->delete(); } catch (Exception $e) { audit('', $this->GetName(), 'Optional uninstall cleanup failed: '.$e->getMessage()); }
}
foreach (array('default','catalog') as $typeName) {
    try { $type = CmsLayoutTemplateType::load($this->GetName().'::'.$typeName); $type->delete(); } catch (Exception $e) { audit('', $this->GetName(), 'Optional uninstall cleanup failed: '.$e->getMessage()); }
}
// Also clean up the legacy module-template records created by 1.0.37.
try { $this->DeleteTemplate('default'); $this->DeleteTemplate('catalog'); } catch (Exception $e) { audit('', $this->GetName(), 'Optional uninstall cleanup failed: '.$e->getMessage()); }
$this->RemovePermission('Manage YouTube Catalog');
$this->RemovePermission('Manage YouTubeCatalog Videos');
$this->RemovePermission('Manage YouTubeCatalog Catalogues');
$this->RemovePermission('Manage YouTubeCatalog Settings');
$this->RemovePreference();
