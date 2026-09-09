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

    <section class="encounters-recent-articles" aria-labelledby="encounters-articles-heading">
        <div class="encounters-container">
            <div class="encounters-section-heading">
                <h2 id="encounters-articles-heading">{translate key="plugins.themes.encounters.recentArticles"}</h2>
            </div>
            {if $encountersRecentArticles}
                <ul class="encounters-article-list">
                    {foreach from=$encountersRecentArticles item=recentArticle}
                        <li>
                            <h3><a href="{url page="article" op="view" path=$recentArticle.path}">{$recentArticle.title|escape}</a></h3>
                            {if $recentArticle.date}
                                <time class="encounters-article-date" datetime="{$recentArticle.date|date_format:'Y-m-d'|escape}">{$recentArticle.date|date_format:$dateFormatLong|escape}</time>
                            {/if}
                        </li>
                    {/foreach}
                </ul>
            {else}
                <p class="encounters-empty">{translate key="plugins.themes.encounters.noArticles"}</p>
            {/if}
        </div>
    </section>

    {if $encountersShowAnnouncements || $encountersMonographUrl}
        <div class="encounters-news">
            <div class="encounters-container encounters-news-grid">
                {if $encountersShowAnnouncements}
                    <section class="encounters-announcements" aria-labelledby="encounters-announcements-heading">
                        <h2 id="encounters-announcements-heading">{translate key="announcement.announcements"}</h2>
                        {if $encountersAnnouncements}
                            {foreach from=$encountersAnnouncements item=announcement}
                                <article class="encounters-announcement">
                                    <h3><a href="{url page="announcement" op="view" path=$announcement->id}">{$announcement->getLocalizedData('title')|escape}</a></h3>
                                    <div class="encounters-announcement-summary">{$announcement->getLocalizedData('descriptionShort')|strip_unsafe_html}</div>
                                    <a class="encounters-more" href="{url page="announcement" op="view" path=$announcement->id}">{translate key="common.readMore"}<span class="pkp_screen_reader"> — {$announcement->getLocalizedData('title')|escape}</span></a>
                                </article>
                            {/foreach}
                        {else}
                            <p class="encounters-empty">{translate key="plugins.themes.encounters.announcementsIntro"}</p>
                            <a class="encounters-more" href="{url page="announcement"}">{translate key="plugins.themes.encounters.viewAnnouncements"}</a>
                        {/if}
                    </section>
                {/if}
                {if $encountersMonographUrl}
                    <section class="encounters-monograph" aria-labelledby="encounters-monograph-heading">
                        <h2 id="encounters-monograph-heading">{translate key="plugins.themes.encounters.monographSeries"}</h2>
                        {if $encountersMonographDescription}
                            <p>{$encountersMonographDescription|escape|nl2br}</p>
                        {/if}
                        <a class="encounters-more" href="{$encountersMonographUrl|escape}">{translate key="plugins.themes.encounters.exploreSeries"}</a>
                    </section>
                {/if}
            </div>
        </div>
    {/if}

    {if $highlights->count()}
        <div class="encounters-container encounters-existing-content">
            {include file="frontend/components/highlights.tpl" highlights=$highlights}
        </div>
    {/if}
    {if $additionalHomeContent}
        <div class="encounters-container encounters-existing-content">{$additionalHomeContent|strip_unsafe_html}</div>
    {/if}
</div>

{include file="frontend/components/footer.tpl"}
