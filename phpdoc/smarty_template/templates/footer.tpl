</div>


<div class="menu">
{if $package }
		<div class="package-title">{$package}</div>
{/if}
{if count($ric) >= 1}
  <div class="package">
	<div id="ric">
		{section name=ric loop=$ric}
			<p><a href="{$subdir}{$ric[ric].file}">{$ric[ric].name}</a></p>
		{/section}
	</div>
	</div>
{/if}
{if $hastodos}
  <div class="package">
	<div id="todolist">
			<p><a href="{$subdir}{$todolink}">Todo List</a></p>
	</div>
  </div>
{/if}

<div class="decorated">
	<h2 class="uplf_rnd_title">Packages</h2>
	<div class="btrg_rnd_payload">
  	<div class="package">
	{section name=packagelist loop=$packageindex}
		<a href="{$subdir}{$packageindex[packagelist].link}">{$packageindex[packagelist].title}</a><br />
	{/section}
	<br />
	</div>
</div></div>

{if $tutorials}
<div class="decorated">
	<h2 class="uplf_rnd_title">Tutorials/Manuals</h2>
	<div class="btrg_rnd_payload">
	<div class="package">
		{if $tutorials.pkg}
			<strong>Package-level:</strong>
			{section name=ext loop=$tutorials.pkg}
				{$tutorials.pkg[ext]}
			{/section}
			<br />
		{/if}
		{if $tutorials.cls}
			<strong>Class-level:</strong>
			{section name=ext loop=$tutorials.cls}
				{$tutorials.cls[ext]}
			{/section}
			<br />
		{/if}
		{if $tutorials.proc}
			<strong>Procedural-level:</strong>
			{section name=ext loop=$tutorials.proc}
				{$tutorials.proc[ext]}
			{/section}
			<br />
		{/if}
	</div>
</div></div>
{/if}

      {if !$noleftindex}{assign var="noleftindex" value=false}{/if}
      {if !$noleftindex}
      {if $compiledinterfaceindex}
	<div class="decorated">
	<h2 class="uplf_rnd_title">Interfaces</h2>
	<div class="btrg_rnd_payload">
	{eval var=$compiledinterfaceindex}
	<br />
	</div></div>
      	<br />
      {/if}
      {if $compiledclassindex}
	<div class="decorated">
	<h2 class="uplf_rnd_title">Classes</h2>
	<div class="btrg_rnd_payload">
	{eval var=$compiledclassindex}
	<br />
	</div></div>
      	<br />
      {/if}
      {if $compiledfileindex}
	<div class="decorated">
	<h2 class="uplf_rnd_title">Files</h2>
	<div class="btrg_rnd_payload">
	{eval var=$compiledfileindex}
	<br />
	</div></div>
      	<br />
      {/if}
      {/if}
</div>


	<div class="credit">
	<hr class="separator" />
	Documentation generated on {$date} by <a href="{$phpdocwebsite}">phpDocumentor {$phpdocversion}</a>
	</div>
</div>
</div>
</div>
</body>
</html>
