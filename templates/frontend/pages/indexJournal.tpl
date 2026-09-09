{** Encounters journal homepage. Distributed under the GNU GPL v3. *}
{assign var=isFullWidth value=true}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div class="encounters-home">
    {call_hook name="Templates::Index::journal"}
    <section class="encounters-hero" aria-labelledby="encounters-introduction-heading">
        <div class="encounters-container encounters-hero-grid">
            <div class="encounters-hero-media">
                {if !empty($homepageImage.uploadName)}
                    <img class="encounters-hero-image" src="{$publicFilesDir|escape}/{$homepageImage.uploadName|escape:"url"}" alt="{$homepageImage.altText|default:''|escape}"{if !empty($homepageImage.width) && !empty($homepageImage.height)} width="{$homepageImage.width|intval}" height="{$homepageImage.height|intval}"{/if}>
                {/if}
            </div>
            <div class="encounters-hero-copy">
                <div class="encounters-hero-text">
                    <div class="encounters-hero-titles">
                        {foreach from=$encountersHeroTitles key=titleLocale item=heroTitle name=heroTitles}
                            {if $smarty.foreach.heroTitles.first}
                                <h2 id="encounters-introduction-heading" lang="{$titleLocale|replace:'_':'-'|escape}" dir="auto">{$heroTitle|escape}</h2>
                            {else}
                                <p lang="{$titleLocale|replace:'_':'-'|escape}" dir="auto">{$heroTitle|escape}</p>
                            {/if}
                        {foreachelse}
                            <h2 id="encounters-introduction-heading">{$currentJournal->getLocalizedName()|escape}</h2>
                        {/foreach}
                    </div>
                    {if $encountersHeroDescription}
                        <div class="encounters-description">{$encountersHeroDescription|escape|nl2br}</div>
                    {/if}
                </div>
                {if $encountersCurrentIssue}
                    <a class="encounters-button" href="{url page="issue" op="view" path=$encountersCurrentIssue->getBestIssueId()}">{translate key="journal.currentIssue"}</a>
                {/if}
            </div>
        </div>
    </section>

    {if $encountersShowIssues}
        <section class="encounters-container encounters-recent" aria-labelledby="encounters-recent-heading">
            <div class="encounters-section-heading">
                <h2 id="encounters-recent-heading">{translate key="plugins.themes.encounters.recentIssues"}</h2>
                <a href="{url page="issue" op="archive"}">{translate key="journal.viewAllIssues"} <img src="{$encountersThemeUrl|escape}/images/arrow-right.svg" width="12" height="12" alt=""></a>
            </div>
            {if $encountersRecentIssues}
            <div class="encounters-issue-grid">
                {foreach from=$encountersRecentIssues item=recentIssue}
                    {include file="frontend/objects/encounters_issue_card.tpl" recentIssue=$recentIssue}
                {/foreach}
            </div>
            {else}
                <p class="encounters-empty">{translate key="plugins.themes.encounters.noIssues"}</p>
            {/if}
        </section>
    {/if}

    {if $highlights->count()}
        <div class="encounters-container encounters-existing-content">
            {include file="frontend/components/highlights.tpl" highlights=$highlights}
        </div>
    {/if}
    {if $numAnnouncementsHomepage && $announcements}
        <div class="encounters-container encounters-existing-content">
            {include file="frontend/objects/announcements_list.tpl" numAnnouncements=$numAnnouncementsHomepage}
        </div>
    {/if}
    {if $additionalHomeContent}
        <div class="encounters-container encounters-existing-content">{$additionalHomeContent|strip_unsafe_html}</div>
    {/if}
</div>

{include file="frontend/components/footer.tpl"}
