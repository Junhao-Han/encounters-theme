{**
 * Adapted from PKP's navigationMenu.tpl for native disclosure controls.
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. See LICENSE.
 *}
{if $navigationMenu}
    <ul id="{$id|escape}" class="{$ulClass|escape} pkp_nav_list">
        {foreach item=assignment from=$navigationMenu->menuTree name=encountersMenu}
            {if !$assignment->navigationMenuItem->getIsDisplayed()}{continue}{/if}
            <li class="{$liClass|escape}">
                <a href="{$assignment->navigationMenuItem->getUrl()|escape}">
                    {$assignment->navigationMenuItem->getLocalizedTitle()|escape}
                    {if $assignment->navigationMenuItem->getData('encountersTaskCount') !== null}
                        <span class="task_count">{$assignment->navigationMenuItem->getData('encountersTaskCount')|intval}</span>
                    {/if}
                </a>
                {if $assignment->navigationMenuItem->getIsChildVisible()}
                    <button type="button" class="encounters-submenu-toggle" data-encounters-submenu aria-expanded="false" aria-controls="{$id|escape}-submenu-{$smarty.foreach.encountersMenu.iteration}" aria-label="{translate|escape key="plugins.themes.encounters.submenu" title=$assignment->navigationMenuItem->getLocalizedTitle()}" hidden>
                        <span aria-hidden="true">&#9662;</span>
                    </button>
                    <ul id="{$id|escape}-submenu-{$smarty.foreach.encountersMenu.iteration}">
                        {foreach item=child from=$assignment->children}
                            {if $child->navigationMenuItem->getIsDisplayed()}
                                <li class="{$liClass|escape}">
                                    <a href="{$child->navigationMenuItem->getUrl()|escape}">
                                        {$child->navigationMenuItem->getLocalizedTitle()|escape}
                                        {if $child->navigationMenuItem->getData('encountersTaskCount') !== null}
                                            <span class="task_count">{$child->navigationMenuItem->getData('encountersTaskCount')|intval}</span>
                                        {/if}
                                    </a>
                                </li>
                            {/if}
                        {/foreach}
                    </ul>
                {/if}
            </li>
        {/foreach}
    </ul>
{/if}
