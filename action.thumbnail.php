<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;

$videoId = isset($params['video_id']) ? (int)$params['video_id'] : 0;
if ($videoId < 1) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$db = cmsms()->GetDb();
$row = $db->GetRow(
    'SELECT youtube_id, thumbnail_url FROM '.CMS_DB_PREFIX.'module_youtubecatalog_videos WHERE video_id=?',
    array($videoId)
);
if (!$row || empty($row['youtube_id'])) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$url = trim((string)$row['thumbnail_url']);
if ($url === '') {
    $url = 'https://i.ytimg.com/vi/'.rawurlencode($row['youtube_id']).'/hqdefault.jpg';
}

$data = false;
$contentType = 'image/jpeg';

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'YouTubeCatalog/1.0.10',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HEADER => false,
    ));
    $data = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($type && stripos($type, 'image/') === 0) $contentType = $type;
    if ($data === false || $status < 200 || $status >= 300) $data = false;
    curl_close($ch);
}

if ($data === false && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(array('http' => array(
        'timeout' => 15,
        'user_agent' => 'YouTubeCatalog/1.0.10',
        'follow_location' => 1,
    )));
    $data = @file_get_contents($url, false, $ctx);
}

// If the API-provided image fails, retry the standard YouTube thumbnail.
if ($data === false) {
    $fallback = 'https://i.ytimg.com/vi/'.rawurlencode($row['youtube_id']).'/hqdefault.jpg';
    if ($fallback !== $url && function_exists('curl_init')) {
        $ch = curl_init($fallback);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'YouTubeCatalog/1.0.10',
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $data = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if ($type && stripos($type, 'image/') === 0) $contentType = $type;
        if ($data === false || $status < 200 || $status >= 300) $data = false;
        curl_close($ch);
    }
}

if ($data === false) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

header('Content-Type: '.$contentType);
header('Cache-Control: public, max-age=21600');
header('Content-Length: '.strlen($data));
echo $data;
exit;
