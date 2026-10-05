{**
 * Adapted from the PKP frontend footer.
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. See LICENSE.
 *}
        </div>{* pkp_structure_main *}
        {if empty($isFullWidth) && $activeTheme->getOption('sidebar') == 'show'}
            {capture assign="sidebarCode"}{call_hook name="Templates::Common::Sidebar"}{/capture}
            {if $sidebarCode}<aside class="pkp_structure_sidebar left">{$sidebarCode}</aside>{/if}
        {/if}
    </div>{* pkp_structure_content *}
    <footer class="pkp_structure_footer_wrapper">
        <a id="pkp_content_footer"></a>
        <div class="pkp_structure_footer">
            {if $pageFooter}<div class="pkp_footer_content">{$pageFooter|strip_unsafe_html}</div>{/if}
            <div class="pkp_brand_footer">
                <a href="{url page="about" op="aboutThisPublishingSystem"}"><img src="{$baseUrl}/{$brandImage|escape}" alt="{translate|escape key="about.aboutThisPublishingSystem"}"></a>
            </div>
        </div>
    </footer>
</div>{* pkp_structure_page *}
{load_script context="frontend"}
{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>
