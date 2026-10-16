# Article voice workspace

Run `python3 tools/article-voice/server.py`, then open http://localhost:8768 in Chrome.
The library is the 414-article snapshot in `.local/article-voice/articles.json`.
Notes autosave to `.local/article-voice/ARTICLE_ID.json`; ready notes have `status: ready`.
Codex can read these files and use the author’s instructions to expand the matching articles.
No publishing or AI rewriting happens inside this form. The original article snapshot stays intact.
SpeechRecognition requires browser support, microphone permission and usually an internet connection.
English, Albanian and German availability depends on the browser’s speech service. Mac Dictation works in the textarea as a fallback.
Notes are private to this Mac (loopback server). Export downloads a portable JSON backup.
Optimistic revisions reject stale tabs; browser localStorage keeps unsaved recovery copies.
