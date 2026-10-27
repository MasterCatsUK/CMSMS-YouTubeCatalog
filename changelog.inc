<h3>1.0.60</h3>
<ul>
<li>General-purpose public distribution with neutral introduction text and frontend template comments.</li>
<li>Replaced site video examples with placeholders and added generic installation documentation.</li>
<li>Existing saved settings and customised Design Manager templates remain unchanged.</li>
</ul>
<h3>1.0.59</h3>
<ul><li>Add an exclusive Featured checkbox to the video list, preserving filters and pagination.</li><li>Add featured=1 for the single-video Design Manager renderer; validate active videos and clear selection on deletion/deactivation.</li><li>Keep the 1.0.58 keyboard-focus improvements.</li></ul>
<h3>1.0.58</h3>
<ul><li>Move keyboard focus into the titled YouTube iframe when Play replaces the thumbnail controls.</li><li>Refresh bundled script URLs; preserve customised Design Manager templates and existing data.</li></ul>
<h3>1.0.57</h3>
<ul><li>Set module author to Andrew Smith and contact address to andrew@backdrop.org.uk.</li><li>Released under the MIT License and added docs/LICENSE.</li></ul>
<h3>1.0.56</h3>
<ul><li>Improved CMSMS ModuleChecker compatibility: explicit backend permission checks and escaped admin messages.</li><li>Replaced dynamic ALTER TABLE variable names with hardcoded module table constants; the identifiers are not user-controlled.</li><li>Removed raw GET/POST access from the save-video action and deprecated access guards.</li><li>Added external-service documentation, scanner notes, uninstall warning and exception logging.</li></ul>
<h3>1.0.55</h3>
<ul>
<li>ModuleChecker-friendly SQL clean-up: removed unnecessary MySQL identifier backticks in module-owned SQL statements; no shell commands are used.</li>
<li>Repackaged the module without macOS Finder metadata/AppleDouble files.</li>
<li>No changes to single-video tags, Design Manager templates, frontend layouts or existing module features.</li>
</ul>
<h3>1.0.54</h3>
<ul>
<li>Single-video tags now use a database-first cache instead of requesting YouTube metadata on every page view.</li>
<li>Added an internal <strong>Single Videos</strong> catalogue for videos first discovered through the <code>video=</code> tag.</li>
<li>New single videos are stored in YouTubeCatalog and linked to Single Videos after the first successful YouTube API lookup.</li>
<li>Existing videos used by a single-video tag are linked to Single Videos without re-requesting their metadata.</li>
<li>Single Videos is inactive and has its frontend filter hidden by default so it does not affect public All Videos/catalogue filtering.</li>
<li>Local thumbnails remain cache-first in <code>/uploads/YouTubeCatalog/</code>; YouTube is contacted for the thumbnail only when the local image is missing.</li>
</ul>
<h3>1.0.53</h3>
<ul>
<li>Added a video-only single-video frontend template.</li>
<li>Added the <strong>YouTubeCatalog Video Only</strong> Design Manager template and <code>YouTubeCatalog::videoonly</code> template type.</li>
<li>Added <code>templates/videoonly.tpl</code> as the file/factory fallback.</li>
<li>The video-only template keeps the thumbnail, centred Play button, duration and Full screen control, but removes the title, description, catalogue badge, views and card body.</li>
</ul>
<h3>1.0.52</h3>
<ul>
<li>Added single-video frontend mode with the <code>video</code> parameter.</li>
<li>The single-video parameter accepts a YouTube URL or 11-character video ID.</li>
<li>Added the <strong>YouTubeCatalog Single Video</strong> Design Manager template type/template.</li>
<li>Added <code>templates/single.tpl</code> as the factory/file fallback template.</li>
<li>Single-video mode uses stored module metadata when available and can fetch unsaved video metadata from the YouTube Data API without adding a database record.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>Reworked the Settings action row into a tidy responsive group.</li>
<li>Save settings, Test YouTube API, Sync all playlists now and Rebuild Search index now have consistent sizing and spacing.</li>
<li>Changed Save settings and Videos filter Apply to plain submit inputs to prevent admin-theme icon/pseudo-element styling from distorting them.</li>
<li>No text/background colours are forced; the active CMSMS admin theme still controls colours.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>Fixed the backend Videos filter Apply button and Settings Save settings button sizing/alignment.</li>
<li>Removed the CMSMS <code>pagebutton</code> class from those two controls because some admin themes apply oversized icon styling to it.</li>
<li>Buttons still inherit their text and background colours from the active CMSMS admin theme.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>Added a backend Videos-list catalogue filter with a Show all option.</li>
<li>Added CMSMS module-link pagination with previous/next, numbered pages and ellipses.</li>
<li>Added a configurable backend videos-per-page setting (default 20).</li>
<li>Added a configurable backend friendly/module display name while keeping the technical name <code>YouTubeCatalog</code>.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>Removed hard-coded backend text, background, button, border and focus colours.</li>
<li>YouTubeCatalog admin screens now inherit their colours from the active CMSMS admin theme, including dark themes.</li>
<li>Kept only structural layout, spacing, sizing and responsive styles.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>Added separate CMSMS backend permissions for Videos, Catalogues and Settings.</li>
<li>Admin navigation now only shows sections the logged-in user is permitted to access.</li>
<li>Action handlers enforce their matching permission, not just the navigation UI.</li>
<li>The legacy <code>Manage YouTube Catalog</code> permission remains an umbrella/full-access permission for backwards compatibility.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>Added <code>show_all_button=0|1</code> frontend parameter.</li>
<li>Existing Design Manager templates are supported without being overwritten: the module scopes the All Videos button visibility to the current module instance.</li>
</ul>
<h3>1.0.51</h3>
<ul>
<li>All Videos now strictly excludes any video that belongs to a catalogue with “Show catalogue filter button on frontend” turned off.</li>
<li>A video belonging to both a visible and a hidden catalogue is also excluded from All Videos.</li>
<li>Hidden catalogues can still be displayed directly with <code>catalog=ID</code>.</li>
</ul>
1.0.44
- Search indexing now strips 4-byte emoji/non-BMP characters before passing text to CMSMS Search, preventing older utf8/utf8mb3 Search tables from blocking video saves.
- Full Unicode/emoji remains unchanged in YouTubeCatalog titles and descriptions.

<h3>1.0.43</h3>
<ul>
<li>Catalogues with the frontend button disabled are now also excluded from the All videos view.</li>
<li>Hidden catalogues can still be targeted directly with <code>catalog=ID</code>.</li>
<li>A video assigned to both hidden and visible catalogues remains in All videos.</li>
</ul>
<h3>1.0.42</h3>
<ul>
<li>Catalogue filter-button visibility is now completely separate from the video result set.</li>
<li>Videos from catalogues with their frontend button hidden still appear in All videos.</li>
<li>The All videos filter now explicitly unhides every rendered video card, including cards produced by customised Design Manager templates.</li>
<li>Added <code>ytc_all_catalogs</code> and <code>ytc_all_videos</code> template variables for custom templates.</li>
</ul>
1.0.41 - Added a per-catalogue option to show or hide its frontend filter button while keeping its videos available.
1.0.40 - Fixed Design Manager frontend rendering using the native cms_template: resource and explicit variable scope transfer.
1.0.37 - Added CMSMS Search indexing/results integration and Design Manager editable frontend templates.
<h3>Version 1.0.36</h3>
<ul><li>Added a WYSIWYG Frontend introduction in Settings.</li><li>Added a WYSIWYG All videos description in Settings.</li><li>The All videos description is shown when the All videos filter is active and switches correctly with catalogue descriptions.</li></ul>
<h3>1.0.35</h3><ul><li>Redesigned Add/Edit Video admin form for a cleaner responsive layout.</li><li>Centred frontend video cards when rows are not full.</li></ul>
<h3>1.0.34</h3>
<ul><li>Fixed playlist catalogue descriptions being overwritten during sync.</li><li>Catalogue names and WYSIWYG descriptions are now treated as CMS-managed content and are preserved when a playlist sync runs.</li><li>Clearing a catalogue description now remains cleared even when a Playlist ID is present.</li></ul>
<h3>Version 1.0.33</h3>
<ul><li>Fixed frontend catalogue descriptions so each catalogue displays its own WYSIWYG description when selected.</li><li>Changed catalogue description switching to a single live output area for more reliable filtering.</li></ul>
<h3>Version 1.0.32</h3>
<ul>
<li>Registered <code>show_catalog_description</code> and <code>show_video_description</code> as valid CMSMS frontend parameters.</li>
<li>Changed all 0/1 display parameters to <code>CLEAN_INT</code> so CMSMS preserves boolean values reliably.</li>
<li>Made catalogue-description switching use explicit display state, so each catalogue shows its own description when selected.</li>
</ul>
<h3>Version 1.0.31</h3>
<ul>
<li>Fixed boolean frontend parameters when CMSMS passes numeric 0/1 values.</li>
<li><code>show_buttons=0</code> now reliably hides catalogue filter buttons.</li>
<li><code>show_description</code> now controls the catalogue description below the filter buttons as requested.</li>
<li>Added optional <code>show_catalog_description</code> and <code>show_video_description</code> parameters for independent control.</li>
</ul>
<h3>Version 1.0.30</h3>
<ul><li>Added WYSIWYG-enabled catalogue descriptions and displays the active catalogue description below the frontend filters.</li><li>Added show_buttons=0/1 frontend parameter.</li><li>Cleaned up the Add/Edit Video admin interface.</li></ul>
<h3>Version 1.0.29</h3>
<ul><li>Changed the backend Catalogues list columns to: ID, Catalogue, Playlist ID, Videos, Last sync, Actions.</li></ul>
1.0.27 - Fix initial catalog=ID selection for numeric catalogue IDs by matching filter button data values directly instead of using CSS.escape selectors.
1.0.26 - Frontend catalogue buttons now appear only for catalogues containing loaded active videos; catalog= selects the correct available button and falls back to All when empty.
1.0.25 - Frontend catalog parameter now selects an initial client-side filter instead of removing videos server-side.
1.0.23 - Frontend catalogue/video association repair and filter robustness.
1.0.22
- Fixed remaining MySQL emoji/4-byte UTF-8 save errors.
- Forces the YouTubeCatalog DB session to utf8mb4 before metadata writes.
- Explicitly repairs title, description, channel and catalogue text columns to utf8mb4.
- Runtime repair means the fix is applied even if a previous CMSMS module upgrade hook was skipped.

1.0.21 - Converted YouTubeCatalog database tables to utf8mb4 so YouTube titles/descriptions containing emoji and other 4-byte Unicode characters save correctly.
1.0.20 - Fixed and hardened manual Add Video handling; supports more YouTube URL formats and returns useful errors to the form.
1.0.19 - Fixed frontend catalogue filters: catalogue pills are now real buttons, filter video cards instantly in the browser, update active styling/ARIA state, and stop a playing video if it becomes hidden.
1.0.18 - Playback rebuilt to eliminate overlapping controls: the YouTube iframe does not exist until the startup Play button is clicked, the custom Play button is then removed permanently, native YouTube controls handle playback, and frontend CSS/JS URLs are cache-busted so browsers cannot keep an older player script.
1.0.17 - The large centre Play button is now startup-only. Once clicked, it is removed completely and no custom play/pause button is created, preventing overlap with YouTube's own play/pause overlay.
1.0.16 - Moved the custom in-player play/pause control to the bottom-left so it never overlaps YouTube's temporary centre play/pause animation.
1.0.15 - Delayed the custom centre play/pause control while YouTube's own temporary overlay is visible, preventing doubled overlapping buttons.
1.0.14 - Added an always-visible Full button at the top-right of each frontend video player with enter/exit fullscreen support.
1.0.13 - Frontend card stats pinned to bottom: duration left, views right.
1.0.12 - Frontend thumbnail layout fix: removed Bootstrap ratio child conflicts and added explicit 16:9 thumbnail layering.
Version 1.0.10
- Frontend thumbnails are now served through YouTubeCatalog on the local site origin.
- Added thumbnail action with caching and YouTube fallback.
- Frontend player restores the proxied thumbnail after stopping playback.

<h3>1.0.9</h3>
<ul><li>Fixed Smarty syntax error caused by inline JavaScript in the thumbnail onerror attribute.</li><li>Moved thumbnail fallback handling into youtubecatalog.js.</li></ul>
Version 1.0.6
- Fixed CMSMS admin post-save redirects so the admin theme remains loaded after editing/saving playlists, videos and settings.
- Uses RedirectToAdminTab and CMSMS message/error handling for admin actions.
- Keeps the correct tab selected after each action.

Version 1.0.4
<ul><li>Show YouTube thumbnails in the backend video list.</li><li>Show the current thumbnail when editing a video.</li><li>Use YouTube thumbnail metadata on the frontend with a safe hqdefault fallback.</li><li>Restored cURL-first YouTube API transport and detailed API errors.</li></ul>

Version 1.0.3
- Fixed frontend Smarty Security error by rendering .tpl files through CMS Made Simple's module template loader instead of an absolute filesystem path.
- Keeps default.tpl/catalog.tpl in the module templates folder while remaining compatible with Smarty security.

<h3>1.0.0</h3><ul><li>Manual YouTube videos</li><li>Playlist-backed catalogues</li><li>Automatic interval syncing</li><li>Title, description, duration and view count metadata</li><li>Bootstrap 5.3 frontend templates</li></ul>

1.0.5
- Restored Test YouTube API control and action.
- Fixed Add video admin link regression.
- Restored/promoted Add YouTube playlist / catalogue control.
- Kept YouTube thumbnail support from 1.0.4.

1.0.7
- Replaced CMSMS admin-theme tab dependency with self-contained YouTubeCatalog admin navigation and styling.
- Fixed raw/unstyled backend output on CMSMS installations whose admin theme does not style module tab helpers.
- Added empty-state messages and safer text-based Edit/Delete actions.

1.0.8
- Fixed blank/fatal page when saving a manually added video.
- Rebuilt manual video saving to use the current youtube_id/duration_iso/duration_text/source_type schema.
- Fixed manual catalogue assignments and automatically assigns uncategorised manual videos to Manual Videos.
- Added server-side display_thumbnail fallback and browser image fallback for frontend thumbnails.
- Hardened Add/Edit Video form for empty/new records.

1.0.11
- YouTube thumbnails are downloaded to /uploads/YouTubeCatalog.
- Installer and upgrader create/check the thumbnail folder automatically.
- Existing thumbnails are migrated to local storage during upgrade.
- Frontend and backend use the local thumbnail copies.

1.0.24 - Rebuilt frontend as video-first query; catalogue links can no longer hide videos; multi-catalogue filtering fixed.

1.0.38
- Fixed Design Manager integration to use CMSMS 2.x CmsLayoutTemplateType/CmsLayoutTemplate APIs.
- Adds YouTubeCatalog::default and YouTubeCatalog::catalog template types.
- Installs editable "YouTubeCatalog Default" and "YouTubeCatalog Catalog" templates.
- Frontend now renders the Design Manager template first with file .tpl fallback.


1.0.39
- Fixed Design Manager rendering so templates are rendered from their saved CMSMS content instead of the broken cms_template:id=... resource.
- template='TestVideo' now resolves an actual YouTubeCatalog Design Manager template named TestVideo before any file fallback.
- Kept default/catalog aliases and module .tpl fallbacks for compatibility.
