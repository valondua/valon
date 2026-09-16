# Source-specific article covers

Theme 2.4.3 replaces the four reused portraits on the 61 published TikTok source groups with 61 distinct original-post thumbnails. English, Albanian and German editions resolve by `_vp_video_id`, so they share the correct source cover. Explicit WordPress featured images retain priority. Legacy article covers are unchanged.

`assets/video-covers.json` records the source URL, local image and actual file dimensions. The thumbnails were retrieved through TikTok's public oEmbed endpoint, with the owner profile verified; TikTok's player uses `/video/{id}` links for photo posts too. Original photo URLs remain in the manifest and article links. Images are stored locally because signed CDN URLs expire. No third-party request runs during article rendering. Existing contain sizing preserves captions and complete photo compositions.

For every future source group, obtain its original-post thumbnail before publication, save it under `assets/video-covers/{source-id}.jpg`, verify the actual image dimensions and add its mapping. Do not reuse generic portraits across new video batches. An explicitly selected WordPress featured image overrides the manifest. Keep retrieval logs and signed URLs private under ignored `review/`; the public manifest contains only public source URLs and local asset paths. If a source is removed or made private, review/remove its stored thumbnail as well.

Validation: `tools/video-cover-qa.php` checks real dimensions, distinct image hashes, and article resolution/rendering against the local WordPress dataset. `tools/editorial-qa.php` covers featured/legacy image and multilingual regressions. Check the public three-language editions and grid after deployment.

Rollback: revert the theme change and redeploy the previous main revision `8e28500d4ba9cc0eb820bc22973211c1bb7f8524`. This release makes no database, plugin, article-text or publication-status changes.
