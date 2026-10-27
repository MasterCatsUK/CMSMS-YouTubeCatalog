<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
$canVideos = $this->canManageVideos();
$canCatalogs = $this->canManageCatalogs();
$canSettings = $this->canManageSettings();
// Explicit CMSMS permission verification for this admin entry point.
if (!$this->CheckPermission('Manage YouTubeCatalog Videos')
    && !$this->CheckPermission('Manage YouTubeCatalog Catalogues')
    && !$this->CheckPermission('Manage YouTubeCatalog Settings')
    && !$this->CheckPermission('Manage YouTube Catalog')) return;
if (!$canVideos && !$canCatalogs && !$canSettings) return;

$this->lazySyncIfDue();
$tab = isset($params['active_tab']) ? $params['active_tab'] : (isset($params['tab']) ? $params['tab'] : '');
$allowedTabs = array();
if ($canVideos) $allowedTabs[] = 'videos';
if ($canCatalogs) $allowedTabs[] = 'catalogs';
if ($canSettings) $allowedTabs[] = 'settings';
if (!in_array($tab, $allowedTabs, true)) $tab = reset($allowedTabs);

if (isset($params['message'])) echo $this->ShowMessage(htmlspecialchars((string)$params['message'], ENT_QUOTES, 'UTF-8'));
if (isset($params['error'])) echo $this->ShowErrors(htmlspecialchars((string)$params['error'], ENT_QUOTES, 'UTF-8'));

// Self-contained admin styling.  This avoids relying on CMSMS admin-theme tab CSS,
// which is not present in every CMSMS admin theme/version.
echo '<style>
.ytc-admin{max-width:1400px;padding:0 2px 24px;box-sizing:border-box;font-family:inherit}
.ytc-admin *{box-sizing:border-box}
.ytc-admin-nav{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 22px;padding:0 0 10px}
.ytc-admin-nav a{display:inline-block;padding:9px 15px;border:1px solid;border-radius:6px;text-decoration:none!important;font-weight:600;line-height:1.2}
.ytc-admin-nav a.active{font-weight:700;text-decoration:underline!important}
.ytc-admin .pageoptions{margin:0 0 16px}
.ytc-admin .pageoptions a{display:inline-block;padding:9px 14px;border:1px solid;border-radius:6px;text-decoration:none!important;font-weight:600}
.ytc-admin table.pagetable{width:100%;border-collapse:collapse}
.ytc-admin table.pagetable th{font-weight:700;text-align:left;padding:10px 12px;white-space:nowrap}
.ytc-admin table.pagetable td{padding:11px 12px;vertical-align:top}
.ytc-admin .pageoverflow{display:grid;grid-template-columns:minmax(180px,260px) minmax(0,1fr);gap:16px;align-items:start;margin:0 0 18px;padding:0}
.ytc-admin .pagetext{margin:7px 0 0;font-weight:700}
.ytc-admin .pageinput{margin:0}
.ytc-admin input[type=text],.ytc-admin input[type=password],.ytc-admin input[type=number],.ytc-admin textarea,.ytc-admin select{width:min(100%,760px);max-width:100%;padding:9px 10px;border-radius:5px;font:inherit}
.ytc-admin textarea{min-height:120px;resize:vertical}
.ytc-admin input[type=submit],.ytc-admin button[type=submit],.ytc-admin .pagebutton{font:inherit}
.ytc-admin .ytc-admin-button{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:auto!important;min-width:0!important;max-width:none!important;height:auto!important;min-height:0!important;margin:0!important;padding:8px 14px!important;border:1px solid!important;border-radius:6px!important;line-height:1.3!important;white-space:nowrap!important;text-decoration:none!important;cursor:pointer;font:inherit;font-weight:600}
.ytc-admin small{line-height:1.45}
.ytc-admin .ytc-empty{padding:22px;border:1px dashed;border-radius:8px}

.ytc-admin .ytc-admin-form-heading{margin:0 0 18px}
.ytc-admin .ytc-admin-form-heading h2{margin:0 0 5px;font-size:1.45rem}
.ytc-admin .ytc-admin-form-heading p{margin:0}
.ytc-admin .ytc-admin-card{border:1px solid;border-radius:10px;padding:20px;margin:0 0 18px}
.ytc-admin .ytc-admin-grid{display:grid;gap:18px}
.ytc-admin .ytc-admin-grid-2{grid-template-columns:minmax(0,1fr) minmax(180px,260px)}
.ytc-admin .ytc-admin-video-layout{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(260px,.75fr);gap:24px;align-items:start}
.ytc-admin .ytc-admin-field{margin:0 0 18px}
.ytc-admin .ytc-admin-field:last-child{margin-bottom:0}
.ytc-admin .ytc-admin-field>label{display:block;font-weight:700;margin:0 0 7px}
.ytc-admin .ytc-admin-field small{display:block;margin-top:6px}
.ytc-admin .ytc-admin-optional{font-weight:400;font-size:.86em}
.ytc-admin .ytc-admin-checks{display:grid;gap:8px;padding:11px;border:1px solid;border-radius:7px;max-height:240px;overflow:auto}
.ytc-admin .ytc-admin-checks label{display:flex;gap:8px;align-items:flex-start;margin:0;font-weight:400}
.ytc-admin .ytc-admin-check-row label{display:flex;gap:8px;align-items:center;font-weight:600}
.ytc-admin .ytc-admin-video-side{display:grid;gap:18px}
.ytc-admin .ytc-admin-thumb-card,.ytc-admin .ytc-admin-thumb-placeholder{border:1px solid;border-radius:8px;padding:12px}
.ytc-admin .ytc-admin-thumb-title{font-weight:700;margin-bottom:9px}
.ytc-admin .ytc-admin-thumb-card img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:6px;margin-bottom:8px}
.ytc-admin .ytc-admin-thumb-placeholder{min-height:150px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:5px}
.ytc-admin .ytc-admin-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px}


.ytc-admin .ytc-settings-actions{display:flex!important;flex-wrap:wrap!important;gap:8px!important;align-items:center!important;margin:18px 0 0!important;padding:0!important}
.ytc-admin .ytc-settings-actions .ytc-settings-submit,
.ytc-admin .ytc-settings-actions .ytc-settings-action,
.ytc-admin .ytc-filter-submit{
  display:inline-block!important;
  width:auto!important;
  min-width:0!important;
  max-width:100%!important;
  height:auto!important;
  min-height:0!important;
  margin:0!important;
  padding:8px 13px!important;
  border:1px solid!important;
  border-radius:6px!important;
  line-height:1.3!important;
  white-space:nowrap!important;
  vertical-align:middle!important;
  text-decoration:none!important;
  font:inherit!important;
  font-weight:600!important;
  cursor:pointer!important
}
.ytc-admin .ytc-settings-actions .ytc-settings-action::before,
.ytc-admin .ytc-settings-actions .ytc-settings-action::after{
  content:none!important;
  display:none!important
}

@media(max-width:900px){.ytc-admin .ytc-admin-video-layout,.ytc-admin .ytc-admin-grid-2{grid-template-columns:1fr}}
@media(max-width:800px){.ytc-admin .pageoverflow{grid-template-columns:1fr;gap:6px}.ytc-admin .pagetext{margin-top:0}.ytc-admin table.pagetable{display:block;overflow-x:auto}}
</style>';

echo '<div class="ytc-admin">';
echo '<nav class="ytc-admin-nav" aria-label="YouTube Catalog sections">';
if ($canVideos) echo $this->CreateLink($id,'defaultadmin',$returnid,'Videos',array('tab'=>'videos'),'','','class="'.($tab==='videos'?'active':'').'"');
if ($canCatalogs) echo $this->CreateLink($id,'defaultadmin',$returnid,'Catalogues',array('tab'=>'catalogs'),'','','class="'.($tab==='catalogs'?'active':'').'"');
if ($canSettings) echo $this->CreateLink($id,'defaultadmin',$returnid,'Settings',array('tab'=>'settings'),'','','class="'.($tab==='settings'?'active':'').'"');
echo '</nav>';

switch ($tab) {
    case 'catalogs':
        if ($canCatalogs) include __DIR__.'/admin.catalogs.php';
        break;
    case 'settings':
        if ($canSettings) include __DIR__.'/admin.settings.php';
        break;
    case 'videos':
    default:
        if ($canVideos) include __DIR__.'/admin.videos.php';
        break;
}

echo '</div>';
