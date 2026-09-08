{** Encounters journal homepage. Distributed under the GNU GPL v3. *}
{assign var=isFullWidth value=true}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div class="encounters-home">
    {call_hook name="Templates::Index::journal"}
    <section class="encounters-hero" aria-label="{translate|escape key="about.aboutContext"}">
        <div class="encounters-container encounters-hero-grid{if !$homepageImage} encounters-hero-grid--text{/if}">
            {if $homepageImage}
                <img class="encounters-hero-image" src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}" alt="{$homepageImage.altText|default:''|escape}">
            {/if}
            <div class="encounters-hero-copy">
                {if $journalDescription}
                    <div class="encounters-description">{$journalDescription|strip_unsafe_html}</div>
                {else}
                    <h2>{$currentJournal->getLocalizedName()|escape}</h2>
                {/if}
                {if $encountersCurrentIssue}
                    <a class="encounters-button" href="{url page="issue" op="view" path=$encountersCurrentIssue->getBestIssueId()}">{translate key="journal.currentIssue"}</a>
                {/if}
            </div>
        </div>
    </section>

    {if $encountersRecentIssues}
        <section class="encounters-container encounters-recent" aria-labelledby="encounters-recent-heading">
            <div class="encounters-section-heading">
                <h2 id="encounters-recent-heading">{translate key="plugins.themes.encounters.recentIssues"}</h2>
                <a href="{url page="issue" op="archive"}">{translate key="journal.viewAllIssues"} <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="encounters-issue-grid">
                {foreach from=$encountersRecentIssues item=recentIssue}
                    {include file="frontend/objects/encounters_issue_card.tpl" recentIssue=$recentIssue}
                {/foreach}
            </div>
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
