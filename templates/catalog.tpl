{* Compact catalogue template: same data, simplified cards *}
<div class="container py-4 ytc-compact">
  <div class="row g-3">
  {foreach $ytc_videos as $video}
    <div class="col-sm-6 col-xl-3">
      <article class="card h-100 shadow-sm border-0">
        <a href="https://www.youtube.com/watch?v={$video.youtube_id|escape}" target="_blank" rel="noopener" class="text-decoration-none text-body">
          {if $video.local_thumbnail_url}<img src="{$video.local_thumbnail_url|escape}" class="card-img-top" alt="{$video.title|escape}" loading="lazy">{/if}
          <div class="card-body d-flex flex-column"><h3 class="h6 fw-bold">{$video.title|escape}</h3><div class="ytc-card-stats small text-muted mt-auto pt-3"><span class="ytc-card-time">{$video.duration_text|escape}</span><span class="ytc-card-views">{$video.view_count|number_format} views</span></div></div>
        </a>
      </article>
    </div>
  {/foreach}
  </div>
</div>
