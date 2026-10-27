<?php
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageSettings()) return;
$backendName = trim(strip_tags((string)($params['backend_module_name'] ?? '')));
if (strlen($backendName) > 80) $backendName = substr($backendName,0,80);
if ($backendName === '') $backendName = 'YouTube Video Catalog';
$this->SetPreference('backend_module_name',$backendName);
$this->SetPreference('backend_page_limit',max(5,min(200,(int)($params['backend_page_limit'] ?? 20))));
$this->SetPreference('youtube_api_key',trim($params['api_key'] ?? ''));
$this->SetPreference('sync_interval_hours',max(1,min(168,(int)($params['sync_hours'] ?? 6))));
$this->SetPreference('frontend_heading',trim($params['frontend_heading'] ?? 'Our Videos'));
$this->SetPreference('frontend_intro',trim($params['frontend_intro'] ?? ''));
$this->SetPreference('all_videos_description',trim($params['all_videos_description'] ?? ''));
$this->SetMessage('Settings saved.');
$this->RedirectToAdminTab('settings');
