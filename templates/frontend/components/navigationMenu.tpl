{**
 * Adapted from PKP's navigationMenu.tpl for native disclosure controls.
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. See LICENSE.
 *}
{if $id == 'navigationPrimary' && $currentJournal && $activeTheme->getOption('primaryMenu') == 'default'}
    {assign var=navigationMenu value=$activeTheme->getPrimaryMenu($navigationMenu, $currentJournal)}
{/if}
{if $navigationMenu}
    {assign var=useDefaultAboutMenu value=($id == 'navigationPrimary' && $currentJournal && $activeTheme->getOption('aboutMenu') == 'default')}
    <ul id="{$id|escape}" class="{$ulClass|escape} pkp_nav_list">
        {foreach item=assignment from=$navigationMenu->menuTree name=encountersMenu}
            {if !$assignment->navigationMenuItem->getIsDisplayed()}{continue}{/if}
            {assign var=useDefaultAbout value=($useDefaultAboutMenu && $assignment->navigationMenuItem->getType() == 'NMI_TYPE_ABOUT')}
            {assign var=itemTitleKey value=$activeTheme->getNavigationTitleKey($assignment->navigationMenuItem, $currentLocale)}
            {if $itemTitleKey}
                {capture assign=itemTitle}{translate key=$itemTitleKey}{/capture}
            {else}
                {assign var=itemTitle value=$assignment->navigationMenuItem->getLocalizedTitle()}
            {/if}
            {if $assignment->navigationMenuItem->getData('encountersPage')}
                {capture assign=itemUrl}{url page=$assignment->navigationMenuItem->getData('encountersPage') op=$assignment->navigationMenuItem->getData('encountersOp')}{/capture}
            {else}
                {assign var=itemUrl value=$assignment->navigationMenuItem->getUrl()|escape}
            {/if}
            <li class="{$liClass|escape}">
                <a href="{$itemUrl}">
                    {$itemTitle|escape}
                    {if $assignment->navigationMenuItem->getData('encountersTaskCount') !== null}
                        <span class="task_count">{$assignment->navigationMenuItem->getData('encountersTaskCount')|intval}</span>
                    {/if}
                </a>
                {if $useDefaultAbout || $assignment->navigationMenuItem->getIsChildVisible()}
                    <button type="button" class="encounters-submenu-toggle" data-encounters-submenu aria-expanded="false" aria-controls="{$id|escape}-submenu-{$smarty.foreach.encountersMenu.iteration}" aria-label="{translate|escape key="plugins.themes.encounters.submenu" title=$itemTitle}" hidden>
                        <img src="{$encountersThemeUrl|escape}/images/menu-chevron.svg" width="8" height="8" alt="">
                    </button>
                    <ul id="{$id|escape}-submenu-{$smarty.foreach.encountersMenu.iteration}">
                        {if $useDefaultAbout}
                            <li class="{$liClass|escape}"><a href="{url page="about"}">{translate key="about.aboutContext"}</a></li>
                            <li class="{$liClass|escape}"><a href="{url page="about" op="editorialMasthead"}">{translate key="plugins.themes.encounters.editorialTeam"}</a></li>
                            <li class="{$liClass|escape}"><a href="{url page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
                            <li class="{$liClass|escape}"><a href="{url page="about" op="contact"}">{translate key="about.contact"}</a></li>
                        {else}
                            {foreach item=child from=$assignment->children}
                                {if $child->navigationMenuItem->getIsDisplayed()}
                                    {assign var=childTitle value=$child->navigationMenuItem->getLocalizedTitle()}
                                    {assign var=childTitleKey value=$activeTheme->getNavigationTitleKey($child->navigationMenuItem, $currentLocale)}
                                    {if $childTitleKey}{capture assign=childTitle}{translate key=$childTitleKey}{/capture}{/if}
                                    <li class="{$liClass|escape}">
                                        <a href="{$child->navigationMenuItem->getUrl()|escape}">
                                            {$childTitle|escape}
                                            {if $child->navigationMenuItem->getData('encountersTaskCount') !== null}
                                                <span class="task_count">{$child->navigationMenuItem->getData('encountersTaskCount')|intval}</span>
                                            {/if}
                                        </a>
                                    </li>
                                {/if}
                            {/foreach}
                        {/if}
                    </ul>
                {/if}
            </li>
        {/foreach}
    </ul>
{/if}
