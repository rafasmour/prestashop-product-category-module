<div class="product-categories">
        <div class="additional-info">
            Categories
        </div>
        <p>
        {foreach from=$categories item=category}
                <a href="{$category.url}" class="category-link">{$category.name}</a>
        {/foreach}

        </p>
</div>