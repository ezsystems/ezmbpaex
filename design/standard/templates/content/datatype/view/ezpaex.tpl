{* View Template for ezpaex datatype *}

<div class="block">

    <div class="element">
        <label>{'Validation Regexp'|i18n( 'mbpaex/content/datatype' )}:</label>
        {if $attribute.content.has_regexp}
            {$attribute.content.passwordvalidationregexp|wash( xhtml )}
        {else}
            <span class="userstatus-disabled"> {'undefined'|i18n( 'mbpaex/content/datatype' )}</span>
        {/if}
    </div>
    
    <div class="element">
        <label>{'Password LifeTime (DAYS)'|i18n( 'mbpaex/content/datatype' )}:</label>
        {if $attribute.content.has_lifetime}
            {$attribute.content.passwordlifetime|wash}
        {else}
            <span class="userstatus-disabled"> {'undefined'|i18n( 'mbpaex/content/datatype' )}</span>
        {/if}
    </div>
    
    <div class="element">
        <label>{'Expiration Notification (SECONDS)'|i18n( 'mbpaex/content/datatype' )}:</label>
        {if $attribute.content.has_notification}
            {$attribute.content.expirationnotification|wash}
        {else}
            <span class="userstatus-disabled"> {'undefined'|i18n( 'mbpaex/content/datatype' )}</span>
        {/if}
    </div>
    <div class="break"></div>
</div>

{if $attribute.content.is_user}
    <div class="block">
        <div class="element">
            <label>{'Password status'|i18n( 'mbpaex/content/datatype' )}:</label>
            {if $attribute.content.is_expired}
            <span class="userstatus-disabled"> {'expired'|i18n( 'mbpaex/content/datatype' )}</span>
            {else}
            <span class="userstatus-enabled">{'active'|i18n( 'mbpaex/content/datatype' )}</span>
            {/if}
        </div>
        <div class="break"></div>
    </div>
{elseif $attribute.content.is_updatechildrenpending}
    <div class="block">
        <div class="element">
            <span class="userstatus-enabled">{'Children update pending, will be done in a few minutes...'|i18n( 'mbpaex/content/datatype' )}</span>
        </div>
        <div class="break"></div>
    </div>
{/if}
