{* YouTubeCatalog video-only single video template.
   Shows only the 16:9 video frame with:
   - local YouTube thumbnail
   - centred Play button
   - duration badge
   - Full screen button

   Usage:
   {YouTubeCatalog video='VIDEO_ID' template='videoonly'}
   {YouTubeCatalog video='https://www.youtube.com/watch?v=VIDEO_ID' template='videoonly'}
*}

<div class="ytc-video-only" data-ytc-gallery data-ytc-initial-catalog="all">
  {if $ytc_video}
    <div class="ytc-video-player"
         data-video-id="{$ytc_video.youtube_id|escape}"
         data-video-title="{$ytc_video.title|escape}"
         data-thumbnail-url="{$ytc_video.local_thumbnail_url|default:''|escape:'html'}">

      {if !empty($ytc_video.local_thumbnail_url)}
        <img src="{$ytc_video.local_thumbnail_url|escape:'html'}"
             class="ytc-video-thumbnail"
             alt="Thumbnail for {$ytc_video.title|escape:'html'}"
             loading="lazy">
      {/if}

      {if !empty($ytc_video.duration_text)}
        <span class="ytc-duration badge text-bg-dark"
              aria-label="Video length {$ytc_video.duration_text|escape}">
          {$ytc_video.duration_text|escape}
        </span>
      {/if}

      <button type="button"
              class="ytc-fullscreen-trigger"
              aria-label="Show {$ytc_video.title|escape} full screen"
              title="Full screen">
        <span aria-hidden="true">⛶</span>
        <span class="ytc-fullscreen-label">Full</span>
      </button>

      <button type="button"
              class="ytc-video-play-button"
              aria-label="Play {$ytc_video.title|escape}">
        <span class="ytc-video-play-triangle" aria-hidden="true"></span>
      </button>
    </div>
  {else}
    <div class="alert alert-warning text-center" role="alert">
      {if $ytc_single_error}{$ytc_single_error|escape}{else}The video could not be loaded.{/if}
    </div>
  {/if}
</div>

<link rel="stylesheet" href="{$ytc_module_url}/templates/youtubecatalog.css?v=1.0.54">
<script src="{$ytc_module_url}/templates/youtubecatalog.js?v=1.0.59" defer></script>
