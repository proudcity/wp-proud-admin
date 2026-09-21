# Changelog

## 2026.09.21.1408

- Added `ProudGFSettings::filterNotificationSender()` on `gform_notification`, keeping every Gravity Forms notification's `From:` header on a domain we can authenticate for. Gravity Forms lets each notification set an arbitrary From address, and several in production sent *as* someone else — form 17 on wendellmass.us used `{Email:6}` (the resident's own address) and both of form 27's notifications used `townclerk@wendellmass.us`. DMARC aligns against the From header domain, so those failed authentication regardless of which domain our Mailgun credential authenticates. Any From value that is neither `{admin_email}` nor an address on `proudcity.com` (or a subdomain) is now replaced with `{admin_email}`.
- The displaced address is preserved as `Reply-To` when it can actually receive mail, so replying still reaches the intended person. A real address or a field merge tag of the form `{Label:ID}` qualifies; a malformed tag does not, since it would land in the header as a literal broken string. An existing `Reply-To` is never overwritten.
- Resident-derived `From` names are replaced with the site name. Notifications built their display name from merge tags, producing headers like `Bruce Ferguson <notify@proudcity.com>` — a personal name over an unrelated domain, which Microsoft weights heavily as a phishing signal and which is the likely reason Microsoft 365 recipients were quarantining town notifications. `gform_notification` fires *before* merge-tag replacement (`gravityforms/common.php:2064`, replacement at ~2107), so a From name containing a merge tag is resident-derived by definition — no name-shaped guessing is needed. Static names an admin typed deliberately ("Meeting material form", "Nomination Paperwork") are left alone, and an empty From name stays empty so Gravity Forms supplies its own default.
- Fixed a latent typo as a side effect: form 16 on wendellmass.us had `{admin-email}` with a hyphen, which is not a valid merge tag and was being used literally as an address. It now normalises to `{admin_email}`.
- Added the `proud_gf_notification_sender` filter as an escape hatch for a site that needs different handling. Overriding the From address back off our sending domains will break DMARC for that notification.
- `settings/forms.php` now loads on every request instead of only when `is_admin()`. The notification filter has to be registered when a form is submitted, and submissions happen on the front end. The other `settings/*.php` files remain admin-only, and the hooks inside `ProudGFSettings::init()` that only ever apply in the admin or the form editor are now themselves wrapped in `is_admin()` — particularly `gform_after_save_form` → `forceDefaultLabelPlacement()`, which calls `GFAPI::update_form()` and has no capability check of its own. A front-end request now registers only the notification filter.
- Hardened both sender checks against email header injection. WordPress' `is_email()` validates the local part with a pattern lacking the `D` modifier, so PCRE's `$` matches before a trailing newline and `"abc\n@proudcity.com"` is accepted — and `From` is the one value Gravity Forms does not run through `remove_extra_commas()`. Both `isAuthenticatedSender()` and `isUsableReplyTo()` now reject any value containing a control character before anything else. Not reachable as an exploit (`wp_mail()` receives array headers, and PHPMailer's `validateAddress()` rejects the newline), but the check should not depend on that.
- Tightened the Reply-To merge-tag pattern to `/\A\{[A-Za-z0-9 ()\-_.\/&']+:\d+(?:\.\d+)?\}\z/`. The previous `[^{}:]+` excluded only three characters, so quotes, angle brackets, commas and `@` were all accepted inside the label, and `{Email:...}` was treated as a valid field reference. The label is now allowlisted rather than blocklisted while still permitting the spaces and parentheses real labels use ("Work Email", "Name (First)"), and `\A`/`\z` replace `^`/`$` so no trailing newline slips through. No ReDoS exposure — the quantified classes are disjoint at the `:` boundary.
- Added 32 tests (`tests/NotificationSenderTest.php`) covering both sender rules, Reply-To preservation, subdomain matching, control-character and header-hostile input, and lookalike domains (`evilproudcity.com`, `proudcity.com.attacker.net`, punycode). The `is_email()` test stub mirrors WordPress' real implementation rather than `filter_var()`, which is stricter and would have hidden the newline case entirely. Verified against the real wendellmass.us database: of 22 stored notifications, 17 are untouched and 5 change exactly as intended.

References: https://github.com/proudcity/wp-proudcity/issues/2937

## 2026.09.16.0942

- Hardened the metabox save path in `lib/meta-box.class.php`. `save_post` fires for every post save on the site and carries no authorization of its own, so `ProudMetaBox::save_meta()` now bails on autosaves and revisions, requires its own nonce, and requires `current_user_can( 'edit_post', $post_id )` before writing. `ProudTermMetaBox::save_term_meta()` gained the same guards against `current_user_can( 'edit_term', $term_id )`. Previously any user who could save any post could write any metabox's meta onto it — this is the input side of the stored-XSS findings in wp-proud-agency (social accounts, contact name link, phone/email).
- Fixed a post-type scoping bug in `ProudMetaBox::validate_values()`. The guard read `!empty( $screen )` — an undefined local variable — instead of `!empty( $this->screen )`, so the post-type check never ran and a metabox registered for one screen would write its fields onto any post type.
- `print_form()` now emits a nonce field (`proud_metabox_nonce_{key}`, action `proud_metabox_save_{key}`) to pair with those checks. A metabox form rendered before this release and submitted after it will silently skip saving that one time; reloading the edit screen fixes it.
- Changed `ProudMetaBox::$key` from `private` to `protected` and removed the shadowing `public $key` redeclaration in `ProudTermMetaBox`. The two were separate properties, so parent methods reading `$this->key` saw an unset value on term metaboxes.
- Moved the guard stack into a `final save_meta_gate()` wrapper hooked to `save_post`, with the checks themselves in `can_save()`. **Nine subclasses** across wp-proud-agency, wp-proud-meeting, wp-proud-location and wp-proud-topic override `save_meta()` and none call `parent::save_meta()`, so guards placed in the base method were skipped entirely for them — including `AgencySection`, which calls `wp_create_nav_menu()` from a post save. A wrapper that cannot be overridden means a new subclass is safe by default rather than safe only if someone remembers the `parent::` call.
- Lowercased the key in `nonce_action()`, `nonce_name()` and `form_was_submitted()`. `FormHelper` lowercases it when building field names, so a mixed-case metabox key would have silently stopped saving. Latent — every key in the tree is already lowercase.
- Added 17 tests covering the save path (`tests/MetaBoxSaveTest.php`), including one asserting that an overriding `save_meta()` cannot skip the guards and one asserting `save_meta_gate()` is `final`, and loaded the real `Proud\Core\FormHelper` from wp-proud-core in the test bootstrap rather than stubbing it, since `getFormValues()` is part of what is being verified.

References: https://github.com/proudcity/wp-proudcity/issues/2933

## 2026.07.09.0846

- Replaced the wp-cron based alert bar expiration with a request-time check. `Proud_Alert_Expiration::check()` now runs on `init`, so an expired alert bar turns off on the next request to the site — wp-cron proved unreliable across environments (loopback spawn failures) and was more complexity than the feature needed. The `proud_alert_expiration_check` cron event and its scheduling were removed; any already-scheduled event is an orphaned no-op.
- Expiry behavior is otherwise unchanged: end-of-day in the site timezone, `alert_expiration` cleared when the bar deactivates, and the settings-save cache-clearing routine runs so cached pages regenerate without the bar.

References: https://github.com/proudcity/wp-proudcity/issues/2850

## 2026-07-08

- Added "Expiration date" field to the Alert bar settings page (Proud Settings > Alert bar). Rendered as a native HTML5 date picker via a small inline admin script; stored in new `alert_expiration` option as YYYY-MM-DD. Blank or unparseable value means no expiration (previous behavior preserved).
- Added `lib/proud-alert-expiration.php` implementing a new hourly wp-cron event `proud_alert_expiration_check` (scheduled defensively on init). On expiry the cron callback sets `alert_active` off, clears `alert_expiration` so re-enabling later does not instantly re-expire, and runs the same cache-clearing routine used on settings save (WP Rocket). Expiry is evaluated as end-of-day in the site's configured timezone (`wp_timezone()`), so the bar stays visible through the chosen day.
- Changed `ProudSettingsPage::clear_cache()` visibility from private to public static so the cron callback can reuse it without duplication.
- Added PHPUnit + Brain Monkey test harness (composer.json, phpunit.xml, tests/) with 9 tests covering the cron callback; run via `composer install && vendor/bin/phpunit`.

References: https://github.com/proudcity/wp-proudcity/issues/2850

## 2026-05-27

- Fixed `vite.config.js` to include `assets/scripts/proud-admin.js` as a Rollup input. The PHP side enqueues `dist/scripts/proud-admin.js` but the Vite config previously only built CSS, causing the JS to 404 in production. Discovered during manual testing of the Embed Document widget (#2744); not part of that feature work.

References: https://github.com/proudcity/wp-proudcity/issues/2744

## [Unreleased]

### Changed
- Migrated build system from legacy Grunt/Gulp to Vite 6
- Switched Vite SCSS preprocessor from `api: 'legacy'` to `api: 'modern-compiler'`
- Converted all project SCSS from `@import` to `@use`/`@forward` (Dart Sass 3.0 readiness)
- Added `assets/styles` to Sass `loadPaths` so pattern files can reference project loaders
- Added `node_modules` to `loadPaths` so fontawesome SCSS is resolvable by package name
- Upgraded Sass from 1.56.x to 1.74.x+ to support modern deprecation tooling
- Added `"type": "module"` to package.json to resolve Vite CJS Node API deprecation warning
- Removed `bourbon` v4 from `devDependencies` and from `vite.config.js` `includePaths` — replaced with a lightweight project-owned drop-in

### Fixed

#### @import → @use/@forward migration (Dart Sass 3.0) — project files fully resolved
- `assets/styles/loaders/_load-variables.scss`: `@import` → `@forward` for both variable source files
- `assets/styles/loaders/_load-local.scss`: `@import` → `@forward` (bootstrap/mixins excluded to avoid mixin-name conflicts with bourbon-replacement)
- `assets/styles/proud-admin.scss`: all `@import` → `@use`; added namespace aliases for duplicate basenames (`layouts/gravity-forms`, `modules/gravity-forms`, `pattern-scss/wp/so-pagebuilder`)
- `assets/styles/components/_forms.scss`, `_grid.scss`, `_wp-classes.scss`: added `@use '../loaders/load-local' as *`
- `assets/styles/layouts/_toolbar.scss`, `_misc.scss`, `_dashboard.scss`: added `@use '../loaders/load-local' as *`
- `assets/styles/loaders/_bootstrap-mixins-bridge.scss` (new): legacy bridge that loads bootstrap/mixins with variables in scope — required because `bootstrap/mixins` uses `$grid-gutter-width` as a default parameter value, which is unavailable in the new module scope
- All proudcity-patterns mixin sub-files: added `@use 'pattern-scss/local-variables' as *` so variables are in scope when forwarded as modules; `_form.scss` also gets `@use './font-awesome' as *`; `_nav.scss` also gets `@use 'loaders/bourbon-replacement' as *` for `clearfix`
- `proudcity-patterns/_proudcity-mixins.scss`: all `@import "mixins/..."` → `@forward "mixins/..."`
- `proudcity-patterns/wp/_admin-menu.scss`, `_sidebar.scss`, `_so-pagebuilder.scss`, `_widget-proud-jumbotron-header.scss`: added `@use 'loaders/load-local' as *`
- `proudcity-patterns/mixins/_font-awesome.scss`: changed fontawesome `@import` paths from `node_modules/@fortawesome/...` to `@fortawesome/...` (works with `node_modules` in Sass `loadPaths`)
- All proudcity-patterns @use/@forward changes added to `npm run patch-patterns` so they apply on fresh clone

**Remaining `[import]` warnings** (require package upgrades, deferred):
- `bootstrap-sass` v3 internal files — Task #5
- `proudcity-patterns/mixins/_font-awesome.scss` 3 `@import` lines — Task #6 (FontAwesome v6 upgrade)

#### [global-builtin] / [color-functions] — proudcity-patterns fully resolved
- `proudcity-patterns/_local-variables.scss`: added `@use 'sass:color'`, `@use 'sass:math'`, `@use 'sass:string'` at top
- Replaced all `lighten()` → `color.adjust($color, $lightness: N%)` (5 instances)
- Replaced all `darken()` → `color.adjust($color, $lightness: -N%)` (19 instances, including 4 nested `darken(adjust-hue(...))`)
- Replaced `lighten(desaturate($brand-primary, 50%), 35%)` → `color.adjust(color.adjust(..., $saturation: -50%), $lightness: 35%)`
- Replaced nested `darken(adjust-hue($state-X-bg, -10), N%)` → `color.adjust(color.adjust(..., $hue: -10deg), $lightness: -N%)` for all 4 state colors
- Replaced `fade_in()` → `color.adjust($color, $alpha: N)`
- Replaced all `ceil()` → `math.ceil()` (4 instances)
- Replaced all `floor()` → `math.floor()` (3 instances)
- Replaced `unquote()` → `string.unquote()` (1 instance)
- `proudcity-patterns/mixins/_media-queries.scss`: added `@use 'sass:list'`; replaced `length()` → `list.length()` in `respond()` mixin
- All changes codified in `npm run patch-patterns` so they apply on fresh clone

**Remaining `[global-builtin]` warnings** (third-party packages, deferred):
- `@fortawesome/fontawesome-free/scss/_variables.scss`: `unquote()` — Task #6 (FontAwesome v6 upgrade)
- `bootstrap-sass/mixins/_grid.scss`: `percentage()` — Task #5 (bootstrap-sass patches)

#### slash-div (Dart Sass 2.0) — fully resolved
- Fixed `slash-div` deprecation in project-owned `assets/styles/components/_wp-classes.scss` (4 instances of `$line-height-computed / 2` → `* 0.5`)
- Fixed `slash-div` across all proudcity-patterns SCSS files by replacing `/` division with `* 0.5` / `* 0.25` multiplication equivalents:
  - `pattern-scss/_local-variables.scss` (lines 497–498)
  - `pattern-scss/helpers/_utilities.scss` (6 instances)
  - `pattern-scss/helpers/_grid.scss` — replaced `percentage(((1 / $grid-columns) / 2))` with `calc(50% / #{$grid-columns})`
  - `pattern-scss/_navbar.scss` (lines 39, 348)
  - `pattern-scss/_social-wall.scss` (lines 164, 165, 197)
  - `pattern-scss/_proudbar.scss` — replaced `114px/22px*$proudbar-logo-height` with `$proudbar-logo-height * 5.18182` (lines 68, 69, 71)
  - `pattern-scss/_page-header.scss` (line 7)
  - `pattern-scss/vendor/_card.scss` (line 50)
  - `pattern-scss/vendor/_hamburger.scss` (line 90)
  - `pattern-scss/wp/_so-pagebuilder.scss` (lines 66–71, including no-space division pattern)
- Fixed `slash-div` in `@fortawesome/fontawesome-free` v5: `$fa-fw-width: (20em / 16)` → `1.25em` via patch-package

### Added
- `assets/styles/loaders/_bourbon-replacement.scss` — lightweight Bourbon v4 drop-in providing only the 8 mixins the project actually uses (`transition`, `transform`, `animation`, `keyframes`, `transition-property`, `transition-duration`, `transition-delay`, `clearfix`). Vendor prefixes omitted — all are universally supported unprefixed since 2015.
- `patch-package` dev dependency with `postinstall` hook — applies npm package patches automatically on `npm install`
- `patches/@fortawesome+fontawesome-free+5.15.4.patch` — fixes slash-div in fontawesome v5 Sass variables
- All proudcity-patterns slash-div fixes codified in `npm run patch-patterns` so they apply automatically on fresh clone via `projectsetup` / `projectupdate`

### Known Issues / Remaining Work
Remaining deprecations are all Dart Sass 3.0 concerns (not 2.0) except where noted.
Work is tracked in the project task list and should be addressed in the following order:

1. **`[legacy-js-api]`** *(Sass 2.0)* — Switch `api: 'legacy'` to `api: 'modern-compiler'` in `vite.config.js`. One-line fix, no dependencies.
2. **`[if-function]` in bootstrap-sass** *(Sass 3.0)* — `bootstrap-sass` is abandoned. Needs patch-package or replacement. Self-contained, no dependencies.
3. **`[import]` — `@import` migration** *(Sass 3.0)* — Full migration of all project SCSS from `@import` to `@use`/`@forward`. Large effort; blocks the item below.
4. ~~**`[global-builtin]` / `[color-functions]`**~~ — ✅ Fixed in all proudcity-patterns project files.
5. **`[if-function]` in bootstrap-sass** *(Sass 3.0)* — `bootstrap-sass` is abandoned. Needs patch-package or replacement. Self-contained, no dependencies.
6. **FontAwesome v5 → v6 upgrade** — Would eliminate the patch-package fix and `[import]` warnings from `_font-awesome.scss`; requires auditing icon class usage across the plugin for breaking changes.
7. **wp-proud-theme** — Also uses proudcity-patterns and needs the same slash-div fixes and bourbon drop-in applied.
