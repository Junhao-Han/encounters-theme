<a class="encounters-submission-brand" href="{url page="index"}">
    {if $activeTheme->getOption('mastheadLogo') == 'uploaded' && !empty($displayPageHeaderLogo.uploadName)}
        <img src="{$publicFilesDir|escape}/{$displayPageHeaderLogo.uploadName|escape:"url"}" alt="{$displayPageHeaderLogo.altText|default:''|escape}">
    {else}
        <img src="{$encountersThemeUrl|escape}/images/encounters-logo.png?v=2" width="496" height="353" alt="">
    {/if}
    <span>
        <span class="encounters-submission-brand__title">{$activeTheme->getOption('mastheadTitle')|default:$displayPageHeaderTitle|escape}</span>
        {assign var=mastheadTagline value=$activeTheme->getMastheadTagline($currentLocale)}
        {if $mastheadTagline}
            <span class="encounters-submission-brand__tagline">{$mastheadTagline|escape}</span>
        {/if}
    </span>
</a>
