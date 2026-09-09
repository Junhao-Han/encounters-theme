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
use PKP\config\Config;
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
        $recentIssues = [];
        $showIssues = $context->getData('publishingMode') != Journal::PUBLISHING_MODE_NONE;
        if ($showIssues) {
            $currentIssue = Repo::issue()->getCurrent($context->getId());
            if ($currentIssue && !$currentIssue->getPublished()) {
                $currentIssue = null;
            }
            $recentIssues = Repo::issue()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_DATE_PUBLISHED)
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
        $templateManager->assign([
            'encountersHeroTitles' => $titles,
            'encountersHeroDescription' => trim((string) $this->getOption('introductionDescription')),
            'encountersCurrentIssue' => $currentIssue,
            'encountersRecentIssues' => $recentIssues,
            'encountersShowIssues' => $showIssues,
            'encountersRecentArticles' => $recentArticles,
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
