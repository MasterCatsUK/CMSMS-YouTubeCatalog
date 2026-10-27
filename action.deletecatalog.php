<?php
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageCatalogs()) return;
$db=cmsms()->GetDb();
$cid=(int)($params['catalog_id']??0);
$linkedVideoIds = $db->GetCol('SELECT video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE catalog_id=?',array($cid));
$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE catalog_id=?',array($cid));
$db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE catalog_id=?',array($cid));
try { foreach ((array)$linkedVideoIds as $linkedVideoId) $this->indexVideo((int)$linkedVideoId); } catch (Exception $e) { audit($cid,$this->GetName(),'Search indexing failed after catalogue delete: '.$e->getMessage()); }
$this->SetMessage('Catalogue deleted.');
$this->RedirectToAdminTab('catalogs');
