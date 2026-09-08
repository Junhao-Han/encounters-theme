{** Recent issue card; use an explicit issue route on the journal homepage. *}
{assign var=cover value=$recentIssue->getLocalizedCoverImageUrl()}
<article class="encounters-issue-card">
    <a href="{url page="issue" op="view" path=$recentIssue->getBestIssueId()}">
        {if $cover}
            <img src="{$cover|escape}" alt="{$recentIssue->getLocalizedCoverImageAltText()|default:''|escape}" loading="lazy">
        {else}
            <span class="encounters-cover-fallback" aria-hidden="true">{$currentJournal->getLocalizedName()|escape}</span>
        {/if}
        <h3>{$recentIssue->getIssueIdentification()|escape}</h3>
    </a>
</article>
