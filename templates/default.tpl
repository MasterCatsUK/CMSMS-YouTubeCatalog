{* YouTubeCatalog Bootstrap 5.3 video catalogue template *}
<section class="ytc-video-gallery py-5 bg-light" data-ytc-gallery data-ytc-initial-catalog="{if $ytc_catalog_id}{$ytc_catalog_id|escape}{else}all{/if}" data-ytc-video-count="{$ytc_videos|@count}">
  <div class="container">
    <div class="text-center mb-5">
      <div class="ytc-section-line mx-auto mb-3"></div>
      <h2 class="fw-bold">{$ytc_heading|escape}</h2>
      {if $ytc_intro}<div class="text-muted mb-0 ytc-frontend-intro">{$ytc_intro}</div>{/if}
    </div>

    {if $ytc_show_buttons == 1 && count($ytc_catalogs) > 1}
      <nav class="d-flex flex-wrap justify-content-center gap-2 mb-3 ytc-catalog-filters" aria-label="Video catalogues" data-ytc-filters>
        {if !isset($ytc_show_all_button) || $ytc_show_all_button == 1}
          <button type="button" class="btn {if !$ytc_catalog_id}btn-ytc is-active{else}btn-outline-secondary{/if} ytc-catalog-filter" data-ytc-catalog="all" aria-pressed="{if !$ytc_catalog_id}true{else}false{/if}">All videos</button>
        {/if}
        {foreach $ytc_catalogs as $catalog}
          <button type="button" class="btn {if $ytc_catalog_id == $catalog.catalog_id}btn-ytc is-active{else}btn-outline-secondary{/if} ytc-catalog-filter" data-ytc-catalog="{$catalog.catalog_id|escape}" aria-pressed="{if $ytc_catalog_id == $catalog.catalog_id}true{else}false{/if}">{$catalog.name|escape}</button>
        {/foreach}
      </nav>
    {/if}

    {if $ytc_show_catalog_description == 1}
      <div class="ytc-catalog-description-wrap mb-4" data-ytc-description-wrap>
        <div class="ytc-catalog-description" data-ytc-description-output aria-live="polite"></div>
        <div class="d-none" aria-hidden="true" data-ytc-description-sources>
          <div data-ytc-description-source="all">{$ytc_all_videos_description}</div>
          {foreach $ytc_all_catalogs as $catalog}
            <div data-ytc-description-source="{$catalog.catalog_id|escape}">{$catalog.description}</div>
          {/foreach}
        </div>
      </div>
    {/if}

    <div class="row g-4">
      {foreach $ytc_videos as $video}
        <div class="col-md-6 col-lg-4 ytc-video-item" data-ytc-catalog-ids="{$video.catalog_ids_csv|escape}" data-ytc-all-visible="{$video.all_visible|default:1}">
          <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="ytc-video-player"
                 data-video-id="{$video.youtube_id|escape}"
                 data-video-title="{$video.title|escape}"
                 data-thumbnail-url="{$video.local_thumbnail_url|escape:'html'}">
              {if $video.local_thumbnail_url}
              <img src="{$video.local_thumbnail_url|escape:'html'}"
                   class="ytc-video-thumbnail"
                   alt="Thumbnail for {$video.title|escape:'html'}"
                   loading="lazy">
              {/if}
              <span class="ytc-duration badge text-bg-dark" aria-label="Video length {$video.duration_text|escape}">{$video.duration_text|escape}</span>
              <button type="button" class="ytc-fullscreen-trigger" aria-label="Show {$video.title|escape} full screen" title="Full screen">
                <span aria-hidden="true">⛶</span> <span class="ytc-fullscreen-label">Full</span>
              </button>
              <button type="button" class="ytc-video-play-button" aria-label="Play {$video.title|escape}">
                <span class="ytc-video-play-triangle" aria-hidden="true"></span>
              </button>
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="badge text-bg-warning mb-2 d-inline-block align-self-start">{$video.catalog_name|escape}</span>
              <h3 class="h5 fw-bold mb-2">{$video.title|escape}</h3>
              {if $ytc_show_description == 1 && $video.description}<p class="text-muted ytc-description">{$video.description|escape|nl2br}</p>{/if}
              {if $ytc_show_stats == 1}
                <div class="ytc-card-stats small text-secondary mt-auto pt-3">
                  {if $video.duration_text}<span class="ytc-card-time"><i class="fa-regular fa-clock" aria-hidden="true"></i> {$video.duration_text|escape}</span>{/if}
                  <span class="ytc-card-views"><i class="fa-regular fa-eye" aria-hidden="true"></i> {$video.view_count|number_format} views</span>
                </div>
              {/if}
            </div>
          </article>
        </div>
      {foreachelse}
        <div class="col-12"><div class="alert alert-light border text-center">No videos are available yet.</div></div>
      {/foreach}
    </div>
  </div>
</section>

<link rel="stylesheet" href="{$ytc_module_url}/templates/youtubecatalog.css?v=1.0.54">
<script src="{$ytc_module_url}/templates/youtubecatalog.js?v=1.0.59" defer></script>
