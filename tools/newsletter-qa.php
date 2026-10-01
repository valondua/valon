<?php
if (wp_get_environment_type() !== 'local') WP_CLI::error('Local only.');
foreach (['en', 'sq', 'de'] as $lang) {
    $html = vp_newsletter_hosted_form('popup', $lang);
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($doc);
    $form = $doc->getElementsByTagName('form')->item(0);
    $action = $form->getAttribute('action');
    parse_str(parse_url($action, PHP_URL_QUERY), $query);
    $checks = [
        'native POST to verified audience' => $form->getAttribute('method') === 'post' && parse_url($action, PHP_URL_HOST) === 'valonasani.us1.list-manage.com' && $query['id'] === '293f2ca94d',
        'required email stays out of action URL' => $xpath->query('//input[@name="EMAIL" and @type="email" and @required]')->length === 1 && !isset($query['EMAIL']),
        'unchecked explicit consent' => $xpath->query('//input[@type="checkbox" and @required and not(@checked)]')->length === 1,
        'honeypot cannot receive keyboard focus' => $xpath->query('//input[@name="b_c37037a826fe84a19f2ad26d1_293f2ca94d" and @tabindex="-1"]')->length === 1,
        'hosted form bypasses REST' => $form->getAttribute('data-hosted') === 'true',
        'language and placement retained' => $form->getAttribute('data-lang') === $lang && $form->getAttribute('data-placement') === 'popup',
    ];
    foreach ($checks as $label => $passed) {
        if (!$passed) WP_CLI::error("$lang: $label");
        WP_CLI::line("PASS $lang: $label");
    }
}
WP_CLI::success('18 newsletter assertions passed; no HTTP requests or emails.');
