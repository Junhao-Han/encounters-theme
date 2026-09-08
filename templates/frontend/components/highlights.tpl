{** Static public highlights, independent of a carousel library. *}
<section class="encounters-highlights" aria-label="{translate|escape key="common.highlights"}">
    {foreach from=$highlights item=highlight}
        <article>
            {if $highlight->getImage()}
                <img src="{$highlight->getImageUrl()|escape}" alt="{$highlight->getImageAltText()|escape}" loading="lazy">
            {/if}
            <h2>{$highlight->getLocalizedTitle()|strip_unsafe_html}</h2>
            <div>{$highlight->getLocalizedDescription()|strip_unsafe_html}</div>
            {if $highlight->getUrl()}
                <a href="{$highlight->getUrl()|escape}">{$highlight->getLocalizedUrlText()|strip_unsafe_html}</a>
            {/if}
        </article>
    {/foreach}
</section>
