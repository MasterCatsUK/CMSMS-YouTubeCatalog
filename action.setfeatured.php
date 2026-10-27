<?php
/** SPDX-License-Identifier: MIT. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;
if (!$this->canManageVideos()) return;

// CMSMS validates the admin form's __c token before dispatching this action.
// Only POST may change the selection; preserve the current list filter/page.
$args = array('tab'=>'videos', 'catalog_filter'=>max(0,(int)($params['catalog_filter'] ?? 0)), 'pagenum'=>max(1,(int)($params['pagenum'] ?? 1)));
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $this->SetError('Use the Featured checkbox in the video list.');
    $this->Redirect($id,'defaultadmin',$returnid,$args);
    return;
}

$videoId = (int)($params['video_id'] ?? 0);
try {
    if ($videoId < 1) throw new InvalidArgumentException('Choose a valid video.');
    if (!empty($params['make_featured'])) {
        $this->setFeaturedVideoId($videoId);
        $this->SetMessage('Featured video updated.');
    } else {
        // An old unchecked form must not clear a different selected video.
        $this->clearFeaturedVideoIfSelected($videoId);
        $this->SetMessage('Featured video selection updated.');
    }
} catch (InvalidArgumentException $e) {
    $this->SetError($e->getMessage());
}
$this->Redirect($id,'defaultadmin',$returnid,$args);
