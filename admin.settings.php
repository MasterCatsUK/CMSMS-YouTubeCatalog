<?php
echo $this->CreateFormStart($id,'savesettings',$returnid);
echo '<h3>Backend</h3>';
echo '<div class="pageoverflow"><p class="pagetext">Backend module name:</p><p class="pageinput"><input type="text" name="'.$id.'backend_module_name" value="'.htmlspecialchars($this->GetPreference('backend_module_name','YouTube Video Catalog'),ENT_QUOTES,'UTF-8').'" size="60" maxlength="80"><br><small>Changes the friendly name shown to administrators. The technical module name, folder and frontend tag remain <strong>YouTubeCatalog</strong>.</small></p></div>';
echo '<div class="pageoverflow"><p class="pagetext">Videos per backend page:</p><p class="pageinput"><input type="number" min="5" max="200" step="5" name="'.$id.'backend_page_limit" value="'.(int)$this->GetPreference('backend_page_limit',20).'"><br><small>Used on the backend Videos list. Minimum 5, maximum 200.</small></p></div>';
echo '<h3>YouTube and frontend</h3>';
echo '<div class="pageoverflow"><p class="pagetext">YouTube Data API v3 key:</p><p class="pageinput"><input type="password" name="'.$id.'api_key" value="'.htmlspecialchars($this->GetPreference('youtube_api_key','')).'" size="80" autocomplete="off"><br><small>Required for playlist import, durations, titles, descriptions and view counts.</small></p></div>';
echo '<div class="pageoverflow"><p class="pagetext">Automatic sync interval:</p><p class="pageinput"><input type="number" min="1" max="168" name="'.$id.'sync_hours" value="'.(int)$this->GetPreference('sync_interval_hours',6).'"> hours<br><small>Due playlists are checked when a page containing this module is requested or when the module admin is opened.</small></p></div>';

$config = cmsms()->GetConfig();
$root = rtrim($config['root_url'],'/');
$token = $this->GetPreference('cron_token','');
$cronUrl = $root.'/moduleinterface.php?mact=YouTubeCatalog,cntnt01,publicsync,0&cntnt01token='.rawurlencode($token);
echo '<div class="pageoverflow"><p class="pagetext">Optional server cron URL:</p><p class="pageinput"><input type="text" readonly value="'.htmlspecialchars($cronUrl).'" size="110"><br><small>For a true background schedule, have your hosting cron request this URL every hour. The module will only call YouTube when your configured interval is due. Keep this URL private because it contains the sync token.</small></p></div>';

echo '<div class="pageoverflow"><p class="pagetext">Frontend heading:</p><p class="pageinput"><input name="'.$id.'frontend_heading" value="'.htmlspecialchars($this->GetPreference('frontend_heading','Our Videos')).'" size="60"></p></div>';
echo '<div class="pageoverflow"><p class="pagetext">Frontend introduction:</p><div class="pageinput">';
echo $this->CreateTextArea(true,$id,$this->GetPreference('frontend_intro','Browse our video collection.'),'frontend_intro');
echo '<br><small>Shown underneath the main frontend heading and above the catalogue buttons. WYSIWYG formatting is supported.</small></div></div>';
echo '<div class="pageoverflow"><p class="pagetext">All videos description:</p><div class="pageinput">';
echo $this->CreateTextArea(true,$id,$this->GetPreference('all_videos_description',''),'all_videos_description');
echo '<br><small>Shown underneath the catalogue buttons when <strong>All videos</strong> is selected.</small></div></div>';
echo '<div class="ytc-settings-actions">';
echo '<input type="submit" value="Save settings" class="ytc-settings-submit">';
echo $this->CreateLink($id,'testapi',$returnid,'Test YouTube API',array(),'','','class="ytc-settings-action"');
echo $this->CreateLink($id,'syncall',$returnid,'Sync all playlists now',array(),'','','class="ytc-settings-action"');
echo $this->CreateLink($id,'rebuildsearch',$returnid,'Rebuild Search index',array(),'','','class="ytc-settings-action"');
echo '</div>';
echo '<p><strong>Last automatic/global sync:</strong> '.($this->GetPreference('last_global_sync',0)?date('Y-m-d H:i:s',(int)$this->GetPreference('last_global_sync')):'Never').'</p>';
echo $this->CreateFormEnd();
