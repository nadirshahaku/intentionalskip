# AI_DOCS_REVIEW.md

## Review summary

The guide set has been rechecked against the current repository code and aligned on the required topics. No remaining contradictions were found for page coverage wording, preview filtering wording, `get_state` capability/review-read wording, final patch-report format, privacy-provider status, or `db/upgrade.php` status.

## Files reviewed

- `AGENTS.md`
- `FILE_INDEX.md`
- `BUG_MAP.md`
- `CODE_OBJECT_INDEX.md`
- `FLOW_MAP.md`
- `PATCH_RULES.md`
- `CHECKS.md`
- `AI_DOCS_REVIEW.md`

## Source files checked

- `rule.php`
- `report.php`
- `db/access.php`
- `db/services.php`
- `db/install.xml`
- `classes/local/state_manager.php`
- `classes/external/get_state.php`
- `classes/privacy/provider.php`
- `amd/src/intentionalskip.js`
- `version.php`

## Resolved alignment status

- Page coverage and `setup_attempt_page()` wording now consistently uses the qualified form: the AMD/module behavior covers quiz attempt-page, summary/final-submit, and review-related contexts, with exact invocation depending on Moodle core quiz access-rule hook/page behavior.
- Preview filtering wording now consistently distinguishes stored quiz config `excludepreviews` from current report filtering by the `includepreviews` request parameter. No built-in report toggle UI is documented in the current code.
- `get_state` wording now consistently states that service reachability is gated by `db/services.php` capability `quizaccess/intentionalskip:use`, while runtime validation may allow review/report-style reads through `validate_attempt(..., false, true)` where applicable. Non-owner review reads therefore require both paths.
- Final patch-report format wording is aligned across `AGENTS.md`, `PATCH_RULES.md`, and `CHECKS.md`.
- The required final report sections now use the same `## Patch summary`, `## Verification`, and `## Risk notes` headings across those guide files.
- Privacy wording now consistently describes the current implementation as metadata/deletion support with export currently unimplemented, and notes that metadata coverage is partial rather than a full column-for-column listing.
- Upgrade wording now consistently states that `db/upgrade.php` is not present and that schema changes require both a `version.php` bump and `db/upgrade.php`.

## Commit readiness note

- Guide files being untracked before commit is not a documentation inconsistency.
- Untracked guide files are expected until they are added to Git for commit.

## Final verdict

Ready to commit
