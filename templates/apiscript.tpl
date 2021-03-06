{include file='header.tpl' title='Integration'}
<nav class="navbar navbar-default navbar-toolbar navbar-static-top">
	<div class="container-fluid">
		<ul class="nav navbar-nav">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" type="button" id="dropdownIntegrationMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<i class="fa fa-code"></i> {t}Halon integration{/t}
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu" aria-labelledby="dropdownIntegrationMenu">
					{if $show_script == "api"}<li class="active">{else}<li>{/if}<a href="?page={$page_active}&script=api">{t}API authentication{/t}</a></li>
					<li role="separator" class="divider"></li>
					{if $show_script == "history"}<li class="active">{else}<li>{/if}<a href="?page={$page_active}&script=history">{t}History log{/t}</a></li>
					<li role="separator" class="divider"></li>
					{if $show_script == "bwlist"}<li class="active">{else}<li>{/if}<a href="?page={$page_active}&script=bwlist">{t}Blacklist and whitelist{/t}</a></li>
					{if $show_script == "datastore"}<li class="active">{else}<li>{/if}<a href="?page={$page_active}&script=datastore">{t}Data store settings{/t}</a></li>
					{if $show_script == "spam"}<li class="active">{else}<li>{/if}<a href="?page={$page_active}&script=spam">{t}Spam settings{/t}</a></li>
					<li role="separator" class="divider"></li>
					{if $show_script == "usercreation"}<li class="active">{else}<li>{/if}<a href="?page={$page_active}&script=usercreation">{t}Automatic user creation{/t}</a></li>
				</ul>
			</li>
		</ul>
	</div>
</nav>
<div class="container">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				{if $show_script == "bwlist"}
					{t}Blacklist and whitelist{/t}
				{else if $show_script == "spam"}
					{t}Spam settings{/t}
				{else if $show_script == "datastore"}
					{t}Data store settings{/t}
				{else if $show_script == "history"}
					{t}History log{/t}
				{else if $show_script == "usercreation"}
					{t}Automatic user creation{/t}
				{else}
					{t}API authentication script{/t}
				{/if}
			</h3>
		</div>
		<div class="panel-body">
			{if $show_script == "bwlist"}
				<p>{t}Used to lookup against the blacklist and whitelist on the Enduser (with a cache). The function should be called before spam checks.{/t}</p>
				{if !$feature_bwlist}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_bwlist.tpl'}</pre>
				<h4 style="margin-top: 20px">{t}ScanBWList with per-user check{/t}</h4>
				<p>{t}This implementation is suboptimal as it queries each $sender and $recipient for the black/white list. It's not necessary to cache these as cache hits would be close to none.{/t}</p>
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_bwlist_peruser.tpl'}</pre>
			{else if $show_script == "spam"}
				<p>{t}If you want to fetch the spam settings from the End-user interface.{/t}</p>
				{if !$feature_spam}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_spam.tpl'}</pre>
			{else if $show_script == "datastore"}
				<p>{t}If you want to fetch the datastore settings from the End-user interface.{/t}</p>
				{if !$feature_datastore}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_datastore.tpl'}</pre>
			{else if $show_script == "history"}
				<p>{t}There's a limit to how many messages can stored in a Halon database, for performance reasons. In order to store large volumes of email history we encourage the use of the end user interface's built-in history log feature. Simply append the following script to the Halon nodes' DATA flow, to push logging information to the End-user.{/t}</p>
				{if !$feature_dblog}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_logging.tpl'}</pre>
				<p>{t}The code above could be placed in a virtual text file, and included in the top of the script. Please note that the "direction" field should probably be changed to "outbound" instead of "inbound" for outbound traffic. For delivery status updates, the following script should be called from (again, preferably by including code from a virtual text file) in the Post-delivery flow.{/t}</p>
				<div class="pre-header">Post-delivery context</div>
				<pre class="pre-body">{include file='scripts/hsl_loggingpost.tpl'}</pre>
				<h4 style="margin-top: 20px">{t}Removing old logs{/t}</h4>
				<p>{t}When using the history log feature you should also edit the crontab file to periodically remove old logs from the database. You do that by typing crontab -e in the terminal and add the following line at the bottom:{/t}</p>
				<pre>0 * * * * /usr/bin/php /var/www/html/sp-enduser/cron.php.txt cleanmessagelog</pre>
			{else if $show_script == "usercreation"}
				<p>{t}If you want users to be automatically created when a message is received, add the following script to your data flow.{/t}</p>
				{if !$feature_users}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_usercreation.tpl'}</pre>
			{else}
				<p>{t}This is a sample authentication script (API script) with all currently enabled features, to be used on your Halon node.{/t}</p>
				<div class="pre-header">API context</div>
				<pre class="pre-body">{$hsl_script}</pre>
			{/if}
		</div>
	</div>
</div>
{include file='footer.tpl'}
