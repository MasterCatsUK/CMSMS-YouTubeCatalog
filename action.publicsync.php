<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
$expected = (string)$this->GetPreference('cron_token','');
$given = isset($params['token']) ? (string)$params['token'] : '';
header('Content-Type: text/plain; charset=utf-8');
if ($expected === '' || !hash_equals($expected,$given)) { http_response_code(403); echo "Forbidden\n"; return; }
$hours = max(1,(int)$this->GetPreference('sync_interval_hours',6));
$last = (int)$this->GetPreference('last_global_sync',0);
if (time()-$last < $hours*3600) { echo "Not due yet.\n"; return; }
require_once __DIR__.'/lib/YouTubeCatalog.api.php';
try {
    $result = YouTubeCatalogApi::syncAll($this,false);
    $this->SetPreference('last_global_sync',time());
    echo "YouTube catalog sync complete.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Sync failed: ".$e->getMessage()."\n";
}
