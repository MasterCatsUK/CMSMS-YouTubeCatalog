<?php
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageCatalogs()) return;
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
$cid=(int)($params['catalog_id']??0);
try {
    $r=YouTubeCatalogApi::syncCatalog($this,$cid,true);
    $msg='Sync complete: '.(int)$r['added'].' added, '.(int)$r['updated'].' updated, '.(int)$r['seen'].' in playlist.';
    $this->SetPreference('last_global_sync',time());
    $this->SetMessage($msg);
} catch(Exception $e) {
    $this->SetError($e->getMessage());
}
$this->RedirectToAdminTab('catalogs');
