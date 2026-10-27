{* YouTubeCatalog single video template.
   Usage:
   {YouTubeCatalog video='VIDEO_ID'}
   {YouTubeCatalog video='https://www.youtube.com/watch?v=VIDEO_ID'}
*}
<section class="ytc-single-video py-4" data-ytc-gallery data-ytc-initial-catalog="all">
  <div class="container">
    {if $ytc_video}
      <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8 ytc-video-item"
             data-ytc-catalog-ids="{$ytc_video.catalog_ids_csv|default:''|escape}"
             data-ytc-all-visible="1">
          <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
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

            <div class="card-body p-4 d-flex flex-column">
              {if !empty($ytc_video.catalog_name)}
                <span class="badge text-bg-warning mb-2 d-inline-block align-self-start">
                  {$ytc_video.catalog_name|escape}
                </span>
              {/if}

              <h2 class="h4 fw-bold mb-2">{$ytc_video.title|escape}</h2>

              {if $ytc_show_description == 1 && !empty($ytc_video.description)}
                <div class="text-muted ytc-description">
                  {$ytc_video.description|escape|nl2br}
                </div>
              {/if}

              {if $ytc_show_stats == 1}
                <div class="ytc-card-stats small text-secondary mt-auto pt-3">
                  {if !empty($ytc_video.duration_text)}
                    <span class="ytc-card-time">
                      <i class="fa-regular fa-clock" aria-hidden="true"></i>
                      {$ytc_video.duration_text|escape}
                    </span>
                  {/if}
                  {if isset($ytc_video.view_count)}
                    <span class="ytc-card-views">
                      <i class="fa-regular fa-eye" aria-hidden="true"></i>
                      {$ytc_video.view_count|number_format} views
                    </span>
                  {/if}
                </div>
              {/if}
            </div>
          </article>
        </div>
      </div>
    {else}
      <div class="alert alert-warning text-center" role="alert">
        {if $ytc_single_error}{$ytc_single_error|escape}{else}The video could not be loaded.{/if}
      </div>
    {/if}
  </div>
</section>

<link rel="stylesheet" href="{$ytc_module_url}/templates/youtubecatalog.css?v=1.0.54">
<script src="{$ytc_module_url}/templates/youtubecatalog.js?v=1.0.59" defer></script>
