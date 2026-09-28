<?php

/**
 * @file plugins/themes/encounters/EncountersThemePlugin.php
 *
 * Encounters journal theme. Distributed under the GNU GPL v3.
 */

namespace APP\plugins\themes\encounters;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\journal\Journal;
use APP\submission\Collector as SubmissionCollector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Carbon\Carbon;
use PKP\config\Config;
use PKP\facades\Locale;
use PKP\i18n\LocaleMetadata;
use PKP\navigationMenu\NavigationMenuItem;
use PKP\plugins\Hook;
use PKP\plugins\ThemePlugin;

class EncountersThemePlugin extends ThemePlugin
{
    public function init()
    {
        $this->addOption('mastheadTitle', 'FieldText', [
            'label' => __('plugins.themes.encounters.mastheadTitle'),
            'default' => 'Encounters',
        ]);
        $this->addOption('mastheadTagline', 'FieldText', [
            'label' => __('plugins.themes.encounters.mastheadTagline'),
            'default' => 'In Education, Humanities, and Technology',
        ]);
        $this->addOption('mastheadTaglineEs', 'FieldText', [
            'label' => __('plugins.themes.encounters.mastheadTaglineEs'),
            'default' => 'En Educación, Humanidades y Tecnología',
        ]);
        $this->addOption('mastheadTaglineFr', 'FieldText', [
            'label' => __('plugins.themes.encounters.mastheadTaglineFr'),
            'default' => 'En éducation, Humanités et technologie',
        ]);
        $this->addOption('aboutMenu', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.encounters.aboutMenu'),
            'description' => __('plugins.themes.encounters.aboutMenu.description'),
            'options' => [
                ['value' => 'default', 'label' => __('plugins.themes.encounters.aboutMenu.default')],
                ['value' => 'custom', 'label' => __('plugins.themes.encounters.aboutMenu.custom')],
            ],
            'default' => 'default',
        ]);
        $this->addOption('introductionTitleEn', 'FieldText', [
            'label' => __('plugins.themes.encounters.introductionTitleEn'),
            'default' => 'Encounters in Education, Humanities, and Technology.',
        ]);
        $this->addOption('introductionTitleEs', 'FieldText', [
            'label' => __('plugins.themes.encounters.introductionTitleEs'),
            'default' => 'Encuentros en Educación, Humanidades y Tecnología',
        ]);
        $this->addOption('introductionTitleFr', 'FieldText', [
            'label' => __('plugins.themes.encounters.introductionTitleFr'),
            'default' => 'Rencontres en éducation, sciences humaines et technologie',
        ]);
        $this->addOption('introductionDescription', 'FieldTextarea', [
            'label' => __('plugins.themes.encounters.introductionDescription'),
            'default' => 'An international, interdisciplinary journal exploring the intersections of Education, Humanities, and Technology',
        ]);
        $this->addOption('introductionDescriptionEs', 'FieldTextarea', [
            'label' => __('plugins.themes.encounters.introductionDescriptionEs'),
            'default' => 'Una revista internacional e interdisciplinaria que explora las intersecciones entre la Educación, las Humanidades y la Tecnología',
        ]);
        $this->addOption('introductionDescriptionFr', 'FieldTextarea', [
            'label' => __('plugins.themes.encounters.introductionDescriptionFr'),
            'default' => 'Une revue internationale et interdisciplinaire explorant les intersections entre l’Éducation, les Humanités et la Technologie',
        ]);
        $this->addOption('heroIssueId', 'FieldSelect', [
            'label' => __('plugins.themes.encounters.heroIssue'),
            'description' => __('plugins.themes.encounters.heroIssue.description'),
            'options' => [['value' => 0, 'label' => __('plugins.themes.encounters.heroIssue.none')]],
            'default' => 0,
        ]);
        $this->addOption('monographDescription', 'FieldTextarea', [
            'label' => __('plugins.themes.encounters.monographDescription'),
            'default' => "Supported by Queen's University Library, THE is an open access series exploring the history, philosophy, and sociology of education.",
        ]);
        $this->addOption('monographDescriptionEs', 'FieldTextarea', [
            'label' => __('plugins.themes.encounters.monographDescriptionEs'),
            'default' => "Con el apoyo de la Biblioteca de Queen's University, THE es una serie de acceso abierto que explora la historia, la filosofía y la sociología de la educación.",
        ]);
        $this->addOption('monographDescriptionFr', 'FieldTextarea', [
            'label' => __('plugins.themes.encounters.monographDescriptionFr'),
            'default' => 'Soutenue par la bibliothèque de l’Université Queen’s, THE est une collection en libre accès consacrée à l’histoire, à la philosophie et à la sociologie de l’éducation.',
        ]);
        $this->addOption('monographUrl', 'FieldText', [
            'label' => __('plugins.themes.encounters.monographUrl'),
            'description' => __('plugins.themes.encounters.monographUrl.description'),
            'inputType' => 'url',
            'default' => 'https://queens.scholarsportal.info/omp/index.php/qulp/index',
        ]);

        // The core article template consults this option before rendering statistics.
        $this->addOption('displayStats', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.encounters.displayStats'),
            'options' => [
                ['value' => 'none', 'label' => __('plugins.themes.encounters.displayStats.none')],
                ['value' => 'bar', 'label' => __('plugins.themes.encounters.displayStats.bar')],
                ['value' => 'line', 'label' => __('plugins.themes.encounters.displayStats.line')],
            ],
            'default' => 'none',
        ]);

        $this->addMenuArea(['primary', 'user']);
        $this->addStyle($this->getStylesheetName(), 'styles/index.less');

        // Core frontend templates and other OJS plugins use these core assets.
        $baseUrl = Application::get()->getRequest()->getBaseUrl();
        $min = Config::getVar('general', 'enable_minified') ? '.min' : '';
        $this->addStyle('fontAwesome', $baseUrl . '/lib/pkp/styles/fontawesome/fontawesome.css', ['baseUrl' => '']);
        $this->addScript('jQuery', $baseUrl . '/js/build/jquery/jquery' . $min . '.js', ['baseUrl' => '']);
        $this->addScript('jQueryUI', $baseUrl . '/js/build/jquery-ui/jquery-ui' . $min . '.js', ['baseUrl' => '']);
        $this->addScript('encounters-navigation', 'js/navigation.js', [
            'priority' => TemplateManager::STYLE_SEQUENCE_LATE,
        ]);
        $this->addScript('encounters-forms', 'js/forms.js', [
            'priority' => TemplateManager::STYLE_SEQUENCE_LATE,
        ]);
        Hook::add('TemplateManager::display', $this->prepareTemplate(...));
        Hook::add('TemplateManager::fetch', $this->prepareDashboardMenuItem(...));
        Hook::add('Locale::translate', $this->translateSearchResults(...));
    }

    public function getMastheadTagline(string $locale): string
    {
        $option = match (strtolower(substr($locale, 0, 2))) {
            'es' => 'mastheadTaglineEs',
            'fr' => 'mastheadTaglineFr',
            default => 'mastheadTagline',
        };
        return trim((string) $this->getOption($option));
    }

    public function getIntroductionDescription(string $locale): string
    {
        $option = match (strtolower(substr($locale, 0, 2))) {
            'es' => 'introductionDescriptionEs',
            'fr' => 'introductionDescriptionFr',
            default => 'introductionDescription',
        };
        return trim((string) $this->getOption($option));
    }

    public function getMonographDescription(string $locale): string
    {
        $option = match (strtolower(substr($locale, 0, 2))) {
            'es' => 'monographDescriptionEs',
            'fr' => 'monographDescriptionFr',
            default => 'monographDescription',
        };
        return trim((string) $this->getOption($option));
    }

    public function formatArticleDate(string $date, string $locale): string
    {
        return Carbon::parse($date)->locale(str_replace('-', '_', $locale))->isoFormat('LL');
    }

    public function getNavigationTitleKey(NavigationMenuItem $item, string $locale): ?string
    {
        if ($item->getData('encountersTitleLocaleKey')) {
            return $item->getData('encountersTitleLocaleKey');
        }
        $key = $item->getTitleLocaleKey();
        if (!$item->getTitle($locale) && $key && !str_contains($key, '{$')) {
            return $key;
        }

        if ($item->getType() === NavigationMenuItem::NMI_TYPE_REMOTE_URL
            && $item->getLocalizedTitle() === 'Monograph Series'
            && rtrim((string) $item->getUrl(), '/') === rtrim((string) $this->getOption('monographUrl'), '/')
            && $this->getOption('monographUrl')) {
            return 'plugins.themes.encounters.monographSeries';
        }

        return null;
    }

    public function getOptionsConfig()
    {
        $options = parent::getOptionsConfig();
        if (!isset($options['heroIssueId'])) {
            return $options;
        }

        $choices = [['value' => 0, 'label' => __('plugins.themes.encounters.heroIssue.none')]];
        $context = Application::get()->getRequest()->getContext();
        if ($context && $context->getData('publishingMode') != Journal::PUBLISHING_MODE_NONE) {
            $issues = Repo::issue()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_DATE_PUBLISHED)
                ->getMany();
            foreach ($issues as $issue) {
                $choices[] = ['value' => $issue->getId(), 'label' => $issue->getIssueIdentification()];
            }
        }
        $options['heroIssueId']->options = $choices;
        return $options;
    }

    public function translateSearchResults(string $hookName, array $args): bool
    {
        if ($args[1] !== 'search.searchResults.foundPlural' || $args[3] === null) {
            return Hook::CONTINUE;
        }

        // OJS 3.5 requests a plural for this singular translation entry.
        $args[0] = __('search.searchResults.foundPlural', ['count' => $args[3]] + $args[2], $args[4]);
        return Hook::ABORT;
    }

    private function getStylesheetName(): string
    {
        $files = [];
        $directory = new \RecursiveDirectoryIterator($this->_getBaseDir('styles'), \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if ($file->isFile() && $file->getExtension() === 'less') {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        // The name identifies both OJS's compiled cache and the browser's CSS URL.
        $hash = hash_init('sha256');
        foreach ($files as $file) {
            hash_update_file($hash, $file);
        }
        return 'encounters-' . substr(hash_final($hash), 0, 12);
    }

    public function prepareDashboardMenuItem(string $hookName, array $args): bool
    {
        [$templateManager, $template] = $args;
        if ($template !== 'frontend/components/navigationMenus/dashboardMenuItem.tpl') {
            return Hook::CONTINUE;
        }

        $menuItem = $templateManager->getTemplateVars('navigationMenuItem');
        if (!$menuItem instanceof NavigationMenuItem) {
            return Hook::CONTINUE;
        }

        // OJS requests this fragment after checking the user's dashboard roles.
        // Keep its title as text and render the count separately in navigation.
        $menuItem->setData('encountersTaskCount', (int) $templateManager->getTemplateVars('unreadNotificationCount'));
        // OJS will copy this fragment into the current locale's title.
        $menuItem->setData('encountersTitleLocaleKey', $this->getNavigationTitleKey($menuItem, (string) $templateManager->getTemplateVars('currentLocale')));
        $args[4] = $menuItem->getLocalizedTitle();
        return Hook::ABORT;
    }

    public function prepareTemplate(string $hookName, array $args): bool
    {
        [$templateManager, $template] = $args;
        if (!str_starts_with((string) $template, 'frontend/')) {
            return Hook::CONTINUE;
        }

        $context = Application::get()->getRequest()->getContext();
        $templateManager->assign('encountersThemeUrl', $this->_getBaseUrl());
        $templateManager->assign('encountersLocales', $context?->getSupportedLocaleNames(LocaleMetadata::LANGUAGE_LOCALE_ONLY) ?? []);
        if ($template !== 'frontend/pages/indexJournal.tpl' || !$context) {
            return Hook::CONTINUE;
        }

        $titles = [];
        foreach (['en' => 'introductionTitleEn', 'es' => 'introductionTitleEs', 'fr' => 'introductionTitleFr'] as $locale => $option) {
            $title = trim((string) $this->getOption($option));
            if ($title !== '') {
                $titles[$locale] = $title;
            }
        }

        $currentIssue = null;
        $heroIssue = null;
        $recentIssues = [];
        $showIssues = $context->getData('publishingMode') != Journal::PUBLISHING_MODE_NONE;
        if ($showIssues) {
            $currentIssue = Repo::issue()->getCurrent($context->getId());
            if ($currentIssue && !$currentIssue->getPublished()) {
                $currentIssue = null;
            }
            $heroIssueId = (int) $this->getOption('heroIssueId');
            if ($heroIssueId > 0) {
                $heroIssue = Repo::issue()->get($heroIssueId, $context->getId());
                if ($heroIssue && !$heroIssue->getPublished()) {
                    $heroIssue = null;
                }
            }
            $recentIssues = Repo::issue()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_SEQUENCE)
                ->limit(3)
                ->getMany()
                ->all();
        }
        $articleIds = Repo::submission()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(SubmissionCollector::ORDERBY_DATE_PUBLISHED, SubmissionCollector::ORDER_DIR_DESC)
            ->limit(3)
            ->getQueryBuilder()
            ->where('po.status', Submission::STATUS_PUBLISHED)
            ->orderBy('s.submission_id', 'desc')
            ->pluck('s.submission_id');
        $recentArticles = [];
        foreach ($articleIds as $articleId) {
            $article = Repo::submission()->get((int) $articleId, $context->getId());
            $publication = $article?->getCurrentPublication();
            if (!$publication || $article->getData('status') !== Submission::STATUS_PUBLISHED || $publication->getData('status') !== Submission::STATUS_PUBLISHED) {
                continue;
            }
            $recentArticles[] = [
                'title' => $publication->getLocalizedFullTitle(),
                'path' => $article->getBestId(),
                'date' => $publication->getData('datePublished'),
            ];
        }
        $monographUrl = trim((string) $this->getOption('monographUrl'));
        if (!filter_var($monographUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($monographUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $monographUrl = '';
        }
        $templateManager->assign([
            'encountersHeroTitles' => $titles,
            'encountersHeroDescription' => $this->getIntroductionDescription(Locale::getLocale()),
            'encountersCurrentIssue' => $currentIssue,
            'encountersHeroIssue' => $heroIssue,
            'encountersRecentIssues' => $recentIssues,
            'encountersShowIssues' => $showIssues,
            'encountersRecentArticles' => $recentArticles,
            'encountersShowAnnouncements' => (bool) $context->getData('enableAnnouncements'),
            'encountersAnnouncements' => $templateManager->getTemplateVars('announcements')?->all() ?? [],
            'encountersMonographDescription' => $this->getMonographDescription(Locale::getLocale()),
            'encountersMonographUrl' => $monographUrl,
        ]);

        return Hook::CONTINUE;
    }

    public function getDisplayName()
    {
        return __('plugins.themes.encounters.name');
    }

    public function getDescription()
    {
        return __('plugins.themes.encounters.description');
    }
}
