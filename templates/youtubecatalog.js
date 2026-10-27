(()=>{
  const cards=document.querySelectorAll('[data-ytc-gallery] .ytc-video-player');

  let activeCard=null;

  // Catalogue filters. The initial catalog= value must work even when
  // the filter button bar is hidden (for example when only one catalogue
  // currently contains videos).
  document.querySelectorAll('[data-ytc-gallery]').forEach(gallery=>{
    const filterBar=gallery.querySelector('[data-ytc-filters]');
    const buttons=filterBar ? filterBar.querySelectorAll('[data-ytc-catalog]') : [];
    const items=gallery.querySelectorAll('.ytc-video-item');

    function itemMatches(item,wanted){
      // All videos excludes any video that belongs to at least one catalogue
      // whose frontend filter is disabled. Hidden catalogues can still be
      // opened directly with catalog=ID.
      if(wanted==='all') return String(item.dataset.ytcAllVisible||'1')!=='0';
      const ids=(item.dataset.ytcCatalogIds||'').split(',').map(v=>v.trim()).filter(Boolean);
      return ids.includes(String(wanted));
    }

    function applyFilterValue(wanted,clickedButton){
      wanted=String(wanted||'all');

      // If the requested catalogue has no videos, safely fall back to All.
      if(wanted!=='all' && !Array.from(items).some(item=>itemMatches(item,wanted))){
        wanted='all';
      }

      if(buttons.length){
        buttons.forEach(btn=>{
          const active=clickedButton ? btn===clickedButton : String(btn.dataset.ytcCatalog||'')===wanted;
          btn.classList.toggle('is-active',active);
          btn.classList.toggle('btn-ytc',active);
          btn.classList.toggle('btn-outline-secondary',!active);
          btn.setAttribute('aria-pressed',active?'true':'false');
        });

        // If no catalogue button matched (e.g. hidden/suppressed catalogue),
        // keep the All button visually active only when we're really on All.
        if(wanted==='all'){
          const allBtn=Array.from(buttons).find(btn=>btn.dataset.ytcCatalog==='all');
          if(allBtn && !allBtn.classList.contains('is-active')){
            allBtn.classList.add('is-active','btn-ytc');
            allBtn.classList.remove('btn-outline-secondary');
            allBtn.setAttribute('aria-pressed','true');
          }
        }
      }

      items.forEach(item=>{
        const show=itemMatches(item,wanted);
        if(!show){
          const player=item.querySelector('.ytc-video-player.ytc-is-playing');
          if(player) restoreThumbnail(player);
        }
        item.hidden=!show;
      });

      // Keep the catalogue description in sync with the active filter.
      // Use one visible output area and copy the selected catalogue's HTML
      // from its hidden source. This is more reliable than toggling multiple
      // WYSIWYG description blocks directly.
      const descriptionOutput=gallery.querySelector('[data-ytc-description-output]');
      const descriptionWrap=gallery.querySelector('[data-ytc-description-wrap]');
      if(descriptionOutput){
        const sources=gallery.querySelectorAll('[data-ytc-description-source]');
        const source=Array.from(sources).find(el=>String(el.dataset.ytcDescriptionSource||'')===wanted);
        const html=source ? source.innerHTML.trim() : '';
        descriptionOutput.innerHTML=html;
        if(descriptionWrap) descriptionWrap.hidden=!html;
      }
    }

    if(filterBar){
      filterBar.addEventListener('click',e=>{
        const button=e.target.closest('[data-ytc-catalog]');
        if(!button || !filterBar.contains(button))return;
        applyFilterValue(button.dataset.ytcCatalog||'all',button);
      });
    }

    // Apply catalog= immediately from the gallery data. This does NOT depend
    // on a visible button existing.
    const initial=String(gallery.dataset.ytcInitialCatalog||'all');
    applyFilterValue(initial,null);
  });

  function esc(value){
    return String(value||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function fullscreenElement(){
    return document.fullscreenElement||document.webkitFullscreenElement||null;
  }

  async function toggleFullscreen(card){
    try{
      if(fullscreenElement()){
        if(document.exitFullscreen) await document.exitFullscreen();
        else if(document.webkitExitFullscreen) document.webkitExitFullscreen();
      }else{
        if(card.requestFullscreen) await card.requestFullscreen();
        else if(card.webkitRequestFullscreen) card.webkitRequestFullscreen();
      }
    }catch(e){}
  }

  function fullscreenButton(title){
    return `<button type="button" class="ytc-fullscreen-trigger" aria-label="Show ${esc(title)} full screen" title="Full screen"><span aria-hidden="true">⛶</span> <span class="ytc-fullscreen-label">Full</span></button>`;
  }

  function updateFullscreenLabels(){
    const current=fullscreenElement();
    cards.forEach(card=>{
      const btn=card.querySelector('.ytc-fullscreen-trigger');
      if(!btn)return;
      const isFull=current===card;
      const title=card.dataset.videoTitle||'video';
      const label=btn.querySelector('.ytc-fullscreen-label');
      if(label)label.textContent=isFull?'Exit':'Full';
      btn.title=isFull?'Exit full screen':'Full screen';
      btn.setAttribute('aria-label',isFull?`Exit full screen for ${title}`:`Show ${title} full screen`);
    });
  }

  document.addEventListener('fullscreenchange',updateFullscreenLabels);
  document.addEventListener('webkitfullscreenchange',updateFullscreenLabels);

  function restoreThumbnail(card){
    const title=card.dataset.videoTitle||'Video';
    const image=card.dataset.thumbnailUrl||'';
    const duration=card.dataset.duration||'';

    card.innerHTML=
      `${image?`<img src="${esc(image)}" class="ytc-video-thumbnail" alt="Thumbnail for ${esc(title)}" loading="lazy">`:''}`+
      `${duration?`<span class="ytc-duration badge text-bg-dark" aria-label="Video length ${esc(duration)}">${esc(duration)}</span>`:''}`+
      fullscreenButton(title)+
      `<button type="button" class="ytc-video-play-button" aria-label="Play ${esc(title)}"><span class="ytc-video-play-triangle" aria-hidden="true"></span></button>`;

    card.classList.remove('ytc-is-playing');
    if(activeCard===card)activeCard=null;
    updateFullscreenLabels();
  }

  function startVideo(card){
    if(card.classList.contains('ytc-is-playing'))return;

    if(activeCard&&activeCard!==card)restoreThumbnail(activeCard);

    const id=card.dataset.videoId;
    const title=card.dataset.videoTitle||'Video';
    if(!id)return;

    // Important: there is NO YouTube iframe before this click.
    // The custom startup Play button is removed before the iframe is inserted,
    // so it cannot overlap YouTube's own player controls/overlay.
    card.classList.add('ytc-is-playing');
    card.innerHTML=
      `<iframe class="ytc-youtube-frame" `+
      `src="https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?autoplay=1&controls=1&rel=0&playsinline=1&modestbranding=1" `+
      `title="${esc(title)}" `+
      `allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" `+
      `allowfullscreen></iframe>`+
      fullscreenButton(title);

    activeCard=card;
    updateFullscreenLabels();

    // Keep keyboard users at the video when the focused Play button is replaced.
    // The iframe title gives the new focus target a video-specific accessible name.
    const frame=card.querySelector('.ytc-youtube-frame');
    if(frame) frame.focus({preventScroll:true});
  }

  cards.forEach(card=>{
    const d=card.querySelector('.ytc-duration');
    if(d)card.dataset.duration=d.textContent.trim();

    card.addEventListener('click',e=>{
      const fullscreen=e.target.closest('.ytc-fullscreen-trigger');
      if(fullscreen){
        e.preventDefault();
        e.stopPropagation();
        toggleFullscreen(card);
        return;
      }

      const play=e.target.closest('.ytc-video-play-button');
      if(play){
        e.preventDefault();
        e.stopPropagation();
        startVideo(card);
      }
    });
  });
})();
