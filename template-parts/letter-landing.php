<?php
defined("ABSPATH") || exit();
$t = fn($en, $sq, $de) => valon_localized(compact("en", "sq", "de"));
?>
<div class="letter-landing">
<section class="letter-hero" aria-labelledby="letter-title">
<div class="letter-hero-copy">
<p class="eyebrow"><?php echo esc_html(
    $t("Letters from Valon", "Letra nga Valoni", "Briefe von Valon"),
); ?></p>
<h1 id="letter-title"><?php echo esc_html(
    $t(
        "A little less scrolling. A little more living.",
        "Pak ma pak scroll. Pak ma shumë jetë.",
        "Weniger scrollen. Mehr leben.",
    ),
); ?></h1>
<p class="letter-intro"><?php echo esc_html(
    $t(
        "Honest thoughts on relationships, building things, and becoming the person you want to be. From my life to your inbox.",
        "Mendime të sinqerta për lidhjet, për me ndërtu diçka dhe për me u ba njeriu që don me qenë. Prej jetës tem, drejt në emailin tand.",
        "Ehrliche Gedanken über Beziehungen, eigene Projekte und den Menschen, der du werden möchtest. Aus meinem Leben direkt in dein Postfach.",
    ),
); ?></p>
<div id="join-letters" class="letter-signup"><?php valon_newsletter(
    "landing",
); ?></div>
<p class="letter-promise"><?php echo esc_html(
    $t(
        "Free letters. Every two weeks. Leave whenever you like.",
        "Letra falas. Çdo dy javë. Largohu kur të duash.",
        "Kostenlos. Alle zwei Wochen. Jederzeit abmelden.",
    ),
); ?></p>
<a class="letter-read-link" href="#inside-letters"><?php echo esc_html(
    $t("What will I get?", "Çka kam me marrë?", "Was erwartet mich?"),
); ?> ↓</a>
</div>
<figure class="letter-portrait"><img src="<?php echo esc_url(
    valon_asset_url("assets/valon-reading.jpeg"),
); ?>" alt="Valon Asani" width="1586" height="1983" fetchpriority="high"><figcaption><?php echo esc_html(
    $t(
        "Still learning. Still building. — Valon",
        "Ende tue mësu. Ende tue ndërtu. — Valoni",
        "Immer am Lernen. Immer am Aufbauen. — Valon",
    ),
); ?></figcaption></figure>
</section>
<section class="section letter-inside" id="inside-letters">
<div class="section-heading"><div><p class="eyebrow"><?php echo esc_html(
    $t(
        "A letter worth opening",
        "Një letër që ia vlen me e hapë",
        "Ein Brief, den du gerne öffnest",
    ),
); ?></p><h2><?php echo esc_html(
    $t(
        "Something to think about. Something to try.",
        "Diçka me mendu. Diçka me provu.",
        "Ein Gedanke. Ein nächster Schritt.",
    ),
); ?></h2></div></div>
<div class="letter-benefits">
<?php
$benefits = [
    [
        "01",
        $t("Live with intention", "Jeto me qëllim", "Bewusster leben"),
        $t(
            "Relationships, self-respect and the questions we often avoid. Space to reflect on what matters to you.",
            "Lidhjet, respekti për veten dhe pyetjet që shpesh i shmangim. Hapësirë me mendu për atë që ka rëndësi për ty.",
            "Beziehungen, Selbstachtung und die Fragen, denen wir oft ausweichen. Raum für das, was dir wichtig ist.",
        ),
    ],
    [
        "02",
        $t(
            "Build something real",
            "Ndërto diçka të vërtetë",
            "Etwas Eigenes aufbauen",
        ),
        $t(
            "Notes from building companies, exploring AI and learning through doing. Ideas you can put to work.",
            "Shënime prej ndërtimit të kompanive, eksplorimit të AI-së dhe mësimit përmes veprimit. Ide që mundesh me i përdorë.",
            "Notizen aus dem Unternehmensalltag, Gedanken zu KI und Erfahrungen beim Ausprobieren. Ideen zum Anwenden.",
        ),
    ],
    [
        "03",
        $t("Keep your curiosity", "Ruaje kureshtjen", "Neugierig bleiben"),
        $t(
            "Books, personal experiments and life between Kosovo and Switzerland. A reason to see things differently.",
            "Libra, eksperimente personale dhe jeta mes Kosovës e Zvicrës. Një arsye me i pa gjanat ndryshe.",
            "Bücher, persönliche Experimente und das Leben zwischen dem Kosovo und der Schweiz. Neue Perspektiven für deinen Alltag.",
        ),
    ],
];
foreach ($benefits as [$number, $title, $body]): ?>
<article><span class="letter-number"><?php echo esc_html(
    $number,
); ?></span><h3><?php echo esc_html($title); ?></h3><p><?php echo esc_html(
    $body,
); ?></p></article>
<?php endforeach;
?>
</div></section>
<section class="letter-personal">
<p class="eyebrow"><?php echo esc_html(
    $t("Why I write", "Pse shkruaj", "Warum ich schreibe"),
); ?></p>
<h2><?php echo esc_html(
    $t(
        "The conversation can go deeper.",
        "Biseda mundet me shku ma thellë.",
        "Das Gespräch darf tiefer gehen.",
    ),
); ?></h2>
<p><?php echo esc_html(
    $t(
        "A short video can start a conversation. A letter gives it room to breathe. This is where I want to share the ideas behind the posts, the questions I am sitting with, and what I am learning along the way.",
        "Një video e shkurtë mundet me e nisë një bisedë. Një letër i jep hapësirë. Këtu dua me i nda idetë prapa postimeve, pyetjet që po i mendoj dhe atë që po e mësoj gjatë rrugës.",
        "Ein kurzes Video kann ein Gespräch anstossen. Ein Brief gibt ihm mehr Raum. Hier möchte ich die Gedanken hinter meinen Beiträgen teilen, die Fragen, die mich beschäftigen, und das, was ich unterwegs lerne.",
    ),
); ?></p>
<p class="letter-signature">Valon</p>
<a class="button" href="#join-letters"><?php echo esc_html(
    $t(
        "Join Letters from Valon",
        "Bashkohu me Letrat nga Valoni",
        "Briefe von Valon abonnieren",
    ),
); ?> ↗</a>
</section>
<section class="letter-faq section" aria-labelledby="letter-faq-title"><h2 id="letter-faq-title"><?php echo esc_html(
    $t("Before you join", "Para se me u bashku", "Bevor du dich anmeldest"),
); ?></h2>
<?php
$faq = [
    [
        $t(
            "How often will you write?",
            "Sa shpesh ke me shkru?",
            "Wie oft schreibst du?",
        ),
        $t(
            "The plan is one letter every two weeks. A thoughtful read with room to put an idea into practice.",
            "Plani është një letër çdo dy javë. Një lexim me kuptim dhe kohë me e provu një ide në praktikë.",
            "Geplant ist ein Brief alle zwei Wochen. Mit Gedanken zum Lesen und Zeit, eine Idee auszuprobieren.",
        ),
    ],
    [
        $t("Does it cost anything?", "A kushton diçka?", "Kostet das etwas?"),
        $t(
            "The letters are free. You can unsubscribe using the link in any email.",
            "Letrat janë falas. Mundesh me u çregjistru përmes linkut në secilin email.",
            "Die Briefe sind kostenlos. Du kannst dich über den Link in jeder E-Mail abmelden.",
        ),
    ],
    [
        $t(
            "Can I read more first?",
            "A mundem me lexu ma shumë fillimisht?",
            "Kann ich zuerst mehr lesen?",
        ),
        $t(
            "Of course. Explore the writing archive to get a feel for my voice and the topics I care about.",
            "Po. Shfleto arkivin e shkrimeve me e njoftë ma mirë zërin tem dhe temat që më interesojnë.",
            "Natürlich. Im Schreibarchiv bekommst du ein Gefühl für meine Stimme und die Themen, die mich beschäftigen.",
        ),
    ],
];
foreach ($faq as [$question, $answer]): ?><details><summary><?php echo esc_html(
    $question,
); ?></summary><p><?php echo esc_html(
    $answer,
); ?></p></details><?php endforeach;
?>
<a class="text-link" href="<?php echo esc_url(
    valon_url("writing"),
); ?>"><?php echo esc_html(
    $t("Explore my writing", "Shfleto shkrimet e mia", "Meine Texte entdecken"),
); ?> ↗</a>
</section>
</div>
