<p>In the examples below, replace <code>VIDEO_ID</code> with the actual 11-character YouTube video ID.</p>
<h3>Featured video</h3>
<p>In <strong>Videos</strong>, tick <strong>Featured</strong> beside a video. Only one video is selected across all catalogues and pages of the list; ticking another replaces it. Untick the selected video to clear it. Inactive or missing videos cannot be newly featured. Deleting or deactivating the selected video clears its selection.</p>
<p>Show the selection with <code>{YouTubeCatalog featured=1 template="single"}</code>, or supply the name of your single-video Design Manager template. No selection renders the template's unavailable state. Choosing a video does not change its catalogue memberships.</p>
<h3>YouTubeCatalog</h3>
<p>Basic use: <code>{YouTubeCatalog}</code></p>
<p>Example: <code>{YouTubeCatalog catalog=2 limit=12 template='default.tpl' show_buttons=1 show_catalog_description=1 show_video_description=1 show_stats=1}</code></p>
<ul>
<li><code>catalog=ID</code> selects the initial catalogue filter.</li>
<li><code>limit=N</code> limits the number of videos (0 = all).</li>
<li><code>show_buttons=0|1</code> hides/shows the catalogue filter buttons.</li>
<li><code>show_catalog_description=0|1</code> hides/shows the selected catalogue description below the filters.</li>
<li><code>show_video_description=0|1</code> hides/shows individual video descriptions.</li>
<li><code>show_description=0|1</code> is a backwards-compatible shortcut affecting both description types unless the specific parameters above are supplied.</li>
<li><code>show_stats=0|1</code> hides/shows time and views.</li>
</ul>


<h3>CMSMS Search</h3>
<p>Active videos are indexed by the standard CMSMS Search module using their title, description, channel and catalogue names. The index is updated when videos are added, edited, deleted or refreshed by playlist sync. Existing videos can be reindexed from <strong>Settings &gt; Rebuild Search index</strong>.</p>

<h3>Design Manager templates</h3>
<p>On install/upgrade YouTubeCatalog creates editable database templates named <strong>default</strong> and <strong>catalog</strong>. These are available to CMSMS/Design Manager as module templates. Existing customised database templates are never overwritten by module upgrades. The files in <code>/modules/YouTubeCatalog/templates/</code> remain as factory fallbacks.</p>
<p>The frontend parameter can use either <code>template='default'</code> or the backwards-compatible <code>template='default.tpl'</code>.</p>


<h3>Design Manager templates</h3>
<p>CMSMS Design Manager contains two module template types: <code>YouTubeCatalog::default</code> and <code>YouTubeCatalog::catalog</code>. The installed editable templates are <strong>YouTubeCatalog Default</strong> and <strong>YouTubeCatalog Catalog</strong>. The files in the module templates folder are retained as factory/fallback copies.</p>

<h3>Frontend parameter: show_all_button</h3>
<p>Use <code>{YouTubeCatalog show_all_button=0}</code> to hide only the
<strong>All videos</strong> filter button while leaving catalogue buttons available.
The default is <code>1</code>.</p>

<h3>Backend permissions</h3>
<p>YouTubeCatalog provides separate CMSMS permissions for:</p>
<ul>
<li><strong>Manage YouTubeCatalog Videos</strong> — add, edit and delete videos.</li>
<li><strong>Manage YouTubeCatalog Catalogues</strong> — add, edit, delete and sync playlist catalogues.</li>
<li><strong>Manage YouTubeCatalog Settings</strong> — API/settings, API test, sync all and Search rebuild.</li>
</ul>
<p>The older <strong>Manage YouTube Catalog</strong> permission is retained as a legacy full-access permission so existing administrators are not locked out after upgrading.</p>

<h3>Backend video list</h3>
<p>The Videos tab can be filtered by catalogue using the <strong>View by catalogue</strong> dropdown. Choose <strong>Show all</strong> to remove the filter.</p>
<p>The list is paginated using CMSMS module links. The number of videos per page can be changed in Settings.</p>
<h3>Backend module name</h3>
<p>Settings includes a backend display-name option. This only changes the friendly admin name; the technical module name, module folder and frontend tag remain <code>YouTubeCatalog</code>.</p>

<h3>Single video tag</h3>
<p>Use the <code>video</code> parameter to render one YouTube video with the
<strong>YouTubeCatalog Single Video</strong> Design Manager template.</p>
<pre>{YouTubeCatalog video='VIDEO_ID'}</pre>
<p>A full YouTube URL is also accepted:</p>
<pre>{YouTubeCatalog video='https://www.youtube.com/watch?v=VIDEO_ID'}</pre>
<p>The <code>video</code> value accepts normal watch URLs, youtu.be links,
Shorts, Live, Embed URLs, or a bare 11-character YouTube video ID.</p>
<p>If the video already exists in YouTubeCatalog, the stored metadata and local
thumbnail are used. Otherwise the module can read its metadata from the
YouTube Data API without adding it to a catalogue.</p>
<p>To use a custom Design Manager template:</p>
<pre>{YouTubeCatalog video='VIDEO_ID' template='My Single Video'}</pre>

<h3>Video-only single video template</h3>
<p>To show only the video frame, without the title, description, catalogue,
views or card body, use:</p>
<pre>{YouTubeCatalog video='VIDEO_ID' template='videoonly'}</pre>
<p>A YouTube URL can also be used:</p>
<pre>{YouTubeCatalog video='https://www.youtube.com/watch?v=VIDEO_ID' template='videoonly'}</pre>
<p>The frame still includes the thumbnail, centred Play button, duration badge
and Full screen button.</p>
<p>The editable Design Manager template is named
<strong>YouTubeCatalog Video Only</strong>.</p>

<h3>Single Videos cache catalogue</h3>
<p>When the <code>video</code> parameter is used, YouTubeCatalog now checks its
own video database first. If the video is already stored, no YouTube Data API
metadata request is made for that page view.</p>
<p>If the video is new, YouTubeCatalog retrieves its metadata once, saves it,
downloads the thumbnail into <code>/uploads/YouTubeCatalog/</code>, and links
the video to the internal <strong>Single Videos</strong> catalogue.</p>
<p>The Single Videos catalogue is inactive and has its frontend filter disabled
by default, so this caching group does not change the normal public catalogue
buttons or All Videos rules.</p>
<p>The actual video file is not copied to the web host. Playback still streams
from YouTube only after the visitor presses Play.</p>
