# YouTubeCatalog security and scanner notes

The module uses the CMSMS database API and parameter binding for values. `ALTER TABLE` identifiers cannot be bound using `?` placeholders; table names are only constructed from the server-defined CMS_DB_PREFIX and hardcoded YouTubeCatalog table names. No visitor-supplied identifier is used.

The module uses CMSMS permissions for administrative access. The three per-section permissions and legacy umbrella permission remain supported.

The video player loads YouTube only after a visitor presses Play. Local thumbnails are used when available.
