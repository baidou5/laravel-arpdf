# Release Checklist (v2.0.0)

1. Ensure tests pass:
   - `vendor/bin/phpunit -c tests/phpunit.xml`
2. Commit changes.
3. Create annotated tag:
   - `git tag -a v2.0.0 -m "Release v2.0.0"`
4. Push branch and tag:
   - `git push origin <branch-name>`
   - `git push origin v2.0.0`
5. Create GitHub Release:
   - Tag: `v2.0.0`
   - Title: `Laravel ArPDF v2.0.0`
   - Body: paste `RELEASE_v2.0.0.md`
6. Verify Packagist auto-update (or trigger manually from Packagist dashboard).
7. Publish announcement text from `ANNOUNCEMENT_v2.0.0_AR.md`.
