# Albania bilingual accuracy patch

Base: `47dd2085546cd09a4610ba0bff3dc252388198e5` (merged PR #56).

Corrects media framing, CPI/RSF rank units, the CPI name, money laundering, community research leads, demographic measures, draft claims, and the graph evidence CTA in the Albanian dictionary and browser bundle. CPI remains 42/100; ranks remain 80 and 83 of 180. Allegation/denial qualifications are preserved.

R44 now acknowledges English and Albanian submission pages and states that neither public HTTP 200 nor synthetic delivery alone establishes privacy, security or access guarantees. The source JSON, rendered English HTML and Albanian translation agree. Inspected `page-albania-submit.php`, `includes/albania-language.php` and `includes/albania-submissions.php`; none is changed. No submission, email, upload or production action was performed.

The latest publisher release had already replaced rendered R37 with a more specific index-aggregate limitation and removed the obsolete R44 assertion from rendered HTML. Preserve that work. This patch corrects the old R37 dictionary entry and synchronizes R44; it does not regenerate the entire register because the generator/source still differ from newer publisher edits elsewhere.

Validation: `node --test tools/albania-accuracy.test.cjs` (6 passing), `node --check assets/albania/sq.js`, `git diff --check`. The initial patch had no local PHP/WordPress runtime. The subsequent approved demands release uses protected WordPress staging on the existing host; see `albania-demands-release.md` for its acceptance checks. Review the EN/SQ routes and anchors on protected staging before any separately authorized deployment. No form security guarantees were tested or certified.

Prepared in an isolated temporary clone. The original dirty checkout and its publisher/newsletter files were not changed. GitHub open-PR search returned none during preparation. No merge or publication is part of this patch.
