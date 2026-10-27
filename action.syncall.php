<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageSettings()) return;
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
try {
    YouTubeCatalogApi::syncAll($this,true);
    $this->SetPreference('last_global_sync',time());
    $this->SetMessage('All playlist catalogues have been checked.');
} catch (Exception $e) {
    $this->SetError($e->getMessage());
}
$this->RedirectToAdminTab('settings');
