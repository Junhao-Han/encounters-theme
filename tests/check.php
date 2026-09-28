<?php

/** Run inside an OJS checkout; no database or journal settings are changed. */

$pluginRoot = dirname(__DIR__);
$ojsRoot = dirname($pluginRoot, 3);
define('PKP_STRICT_MODE', true);
require $ojsRoot . '/lib/pkp/lib/vendor/autoload.php';
$plugin = require $pluginRoot . '/index.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Check metadata using OJS's actual plugin DTD.
$xml = new DOMDocument();
$xml->load($pluginRoot . '/version.xml');
check($xml->validate(), 'Plugin metadata does not validate');
check($xml->getElementsByTagName('application')[0]->textContent === 'encounters', 'Incorrect installation directory');

// Use OJS's PHP LESS compiler, not only the npm compiler.
$less = new Less_Parser();
$less->parse('@baseUrl: "";');
$less->parseFile($pluginRoot . '/styles/index.less');
$css = $less->getCss();
check(str_contains($css, '.encounters-issue-grid'), 'Homepage styles missing');
foreach (Less_Parser::AllParsedFiles() as $import) {
    check(!str_contains(str_replace('\\', '/', $import), '/themes/default/'), 'Stylesheet still imports the Default Theme');
}
check(str_contains($css, '.obj_article_details'), 'Core article baseline styles missing');
check(str_contains($css, '.page_login'), 'Core login baseline styles missing');
check(!str_contains($css, '/plugins/themes/default/'), 'Compiled CSS references parent-theme assets');

$translationLoader = new Gettext\Loader\PoLoader();
$translations = $translationLoader->loadFile($pluginRoot . '/locale/en/locale.po');
$catalogs = [];
foreach (['en', 'es', 'fr'] as $locale) {
    $files = array_merge(glob($ojsRoot . '/lib/pkp/locale/' . $locale . '/*.po'), glob($ojsRoot . '/locale/' . $locale . '/*.po'), [$pluginRoot . '/locale/' . $locale . '/locale.po']);
    foreach ($files as $file) {
        foreach ($translationLoader->loadFile($file) as $translation) {
            if ($translation->getTranslation() !== '') {
                $catalogs[$locale][$translation->getOriginal()] = $translation->getTranslation();
            }
        }
    }
}
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginRoot . '/templates', FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->getExtension() !== 'tpl') continue;
    preg_match_all('/\{translate\b[^}]*\bkey=["\']([^"\']+)["\']/', file_get_contents($file->getPathname()), $keys);
    foreach ($keys[1] as $key) {
        foreach (['en', 'es', 'fr'] as $locale) {
            check(isset($catalogs[$locale][$key]), 'Missing frontend translation in ' . $locale . ': ' . $key);
        }
    }
}
foreach (['fr', 'es'] as $locale) {
    $localized = $translationLoader->loadFile($pluginRoot . '/locale/' . $locale . '/locale.po');
    foreach ($translations as $translation) {
        $message = $localized->find(null, $translation->getOriginal())?->getTranslation();
        check(!empty($message), 'Missing ' . $locale . ' translation: ' . $translation->getOriginal());
        preg_match_all('/\{\$\w+\}/', $translation->getTranslation(), $placeholders);
        foreach ($placeholders[0] as $placeholder) {
            check(str_contains($message, $placeholder), 'Missing translation placeholder: ' . $placeholder);
        }
    }
}

$temporary = sys_get_temp_dir() . '/encounters-check-' . bin2hex(random_bytes(5));
mkdir($temporary . '/fixtures/frontend/components', 0700, true);
mkdir($temporary . '/compile', 0700, true);
file_put_contents($temporary . '/fixtures/frontend/components/headerHead.tpl', '<head><title>{$pageTitleTranslated|escape}</title></head>');

class FixtureJournal
{
    public function getLocalizedName(): string
    {
        return 'Encounters & Education';
    }
}

class FixtureTheme extends \APP\plugins\themes\encounters\EncountersThemePlugin
{
    public function __construct(private string $aboutMenu = 'default', private array $overrides = []) {}
    public function getOption($name): string
    {
        if ($name === 'aboutMenu') return $this->aboutMenu;
        return $this->overrides[$name] ?? match ($name) {
            'mastheadTitle' => 'Encounters',
            'mastheadTagline' => 'Education & Humanities',
            'mastheadTaglineEs' => 'Educación <Humanidades>',
            'mastheadTaglineFr' => 'Éducation & Humanités',
            'introductionDescription' => "Education & Humanities\n<script>bad</script>",
            'introductionDescriptionEs' => "Educación & Humanidades\n<script>bad</script>",
            'introductionDescriptionFr' => "Éducation & Humanités\n<script>bad</script>",
            'monographDescription' => 'History & <education>',
            'monographDescriptionEs' => 'Historia & <educación>',
            'monographDescriptionFr' => 'Histoire & <éducation>',
            default => '',
        };
    }
}

class FixtureAnnouncement
{
    public int $id = 17;
    public function getLocalizedData(string $name): string
    {
        return $name === 'title' ? 'Call for papers & <script>bad</script>' : '<p>Send your <strong>proposal</strong>.</p>';
    }
}

class FixtureIssue
{
    public function __construct(private string $id, private string $title, private string $cover = '') {}
    public function getBestIssueId(): string { return $this->id; }
    public function getLocalizedCoverImageUrl(): string { return $this->cover; }
    public function getLocalizedCoverImageAltText(): string { return 'Cover & artwork'; }
    public function getIssueIdentification(): string { return $this->title; }
}

class FixtureMenuItem extends \PKP\navigationMenu\NavigationMenuItem
{
    public function __construct(private string $title, private bool $visible = true, private bool $children = false, private string $url = '/about?x=1&y=2')
    {
        $this->setType(self::NMI_TYPE_CUSTOM);
    }
    public function getLocalizedTitle(): string { return $this->title; }
    public function getUrl(): string { return $this->url; }
    public function getIsDisplayed(): bool { return $this->visible; }
    public function getIsChildVisible(): bool { return $this->children; }
}

class FixtureHighlight
{
    public function getImage(): bool { return false; }
    public function getLocalizedTitle(): string { return 'Featured publication'; }
    public function getLocalizedDescription(): string { return '<p>Public highlight.</p>'; }
    public function getUrl(): string { return '/highlights?x=1&y=2'; }
    public function getLocalizedUrlText(): string { return 'Read more'; }
}

try {
    $smarty = new Smarty();
    $smarty->setCompileDir($temporary . '/compile');
    $smarty->setTemplateDir([
        $temporary . '/fixtures',
        $pluginRoot . '/templates',
        $ojsRoot . '/templates',
        $ojsRoot . '/lib/pkp/templates',
    ]);
    $smarty->error_reporting = E_ALL & ~E_DEPRECATED;
    $smarty->registerPlugin('function', 'translate', static function (array $params) use ($catalogs, $smarty): string {
        $locale = substr($smarty->getTemplateVars('currentLocale') ?? 'en', 0, 2);
        $message = $catalogs[$locale][$params['key']] ?? $catalogs['en'][$params['key']] ?? $params['key'];
        foreach ($params as $key => $value) {
            if ($key !== 'key') {
                $message = str_replace('{$' . $key . '}', (string) $value, $message);
            }
        }
        return $message;
    });
    $smarty->registerPlugin('function', 'url', static function (array $params): string {
        $parts = [$params['page'] ?? 'index', $params['op'] ?? 'index'];
        if (isset($params['path'])) {
            array_push($parts, ...(array) $params['path']);
        }
        return '/index.php/encounters/en/' . implode('/', array_map('rawurlencode', $parts));
    });
    $smarty->registerPlugin('function', 'load_menu', static fn (array $params): string => '<ul id="' . htmlspecialchars($params['id']) . '"><li><a href="/about">About</a></li></ul>');
    $smarty->registerPlugin('function', 'load_script', static fn (): string => '');
    $smarty->registerPlugin('function', 'call_hook', static fn (): string => '');
    $smarty->registerPlugin('modifier', 'intval', intval(...));
    $smarty->registerPlugin('modifier', 'date_format', static fn ($date, $format): string => (new \Carbon\Carbon($date))->locale('en')->translatedFormat($format));
    // OJS's sanitization is outside this fixture test; use trusted sample HTML.
    $smarty->registerPlugin('modifier', 'strip_unsafe_html', static fn (string $html): string => $html);

    $journal = new FixtureJournal();
    $issue = new FixtureIssue('vol-3', 'Issue <script>bad</script>', '/cover.png?a=1&b=2');
    $smarty->assign([
        'currentJournal' => $journal, 'currentContext' => $journal,
        'activeTheme' => new FixtureTheme(), 'currentLocale' => 'en', 'currentLocaleLangDir' => 'ltr',
        'encountersLocales' => ['en' => 'English', 'fr' => 'Français'],
        'encountersHeroTitles' => [
            'en' => 'Encounters & Education',
            'es' => 'Encuentros en Educación',
            'fr' => 'Rencontres <script>bad</script>',
        ],
        'encountersThemeUrl' => '/plugins/themes/encounters',
        'requestedPage' => 'index', 'requestedOp' => 'index',
        'displayPageHeaderLogo' => null, 'displayPageHeaderTitle' => $journal->getLocalizedName(),
        'siteTitle' => 'Test site', 'pageTitleTranslated' => '', 'pageTitle' => '',
        'publicFilesDir' => '/public/journals/1', 'hasSidebar' => false, 'isFullWidth' => false,
        'homepageImage' => ['uploadName' => 'hero.jpg', 'altText' => 'Journal & campus', 'width' => 360, 'height' => 240],
        'encountersHeroDescription' => "Education & Humanities\n<script>bad</script>",
        'encountersCurrentIssue' => $issue,
        'encountersHeroIssue' => $issue,
        'encountersRecentIssues' => [$issue, new FixtureIssue('vol-2', 'Issue II')],
        'encountersShowIssues' => true,
        'encountersRecentArticles' => [
            ['title' => 'Teaching & <script>bad</script>', 'path' => 'teaching-and-learning', 'date' => '2026-09-09'],
            ['title' => 'Learning without a recorded date', 'path' => 42, 'date' => null],
        ],
        'dateFormatLong' => 'F j, Y',
        'encountersShowAnnouncements' => true, 'encountersAnnouncements' => [new FixtureAnnouncement()],
        'encountersMonographUrl' => 'https://example.org/series?a=1&b=2',
        'encountersMonographDescription' => 'History & <script>bad</script>',
        'highlights' => new ArrayObject(), 'numAnnouncementsHomepage' => 1,
        'announcements' => [], 'additionalHomeContent' => '',
        'pageFooter' => '', 'baseUrl' => '', 'brandImage' => 'ojs.svg',
    ]);
    $html = $smarty->fetch('frontend/pages/indexJournal.tpl');
    check(str_contains($html, 'href="#homepageIssue"') && str_contains($html, 'id="homepageIssue"'), 'Current issue skip link has no destination');
    check(str_contains($html, 'href="#homepageAnnouncements"') && str_contains($html, 'id="homepageAnnouncements"'), 'Announcement skip link has no destination');
    check(str_contains($html, '/issue/view/vol-3'), 'Issue card or current-issue URL is incorrect');
    check(str_contains($html, '/issue/archive'), 'Archive URL is incorrect');
    check(str_contains($html, 'Issue &lt;script&gt;bad&lt;/script&gt;'), 'Issue title is not escaped');
    check(!str_contains($html, '<script>bad</script>'), 'Unsafe title entered HTML');
    check(substr_count($html, 'class="encounters-issue-card"') === 2, 'Issue list is not rendered from supplied data');
    check(str_contains($html, 'encounters-cover-fallback'), 'Missing-cover fallback is absent');
    check(str_contains($html, '/user/setLocale/fr'), 'Language route missing');
    check(str_contains($html, 'Recent Issues'), 'Theme translation not loaded');
    check(str_contains($html, 'lang="es" dir="auto">Encuentros en Educación'), 'Translated introduction title or language is missing');
    check(str_contains($html, 'Rencontres &lt;script&gt;bad&lt;/script&gt;'), 'Introduction title is not escaped');
    check(str_contains($html, '<a class="encounters-hero-cover" href="/index.php/encounters/en/issue/view/vol-3">'), 'Hero cover does not link to its issue');
    check(str_contains($html, 'class="encounters-hero-image" src="/public/journals/1/hero.jpg" alt="Journal &amp; campus" width="360" height="240"'), 'Uploaded hero image or alternative text is missing or unsafe');
    check(str_contains($html, "Education &amp; Humanities<br />\n&lt;script&gt;bad&lt;/script&gt;"), 'Introduction text is not escaped or line breaks are missing');
    check(str_contains($html, 'Forthcoming Articles'), 'Article section heading is missing');
    check(str_contains($html, 'Teaching &amp; &lt;script&gt;bad&lt;/script&gt;'), 'Article title is not escaped');
    check(str_contains($html, '/article/view/teaching-and-learning') && str_contains($html, '/article/view/42'), 'Article links do not support URL paths and numeric IDs');
    check(str_contains($html, 'datetime="2026-09-09">September 9, 2026</time>'), 'Publication date is not formatted');
    check(substr_count($html, 'class="encounters-article-date"') === 1, 'A missing publication date leaves a date label');
    check(str_contains($html, '/announcement/view/17'), 'Announcement does not link to its public page');
    check(str_contains($html, 'Call for papers &amp; &lt;script&gt;bad&lt;/script&gt;'), 'Announcement title is not escaped');
    check(str_contains($html, '<strong>proposal</strong>'), 'Announcement summary formatting is lost');
    check(str_contains($html, 'https://example.org/series?a=1&amp;b=2'), 'Series URL is not escaped');
    check(str_contains($html, 'History &amp; &lt;script&gt;bad&lt;/script&gt;'), 'Series description is not escaped');

    foreach (['en' => 'History &amp; &lt;education&gt;', 'es' => 'Historia &amp; &lt;educación&gt;', 'fr' => 'Histoire &amp; &lt;éducation&gt;'] as $locale => $description) {
        $smarty->assign(['currentLocale' => $locale, 'encountersMonographDescription' => (new FixtureTheme())->getMonographDescription($locale)]);
        $localizedHome = $smarty->fetch('frontend/pages/indexJournal.tpl');
        check(str_contains($localizedHome, '<p>' . $description . '</p>'), 'Monograph description is not localized or escaped: ' . $locale);
        foreach (['recentIssues', 'recentArticles', 'exploreSeries'] as $key) {
            check(str_contains($localizedHome, $catalogs[$locale]['plugins.themes.encounters.' . $key]), 'Homepage section or button is not localized: ' . $key);
        }
    }
    check((new FixtureTheme(overrides: ['monographDescriptionFr' => '']))->getMonographDescription('fr') === '', 'Empty monograph description falls back to English');
    $smarty->assign(['currentLocale' => 'en', 'encountersMonographDescription' => 'History & <script>bad</script>']);

    $theme = new FixtureTheme();
    foreach (['en' => 'Education &amp; Humanities', 'es' => 'Educación &amp; Humanidades', 'fr' => 'Éducation &amp; Humanités', 'es_MX' => 'Educación &amp; Humanidades', 'fr-CA' => 'Éducation &amp; Humanités', 'de' => 'Education &amp; Humanities'] as $locale => $description) {
        $smarty->assign(['currentLocale' => $locale, 'encountersHeroDescription' => $theme->getIntroductionDescription($locale)]);
        $localizedHome = $smarty->fetch('frontend/pages/indexJournal.tpl');
        check(str_contains($localizedHome, '<div class="encounters-description">' . $description . "<br />\n&lt;script&gt;bad&lt;/script&gt;</div>"), 'Hero introduction does not follow the locale or preserve safe line breaks: ' . $locale);
    }
    $emptyIntroduction = new FixtureTheme(overrides: ['introductionDescriptionFr' => '']);
    $smarty->assign('encountersHeroDescription', $emptyIntroduction->getIntroductionDescription('fr'));
    check(!str_contains($smarty->fetch('frontend/pages/indexJournal.tpl'), 'class="encounters-description"'), 'Empty introduction falls back to another language');
    $smarty->assign(['currentLocale' => 'en', 'encountersHeroDescription' => $theme->getIntroductionDescription('en')]);

    $smarty->assign('homepageImage', ['uploadName' => 'hero image.jpg', 'altText' => '']);
    $imageWithoutAlt = $smarty->fetch('frontend/pages/indexJournal.tpl');
    check(str_contains($imageWithoutAlt, 'src="/public/journals/1/hero%20image.jpg" alt="Issue &lt;script&gt;bad&lt;/script&gt;"'), 'Linked hero image has no accessible issue name');
    $smarty->assign('encountersHeroIssue', null);
    $unlinkedImage = $smarty->fetch('frontend/pages/indexJournal.tpl');
    check(str_contains($unlinkedImage, 'class="encounters-hero-image"') && !str_contains($unlinkedImage, 'class="encounters-hero-cover"'), 'Unlinked hero image is missing or falls back to the current issue');
    $smarty->assign(['homepageImage' => null, 'encountersHeroIssue' => $issue]);
    $missingImage = $smarty->fetch('frontend/pages/indexJournal.tpl');
    check(!str_contains($missingImage, 'class="encounters-hero-image"') && !str_contains($missingImage, 'class="encounters-hero-cover"'), 'Missing homepage image leaves an empty link or uses the issue cover');

    $smarty->assign([
        'encountersHeroIssue' => null, 'encountersCurrentIssue' => null,
        'encountersRecentIssues' => [], 'encountersLocales' => ['en' => 'English'],
        'encountersHeroTitles' => [], 'encountersHeroDescription' => '',
        'encountersRecentArticles' => [], 'encountersAnnouncements' => [],
        'encountersShowAnnouncements' => false, 'encountersMonographUrl' => '',
    ]);
    $empty = $smarty->fetch('frontend/pages/indexJournal.tpl');
    check(!str_contains($empty, 'href="#homepageIssue"') && !str_contains($empty, 'href="#homepageAnnouncements"'), 'Empty homepage contains broken skip links');
    check(!str_contains($empty, 'class="encounters-issue-card"'), 'Empty issue list shows fabricated cards');
    check(!str_contains($empty, 'class="encounters-button"'), 'Current issue link shown without an issue');
    check(!str_contains($empty, '<details'), 'Single-language journal shows unnecessary language selector');
    check(str_contains($empty, 'class="encounters-hero-media"') && !str_contains($empty, 'class="encounters-hero-image"'), 'Empty hero image area is missing or contains an image');
    check(str_contains($empty, 'id="encounters-introduction-heading">Encounters &amp; Education'), 'Introduction title does not fall back to the journal name');
    check(!str_contains($empty, 'class="encounters-description"'), 'Empty journal description leaves an empty block');
    check(str_contains($empty, 'Published issues will appear here.'), 'Empty recent issues message is missing');
    check(str_contains($empty, 'Published articles will appear here.'), 'Empty recent articles message is missing');
    check(!str_contains($empty, 'class="encounters-news"'), 'Disabled announcements and series leave an empty news section');
    $smarty->assign(['encountersShowIssues' => false, 'encountersShowAnnouncements' => true]);
    $noIssues = $smarty->fetch('frontend/pages/indexJournal.tpl');
    check(!str_contains($noIssues, 'id="encounters-recent-heading"'), 'Issues appear when journal publishing is disabled');
    check(str_contains($noIssues, '/announcement/index'), 'Announcement fallback does not link to the announcements page');

    $logo = ['uploadName' => 'logo.png', 'width' => 200, 'height' => 100, 'altText' => 'Journal "logo" & <identity>'];
    $smarty->assign('displayPageHeaderLogo', $logo);
    $header = $smarty->fetch('frontend/components/header.tpl');
    check(str_contains($header, 'alt="Journal &quot;logo&quot; &amp; &lt;identity&gt;"'), 'Configured logo alternative text is missing or unsafe');
    foreach (['', null] as $altText) {
        $logo['altText'] = $altText;
        $smarty->assign('displayPageHeaderLogo', $logo);
        $header = $smarty->fetch('frontend/components/header.tpl');
        check(str_contains($header, 'alt="Encounters &amp; Education"'), 'Logo alternative text does not fall back to the journal name');
    }
    $smarty->assign('displayPageHeaderTitle', '');
    $header = $smarty->fetch('frontend/components/header.tpl');
    check(str_contains($header, 'alt="Test site"'), 'Logo alternative text does not fall back to the site title');
    $smarty->assign(['displayPageHeaderLogo' => null, 'displayPageHeaderTitle' => $journal->getLocalizedName()]);

    foreach (['en' => 'Education &amp; Humanities', 'es' => 'Educación &lt;Humanidades&gt;', 'fr' => 'Éducation &amp; Humanités', 'es_MX' => 'Educación &lt;Humanidades&gt;', 'fr-CA' => 'Éducation &amp; Humanités', 'de' => 'Education &amp; Humanities'] as $locale => $tagline) {
        $smarty->assign(['currentLocale' => $locale, 'requestedPage' => 'about']);
        $header = $smarty->fetch('frontend/components/header.tpl');
        check(str_contains($header, '<span class="encounters-tagline">' . $tagline . '</span>'), 'Header subtitle does not follow the locale or is not escaped: ' . $locale);
    }
    $smarty->assign(['activeTheme' => new FixtureTheme(overrides: ['mastheadTaglineFr' => '']), 'currentLocale' => 'fr']);
    check(!str_contains($smarty->fetch('frontend/components/header.tpl'), 'class="encounters-tagline"'), 'Empty subtitle falls back to another language');
    $smarty->assign(['activeTheme' => new FixtureTheme(), 'currentLocale' => 'en', 'requestedPage' => 'index']);

    $smarty->assign([
        'navigationMenu' => (object) ['menuTree' => [
            (object) [
                'navigationMenuItem' => new FixtureMenuItem('About & Journal', true, true),
                'children' => [
                    (object) ['navigationMenuItem' => new FixtureMenuItem('Editorial board')],
                    (object) ['navigationMenuItem' => new FixtureMenuItem('Private item', false)],
                ],
            ],
        ]],
        'id' => 'navigationPrimary', 'ulClass' => 'pkp_navigation_primary', 'liClass' => '',
    ]);
    $navigation = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($navigation, 'aria-controls="navigationPrimary-submenu-1"'), 'Disclosure control is not linked to its submenu');
    check(str_contains($navigation, 'href="/about?x=1&amp;y=2"'), 'Navigation URL is not escaped');
    check(str_contains($navigation, 'Show submenu: About &amp; Journal'), 'Accessible submenu label is missing');
    check(!str_contains($navigation, 'Private item'), 'Hidden navigation item is displayed');
    check(!str_contains($navigation, 'class="task_count"'), 'Regular menu item has a notification badge');

    $aboutItem = new FixtureMenuItem('About & Journal', true, true);
    $aboutItem->setType(\PKP\navigationMenu\NavigationMenuItem::NMI_TYPE_ABOUT);
    $aboutMenu = (object) ['menuTree' => [(object) [
        'navigationMenuItem' => $aboutItem,
        'children' => [(object) ['navigationMenuItem' => new FixtureMenuItem('Custom child')]],
    ]]];
    $smarty->assign('navigationMenu', $aboutMenu);
    $defaultAbout = $smarty->fetch('frontend/components/navigationMenu.tpl');
    $menuDom = new DOMDocument();
    $menuDom->loadHTML($defaultAbout);
    $menuXPath = new DOMXPath($menuDom);
    $defaultLinks = $menuXPath->query('//ul[@id="navigationPrimary-submenu-1"]/li/a');
    $labels = [];
    $paths = [];
    foreach ($defaultLinks as $link) {
        $labels[] = $link->textContent;
        $paths[] = $link->getAttribute('href');
    }
    check($labels === ['About the Journal', 'Editorial Team', 'Submissions', 'Contact'], 'Default About menu labels or order are incorrect');
    check($paths === array_map(fn ($op) => '/index.php/encounters/en/about/' . $op, ['index', 'editorialMasthead', 'submissions', 'contact']), 'Default About links are incorrect');
    check(!str_contains($defaultAbout, 'Custom child'), 'Default About menu duplicates configured children');
    $smarty->assign('activeTheme', new FixtureTheme('custom'));
    $customAbout = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($customAbout, 'Custom child') && !str_contains($customAbout, 'Editorial Team'), 'Custom About menu is overridden');
    $smarty->assign(['activeTheme' => new FixtureTheme(), 'id' => 'navigationUser']);
    $userAbout = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($userAbout, 'Custom child') && !str_contains($userAbout, 'Editorial Team'), 'Default About menu affects the user menu');
    $smarty->assign(['id' => 'navigationPrimary', 'currentJournal' => null]);
    $siteAbout = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($siteAbout, 'Custom child') && !str_contains($siteAbout, 'Editorial Team'), 'Default About menu affects the site menu');
    $smarty->assign('currentJournal', $journal);
    $navigationKeys = ['navigation.about', 'about.submissions', 'navigation.current', 'navigation.archives', 'manager.announcements', 'navigation.login', 'navigation.register', 'navigation.dashboard', 'common.viewProfile', 'navigation.admin', 'user.logOut'];
    $localizedItems = [];
    foreach ($navigationKeys as $key) {
        $item = new FixtureMenuItem($catalogs['en'][$key], children: true);
        $item->setTitleLocaleKey($key);
        $item->setTitle($catalogs['en'][$key], 'en');
        $localizedItems[] = (object) ['navigationMenuItem' => $item, 'children' => [(object) ['navigationMenuItem' => clone $item]]];
    }
    $series = new FixtureMenuItem('Monograph Series', url: 'https://example.org/series/');
    $series->setType(\PKP\navigationMenu\NavigationMenuItem::NMI_TYPE_REMOTE_URL);
    foreach (['en', 'es', 'fr'] as $locale) $series->setTitle('Monograph Series', $locale);
    $localizedItems[] = (object) ['navigationMenuItem' => $series, 'children' => []];
    $smarty->assign(['activeTheme' => new FixtureTheme(overrides: ['monographUrl' => 'https://example.org/series']), 'navigationMenu' => (object) ['menuTree' => $localizedItems]]);
    foreach (['en', 'es', 'fr'] as $locale) {
        $smarty->assign('currentLocale', $locale);
        $localizedMenu = $smarty->fetch('frontend/components/navigationMenu.tpl');
        foreach ($navigationKeys as $key) {
            $label = htmlspecialchars($catalogs[$locale][$key], ENT_QUOTES);
            check(substr_count($localizedMenu, $label) >= 2, 'Navigation or child label is not localized with English-only form titles: ' . $locale . ' ' . $key);
        }
        check(str_contains($localizedMenu, $catalogs[$locale]['plugins.themes.encounters.monographSeries']), 'Monograph Series menu label is not localized');
    }
    $customTitle = new FixtureMenuItem('Notre équipe & <staff>');
    $customTitle->setTitleLocaleKey('navigation.about');
    $customTitle->setTitle('Notre équipe & <staff>', 'fr');
    $username = new FixtureMenuItem('junhao');
    $username->setTitleLocaleKey('{$loggedInUsername}');
    $unrelated = new FixtureMenuItem('Monograph Series', url: 'https://example.org/unrelated');
    $unrelated->setType(\PKP\navigationMenu\NavigationMenuItem::NMI_TYPE_REMOTE_URL);
    $smarty->assign(['currentLocale' => 'fr', 'navigationMenu' => (object) ['menuTree' => array_map(fn ($item) => (object) ['navigationMenuItem' => $item, 'children' => []], [$customTitle, $username, $unrelated])]]);
    $customMenu = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($customMenu, 'Notre équipe &amp; &lt;staff&gt;') && str_contains($customMenu, 'junhao') && str_contains($customMenu, 'Monograph Series'), 'Custom translations, usernames or unrelated links are overridden');
    $smarty->assign(['currentLocale' => 'en', 'activeTheme' => new FixtureTheme(), 'navigationMenu' => $aboutMenu]);
    $aboutMenu->menuTree[0]->navigationMenuItem = new FixtureMenuItem('About without children');
    $aboutMenu->menuTree[0]->navigationMenuItem->setType(\PKP\navigationMenu\NavigationMenuItem::NMI_TYPE_ABOUT);
    $aboutMenu->menuTree[0]->children = [];
    $emptyAbout = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($emptyAbout, 'Editorial Team') && str_contains($emptyAbout, 'data-encounters-submenu'), 'Default About menu needs database children to work');

    // Exercise the fetch hook at the same point where OJS decorates a dashboard title.
    \PKP\plugins\Hook::add('TemplateManager::fetch', $plugin->prepareDashboardMenuItem(...));
    foreach (['es', 'fr'] as $locale) {
        $translatedDashboard = new FixtureMenuItem('Dashboard');
        $translatedDashboard->setTitleLocaleKey('navigation.dashboard');
        $translatedDashboard->setTitle('Dashboard', 'en');
        $smarty->assign(['currentLocale' => $locale, 'navigationMenuItem' => $translatedDashboard, 'unreadNotificationCount' => 3]);
        $result = null;
        \PKP\plugins\Hook::call('TemplateManager::fetch', [$smarty, 'frontend/components/navigationMenus/dashboardMenuItem.tpl', null, null, &$result]);
        $translatedDashboard->setTitle($result, $locale);
        $smarty->assign('navigationMenu', (object) ['menuTree' => [(object) ['navigationMenuItem' => $translatedDashboard, 'children' => []]]]);
        $localizedDashboard = $smarty->fetch('frontend/components/navigationMenu.tpl');
        check(str_contains($localizedDashboard, $catalogs[$locale]['navigation.dashboard']) && str_contains($localizedDashboard, '<span class="task_count">3</span>'), 'Dashboard fragment loses its translation or notification count');
    }
    $smarty->assign('currentLocale', 'en');
    $dashboard = new FixtureMenuItem('Dashboard & "tasks" <img src=x onerror=alert(1)>', true, true);
    $childDashboard = new FixtureMenuItem('Submissions & reviews');
    foreach ([[$dashboard, 3], [$childDashboard, 0]] as [$menuItem, $count]) {
        $smarty->assign(['navigationMenuItem' => $menuItem, 'unreadNotificationCount' => $count]);
        $result = null;
        $handled = \PKP\plugins\Hook::call('TemplateManager::fetch', [
            $smarty, 'frontend/components/navigationMenus/dashboardMenuItem.tpl', null, null, &$result,
        ]);
        check($handled === \PKP\plugins\Hook::ABORT, 'Dashboard fragment is not handled by the theme');
        check($result === $menuItem->getLocalizedTitle(), 'Dashboard title changed or was encoded before navigation rendering');
        check($menuItem->getData('encountersTaskCount') === $count, 'Dashboard notification count was not retained');
    }
    $smarty->assign([
        'navigationMenu' => (object) ['menuTree' => [
            (object) ['navigationMenuItem' => $dashboard, 'children' => [
                (object) ['navigationMenuItem' => $childDashboard],
                (object) ['navigationMenuItem' => new FixtureMenuItem('<script>bad</script> & Custom')],
            ]],
        ]],
    ]);
    $navigation = $smarty->fetch('frontend/components/navigationMenu.tpl');
    check(str_contains($navigation, '<span class="task_count">3</span>'), 'Top-level dashboard notification badge is missing');
    check(str_contains($navigation, '<span class="task_count">0</span>'), 'Child dashboard zero-count badge is missing');
    check(substr_count($navigation, 'class="task_count"') === 2, 'Notification badge was duplicated or added to a regular item');
    check(!str_contains($navigation, '&lt;span'), 'Dashboard badge markup is displayed as text');
    check(str_contains($navigation, 'Dashboard &amp; &quot;tasks&quot; &lt;img src=x onerror=alert(1)&gt;'), 'Dashboard title is not escaped exactly once');
    check(str_contains($navigation, '&lt;script&gt;bad&lt;/script&gt; &amp; Custom'), 'Custom menu title is not escaped');
    check(!preg_match('/<img\b[^>]*\bonerror\s*=/i', $navigation) && !str_contains($navigation, '<script'), 'Menu title injected an HTML element');
    check(str_contains($navigation, 'aria-label="Show submenu: Dashboard &amp; &quot;tasks&quot; &lt;img src=x onerror=alert(1)&gt;"'), 'Dashboard submenu label includes markup or incorrect escaping');

    $result = 'unchanged';
    check(\PKP\plugins\Hook::call('TemplateManager::fetch', [
        $smarty, 'frontend/components/header.tpl', null, null, &$result,
    ]) === \PKP\plugins\Hook::CONTINUE && $result === 'unchanged', 'Dashboard hook intercepts unrelated templates');
    $smarty->assign('navigationMenuItem', null);
    check(\PKP\plugins\Hook::call('TemplateManager::fetch', [
        $smarty, 'frontend/components/navigationMenus/dashboardMenuItem.tpl', null, null, &$result,
    ]) === \PKP\plugins\Hook::CONTINUE, 'Dashboard hook intercepts an invalid menu item');

    $smarty->assign('highlights', new ArrayObject([new FixtureHighlight()]));
    $highlights = $smarty->fetch('frontend/components/highlights.tpl');
    check(str_contains($highlights, 'Featured publication'), 'Public highlight is missing');
    check(!str_contains($highlights, 'swiper'), 'Highlights still depend on a carousel');

    echo "PASS: plugin metadata, PHP LESS compilation, translations and Smarty rendering.\n";
    echo "PASS: issue routes, escaping, cover fallback, language routes and empty states.\n";
    echo "PASS: no parent-theme LESS imports; disclosure markup and static highlights.\n";
    echo "PASS: dashboard notification badges, menu title escaping and logo alternative text.\n";
    echo "PASS: default About menu, custom menu option and menu isolation.\n";
    echo "PASS: three-language navigation, homepage content and all theme template translation keys.\n";
    echo "Installation, database filtering and browser appearance still require live testing.\n";
} finally {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($temporary);
}
