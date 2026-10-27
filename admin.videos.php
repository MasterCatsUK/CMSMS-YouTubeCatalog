<?php
$db = cmsms()->GetDb();
$featuredVideoId = $this->getFeaturedVideoId();
require_once __DIR__.'/lib/YouTubeCatalog.api.php';

// Backend catalogue filter. 0 means "Show all".
$catalogFilter = isset($params['catalog_filter']) ? max(0, (int)$params['catalog_filter']) : 0;
$catalogRows = $db->GetArray(
    'SELECT catalog_id,name FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs ORDER BY sort_order ASC,name ASC,catalog_id ASC'
);
$catalogOptions = array('Show all' => 0);
$validCatalogIds = array();
foreach ((array)$catalogRows as $catalogRow) {
    $cid = (int)$catalogRow['catalog_id'];
    $validCatalogIds[$cid] = true;
    $label = trim((string)$catalogRow['name']);
    if ($label === '') $label = 'Catalogue '.$cid;
    $catalogOptions[$label.' (ID '.$cid.')'] = $cid;
}
if ($catalogFilter > 0 && !isset($validCatalogIds[$catalogFilter])) $catalogFilter = 0;

// Configurable backend page size. Keep the range sensible even if a stored
// preference is edited outside the module.
$pageLimit = (int)$this->GetPreference('backend_page_limit', 20);
$pageLimit = max(5, min(200, $pageLimit));
$pageNum = isset($params['pagenum']) ? max(1, (int)$params['pagenum']) : 1;

$countSql = 'SELECT COUNT(DISTINCT v.video_id) FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos v';
$countArgs = array();
if ($catalogFilter > 0) {
    $countSql .= ' INNER JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos fcv ON fcv.video_id=v.video_id AND fcv.catalog_id=?';
    $countArgs[] = $catalogFilter;
}
$totalRows = (int)$db->GetOne($countSql, $countArgs);
$totalPages = max(1, (int)ceil($totalRows / $pageLimit));
if ($pageNum > $totalPages) $pageNum = $totalPages;
$offset = ($pageNum - 1) * $pageLimit;

$sql = 'SELECT v.*, GROUP_CONCAT(DISTINCT c.name ORDER BY c.sort_order ASC,c.name ASC SEPARATOR ", ") catalogs '
     . 'FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos v ';
$queryArgs = array();
if ($catalogFilter > 0) {
    $sql .= 'INNER JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos fcv ON fcv.video_id=v.video_id AND fcv.catalog_id=? ';
    $queryArgs[] = $catalogFilter;
}
$sql .= 'LEFT JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos cv ON cv.video_id=v.video_id '
      . 'LEFT JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs c ON c.catalog_id=cv.catalog_id '
      . 'GROUP BY v.video_id ORDER BY v.video_id DESC';
$rows = $db->SelectLimit($sql, $pageLimit, $offset, $queryArgs);
if (is_object($rows)) $rows = $rows->GetArray();
if (!is_array($rows)) $rows = array();

echo '<style>
.ytc-admin-thumb{width:160px;max-width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:6px;display:block}
.ytc-admin-video-cell{display:flex;gap:14px;align-items:flex-start;min-width:330px}
.ytc-admin-video-info{min-width:0}.ytc-admin-video-title{display:block;margin-bottom:4px}.ytc-admin-video-id{word-break:break-all}
.ytc-admin-list-tools{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:12px;margin:0 0 16px}
.ytc-admin-filter-form{display:flex;flex-wrap:wrap;align-items:flex-end;gap:8px;margin:0}
.ytc-admin-filter-form label{display:grid;gap:5px;font-weight:600}
.ytc-admin-pagination{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:6px;margin:18px 0 4px}
.ytc-admin-pagination a,.ytc-admin-pagination strong,.ytc-admin-pagination span{display:inline-flex;min-width:2.1em;min-height:2.1em;align-items:center;justify-content:center;padding:.25em .5em;border:1px solid;border-radius:4px;text-decoration:none}
.ytc-admin-pagination strong{font-weight:700}
.ytc-admin-pagination .ytc-ellipsis{border:0;min-width:auto;padding-left:.2em;padding-right:.2em}
.ytc-admin-result-count{margin:10px 0 0}
@media(max-width:900px){.ytc-admin-video-cell{min-width:250px}.ytc-admin-thumb{width:120px}}
</style>';

echo '<div class="pageoptions">'.$this->CreateLink($id,'editvideo',$returnid,'Add video',array()).'</div>';
echo '<div class="ytc-admin-list-tools">';
echo '<div class="ytc-admin-filter-form">';
echo $this->CreateFormStart($id,'defaultadmin',$returnid,'post');
echo $this->CreateInputHidden($id,'tab','videos');
echo '<label for="'.$id.'catalog_filter">View by catalogue';
echo $this->CreateInputDropdown($id,'catalog_filter',$catalogOptions,-1,$catalogFilter);
echo '</label>';
echo '<input type="submit" value="Apply" class="ytc-filter-submit">';
echo $this->CreateFormEnd();
echo '</div>';
echo '<div><small>'.number_format($totalRows).' video'.($totalRows === 1 ? '' : 's').'</small></div>';
echo '</div>';

if (!$rows) {
    echo '<div class="ytc-empty">'.($catalogFilter > 0 ? 'No videos are assigned to this catalogue.' : 'No videos have been added yet.').'</div>';
    return;
}

echo '<p id="ytc-featured-help">Choose one featured video. Selecting another automatically unticks the previous video. Untick it to remove the featured selection.</p>';
echo '<table class="pagetable"><thead><tr><th>Video</th><th scope="col">Featured</th><th>Length</th><th>Views</th><th>Catalogue</th><th>Source</th><th>Actions</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $thumb = '';
    try { $thumb = YouTubeCatalogApi::ensureLocalThumbnail($this, $r['youtube_id'], $r['thumbnail_url'], false); }
    catch (Exception $e) { $thumb = ''; }
    echo '<tr><td><div class="ytc-admin-video-cell">';
    if ($thumb !== '') echo '<img class="ytc-admin-thumb" src="'.htmlspecialchars($thumb,ENT_QUOTES,'UTF-8').'" alt="" loading="lazy">';
    echo '<div class="ytc-admin-video-info"><strong class="ytc-admin-video-title">'.htmlspecialchars($r['title']).'</strong><small class="ytc-admin-video-id">'.htmlspecialchars($r['youtube_id']).'</small></div></div></td><td>';
    echo $this->CreateFormStart($id,'setfeatured',$returnid,'post');
    echo $this->CreateInputHidden($id,'video_id',(int)$r['video_id']);
    echo $this->CreateInputHidden($id,'catalog_filter',$catalogFilter);
    echo $this->CreateInputHidden($id,'pagenum',$pageNum);
    $isFeatured = (int)$r['video_id'] === $featuredVideoId;
    $disabled = empty($r['active']) && !$isFeatured ? ' disabled' : '';
    echo '<input type="checkbox" name="'.$id.'make_featured" value="1" aria-label="Featured: '.htmlspecialchars($r['title'],ENT_QUOTES,'UTF-8').'" aria-describedby="ytc-featured-help"'.($isFeatured ? ' checked' : '').$disabled.' onchange="this.form.requestSubmit()">';
    echo '<noscript><button type="submit">Save featured choice</button></noscript>';
    echo $this->CreateFormEnd();
    echo '</td><td>'.htmlspecialchars($r['duration_text']).'</td><td>'.number_format((int)$r['view_count']).'</td><td>'.htmlspecialchars($r['catalogs'] ?: '—').'</td><td>'.htmlspecialchars($r['source_type']).'</td><td>'.$this->CreateLink($id,'editvideo',$returnid,'Edit',array('video_id'=>$r['video_id'])).' &nbsp; '.$this->CreateLink($id,'deletevideo',$returnid,'Delete',array('video_id'=>$r['video_id']),'Delete this video?').'</td></tr>';
}
echo '</tbody></table>';

if ($totalPages > 1) {
    $baseParams = array('tab'=>'videos','catalog_filter'=>$catalogFilter);
    echo '<nav class="ytc-admin-pagination" aria-label="Video list pages">';
    if ($pageNum > 1) {
        echo $this->CreateLink($id,'defaultadmin',$returnid,'‹',array_merge($baseParams,array('pagenum'=>$pageNum-1)));
    }

    // Always show first/last, plus two pages either side of the current page.
    $pageSet = array(1,$totalPages);
    for ($i=max(1,$pageNum-2); $i<=min($totalPages,$pageNum+2); $i++) $pageSet[] = $i;
    $pageSet = array_values(array_unique($pageSet));
    sort($pageSet,SORT_NUMERIC);
    $previousShown = 0;
    foreach ($pageSet as $page) {
        if ($previousShown && $page > $previousShown + 1) echo '<span class="ytc-ellipsis" aria-hidden="true">…</span>';
        if ($page === $pageNum) {
            echo '<strong aria-current="page">'.$page.'</strong>';
        } else {
            echo $this->CreateLink($id,'defaultadmin',$returnid,(string)$page,array_merge($baseParams,array('pagenum'=>$page)));
        }
        $previousShown = $page;
    }

    if ($pageNum < $totalPages) {
        echo $this->CreateLink($id,'defaultadmin',$returnid,'›',array_merge($baseParams,array('pagenum'=>$pageNum+1)));
    }
    echo '</nav>';
}

echo '<p class="ytc-admin-result-count"><small>Page '.number_format($pageNum).' of '.number_format($totalPages).'</small></p>';
