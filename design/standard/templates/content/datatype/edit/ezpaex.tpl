{* Edit Template for ezpaex datatype *}

{if is_set($attribute_base)|not}
	{def $attribute_base=ContentObjectAttribute}
{/if}

<div class="block">
    {* Validation Regexp. *}
    <div class="element">
        <label>{'Validation Regexp'|i18n( 'mbpaex/content/datatype' )}:</label>
        <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_passwordvalidationregexp" class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_data_paex_passwordvalidationregexp_{$attribute.id}" size="28" value="{if $attribute.content.has_regexp}{$attribute.content.passwordvalidationregexp|wash( xhtml )}{/if}" {if $attribute.content.can_edit|not}disabled="disabled"{/if}/>
    </div>
    
    {* Password LifeTime. *}
    <div class="element">
        <label>{'Password LifeTime (DAYS)'|i18n( 'mbpaex/content/datatype' )}:</label>
        <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_passwordlifetime" class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_data_paex_passwordlifetime_{$attribute.id}" size="16" value="{if $attribute.content.has_lifetime}{$attribute.content.passwordlifetime|wash}{/if}" {if $attribute.content.can_edit|not}disabled="disabled"{/if}/>
    </div>
    
    {* Expiration Notification *}
    <div class="element">
        <label>{'Expiration Notification (SECONDS)'|i18n( 'mbpaex/content/datatype' )}:</label>
        <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_expirationnotification" class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_data_paex_expirationnotification_{$attribute.id}" size="16" value="{if $attribute.content.has_notification}{$attribute.content.expirationnotification|wash}{/if}" {if $attribute.content.can_edit|not}disabled="disabled"{/if}/>
    </div>
    
    <div class="break"></div>
</div>

{* If we are editing a user, show password status, if not and the current user 
   have permissions, show a checkbox to choose update children *}
{if $attribute.content.is_user}
    <div class="block">
        <div class="element">
            <label>{'Current password status:'|i18n( 'mbpaex/content/datatype' )}
            {if $attribute.content.is_expired}
            <span class="userstatus-disabled">{'expired'|i18n( 'mbpaex/content/datatype' )}</span>
            {else}
            <span class="userstatus-enabled">{'active'|i18n( 'mbpaex/content/datatype' )}</span>
            {/if}
            </label>
        </div>
        <div class="break"></div>
    </div>
{elseif $attribute.content.can_edit}
    <div class="block">
        <div class="element">
            <label>{'Update children nodes?'|i18n( 'mbpaex/content/datatype' )}:</label>
            <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_updatechildren" class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="checkbox" name="{$attribute_base}_data_paex_updatechildren_{$attribute.id}" value="1" {if $attribute.content.is_updatechildrenpending}checked="checked"{/if}/>
        </div>
        <div class="break"></div>
    </div>
{/if}
