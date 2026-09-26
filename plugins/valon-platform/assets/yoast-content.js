/* global YoastSEO, jQuery, valonYoastTemplate */
(function () {
    let registered = false;
    function register() {
        if (registered || !window.YoastSEO?.app || !window.valonYoastTemplate) return;
        registered = true;
        YoastSEO.app.registerPlugin('ValonTemplateContent', { status: 'ready' });
        YoastSEO.app.registerModification('content', function (editorContent) {
            const { html, slot, emptyEditorFallback } = valonYoastTemplate;
            const content = editorContent.trim() ? editorContent : (emptyEditorFallback || '');
            return slot ? html.replace(slot, () => content) : html;
        }, 'ValonTemplateContent', 100);
    }
    jQuery(window).on('YoastSEO:ready', register);
    register();
}());
