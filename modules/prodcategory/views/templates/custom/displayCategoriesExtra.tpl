<div class="product-categories">
    <ul>
        {foreach from=$categories item=category}
            <li>
                <a href="{$category.url}" class="category-link">{$category.name}</a>
            </li>
        {/foreach}
    </ul>
</div>