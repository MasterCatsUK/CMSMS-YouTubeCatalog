<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageSettings()) return;
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
try {
    $key = trim($this->GetPreference('youtube_api_key',''));
    if ($key === '') throw new Exception('No YouTube API key has been saved yet.');
    // Small, inexpensive request used only to verify connectivity/key restrictions.
    YouTubeCatalogApi::apiGet($this,'videos',array('part'=>'snippet','id'=>'dQw4w9WgXcQ'));
    $this->SetMessage('YouTube API connection successful. The API key and HTTPS connection are working.');
} catch (Exception $e) {
    $this->SetError('YouTube API test failed: '.$e->getMessage());
}
$this->RedirectToAdminTab('settings');
