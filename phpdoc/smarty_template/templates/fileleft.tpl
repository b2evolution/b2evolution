{foreach key=subpackage item=files from=$fileleftindex}
  <div class="package">
	{if $subpackage != ""}<strong>{$subpackage}</strong><br />{/if}
	{section name=files loop=$files}
		{if $files[files].link != ''}<a href="{$files[files].link}">{/if}{$files[files].title}{if $files[files].link != ''}</a>{/if}<br />
	{/section}
  </div>
{/foreach}
