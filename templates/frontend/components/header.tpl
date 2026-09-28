{**
 * Encounters header, adapted from the OJS Default Theme frontend header.
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. See LICENSE.
 * The content wrappers are closed by this theme's frontend footer.
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"|escape}" dir="{$currentLocaleLangDir|default:"ltr"|escape}">
{if !$pageTitleTranslated}{capture assign="pageTitleTranslated"}{translate key=$pageTitle}{/capture}{/if}
{include file="frontend/components/headerHead.tpl"}
<body class="encounters_theme pkp_page_{$requestedPage|default:"index"|escape} pkp_op_{$requestedOp|default:"index"|escape}{if $displayPageHeaderLogo} has_site_logo{/if}" dir="{$currentLocaleLangDir|default:"ltr"|escape}">
<div class="pkp_structure_page">
    <header class="pkp_structure_head encounters-header" id="headerNavigationContainer" role="banner">
        {include file="frontend/components/skipLinks.tpl" issue=$encountersCurrentIssue|default:null announcements=$encountersAnnouncements|default:[]}
        <div class="pkp_head_wrapper">
            <div class="encounters-topbar">
                {if !empty($encountersLocales) && count($encountersLocales) > 1}
                    <details class="encounters-language">
                        <summary aria-label="{translate|escape key="common.language"}">
                            {$encountersLocales[$currentLocale]|default:$currentLocale|escape}
                            <img src="{$encountersThemeUrl|escape}/images/language-chevron.svg" width="8" height="8" alt="">
                        </summary>
                        <ul>
                            {foreach from=$encountersLocales key=localeKey item=localeName}
                                <li><a href="{url page="user" op="setLocale" path=$localeKey}" lang="{$localeKey|replace:"_":"-"|escape}"{if $localeKey == $currentLocale} aria-current="true"{/if}>{$localeName|escape}</a></li>
                            {/foreach}
                        </ul>
                    </details>
                {/if}
            </div>
            <div class="encounters-branding-nav">
                <div class="pkp_site_name_wrapper">
                    {if !$requestedPage || $requestedPage === 'index'}
                        <h1 class="pkp_screen_reader">{$displayPageHeaderTitle|default:$siteTitle|escape}</h1>
                    {/if}
                    <div class="pkp_site_name">
                        <a class="{if $displayPageHeaderLogo}is_img{else}is_text{/if}" href="{url page="index"}">
                            {if $displayPageHeaderLogo}
                                <img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:"url"}" alt="{$displayPageHeaderLogo.altText|default:$displayPageHeaderTitle|default:$siteTitle|escape}" width="{$displayPageHeaderLogo.width|escape}" height="{$displayPageHeaderLogo.height|escape}">
                            {else}
                                <img class="encounters-brand-mark" src="{$encountersThemeUrl|escape}/images/encounters-logo.png?v=2" width="496" height="353" alt="">
                                <span class="encounters-brand-copy">
                                    <span class="encounters-brand-title">{$activeTheme->getOption('mastheadTitle')|default:$displayPageHeaderTitle|escape}</span>
                                    {if $activeTheme->getOption('mastheadTagline')}
                                        <span class="encounters-tagline">{$activeTheme->getOption('mastheadTagline')|escape}</span>
                                    {/if}
                                </span>
                            {/if}
                        </a>
                    </div>
                </div>
                <button type="button" class="pkp_site_nav_toggle" aria-controls="encounters-navigation" aria-expanded="false" hidden>
                    {translate key="plugins.themes.encounters.menu"}
                </button>
                <nav class="pkp_site_nav_menu" id="encounters-navigation" aria-label="{translate|escape key="common.navigation.site"}">
                    <a id="siteNav"></a>
                    <div class="pkp_navigation_primary_row">
                        <div class="pkp_navigation_primary_wrapper">
                            {load_menu name="primary" id="navigationPrimary" ulClass="pkp_navigation_primary"}
                            {if $currentContext && $requestedPage !== 'search'}
                                <a href="{url page="search"}" class="encounters-search" aria-label="{translate|escape key="common.search"}"><img src="{$encountersThemeUrl|escape}/images/search.svg" width="14" height="14" alt=""></a>
                            {/if}
                        </div>
                    </div>
                    <div class="pkp_navigation_user_wrapper" id="navigationUserWrapper">
                        {load_menu name="user" id="navigationUser" ulClass="pkp_navigation_user" liClass="profile"}
                    </div>
                </nav>
            </div>
        </div>
    </header>
    {if $isFullWidth}{assign var=hasSidebar value=0}{/if}
    <div class="pkp_structure_content{if $hasSidebar} has_sidebar{/if}">
        <div class="pkp_structure_main" role="main">
            <a id="pkp_content_main"></a>
