<div class="product-categories">
    <ul>
        {foreach from=$categories item=category}
            <li>
                <a href="{$category.url}" class="p-1">{$category.name}</a>
            </li>
        {/foreach}
    </ul>
</div>