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
        $this->addStyle('stylesheet', 'styles/index.less');

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

        $currentIssue = null;
        $recentIssues = [];
        if ($context->getData('publishingMode') != Journal::PUBLISHING_MODE_NONE) {
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
        $templateManager->assign([
            'encountersCurrentIssue' => $currentIssue,
            'encountersRecentIssues' => $recentIssues,
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
