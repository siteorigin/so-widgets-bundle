# SiteOrigin Widgets Bundle — agent router

> WordPress plugin: ~28 widgets (widgets/), shared widget framework (base/), block-editor +
> builder compat (compat/). PHP floor 7.0 for shipped code; dev tooling needs PHP 8.3+.
> Tests: `composer test` (PHPUnit x2 processes) + Playwright e2e. Branch off `develop`.
> AI abilities: sowb/widget-get|widget-update|widget-describe in compat/block-editor/.
> This file ROUTES agents to the right docs/tools; it holds no content itself.
> Convention it enforces: the first 7 lines of every doc are a dense, greppable summary.

## Where things live
| Path | What |
|---|---|
| `so-widgets-bundle.php` | plugin entry; loads base/ + compat/ |
| `base/` | widget framework: `siteorigin-widget.class.php` (update()/sanitize chain), `inc/fields/` (~40 field classes), `inc/routes/` (REST `sowb/v1`) |
| `widgets/<id>/<id>.php` | the individual widgets |
| `compat/block-editor/` | widget block (`widget-block.php`, untrusted-sanitize chokepoint), AI exposure (`ai-exposure.php`, shared walk + REST), abilities (`abilities.php`), describer (`widget-describer.php`) |
| `docs/notes/` | design notes / decisions — first 7 lines of each = dense summary |
| `docs/plans/current.md` | pipeline plan workspace — NEVER committed (gitignored) |
| `tasks/runs/` | archived pipeline run records — committed, sanitized (public repo) |
| `tests/phpunit/` | PHP unit tests — `composer test` (default suite + `phpunit-widget.xml` real-widget-chain suite, separate process) |
| `tests/e2e/` | Playwright e2e — `npx playwright test` |

## How to run the app
- Local WordPress site at `/Users/misplon/Sites/siteorigin` (this plugin is inside it);
  drive it via wp-cli or the Claude browser tools against the local site.
- Agents verify their work by RUNNING the app (wp-cli, browser tools) — a green test
  suite alone is not verification.

## Hard rules
- Shipped PHP stays compatible with the readme.txt PHP floor (7.0): no typed properties,
  no PHP 8 syntax outside `tests/phpunit/`.
- Never bump the `;sowb:N` sanitize-version signal (compat/compat.php) unless a
  normal-path field sanitizer becomes MORE restrictive — see the comment there.
- AI/ability writes are origin-untrusted: they must route through
  `sanitize_widget_block_untrusted()` (forced kses floor). Never bypass it.

## Doc conventions
- First 7 lines of every doc: dense summary, greppable.
- A step that changes behavior updates the doc that describes that behavior — same step,
  same commit (docs stay self-healing).

## Pipeline
- Default flow: `/native-pipeline "<job>"` — see the multi-agent-pipeline repo.
- Branches off `develop`; atomic per-step source commits; plan file never committed.
- Public repo: run records are sanitized before commit — no keys/tokens, no private
  URLs, no client or personal data.
