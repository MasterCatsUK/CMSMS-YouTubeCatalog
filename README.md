# YouTubeCatalog for CMS Made Simple

Version **1.0.60**. Display YouTube videos in catalogues, import playlists,
feature a selected video and embed individual videos in CMSMS pages.

## Features

- Manually added videos and YouTube playlist imports.
- Catalogue filters, descriptions, durations and view counts.
- Featured-video, single-video and video-only layouts.
- Local thumbnail caching and click-to-play YouTube embeds.
- Editable Design Manager templates and CMSMS Search integration.
- Separate permissions for videos, catalogues and settings.

## Install and configure

1. Back up your CMSMS files and database before installing or upgrading.
2. Extract the install ZIP and copy `YouTubeCatalog` into `modules/`, or use
   the XML install package through the CMSMS module manager.
3. In **Extensions → Modules**, install or upgrade **YouTubeCatalog**.
   Upgrade an existing installation in place; do not uninstall first.
4. Open the module's **Settings**, enter your own YouTube Data API v3 key,
   and choose the frontend heading and introduction.
5. Add videos manually or create catalogues linked to YouTube playlists.
6. Add `{YouTubeCatalog}` to a CMSMS page or page template.

Requires CMSMS **2.2.0 or later**. The bundled catalogue layout uses
**Bootstrap 5.3** and Font Awesome icons; include these in your site theme
or adapt the Design Manager templates to your theme.

## Examples

Show the full catalogue:

```smarty
{YouTubeCatalog}
```

Show the featured video:

```smarty
{YouTubeCatalog featured=1 template='single'}
```

Show an individual video, or only its player. Replace `VIDEO_ID` with the
video's actual 11-character YouTube ID:

```smarty
{YouTubeCatalog video='VIDEO_ID'}
{YouTubeCatalog video='VIDEO_ID' template='videoonly'}
```

See the module's built-in help or [help.inc](help.inc) for catalogue filters,
template selection, caching, featured videos and permissions.

## Upgrades and customisation

This public distribution uses generic introduction text, template comments and
video placeholders. No site database, video catalogue, thumbnails or API keys
are included. Existing settings and customised Design Manager templates are
retained during upgrades; review the frontend introduction in Settings and
your templates when adapting an existing installation.

## Validation

All PHP files passed local syntax checks. Package contents were checked against
the source, and the publishing copy was scanned for site-specific branding,
credentials and personal filesystem paths. Installation and playback in a live
CMSMS site remain to be verified.

## External services


- **Google YouTube Data API** (`https://www.googleapis.com/youtube/v3/`): retrieve video title, description, duration, statistics, playlist contents and thumbnails. Sends the configured API key, YouTube video/playlist IDs, and API query parameters to Google. Terms: https://developers.google.com/youtube/terms/api-services-terms-of-service ; Privacy: https://policies.google.com/privacy
- **YouTube thumbnail CDN** (`https://i.ytimg.com/` and thumbnails returned by YouTube's API): download video thumbnail images into the hosting `/uploads/YouTubeCatalog/` directory. Requests send the YouTube video ID in the URL and the server's ordinary HTTP request data. Terms: https://www.youtube.com/t/terms ; Privacy: https://policies.google.com/privacy
- **YouTube embedded player** (`https://www.youtube-nocookie.com/`): streams video only after a visitor clicks Play. The video ID and the visitor's browser request details are sent to YouTube at playback time. Terms: https://www.youtube.com/t/terms ; Privacy: https://policies.google.com/privacy

The admin thumbnail refresh action may also request YouTube-hosted thumbnail URLs. No YouTube video files are stored on your server.

## Licence and author

MIT License, copyright (c) 2026 Andrew Smith. See [LICENSE](LICENSE).
Project support: [GitHub issues](https://github.com/MasterCatsUK/CMSMS-YouTubeCatalog/issues).
