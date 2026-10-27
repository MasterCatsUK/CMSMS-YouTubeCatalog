<?php
class YouTubeCatalogApi
{
    private static $dbPrepared = false;
    private static $schemaPrepared = false;

    public static function db()
    {
        $db = cmsms()->GetDb();
        // CMSMS installations upgraded over many years may still open the
        // MySQL session as utf8/utf8mb3.  YouTube metadata can contain emoji,
        // so make this module's current DB session explicitly 4-byte UTF-8.
        if (!self::$dbPrepared) {
            $db->Execute('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
            self::$dbPrepared = true;
        }
        return $db;
    }

    public static function ensureUtf8mb4Schema()
    {
        if (self::$schemaPrepared) return;
        $db = self::db();

        // CONVERT TO changes existing columns, but explicitly MODIFY the text
        // columns too.  This repairs installations where an earlier upgrade
        // changed only the table default or where the upgrade hook was skipped.
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_videos  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_videos  MODIFY title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_videos  MODIFY description LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_videos  MODIFY channel_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');

        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs  MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs  MODIFY description LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        $db->Execute('ALTER TABLE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs  MODIFY last_error LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');

        self::$schemaPrepared = true;
    }

    public static function apiGet($module, $endpoint, array $params)
    {
        $key = trim($module->GetPreference('youtube_api_key', ''));
        if ($key === '') throw new Exception('YouTube API key has not been configured.');
        $params['key'] = $key;
        $url = 'https://www.googleapis.com/youtube/v3/'.$endpoint.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $raw = false;
        $httpCode = 0;
        $transportError = '';

        // Prefer cURL. A lot of shared hosts disable allow_url_fopen, which
        // prevents file_get_contents() from opening HTTPS URLs even though
        // outbound HTTPS itself is available.
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_USERAGENT => 'CMSMS YouTubeCatalog/1.0.22',
                CURLOPT_HTTPHEADER => array('Accept: application/json'),
            ));
            $raw = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($raw === false) $transportError = 'cURL: '.curl_error($ch);
            curl_close($ch);
        } else {
            // Fallback for hosts without cURL. Capture the PHP warning so the
            // admin receives a useful diagnostic rather than a generic error.
            $ctx = stream_context_create(array(
                'http' => array(
                    'timeout' => 25,
                    'ignore_errors' => true,
                    'user_agent' => 'CMSMS YouTubeCatalog/1.0.22',
                    'header' => "Accept: application/json\r\n"
                )
            ));
            $lastError = null;
            set_error_handler(function($severity, $message) use (&$lastError) { $lastError = $message; });
            $raw = file_get_contents($url, false, $ctx);
            restore_error_handler();
            if ($raw === false) $transportError = $lastError ? 'PHP stream: '.$lastError : 'PHP could not open the HTTPS URL.';
            if (!empty($http_response_header) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
                $httpCode = (int)$m[1];
            }
        }

        if ($raw === false) {
            throw new Exception('Could not contact the YouTube API'.($transportError ? ': '.$transportError : '.'));
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) throw new Exception('YouTube returned an invalid JSON response'.($httpCode ? ' (HTTP '.$httpCode.')' : '.'));
        if (isset($json['error'])) {
            $msg = isset($json['error']['message']) ? $json['error']['message'] : 'YouTube API error.';
            if ($httpCode) $msg .= ' (HTTP '.$httpCode.')';
            throw new Exception($msg);
        }
        return $json;
    }

    public static function extractVideoId($input)
    {
        $input = trim((string)$input);
        if ($input === '') return null;

        // A bare 11-character YouTube video ID.
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) return $input;

        // Allow URLs pasted without a scheme, e.g. youtube.com/watch?v=...
        if (!preg_match('#^https?://#i', $input) && preg_match('#^(www\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)/#i', $input)) {
            $input = 'https://'.$input;
        }

        $parts = @parse_url($input);
        if (!is_array($parts) || empty($parts['host'])) return null;
        $host = strtolower(preg_replace('/^www\./i', '', $parts['host']));
        $path = isset($parts['path']) ? trim($parts['path'], '/') : '';

        if ($host === 'youtu.be') {
            $candidate = preg_split('#/#', $path)[0] ?? '';
            return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
        }

        if ($host === 'youtube.com' || $host === 'm.youtube.com' || $host === 'music.youtube.com' || $host === 'youtube-nocookie.com') {
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $q);
                if (!empty($q['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', $q['v'])) return $q['v'];
            }

            // Shorts, live streams, embeds and legacy /v/ URLs.
            if (preg_match('#^(?:shorts|embed|live|v)/([A-Za-z0-9_-]{11})(?:/|$)#', $path, $m)) return $m[1];
        }

        return null;
    }

    public static function extractPlaylistId($input)
    {
        $input = trim($input);
        if (preg_match('/^[A-Za-z0-9_-]{10,}$/', $input) && strpos($input, 'http') !== 0) return $input;
        $parts = @parse_url($input);
        if (!is_array($parts) || empty($parts['query'])) return null;
        parse_str($parts['query'], $q);
        return !empty($q['list']) ? $q['list'] : null;
    }

    public static function fetchVideos($module, array $ids)
    {
        $out = array();
        $ids = array_values(array_unique(array_filter($ids)));
        foreach (array_chunk($ids, 50) as $chunk) {
            $json = self::apiGet($module, 'videos', array(
                'part' => 'snippet,contentDetails,statistics,status',
                'id' => implode(',', $chunk)
            ));
            foreach ((array)($json['items'] ?? array()) as $item) {
                $id = $item['id'];
                $s = $item['snippet'] ?? array();
                $c = $item['contentDetails'] ?? array();
                $st = $item['statistics'] ?? array();
                $thumbs = $s['thumbnails'] ?? array();
                $thumb = '';
                foreach (array('maxres','standard','high','medium','default') as $k) {
                    if (!empty($thumbs[$k]['url'])) { $thumb = $thumbs[$k]['url']; break; }
                }
                $out[$id] = array(
                    'youtube_id' => $id,
                    'title' => $s['title'] ?? '',
                    'description' => $s['description'] ?? '',
                    'duration_iso' => $c['duration'] ?? '',
                    'duration_text' => self::formatDuration($c['duration'] ?? ''),
                    'view_count' => isset($st['viewCount']) ? (int)$st['viewCount'] : 0,
                    'thumbnail_url' => $thumb,
                    'published_at' => !empty($s['publishedAt']) ? gmdate('Y-m-d H:i:s', strtotime($s['publishedAt'])) : null,
                    'channel_title' => $s['channelTitle'] ?? '',
                    'youtube_status' => ($item['status']['privacyStatus'] ?? 'public')
                );
            }
        }
        return $out;
    }

    public static function getUploadsInfo()
    {
        $config = cmsms()->GetConfig();
        $basePath = isset($config['uploads_path']) && $config['uploads_path'] ? rtrim($config['uploads_path'], '/\\') : rtrim(CMS_ROOT_PATH, '/\\').DIRECTORY_SEPARATOR.'uploads';
        $baseUrl = isset($config['uploads_url']) && $config['uploads_url'] ? rtrim($config['uploads_url'], '/') : '/uploads';
        return array(
            'path' => $basePath.DIRECTORY_SEPARATOR.'YouTubeCatalog',
            'url' => $baseUrl.'/YouTubeCatalog'
        );
    }

    public static function ensureThumbnailDirectory()
    {
        $info = self::getUploadsInfo();
        if (!is_dir($info['path'])) {
            if (!@mkdir($info['path'], 0755, true) && !is_dir($info['path'])) {
                throw new Exception('Could not create thumbnail folder: '.$info['path']);
            }
        }
        if (!is_writable($info['path'])) {
            throw new Exception('Thumbnail folder is not writable: '.$info['path']);
        }
        $index = $info['path'].DIRECTORY_SEPARATOR.'index.html';
        if (!is_file($index)) @file_put_contents($index, "<!doctype html><title></title>\n");
        return $info;
    }

    public static function localThumbnailFilename($youtubeId)
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$youtubeId);
        return $safe.'.jpg';
    }

    public static function getLocalThumbnailPath($youtubeId)
    {
        $info = self::getUploadsInfo();
        return $info['path'].DIRECTORY_SEPARATOR.self::localThumbnailFilename($youtubeId);
    }

    public static function getLocalThumbnailUrl($youtubeId)
    {
        $info = self::getUploadsInfo();
        return $info['url'].'/'.rawurlencode(self::localThumbnailFilename($youtubeId));
    }

    public static function ensureLocalThumbnail($module, $youtubeId, $remoteUrl = '', $force = false)
    {
        $youtubeId = trim((string)$youtubeId);
        if ($youtubeId === '') return '';
        $info = self::ensureThumbnailDirectory();
        $dest = $info['path'].DIRECTORY_SEPARATOR.self::localThumbnailFilename($youtubeId);
        if (!$force && is_file($dest) && filesize($dest) > 500) {
            return self::getLocalThumbnailUrl($youtubeId);
        }

        $sources = array();
        if ($remoteUrl) $sources[] = trim($remoteUrl);
        $sources[] = 'https://i.ytimg.com/vi/'.rawurlencode($youtubeId).'/maxresdefault.jpg';
        $sources[] = 'https://i.ytimg.com/vi/'.rawurlencode($youtubeId).'/hqdefault.jpg';
        $sources = array_values(array_unique(array_filter($sources)));

        foreach ($sources as $url) {
            $data = false;
            $httpCode = 0;
            $contentType = '';
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT => 25,
                    CURLOPT_USERAGENT => 'CMSMS YouTubeCatalog/1.0.22',
                    CURLOPT_HTTPHEADER => array('Accept: image/*'),
                ));
                $data = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);
            } else {
                $ctx = stream_context_create(array('http'=>array('timeout'=>25,'ignore_errors'=>true,'user_agent'=>'CMSMS YouTubeCatalog/1.0.20')));
                $data = @file_get_contents($url, false, $ctx);
                if (!empty($http_response_header) && preg_match('#HTTP/\\S+\\s+(\\d{3})#', $http_response_header[0], $m)) $httpCode = (int)$m[1];
                foreach ((array)$http_response_header as $h) if (stripos($h, 'Content-Type:') === 0) $contentType = trim(substr($h, 13));
            }
            if ($data === false || strlen($data) < 500) continue;
            if ($httpCode && ($httpCode < 200 || $httpCode >= 300)) continue;
            if ($contentType && stripos($contentType, 'image/') !== 0) continue;

            $tmp = $dest.'.tmp';
            if (@file_put_contents($tmp, $data) === false) continue;
            @chmod($tmp, 0644);
            if (!@rename($tmp, $dest)) {
                @unlink($tmp);
                continue;
            }
            return self::getLocalThumbnailUrl($youtubeId);
        }

        return (is_file($dest) && filesize($dest) > 500) ? self::getLocalThumbnailUrl($youtubeId) : '';
    }

    public static function formatDuration($iso)
    {
        if (!$iso) return '';
        try { $d = new DateInterval($iso); } catch (Exception $e) { return $iso; }
        $hours = ($d->d * 24) + $d->h;
        if ($hours > 0) return sprintf('%d:%02d:%02d', $hours, $d->i, $d->s);
        return sprintf('%d:%02d', $d->i, $d->s);
    }

    public static function getPlaylistMeta($module, $playlistId)
    {
        $json = self::apiGet($module, 'playlists', array('part' => 'snippet,contentDetails', 'id' => $playlistId));
        if (empty($json['items'][0])) throw new Exception('Playlist was not found or is not public.');
        return $json['items'][0];
    }

    public static function syncCatalog($module, $catalogId, $manual = true)
    {
        $db = self::db();
        self::ensureUtf8mb4Schema();
        $cat = $db->GetRow('SELECT * FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE catalog_id=?', array((int)$catalogId));
        if (!$cat || empty($cat['youtube_playlist_id'])) return array('added'=>0,'updated'=>0,'seen'=>0);

        $playlistId = $cat['youtube_playlist_id'];
        // Validate that the playlist still exists/is public, but do not copy
        // its title or description over the CMS-managed catalogue fields.
        // This preserves WYSIWYG catalogue descriptions and custom names.
        self::getPlaylistMeta($module, $playlistId);

        $items = array(); $token = null;
        do {
            $args = array('part'=>'snippet,contentDetails,status','playlistId'=>$playlistId,'maxResults'=>50);
            if ($token) $args['pageToken'] = $token;
            $json = self::apiGet($module, 'playlistItems', $args);
            foreach ((array)($json['items'] ?? array()) as $it) {
                $vid = $it['contentDetails']['videoId'] ?? ($it['snippet']['resourceId']['videoId'] ?? null);
                if (!$vid) continue;
                $items[$vid] = array('position'=>(int)($it['snippet']['position'] ?? 0));
            }
            $token = $json['nextPageToken'] ?? null;
        } while ($token);

        $details = self::fetchVideos($module, array_keys($items));
        $added = 0; $updated = 0;
        foreach ($items as $vid => $pi) {
            if (empty($details[$vid])) continue;
            $v = $details[$vid];
            $existing = $db->GetRow('SELECT video_id,source_type FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE youtube_id=?', array($vid));
            if ($existing) {
                $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_videos SET title=?,description=?,duration_iso=?,duration_text=?,view_count=?,thumbnail_url=?,published_at=?,channel_title=?,youtube_status=?,modified_date=? WHERE video_id=?',
                    array($v['title'],$v['description'],$v['duration_iso'],$v['duration_text'],$v['view_count'],$v['thumbnail_url'],$v['published_at'],$v['channel_title'],$v['youtube_status'],date('Y-m-d H:i:s'),$existing['video_id']));
                $videoId = (int)$existing['video_id']; $updated++;
            } else {
                $db->Execute('INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_videos (youtube_id,title,description,duration_iso,duration_text,view_count,thumbnail_url,published_at,channel_title,youtube_status,source_type,active,create_date,modified_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array($vid,$v['title'],$v['description'],$v['duration_iso'],$v['duration_text'],$v['view_count'],$v['thumbnail_url'],$v['published_at'],$v['channel_title'],$v['youtube_status'],'playlist',1,date('Y-m-d H:i:s'),date('Y-m-d H:i:s')));
                $videoId = (int)$db->Insert_ID(); $added++;
            }
            // Keep a local thumbnail copy for playlist-imported videos too.
            try {
                self::ensureLocalThumbnail($module, $vid, $v['thumbnail_url'], false);
            } catch (Exception $e) {
                audit('', $module->GetName(), 'Thumbnail download failed for '.$vid.': '.$e->getMessage());
            }
            $link = $db->GetOne('SELECT link_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE catalog_id=? AND video_id=?', array($catalogId,$videoId));
            if ($link) {
                $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos SET sort_order=?,seen_in_sync=1 WHERE link_id=?', array($pi['position'],$link));
            } else {
                $db->Execute('INSERT INTO '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos (catalog_id,video_id,sort_order,seen_in_sync) VALUES (?,?,?,1)', array($catalogId,$videoId,$pi['position']));
            }
            try {
                $module->indexVideo($videoId);
            } catch (Exception $e) {
                audit($videoId, $module->GetName(), 'Search indexing failed after playlist sync: '.$e->getMessage());
            }
        }
        // Do not delete videos: merely detach ones no longer present, preserving manually-linked content.
        $db->Execute('DELETE FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos WHERE catalog_id=? AND seen_in_sync=0', array($catalogId));
        $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_catalog_videos SET seen_in_sync=0 WHERE catalog_id=?', array($catalogId));
        $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs SET last_sync=?,last_error=NULL WHERE catalog_id=?', array(date('Y-m-d H:i:s'),$catalogId));
        return array('added'=>$added,'updated'=>$updated,'seen'=>count($items));
    }

    public static function syncAll($module, $manual = false)
    {
        $db = self::db();
        $ids = $db->GetCol('SELECT catalog_id FROM '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs WHERE active=1 AND youtube_playlist_id IS NOT NULL AND youtube_playlist_id<>\'\'');
        $result = array();
        foreach ((array)$ids as $id) {
            try { $result[$id] = self::syncCatalog($module, $id, $manual); }
            catch (Exception $e) {
                $db->Execute('UPDATE '.CMS_DB_PREFIX.'module_youtubecatalog_catalogs SET last_error=? WHERE catalog_id=?', array($e->getMessage(),$id));
                if ($manual) $result[$id] = array('error'=>$e->getMessage());
            }
        }
        return $result;
    }
}
