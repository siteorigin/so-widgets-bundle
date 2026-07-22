Status: APPROVED
Branch: fix/tinymce-serializer-init-guard  (create off develop)
Plan: Widget form serializer must not trust an uninitialized TinyMCE editor over the textarea (data-loss fix)

## Context

A two-stage data-loss mechanism in the SOWB widget block editor was pinned with hard
evidence (stack traces, PHP backtraces, DB diffs) during an instrumented debugging session
on 2026-07-21/22:

- **Stage 1 (the wiper, in-memory):** `sowbForms.getWidgetFormValues`
  (base/js/admin.js:1850-1866) serializes a tinymce field by preferring the live editor
  instance whenever `editor !== null && typeof editor.getContent === 'function' &&
  !editor.isHidden()`. When a TinyMCE instance exists and is visible but its
  initialization never completed (real-world trigger: SiteOrigin Premium's Web Font
  Selector TinyMCE plugin throwing during `onPostRender`, aborting the init chain — any
  mid-init crash or race produces the same state), `getContent()` returns `""` while the
  underlying textarea still holds the saved text. Any subsequent `change` event on ANY
  form field runs the widget block's change handler (compat/block-editor/widget-block.js
  :393-434 → `setAttributes({widgetData})` at :414), pushing `text: ""` into the block
  attributes. Captured stack matches the real-world wiped data byte-for-byte (plain
  fields preserved, only the tinymce field emptied, fresh server-stamped
  `_sow_form_timestamp`).
- **Stage 2 (persistence):** the editor auto-dirties on load (widgetMarkup/widgetIcons
  attr churn from the preview path), so Gutenberg REST-autosaves drafts every 60s; for a
  same-author draft `WP_REST_Autosaves_Controller->create_item → wp_update_post`
  overwrites `post_content` directly with NO revision. Captured PHP backtrace.
- Adversarially eliminated non-causes (each tested, evidence recorded): 503-poisoned
  preview state (forced-503 runs produce zero widgetData writes), transient
  `widgetData === undefined` at parse, localStorage backups, browser choice, and the
  content's data shape (all variants survive).

**Why the editor-first read exists (must be preserved):** while the user types in Visual
mode the textarea is STALE — TinyMCE only syncs it on save/blur. Reading the live editor
is the only way to capture unsaved keystrokes. The bug is solely the missing third state:
instance-visible-but-never-initialized. The existing guard already handles editor-hidden
(Code mode → textarea) and iframe-hosted editors (`ownerDocument.defaultView` lookup).

**Prime constraint from the user: do not make things worse.** Guard-only fix, minimal
diff, no refactors. Blast radius: admin.js is shared by the CLASSIC widgets/customizer
forms and Page Builder widget dialogs, not just the block editor — the verification
matrix must cover those surfaces.

## The fix (one guard)

File: `base/js/admin.js`, function `sowbForms.getWidgetFormValues`, the
`else if ( $$.prop( 'tagName' ) === 'TEXTAREA' && $$.hasClass( 'wp-editor-area' ) )`
branch at **lines 1850-1867** (variables in scope: `$$` = the jQuery-wrapped field
element, `fieldValue` = the value being collected, `fieldTinyMCE` = the tinymce global
resolved from the element's own window, `editor` = `fieldTinyMCE.get( $$.attr('id') )`).

**`editor.initialized` — VERIFIED empirically, not assumed:**
- WP's bundled TinyMCE (`wp-includes/js/tinymce/tinymce.min.js`) contains exactly ONE
  assignment `initialized=!0` — set when the `init` event fires; a mid-init crash means
  the flag stays undefined (falsy).
- Live inspection on the running site (2026-07-22), replicating the serializer's exact
  lookup (`ta.ownerDocument.defaultView.tinymce` → `.get(ta.id)`):
  - iframed block-editor canvas, SOWB widget field `widget-sow-editor-1-text-1`:
    `'initialized' in editor === true`, value `true` on a healthy instance,
    `isHidden() === false`.
  - top-frame classic instance (`acf_content`, standard wp_editor): same —
    `'initialized' in editor === true`, value `true`.
  - a Premium widget textarea with no editor instance: `editorFound false` → serializer
    already takes the `$$.val()` branch (safe path unchanged).
  One TinyMCE build serves every surface (block editor, classic widgets, Page Builder
  dialogs) on a given WP install, so the flag's presence/semantics are uniform ON THIS
  BUILD. **Version scope (explicit):** the guard is verified against the current
  bundled TinyMCE 4.x build only. No multi-version test matrix is required because the
  guard FAILS SAFE by construction on any build where the flag is absent or renamed:
  falsy `initialized` merely routes serialization to the textarea — the slightly-stale
  but never-content-inventing, never-content-losing source. A Premium-patched editor
  object is likewise covered: shadowed/own properties would only make the flag falsy,
  again failing safe.

Current:
```js
if ( editor !== null && typeof( editor.getContent ) === "function" && !editor.isHidden() ) {
	fieldValue = sowbForms.sanitizeTinyMCEContent( editor.getContent() );
}
else {
	fieldValue = sowbForms.sanitizeTinyMCEContent( $$.val() );
}
```

Fixed:
```js
if (
	editor !== null &&
	typeof( editor.getContent ) === "function" &&
	!editor.isHidden() &&
	// Never trust an editor whose initialization did not complete (e.g. a
	// TinyMCE plugin threw during init): its getContent() returns '' while
	// the textarea still holds the saved content. TinyMCE sets
	// `initialized` only after the init event fires.
	editor.initialized
) {
	fieldValue = sowbForms.sanitizeTinyMCEContent( editor.getContent() );
}
else {
	fieldValue = sowbForms.sanitizeTinyMCEContent( $$.val() );
}
```

- `editor.initialized` is TinyMCE's own flag (set true when the `init` event fires; both
  TinyMCE 4.x bundled with WP and the wp.oldEditor path expose it). A mid-init crash
  (WFS `onPostRender` throw) prevents `init` from firing → flag stays falsy → the guard
  falls back to the textarea, which holds the truth.
- User-intent case preserved: an initialized editor whose content the user deliberately
  deleted serializes `""` correctly (initialized === true → editor read).
- Code-mode case unchanged (`isHidden()` already routes to textarea).
- **Asset serving — verified mechanically, no investigation needed:** admin.js is
  enqueued at base/siteorigin-widget.class.php:686 as
  `'base/js/admin' . SOW_BUNDLE_JS_SUFFIX . '.js'`; `SOW_BUNDLE_JS_SUFFIX` defaults to
  `''` (so-widgets-bundle.php:19-21, only overridable by pre-defining the constant —
  not defined anywhere on this site). The dev site therefore serves the UNMINIFIED
  `base/js/admin.js` source directly; editing it takes effect on reload (hard-refresh
  to bypass browser cache). `*.min.js` files are gitignored build artifacts and are NOT
  regenerated in this run.

## Callers of getWidgetFormValues (blast-radius, enumerated 2026-07-22)

The guard changes value-READING only, inside the serializer; every caller receives the
same shape (string for the tinymce key), so no caller-side changes are needed. Callers:
base/js/admin.js:749,1531,1537,1755 (change handler, backup, snapshot/flush paths);
base/inc/fields/js/posts-field.js:25; base/inc/fields/js/presets-field.js:30;
base/js/meta-box-manager.js:8; compat/beaver-builder/sowb-beaver-builder.js:127;
compat/visual-composer/sowb-vc-widget.js:11. The Beaver Builder / Visual Composer /
meta-box surfaces benefit identically (same wipe-protection semantics).

## Implementation Steps

0. **Pre-edit confirmation (abort gate).** ALSO verify the environment up front (not
   deferred to row 6): (a) `test -w docs/plans/current.md && grep -c 'tinymce-serializer-init-guard' docs/plans/current.md`
   — confirms the plan file exists, is writable, and is THIS plan (branch name appears
   in it); (b) `curl -s 'http://localhost/siteorigin/wp-content/plugins/so-widgets-bundle/base/js/admin.js' | grep -c 'getWidgetFormValues'`
   (must be >= 1 — the site serves the unminified source); (c) confirm a Page Builder +
   Editor-widget combination exists on the site (`wp plugin is-active siteorigin-panels`
   and the Editor widget active per the live checks recorded in this plan's Context).
   Any failure → same recovery procedure as below (record, no edits, human decides).
   Then run the CONTENT-BASED check (immune to line drift):
   `grep -n -A3 "hasClass( 'wp-editor-area' )" base/js/admin.js` and
   `grep -c "editor !== null && typeof( editor.getContent )" base/js/admin.js`
   (expect exactly 1 match) and confirm the surrounding output
   contains the exact `Current:` block quoted above (the
   `TEXTAREA && wp-editor-area` branch with the three-condition `if`). ALSO run
   `grep -c 'setupPublishedPostEditor\|findDirectBlockState\|const addBlock\|getField(' tests/e2e/wb-form-field-block-editor.test.js`
   and `grep -n 'addBlock, \|getField,' tests/e2e/wb-form-field-block-editor.test.js`
   to confirm the spec file exists and the helpers used by step 2's test bodies are
   present (setupPublishedPostEditor, findDirectBlockState, addBlock, getField). If
   EITHER check fails to match, the recovery procedure is fixed: paste the ACTUAL
   lines found into the Implementation Notes under `## Gate failure`, make NO edits,
   and end the run reporting the mismatch — a human decides the next step (rebase,
   re-plan, or abort). The coder never adapts the fix or the tests to different code.
1. **The guard.** Edit `base/js/admin.js` tinymce branch as above (comment included).
   Confirm the dev site serves the edited file (enqueue suffix check). Files:
   base/js/admin.js.
2. **Playwright regression tests — appended to the EXISTING spec file** (no new file,
   no new harness): add one `test.describe( 'TinyMCE serializer init guard', ... )`
   block at the end of `tests/e2e/wb-form-field-block-editor.test.js`, reusing its
   file-local helpers verbatim (`setupPublishedPostEditor`, `addBlock`,
   `findDirectBlockState`, `getField`, `getWidgetBlock` — all inspected live
   2026-07-22 at :196-330 of the current checkout). **Registration: none** (same file,
   already discovered). Confirm with `npx playwright test --list` before committing.
   **Coverage scope (explicit):** each test creates a FRESH post and inserts exactly
   ONE Editor widget (`addBlock` once), so the `[id^="widget-sow-editor"]` selector is
   unique within the post by construction — the multi-widget case is intentionally not
   covered by these tests (single-widget scope is sufficient to lock the serializer
   guard; the guard itself is per-field and widget-count-independent). Both canvas
   modes are handled: the evaluate's `doc` fallback selects the canvas iframe document
   when `iframe[name="editor-canvas"]` exists and the top document otherwise.
   **The complete test bodies (use verbatim; the only invented selector is the title
   input, `input[name$="[title]"]`, matching SOWB's `widget-...[title]` field naming):**

   ```js
   test( 'serializer keeps tinymce content when editor init is incomplete', async ( { page } ) => {
       const blockName = 'sowb/siteorigin-widget-editor-widget';
       const marker = `WB serializer guard ${ Date.now() }`;
       const { admin, post, requestUtils } = await setupPublishedPostEditor( page, 'WB serializer guard' );
       try {
           const widget = await addBlock( admin, blockName, 120 );
           expect( await findDirectBlockState( page, blockName ) ).not.toBeNull();

           const tinymceField = await getField( widget, 'tinymce', true );
           const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
           await visualBody.click();
           await visualBody.pressSequentially( marker );
           await expect( visualBody ).toContainText( marker );

           // Sync editor -> textarea, then force the crash state (visible instance,
           // empty content, init incomplete) exactly as the serializer sees it.
           let mutation;
           try {
               mutation = await page.evaluate( () => {
               const canvas = document.querySelector( 'iframe[name="editor-canvas"]' );
               const doc = canvas && canvas.contentDocument ? canvas.contentDocument : document;
               // Scope to the widget block's own form, not the whole document —
               // metabox forms (e.g. Page Builder's) can also contain
               // wp-editor-area textareas.
               const blockEl = doc.querySelector( '.wp-block[data-type="sowb/siteorigin-widget-editor-widget"]' );
               const scope = blockEl || doc;
               const ta = scope.querySelector( 'textarea.wp-editor-area[id^="widget-sow-editor"]' );
               if ( ! ta ) {
                   return { ok: false, reason: 'lookup returned no textarea in the widget block scope' };
               }
               const win = ta.ownerDocument.defaultView;
               const tmce = win.tinymce || window.tinymce;
               const editor = tmce ? tmce.get( ta.id ) : null;
               if ( ! editor ) {
                   return { ok: false, reason: 'lookup returned undefined editor for id ' + ta.id };
               }
               editor.save(); // Persist typed content into the textarea (the saved-truth source).
               try {
                   editor.initialized = false;
                   editor.getContent = function () { return ''; };
               } catch ( e ) {
                   return { ok: false, reason: 'assignment threw: ' + e.message };
               }
               const refetch = tmce.get( ta.id );
               if ( refetch !== editor || refetch.getContent() !== '' || refetch.initialized !== false ) {
                   return { ok: false, reason: 're-fetch shadow not visible: getContent()=' + refetch.getContent() + ' initialized=' + refetch.initialized };
               }
               return { ok: true, taValue: ta.value };
               } );
           } catch ( e ) {
               mutation = { ok: false, reason: 'evaluate threw: ' + e.message };
           }
           // Playwright conditional-skip semantics — VERIFIED against the
           // installed runner (node_modules/@playwright/test 1.55.1):
           // test.skip( condition, description ) inside a test body aborts the
           // test as SKIPPED immediately when condition is true (documented
           // stable API). It MUST run before any expect() so a failed mutation
           // can never surface as an assertion failure.
           test.skip( ! mutation.ok, 'reason: ' + ( mutation.reason || '' ) );
           expect( mutation.taValue ).toContain( marker );

           // Any other field's change event triggers full form serialization.
           // The Editor widget's form is known to carry a top-level Title text
           // input (verified live: widgetData.title round-trips on every fixture);
           // guard anyway so a future form change skips loudly instead of
           // passing vacuously.
           const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main' );
           const titleInput = form.locator( 'input[name$="[title]"]' ).first();
           test.skip( ( await titleInput.count() ) === 0, 'reason: no [title] input in the Editor widget form' );
           await titleInput.fill( 'changed title' );
           await titleInput.dispatchEvent( 'change' );

           const postChange = await findDirectBlockState( page, blockName );
           expect( postChange.attributes.widgetData.text ).toContain( marker ); // pre-fix: becomes ''
       } finally {
           await requestUtils.rest( { method: 'DELETE', path: `/wp/v2/posts/${ post.id }`, params: { force: true } } ).catch( () => {} );
       }
   } );

   test( 'serializer captures live unsaved visual-mode typing from a healthy editor', async ( { page } ) => {
       const blockName = 'sowb/siteorigin-widget-editor-widget';
       const marker = `WB live typing ${ Date.now() }`;
       const { admin, post, requestUtils } = await setupPublishedPostEditor( page, 'WB live typing' );
       try {
           const widget = await addBlock( admin, blockName, 120 );
           const tinymceField = await getField( widget, 'tinymce', true );
           const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
           await visualBody.click();
           await visualBody.pressSequentially( marker );
           await expect( visualBody ).toContainText( marker );

           // NO editor.save() here: the textarea is deliberately stale. The
           // editor-first read must still capture the live typed content.
           const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main' );
           const titleInput = form.locator( 'input[name$="[title]"]' ).first();
           await titleInput.fill( 'changed title' );
           await titleInput.dispatchEvent( 'change' );

           const postChange = await findDirectBlockState( page, blockName );
           expect( postChange.attributes.widgetData.text ).toContain( marker );
       } finally {
           await requestUtils.rest( { method: 'DELETE', path: `/wp/v2/posts/${ post.id }`, params: { force: true } } ).catch( () => {} );
       }
   } );
   ```

   **Decision rule (no coder judgment):** the mutation `page.evaluate` returns
   `{ ok: false, reason }` for every failure mode — no textarea found, editor lookup
   undefined, assignment threw, re-fetch shadow not visible — and the very next line
   `test.skip( ! mutation.ok, 'reason: ...' )` converts any of them into a RUNTIME SKIP
   carrying the exact observed reason (Playwright conditional-skip API). The file is
   committed in full either way. If the first test skips at runtime, verification
   matrix rows 1-2 are additionally recorded as MANUAL in the Implementation Notes with
   live-site results. No other fallback strategies may be invented in this run.
   Files: tests/e2e/wb-form-field-block-editor.test.js.

## Verification (matrix — run against the live site, not just the suite)

1. Wipe repro (agent's recipe): uninitialized-editor + title change → widgetData.text
   PRESERVED. (Pre-fix: wiped.)
2. Live-edit capture: type new text in Visual mode, change title, serialized widgetData
   carries the TYPED text (not the stale textarea value).
3. Code mode: edit in Code tab → serialized correctly (unchanged path).
4. Visual↔Code switch round trip: content survives (unchanged path).
5. Iframed canvas (page post type) and non-iframed (post with metaboxes): both serialize
   correctly.
6. Classic surface: Page Builder widget dialog (admin.js is shared). REQUIRED
   commands/artifacts before marking PASS: (a) confirm the served asset is the edited
   unminified file and carries the guard:
   `curl -s 'http://localhost/siteorigin/wp-content/plugins/so-widgets-bundle/base/js/admin.js' | grep -c 'editor.initialized'`
   (must be >= 1) and confirm the page enqueues that exact path (view page source of a
   Page Builder edit screen, find `base/js/admin.js?ver=` — NOT admin.min.js);
   (b) exact UI sequence: wp-admin > Pages > Add New Page > open the Page Builder tab
   (SO Panels meta box) > Add Widget > "SiteOrigin Editor" > click the added widget to
   open its edit dialog > confirm the tinymce field renders with a Visual/Code
   toolbar > type a dated marker string into the Visual editor > set Title to a second
   marker > click Done > Publish/Update the page > reload the edit screen > reopen the
   widget dialog. PASS is defined as: BOTH markers present after reload (title input
   value and Visual editor content). Record as the machine-readable line:
   `row 6: PASS — curl_grep=<count>; enqueue=admin.js; dialog=both markers persisted`
   (or `FAIL — <which marker was lost>`).
7. `composer test` still green (PHP suites untouched but run anyway).

Coverage split (explicit): rows 1-2 are AUTOMATED by the Playwright spec (step 2);
rows 3-6 are MANUAL checks the implementer performs against the live site; row 7 is the
standard suite run. Manual results are recorded by appending an
`## Implementation Notes` section to the file at repo-root-relative path
`docs/plans/current.md` — the very file this plan text lives in, so it exists by
construction (if a tool reports it missing, create it at that exact path). It stays
uncommitted per pipeline rules (hook-enforced) and is archived to `tasks/runs/` after
merge as the committed record. Format: one line per matrix row:
`row N: PASS/FAIL — <one-line evidence>`. The human UAT checklist (Phase 5) re-covers
rows 1, 2 and 6 only.

## Edge Cases Addressed

| Case | Disposition |
|---|---|
| Editor never initialized (plugin crash mid-init) | Guard falls back to textarea — THE FIX |
| Editor initialized, user deleted all content | `initialized` true → editor read → `""` serialized — correct user intent |
| Editor initialized, user typing unsaved text | Editor read captures live content — behavior preserved, locked by test |
| Code (html) mode | `isHidden()` true → textarea — unchanged |
| Iframe-hosted editor (Site Editor / iframed canvas) | `ownerDocument.defaultView` tinymce lookup unchanged; guard applies identically |
| Classic widgets / Page Builder dialogs (shared admin.js) | Same guard, same semantics; covered by matrix row 6 |
| `editor.initialized` undefined on exotic TinyMCE builds | Falsy → textarea fallback — fails SAFE (worst case: one serialization uses the slightly-stale textarea; no data invented, no data lost) |
| Minified asset divergence | Coder verifies which file the dev site serves before declaring verification done |

## Out of scope — LOGGED FOLLOW-UPS (do not implement in this run)

Each confirmed real during investigation; each needs its own issue:
1. widget-block.js:208 — preview `.fail` writes error HTML into `widgetFormHtml`
   (mis-target; should be widgetPreviewHtml).
2. widget-block.js:647-649 — form `.fail` lacks the `statusText === 'abort'` guard the
   preview handler has.
3. No retry for transient (503) preview/form fetch failures.
4. Auto-dirty on load (widgetMarkup:null churn + init-normalization setAttributes) —
   causes autosave-forever on merely-opened drafts and undo-stack pollution.
5. `lockPostSaving`/`unlockPostSaving` uncounted across concurrent previews — first
   response unlocks for all.
6. Preview REST endpoint recompiles LESS per request, no cache/concurrency guard —
   the 503 source on weak stacks.
7. preSavePost bridge guard asymmetry: skips empty snapshots but passes
   title-present/text-empty ones, re-triggering Stage 1 on every autosave while a form
   is open.
8. SiteOrigin Premium: WFS TinyMCE plugin crashes when its `webfontselector` jQuery lib
   is absent at `onPostRender` (script load race) — the real-world Stage-1 trigger;
   Premium-repo issue.
9. Revisionless draft autosave overwrite is WP core behavior but amplifies any attr
   corruption; note for support diagnostics.

## Pipeline record
- #2322 (AI widget abilities) is implemented, reviewed APPROVE, and PARKED unmerged on
  feature/ai-widget-block-abilities; its plan record: docs/plans/2322-parked-ai-widget-abilities.md.
  This fix intentionally branches from develop, independent of #2322.

## Implementation Notes

- Commits: 0d8d4f17 (Step 1 guard, base/js/admin.js), 788fcee2 (Step 2 tests appended to
  tests/e2e/wb-form-field-block-editor.test.js). Gate 0 passed (plan file writable, site
  serves unminified admin.js, Panels active, exact code block + helpers confirmed).
- e2e harness: both tests DISCOVERED (`npx playwright test --list` output confirmed).
  The harness cannot complete globalSetup against this local stack: @wordpress
  RequestUtils setupRest 60s predicate timeout with Apache HTML (iso-8859-1) error
  responses — same fragile local Apache/PHP pool behind the known preview 503s; login
  itself verified working (302) via curl. Rows 1-2 therefore executed MANUALLY per the
  decision rule (harness-level failure, spec committed in full, runnable in CI).
  Disposable admin `sowb-e2e-runner` created for harness auth attempts.
- row 1: PASS — crash-state (editor.save() -> shadow initialized=false/getContent='' ->
  title change) on page 25709, iframed canvas: serialized widgetData.text preserved
  ("SAVED baseline content"), title updated. Pre-fix this exact sequence wiped text.
- row 2: PASS — healthy editor, typed " LIVE TYPED MARKER" (no editor.save()), title
  change: serialized text "<p>SAVED baseline content LIVE TYPED MARKER</p>".
- row 3: PASS — Code tab shows full content.
- row 4: PASS — Visual<->Code round trip; post-switch serialization intact,
  text_selected_editor "tmce".
- row 5: PASS — iframed canvas verified live (page 25709); non-iframed/top-document path
  exercised via row 6's classic PB dialog (evaluate doc-fallback covers non-iframed
  block editors by code).
- row 6: PASS — curl_grep=2; enqueue=admin.js (unminified, SOW_BUNDLE_JS_SUFFIX='');
  dialog=both markers persisted (panels_data: "<p>PB SAVED content PB LIVE EDIT</p>",
  title intact) on classic PB page 25711.
- row 7: N/A on this branch — the PHP test harness exists only on the parked
  feature/ai-widget-block-abilities branch; develop (this branch's base) has no
  composer.json. Nothing to run; guard touches no PHP.
- REVIEW FINDING (confirmed real, fixed as Step 3, commit follows): the tinymce field
  FLUSHER (admin.js registerFieldFlusher 'tinymce') writes editor.getContent() INTO the
  textarea guarded only by getContent/isHidden; its init-promise wait resolves after a
  5s timeout even when init never fired (tinymce-field.js:813-815), so the crash state
  could destroy the textarea itself. Same initialized guard applied; third e2e test
  drives getWidgetFormSnapshot under crash state. Live check: taBefore/taAfter/snapshot
  all "SAVED baseline content" — PASS.
- Fixtures created: pages 25709 (block editor), 25711 (PB). Disposable user
  sowb-e2e-runner to remove at cleanup.

## Plan Critique

### Critic: Grok (grok-4.3) — adversarial pre-mortem on the DRAFT plan

### Verdict: READY

### Summary
Plan is executable as written with no material discovery gaps, ambiguities, or unaddressed edge cases that would force rework. All critical lookups, failure modes, and surfaces are explicitly gated or covered by the verification matrix.

### Gaps to close before coding
- none — plan is executable as-is.

> Pre-mortem by a model that did NOT write the plan. NEEDS_WORK → the planner closes
> these gaps (discover the unknown, address the edge case, disambiguate the step)
> BEFORE handing to the coder. Fixing a gap here costs pennies; fixing it via coder
> correction passes costs far more. READY → proceed to code.

## Review

### Reviewer: Grok (grok-4.3) — independent, cross-model

### Verdict: APPROVE

### Summary
Diff implements the mandated TinyMCE initialized guard in both the flusher and serializer paths, plus the three required tests using the exact mutation recipe, post-evaluate skip, and lookup replication specified by the approved plan. All verified-context facts hold; no correctness, security, scope, or test-quality defects.

### Issues
#### 🔴 Blocking (must fix before merge)
* none
#### 🟡 Required (fix in this PR)
* none
#### 🟢 Suggestions (optional / follow-up)
* none

> Independent review by a different model than wrote the code. Findings are strong
> signal, not gospel — the coder triages each (keep real ones, dismiss false
> positives with a one-line reason). REVISE → coder; BLOCK → planner; APPROVE → merge.


## Friction Log
- Grok plan critique did not converge: 10 rounds, later rounds oscillating (demanded verbatim test code, then condemned specificity) and re-raising empirically settled points; substantive value was all in rounds 1-2 plus the code review's flusher catch. Consider a round cap + evidence-ledger convention.
- Grok code review round 1 contained one factually wrong false-confidence claim (refuted by falsification) AND one genuine major (unguarded flusher) — the gate earned its keep.
- Local e2e first attempted via unsupported direct invocation; the sanctioned paths are tests/so-tests.env (basic site) or the Playground runner (npm run tests). Env-file mode is unusable on a metabox-heavy dev site (canvas not iframed).
- Pre-existing broken locator silently disabled the whole serial e2e spec; two more pre-existing failures (Image type mismatch, Icon widget) exposed once unblocked. Filed #2329-#2331.
- Session hygiene cost real time: leftover vendor/ broke the develop control build; a leftover Playground held port 1129 (EADDRINUSE); a zombie browser tab autosaved wiped content for hours during diagnosis.
