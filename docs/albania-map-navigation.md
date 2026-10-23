# Map and persistent section navigation — 24 October 2026

The map's explicit load button worked, but location controls used no-op view changes before loading and the initial display was an empty 460px container. This release lets all three location controls initialize the map and select their view. The empty canvas stays hidden until requested. Loading status, a 15-second failure fallback, retry, and static OpenStreetMap links provide a usable result when tiles or the library fail. Map loading remains a deliberate visitor action; it is not triggered by page load. Wheel zoom is disabled to preserve story scrolling, while map zoom buttons and touch controls remain available.

A separate navigation/map script keeps these controls independent of errors in other investigation widgets. Map popups and status messages are translated. The existing Leaflet library and same OpenStreetMap endpoint are retained.

The section navigation is sticky beneath the measured masthead. Desktop retains the horizontal links plus an all-sections selector; mobile uses the compact native selector to expose every section without a large menu covering the story. Without JavaScript the sticky horizontal anchor links remain usable. Anchor offsets include masthead, section-menu and WordPress admin-bar heights. The submit-information action remains in the masthead.

Design reference: https://www.lazyweb.com/agentic-search/2f29fa92-f227-4a05-8f16-1ecdf1101d0c . Adapted the long-form section-navigation pattern to the existing editorial design.

Application-only deployment. Before-images are outside the document root at /data/private-albania-form-release-20261024/map-navigation-before. No research values, database, mail or uploads changes.

Validation: protected staging rendered English and Albanian with 55 research records and 37 events preserved. Desktop tests loaded the coastal map directly from its filter before using the load button, switched to Tirana, opened Parliament marker details, and repeated All sites without failure. Albanian mobile testing at 390px loaded map tiles, confirmed translated status, no horizontal overflow, and navigation to Sources with its heading below the sticky menu (150px vs menu bottom 134px). The header and navigation edges align at both widths. A missing-library failure test confirmed the map remains hidden, an accessible error appears and retry remains usable. JavaScript syntax, PHP upload lint and diff checks passed.

Concurrent PR #59 was merged before publication. Its Demands content, navigation link and bilingual accuracy changes are preserved. All eight accuracy tests pass. The combined Albanian mobile page has 16 selector options (placeholder plus every link), aligned header/menu edges at 98.5px, menu bottom 155.5px and Demands target at 171.5px. Tirana tiles load successfully. The combined before-image snapshot is map-navigation-combined-before in the same protected backup directory.
