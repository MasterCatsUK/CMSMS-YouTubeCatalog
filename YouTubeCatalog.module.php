<?php
// YouTubeCatalog module for CMS Made Simple.
// Copyright (c) 2026 Andrew Smith. Licensed under the MIT License.
// See docs/LICENSE for the full licence text.
if (!defined('CMS_VERSION')) exit;

class YouTubeCatalog extends CMSModule
{
    public function GetName() { return 'YouTubeCatalog'; }
    public function GetFriendlyName()
    {
        $name = trim((string)$this->GetPreference('backend_module_name','YouTube Video Catalog'));
        return $name !== '' ? $name : 'YouTube Video Catalog';
    }
    public function GetVersion() { return '1.0.60'; }
    public function GetAuthor() { return 'Andrew Smith'; }
    public function GetAuthorEmail() { return ''; }
    public function GetHelp() { return file_get_contents(__DIR__.'/help.inc'); }
    public function GetChangeLog() { return file_get_contents(__DIR__.'/changelog.inc'); }
    public function UninstallPreMessage() { return 'Uninstalling YouTubeCatalog deletes its stored video/catalogue records and module settings. Back up your data before continuing.'; }
    public function IsPluginModule() { return true; }
    public function HasAdmin() { return true; }
    public function GetAdminSection() { return 'content'; }
    public function GetAdminDescription() { return 'Manage YouTube videos and playlist-backed video catalogues.'; }
    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Manage YouTubeCatalog Videos')
            || $this->CheckPermission('Manage YouTubeCatalog Catalogues')
            || $this->CheckPermission('Manage YouTubeCatalog Settings')
            || $this->CheckPermission('Manage YouTube Catalog');
    }

    // The original permission remains an umbrella permission for backwards
    // compatibility with existing CMSMS user/group setups.
    public function canManageVideos()
    {
        return $this->CheckPermission('Manage YouTubeCatalog Videos')
            || $this->CheckPermission('Manage YouTube Catalog');
    }

    public function canManageCatalogs()
    {
        return $this->CheckPermission('Manage YouTubeCatalog Catalogues')
            || $this->CheckPermission('Manage YouTube Catalog');
    }

    public function canManageSettings()
    {
        return $this->CheckPermission('Manage YouTubeCatalog Settings')
            || $this->CheckPermission('Manage YouTube Catalog');
    }
    /** One preference slot makes the featured selection exclusive across all catalogues/pages. */
    public function getFeaturedVideoId()
    {
        return max(0, (int)$this->GetPreference('featured_video_id', 0));
    }

    public function setFeaturedVideoId($videoId)
    {
        $videoId = (int)$videoId;
        if ($videoId < 0) throw new InvalidArgumentException('Choose a valid video.');
        if ($videoId > 0) {
            $exists = cmsms()->GetDb()->GetOne(
                'SELECT video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=? AND COALESCE(active,1)<>0',
                array($videoId)
            );
            if (!$exists) throw new InvalidArgumentException('Only an existing active video can be featured.');
        }
        // Replacing this single ID automatically removes the previous selection.
        $this->SetPreference('featured_video_id', $videoId);
    }

    public function clearFeaturedVideoIfSelected($videoId)
    {
        if ($this->getFeaturedVideoId() === (int)$videoId) $this->setFeaturedVideoId(0);
    }

    public function MinimumCMSVersion() { return '2.2.0'; }

    public function InitializeFrontend()
    {
        $this->RegisterModulePlugin();
        $this->RestrictUnknownParams();
        $this->SetParameterType('catalog', CLEAN_INT);
        $this->SetParameterType('limit', CLEAN_INT);
        $this->SetParameterType('template', CLEAN_STRING);
        $this->SetParameterType('show_catalogs', CLEAN_INT);
        $this->SetParameterType('show_buttons', CLEAN_INT);
        $this->SetParameterType('show_all_button', CLEAN_INT);
        $this->SetParameterType('show_description', CLEAN_INT);
        $this->SetParameterType('show_catalog_description', CLEAN_INT);
        $this->SetParameterType('show_video_description', CLEAN_INT);
        $this->SetParameterType('show_stats', CLEAN_INT);
        $this->SetParameterType('video', CLEAN_STRING);
        $this->SetParameterType('video_id', CLEAN_INT);
        $this->SetParameterType('featured', CLEAN_INT);
    }

    public function InitializeAdmin()
    {
        $this->CreateParameter('catalog', null, 'Catalogue ID');
        $this->CreateParameter('limit', 0, 'Maximum videos (0 = all)');
        $this->CreateParameter('template', 'default', 'Frontend template name from Design Manager (default, catalog or single; .tpl is also accepted)');
        $this->CreateParameter('video', '', 'Single YouTube video URL or 11-character YouTube video ID');
        $this->CreateParameter('featured', 0, 'Use the one featured video selected in the Videos list (1 = enabled)');
    }

    public function GetDependencies() { return array(); }

    public function DoEvent($originator, $eventname, &$params)
    {
        // Kept intentionally light: syncing is performed lazily when the module is rendered/administered.
    }


    /**
     * Add or remove one video from the standard CMSMS Search index.
     * Search is optional, so this method is safe when the Search module is
     * not installed or is inactive.
     */
    public function indexVideo($videoId, $search = null)
    {
        $videoId = (int)$videoId;
        if ($videoId < 1) return false;

        if (!is_object($search)) $search = \cms_utils::get_search_module();
        if (!is_object($search)) return false;

        $db = cmsms()->GetDb();
        $row = $db->GetRow(
            'SELECT video_id,title,description,channel_title,active FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=?',
            array($videoId)
        );

        if (!$row || empty($row['active'])) {
            $search->DeleteWords($this->GetName(), $videoId, 'video');
            return true;
        }

        $catalogNames = $db->GetCol(
            'SELECT c.name FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs c '
          . 'INNER JOIN '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos cv ON cv.catalog_id=c.catalog_id '
          . 'WHERE cv.video_id=? AND COALESCE(c.active,1)<>0 ORDER BY c.sort_order,c.name',
            array($videoId)
        );

        $words = trim(
            (string)$row['title'].' '.
            strip_tags((string)$row['description']).' '.
            (string)$row['channel_title'].' '.
            implode(' ', (array)$catalogNames)
        );

        // The core CMSMS Search tables on some older/upgraded sites still use
        // MySQL utf8/utf8mb3. Those tables cannot store 4-byte Unicode
        // characters (emoji), even though YouTubeCatalog itself uses utf8mb4.
        // Strip only non-BMP code points for the Search copy so an emoji in a
        // YouTube title/description can never prevent the video itself saving.
        // The original title and description remain unchanged in our tables.
        $searchWords = preg_replace('/[\x{10000}-\x{10FFFF}]/u', ' ', $words);
        if ($searchWords === null) $searchWords = $words;
        $searchWords = preg_replace('/\s+/u', ' ', trim($searchWords));

        $search->DeleteWords($this->GetName(), $videoId, 'video');
        if ($searchWords !== '') {
            $search->AddWords($this->GetName(), $videoId, 'video', $searchWords);
        }
        return true;
    }

    /** Called by the CMSMS Search module when its index is rebuilt. */
    public function SearchReindex(&$search_module)
    {
        $db = cmsms()->GetDb();
        $ids = $db->GetCol(
            'SELECT video_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE COALESCE(active,1)<>0 ORDER BY video_id'
        );
        foreach ((array)$ids as $videoId) $this->indexVideo((int)$videoId, $search_module);
    }

    /** Return a standard CMSMS Search result for a YouTubeCatalog video. */
    public function SearchResultWithParams($returnid, $articleid, $attr = '', $params = '')
    {
        $result = array();
        if ($attr !== '' && $attr !== 'video') return $result;

        $db = cmsms()->GetDb();
        $row = $db->GetRow(
            'SELECT video_id,title FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=? AND COALESCE(active,1)<>0',
            array((int)$articleid)
        );
        if (!$row) return $result;

        $result[0] = $this->GetFriendlyName();
        $result[1] = $row['title'];
        $result[2] = $this->CreateLink(
            'cntnt01', 'default', $returnid, '',
            array('video_id'=>(int)$row['video_id']), '', true
        );
        return $result;
    }

    // Compatibility with Search versions that call SearchResult directly.
    public function SearchResult($returnid, $articleid, $attr = '')
    {
        return $this->SearchResultWithParams($returnid, $articleid, $attr, array());
    }

    /**
     * Factory content callback used by CMSMS Design Manager when a
     * YouTubeCatalog template type is reset to its module default.
     */
    public static function resetTemplateTypeDefaults(CmsLayoutTemplateType $type)
    {
        if ($type->get_originator() !== 'YouTubeCatalog') {
            throw new CmsLogicException('Invalid template type originator');
        }
        $name = $type->get_name();
        if (!in_array($name, array('default','catalog','single','videoonly'), true)) {
            throw new CmsLogicException('Unknown YouTubeCatalog template type');
        }
        $file = __DIR__.'/templates/'.$name.'.tpl';
        return is_file($file) ? file_get_contents($file) : '{* Missing YouTubeCatalog factory template *}';
    }

    /**
     * Register real CMSMS 2.x Design Manager template types and install a
     * default editable template for each one.  The older CMSModule
     * SetTemplate/GetTemplate API writes to the legacy module-template store
     * and does not create items visible in Design Manager.
     */
    public function ensureDesignManagerTemplates()
    {
        $uid = 1;
        if (!cmsms()->test_state(CmsApp::STATE_INSTALL)) {
            $tmp = (int)get_userid();
            if ($tmp > 0) $uid = $tmp;
        }

        $defs = array(
            'default' => array('display'=>'YouTubeCatalog Default','file'=>'default.tpl'),
            'catalog' => array('display'=>'YouTubeCatalog Catalog','file'=>'catalog.tpl'),
            'single' => array('display'=>'YouTubeCatalog Single Video','file'=>'single.tpl'),
            'videoonly' => array('display'=>'YouTubeCatalog Video Only','file'=>'videoonly.tpl')
        );

        foreach ($defs as $typeName => $def) {
            // Create/load the Design Manager template type.
            try {
                $type = CmsLayoutTemplateType::load($this->GetName().'::'.$typeName);
            } catch (CmsDataNotFoundException $e) {
                $type = new CmsLayoutTemplateType();
                $type->set_originator($this->GetName());
                $type->set_name($typeName);
                $type->set_dflt_flag(true);
                $type->set_content_callback('YouTubeCatalog::resetTemplateTypeDefaults');
                $type->save();
            }

            // Do not overwrite a Design Manager template the site owner has
            // already edited.  Only create it when it does not exist.
            try {
                $tpl = CmsLayoutTemplate::load($def['display']);
                continue;
            } catch (CmsDataNotFoundException $e) {
                // Continue and create it below.
            }

            $content = '';
            // Preserve content from the 1.0.37 legacy module-template store
            // if one exists, otherwise use the shipped .tpl as the factory.
            try {
                $legacy = $this->GetTemplate($typeName);
                if ($legacy !== false && trim((string)$legacy) !== '') $content = $legacy;
            } catch (Exception $e) { }
            if ($content === '') {
                $file = __DIR__.'/templates/'.$def['file'];
                if (is_file($file)) $content = file_get_contents($file);
            }
            if (trim($content) === '') $content = '{* Empty YouTubeCatalog template *}';

            $tpl = new CmsLayoutTemplate();
            $tpl->set_name($def['display']);
            $tpl->set_owner($uid);
            $tpl->set_content($content);
            $tpl->set_description('Frontend template installed by YouTubeCatalog.');
            $tpl->set_type($type);
            $tpl->set_type_dflt(true);
            $tpl->save();
        }
    }

    /**
     * Return a Design Manager template object for a frontend template key.
     * Returns null when the DM template is unavailable so file templates can
     * remain a safe fallback.
     */
    public function getDesignManagerTemplate($name)
    {
        // Accept the actual Design Manager template name supplied by the
        // module tag, e.g. template='TestVideo'.  The two factory aliases
        // remain for backwards compatibility.
        $name = trim(preg_replace('/\.tpl$/i', '', (string)$name));
        $name = basename(str_replace('\\', '/', $name));
        if ($name === '') $name = 'default';

        $lookup = $name;
        if ($name === 'default') $lookup = 'YouTubeCatalog Default';
        elseif ($name === 'catalog') $lookup = 'YouTubeCatalog Catalog';
        elseif ($name === 'single') $lookup = 'YouTubeCatalog Single Video';
        elseif ($name === 'videoonly') $lookup = 'YouTubeCatalog Video Only';

        try {
            $tpl = CmsLayoutTemplate::load($lookup);
            $type = CmsLayoutTemplateType::load($tpl->get_type_id());
            if ($type->get_originator() !== $this->GetName()) return null;
            return $tpl;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Render a CMSMS Design Manager template through the native
     * cms_template: resource.  This is the CMSMS 2.x resource used by
     * Design Manager and avoids the scope/security problems caused by
     * trying to recompile the saved template content as a string resource.
     */
    public function renderDesignManagerTemplate($tpl, $smarty)
    {
        if (!is_object($tpl)) return false;

        $name = trim((string)$tpl->get_name());
        if ($name === '') return false;

        try {
            $engine = cmsms()->GetSmarty();
            $parent = null;
            if (is_object($smarty) && method_exists($smarty, 'get_template_parent')) {
                $parent = $smarty->get_template_parent();
            } elseif (is_object($smarty)) {
                $parent = $smarty;
            }

            $compiled = $engine->CreateTemplate('cms_template:'.$name, null, null, $parent);

            // Module actions assign their frontend data to the current Smarty
            // scope.  Copy that data into the Design Manager template scope so
            // variables such as $ytc_videos and $ytc_catalogs are guaranteed
            // to be available regardless of the CMSMS/Smarty version.
            if (is_object($smarty) && method_exists($smarty, 'getTemplateVars')) {
                $vars = $smarty->getTemplateVars();
                if (is_array($vars)) {
                    foreach ($vars as $key => $value) $compiled->assign($key, $value);
                }
            }

            echo $compiled->fetch();
            return true;
        } catch (Exception $e) {
            audit('', $this->GetName(), 'Design Manager template render failed for '.$name.': '.$e->getMessage());
            return false;
        }
    }

    public function lazySyncIfDue()
    {
        $last = (int)$this->GetPreference('last_global_sync', 0);
        $hours = max(1, (int)$this->GetPreference('sync_interval_hours', 6));
        if (time() - $last < ($hours * 3600)) return;

        $lock = TMP_CACHE_LOCATION.'/youtubecatalog-sync.lock';
        if (is_file($lock) && filemtime($lock) > time() - 600) return;
        @file_put_contents($lock, (string)time());
        try {
            require_once __DIR__.'/lib/YouTubeCatalog.api.php';
            YouTubeCatalogApi::syncAll($this, false);
            $this->SetPreference('last_global_sync', time());
        } catch (Exception $e) {
            audit('', $this->GetName(), 'Automatic sync failed: '.$e->getMessage());
        }
        @unlink($lock);
    }
}
