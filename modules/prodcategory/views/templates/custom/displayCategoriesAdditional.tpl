<div class="product-categories">
        <span class="additional-info">
            Categories
        </span>
        <span class="d-flex ">
                {foreach from=$categories item=category}
                        <a href="{$category.url}" class="p-1">{$category.name}</a>
                {/foreach}
        </span>
</div>