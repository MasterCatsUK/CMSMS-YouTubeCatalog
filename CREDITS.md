# Credits — YouTubeCatalog

This page records the software, integrations, documentation and creative
resources evidenced in this project's source and development records. A listed
integration is not necessarily bundled in the repository. Existing copyright
and licence notices remain in force; this acknowledgement does not relicense
third-party work or imply endorsement.

## Platform and shared components

| Project / contributors | Contribution and source | How it is used |
|---|---|---|
| CMS Made Simple development team and contributors | [CMSMS source](https://github.com/cmsmadesimple/cmsmadesimple), [developer handbook](https://github.com/cmsmadesimple/cmsms-developer-handbook), [API documentation](https://apidoc.cmsmadesimple.org/) | Host module framework, permissions, content/module APIs and administration. CMSMS is installed separately and retains its own [licence](https://github.com/cmsmadesimple/cmsmadesimple/blob/master/LICENCE). |
| Smarty project and contributors | [Smarty](https://www.smarty.net/), [source and licence](https://github.com/smarty-php/smarty) | Template rendering supplied by CMSMS; Smarty source is not bundled here. |
| PHP Group and PHP contributors | [PHP](https://www.php.net/), [manual](https://www.php.net/manual/en/) | Server language and standard extensions; supplied by the site's hosting environment. |
| ADOdb project and contributors | [ADOdb](https://adodb.org/), [source](https://github.com/ADOdb/ADOdb) | Database abstraction used through CMSMS's database API; supplied by CMSMS. |
| Bootstrap team and contributors | [Bootstrap 5 documentation](https://getbootstrap.com/docs/5.3/), [MIT licence](https://github.com/twbs/bootstrap/blob/main/LICENSE) | The supplied frontend markup uses Bootstrap classes. The site theme supplies Bootstrap; its CSS/JavaScript is not copied into this module. |
| curl/libcurl project and contributors | [curl](https://curl.se/), [licence](https://curl.se/docs/copyright.html) | HTTP requests through the hosting environment's PHP cURL extension; libcurl is not bundled. |
| Fonticons, Inc. / Font Awesome contributors | [Font Awesome](https://fontawesome.com/), [Free licence](https://fontawesome.com/license/free) | The supplied template references Font Awesome clock and eye classes. Icon/font files are supplied by the site's theme, not bundled. Font Awesome Free has separate icon, font and code licences; retain the notices for the version used by the site. |

## Video services and content

- **Google / YouTube**: the [YouTube Data API v3](https://developers.google.com/youtube/v3)
  supplies metadata and playlist information; the [embedded player](https://developers.google.com/youtube/player_parameters)
  supplies playback. YouTube-hosted thumbnails are cached locally by the module.
- Integration references: [videos.list](https://developers.google.com/youtube/v3/docs/videos/list)
  and [playlistItems.list](https://developers.google.com/youtube/v3/docs/playlistItems/list).
- Video, thumbnail, music and channel credits belong to the respective creators.
  No videos or example channel catalogues are bundled with the module. API and
  player use remains subject to [YouTube API terms](https://developers.google.com/youtube/terms/api-services-terms-of-service)
  and [Google privacy policy](https://policies.google.com/privacy).

## Development assistance

- Maintained and published by [MasterCatsUK](https://github.com/MasterCatsUK).
- [OpenAI Codex](https://openai.com/codex/) was used for AI-assisted implementation,
  troubleshooting, documentation and release preparation. This records tool
  provenance; it does not replace the credits or licences of upstream projects.
- **ModuleChecker 1.2.0**, credited in its source to **Pixel Solutions**, was
  used for local static preflight with a CMS metadata shim. See the
  [official ModuleChecker documentation](https://developer.cmsmadesimple.org/docs/developer-tools/module-checker)
  and [CMSMS scanner rules project](https://github.com/cmsmadesimple/cmsms-mc-rules).
  The scanner is a development tool, not a bundled runtime dependency; the
  preflight does not establish live CMSMS compatibility.

## Project artwork

The Module Manager icon was generated with **OpenAI ImageGen** on 29 October
2026, with direction and selection by the maintainer. PNG exports were resized
with **Pillow** ([project and contributors](https://python-pillow.github.io/),
[licence](https://github.com/python-pillow/Pillow/blob/main/LICENSE)).
No external stock image or third-party icon pack was used for this icon.

The promotional module banner was generated for this project with **OpenAI
ImageGen** on 27 October 2026, with direction and selection by the maintainer.
It is an illustration of the module's purpose, not a screenshot of its interface.
Standard and retina exports are in `assets/`. No third-party photo library or
external stock image is represented as the source of this generated banner.

The original **Andrew Smith** copyright and MIT licence are retained in
[LICENSE](LICENSE) and [docs/LICENSE](docs/LICENSE). Acknowledging YouTube does
not make the module an official Google/YouTube product.

Attribution inventory reviewed: 29 October 2026.
