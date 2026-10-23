<?php defined('ABSPATH') || exit; ?>
<form id="investigation-submission" class="tip-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-token-url="<?php echo esc_url(admin_url('admin-post.php?action=valon_tip_token')); ?>" aria-describedby="tip-privacy">
    <input type="hidden" name="action" value="valon_tip_submit">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="language" value="<?php echo valon_albania_is_sq()?'sq':'en'; ?>">
    <div class="tip-honeypot" aria-hidden="true"><label>Leave this empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <label for="tip-category">What would you like to share? <span>(required)</span></label><select id="tip-category" name="category" required><option value="">Choose a type</option><option value="information">Information or documents</option><option value="injustice">A case of injustice</option><option value="correction">A correction</option></select>
    <label for="tip-message">Tell me what happened <span>(required)</span></label>
    <p class="field-help" id="tip-message-help">Include where and when it happened, what you know directly, and any source links. 20–12,000 characters. Albanian, English and German are welcome.</p>
    <textarea id="tip-message" name="message" rows="7" required minlength="20" maxlength="12000" aria-describedby="tip-message-help"></textarea>
    <div class="tip-upload"><label for="tip-files">Attach photos or documents <span>(optional)</span></label><p id="tip-files-help" class="field-help">Up to 3 files: PDF, TXT, JPG, PNG or WebP. Maximum 5 MB each, 10 MB total. Export Word documents as PDF. Files are kept out of the public media library.</p><input id="tip-files" name="attachments[]" type="file" multiple accept=".pdf,.txt,.jpg,.jpeg,.png,.webp" aria-describedby="tip-files-help tip-file-list"><p id="tip-file-list" class="field-help" aria-live="polite">No files selected.</p></div>
    <div class="tip-grid"><div><label for="tip-name">Your name <span>(optional)</span></label><input id="tip-name" name="name" autocomplete="name" maxlength="120"></div><div><label for="tip-email">Email for follow-up <span>(optional)</span></label><input id="tip-email" name="email" type="email" autocomplete="email" maxlength="254"></div></div>
    <p id="tip-privacy" class="field-help">Your account and files are stored privately and sent to valon@dua.com for Valon’s review, not published automatically. Name and email are optional; this is not a guaranteed anonymous reporting service. Photos and documents may contain identifying metadata. Include only personal details needed to understand the case. To request removal, contact valon@dua.com with your receipt code. <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/privacy/')); ?>">Privacy policy</a>.</p>
    <label class="tip-consent"><input type="checkbox" name="consent" value="1" required><span>I agree that Valon may store and review this information and its attachments for the investigation.</span></label>
    <p id="tip-status" role="status" aria-live="polite" tabindex="-1">Preparing the submission form…</p>
    <button class="submit-email" id="tip-submit" type="submit" disabled>Send for review →</button>
    <noscript><p>This form needs JavaScript to upload safely and confirm receipt. Enable JavaScript, or contact <a href="mailto:valon@dua.com">valon@dua.com</a>.</p></noscript>
</form>
