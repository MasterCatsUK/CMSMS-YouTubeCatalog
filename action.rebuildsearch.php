<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageSettings()) return;

try {
    $search = \cms_utils::get_search_module();
    if (!is_object($search)) {
        $this->SetError('The CMSMS Search module is not installed or is not active.');
    } else {
        $this->SearchReindex($search);
        $this->SetMessage('YouTubeCatalog Search index rebuilt.');
    }
} catch (Exception $e) {
    $this->SetError('Could not rebuild the Search index: '.$e->getMessage());
}
$this->RedirectToAdminTab('settings');
