<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
$db=cmsms()->GetDb(); $rows=$db->GetArray('SELECT c.*,COUNT(cv.video_id) video_count FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs c LEFT JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos cv ON cv.catalog_id=c.catalog_id GROUP BY c.catalog_id ORDER BY c.sort_order,c.name');
echo '<div class="pageoptions">'.$this->CreateLink($id,'editcatalog',$returnid,'Add YouTube playlist / catalogue',array()).'</div>';
if (!$rows) { echo '<div class="ytc-empty">No catalogues or YouTube playlists have been added yet.</div>'; return; }
echo '<table class="pagetable"><thead><tr><th>ID</th><th>Catalogue</th><th>Playlist ID</th><th>Videos</th><th>Last sync</th><th>Actions</th></tr></thead><tbody>';
foreach($rows as $r){echo '<tr><td>'.(int)$r['catalog_id'].'</td><td><strong>'.htmlspecialchars($r['name']).'</strong></td><td>'.htmlspecialchars($r['youtube_playlist_id']?:'Manual').'</td><td>'.(int)$r['video_count'].'</td><td>'.htmlspecialchars($r['last_sync']?:'Never').'</td><td>'; if($r['youtube_playlist_id']) echo $this->CreateLink($id,'synccatalog',$returnid,'Sync',array('catalog_id'=>$r['catalog_id'])).' '; echo $this->CreateLink($id,'editcatalog',$returnid,'Edit',array('catalog_id'=>$r['catalog_id'])).' '.$this->CreateLink($id,'deletecatalog',$returnid,'Delete',array('catalog_id'=>$r['catalog_id']),'Delete this catalogue and its links?').'</td></tr>'; }
echo '</tbody></table>';
