<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');
require_once('inc/utils.php');

$node = intval($_GET['node']);
$queueid = intval($_GET['queueid']);
$client = soap_client($node);

// Access permission
$query['filter'] = build_query_restrict().' && queueid='.$queueid;
if (isset($_GET['id']) && isset($_GET['to']))
	$query['filter'] .= ' to='.$_GET['to'].' messageid='.$_GET['id'];
$query['offset'] = 0;
$query['limit'] = 1;
if ($_GET['type'] == 'history')
	$queue = $client->mailHistory($query);
else
	$queue = $client->mailQueue($query);
if (count($queue->result->item) != 1)
	die('Invalid queueid');

if (isset($_POST['action'])) {
	if ($_POST['action'] == 'bounce')
		$client->mailQueueBounce(array('id' => $queueid));
	if ($_POST['action'] == 'delete')
		$client->mailQueueDelete(array('id' => $queueid));
	if ($_POST['action'] == 'retry')
		$client->mailQueueRetry(array('id' => $queueid));
	$title = 'Message';
	require_once('inc/header.php'); ?>
				<div class="item">
					<div class="button back" onclick="location.href='<?php p($_POST['referer']) ?>';">Back</div>
				</div>
			</div>
			<div class="pad message ok">The requested action has been performed</div>
	<?php require_once('inc/footer.php');
	die();
}

// Prepare data
$scores = isset($settings['display-scores']) ? $settings['display-scores'] : false;
$logs = isset($settings['display-textlog']) ? $settings['display-textlog'] : false;
$mail = $queue->result->item[0];
if (isset($settings['display-transport'][$mail->msgtransport])) $transport = $settings['display-transport'][$mail->msgtransport];
if (isset($settings['display-listener'][$mail->msglistener])) $listener = $settings['display-listener'][$mail->msglistener];
if ($_GET['type'] == 'queue' && $mail->msgaction == 'DELIVER')
	$desc = 'In queue (retry '.$mail->msgretries.') <span class="semitrans">'.htmlspecialchars($mail->msgerror).'</span>';
else
	$desc = htmlspecialchars($mail->msgdescription);
if ($_GET['type'] == 'queue') {
	$uniq = uniqid();
	$command = array('previewmessage', $mail->msgpath, $uniq);
	if ($mail->msgdeltapath)
		$command[] = $mail->msgdeltapath;
	$data = soap_exec($command, $client);
	$data = str_replace("\r\n", "\n", $data);
	$data = explode("$uniq|", $data);
	$result = array();
	$result['HEADERS'] = trim($data[0]);
	for ($i = 1; $i < count($data); ++$i) {
		list($type, $content) = explode("\n", $data[$i], 2);
		$result[$type] = trim($content);
	}
	if (isset($result['TEXT']) || isset($result['HTML'])) {
		require_once('inc/htmlpurifier-4.6.0-lite/library/HTMLPurifier.auto.php');
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$config->set('URI.Disable', true);
		$purifier = new HTMLPurifier($config);
		$header = $result['HEADERS'];
		$headerdelta = $result['HEADERS-DELTA'];
		$attachments = $result['ATTACHMENTS'] != "" ? array_map(function ($k) { return explode('|', $k); }, explode("\n", $result['ATTACHMENTS'])) : array();

		$body = isset($result['TEXT']) ? htmlspecialchars($result['TEXT']) : $result['HTML'];
		$body = trim($purifier->purify($body));
		$encode = isset($result['TEXT']) ? 'TEXT' : 'HTML';
	} else {
		$encode = 'TEXT';
		$body = 'Preview not available';
	}
}

$title = 'Message';
$javascript[] = 'static/preview.js';
$javascript[] = 'static/diff_match_patch.js';
$javascript[] = 'static/diff.js';
require_once('inc/header.php');
?>
			<form>
				<div class="item">
					<div class="button back" onclick="history.back()">Back</div>
				</div>
				<?php if ($logs) { ?>
				<div class="item">
					<a href="?page=log&queueid=<?php echo $queueid?>&node=<?php echo $node?>&type=<?php p($_GET['type']) ?>&id=<?php echo $mail->msgid ?>"><div class="button search">Text log</div></a>
				</div>
				<?php } ?>
				<?php if ($_GET['type'] == 'queue') { ?>
				<div class="item">
					<a href="?page=download&queueid=<?php echo $queueid?>&node=<?php echo $node?>"><div class="button down">Download</div></a>
				</div>
				<div class="item">
					<div class="button start tracking-actions">Actions...</div>
				</div>
				<?php } ?>
			</form>
		</div>
		<div class="fullpage">
			<?php if ($listener) { ?><div class="preview-header">Received by</div> <?php echo $listener ?><br><?php } ?>
			<div class="preview-header">Date</div> <?php p(strftime('%Y-%m-%d %H:%M:%S', $mail->msgts0 - $_SESSION['timezone'] * 60)) ?><br>
			<div class="preview-header">Server</div> <?php p($mail->msgfromserver) ?><br>
			<?php if ($mail->msgsasl) { ?><div class="preview-header">User</div> <?php p($mail->msgsasl) ?><br><?php } ?>
			<div class="preview-header">From</div> <?php p($mail->msgfrom) ?><br>
			<div class="preview-header">To</div> <?php p($mail->msgto) ?><br>
			<div class="preview-header">Subject</div> <?php p($mail->msgsubject) ?><br>
			<div class="preview-header">Action</div> <?php p(ucfirst(strtolower($mail->msgaction))) ?><br>
			<?php if ($desc) { ?><div class="preview-header">Details</div> <?php echo $desc ?><br><?php } ?>
			<?php if ($transport) { ?><div class="preview-header">Destination</div> <?php echo $transport ?><br><?php } ?>
			<div class="preview-header">ID</div> <?php p($mail->msgid.':'.$mail->id) ?><br>
			<div class="hr"></div>

			<?php
			if ($encode == 'TEXT')
				echo '<pre>'.$body.'</pre>';
			else if ($body)
				echo $body;
			?>

			<?php if (count($attachments) > 0) { ?>
			<div class="preview-attachments">
			<?php foreach ($attachments as $a) { ?>
				<div class="preview-attachment"><?php p($a[2]) ?> (<?php echo round($a[1]/1024, 0) ?> KiB)</div>
			<?php } ?>
			</div>
			<?php } ?>
			<?php if ($header != '') { ?>
			<div style="clear:both;margin-top:5px;">
				<div style="float:left;margin:5px;height:8px;width:8px;background-color:#ddffdd;border: 1px solid #ccc;"></div>
				<div style="float:left;font-size:10px;padding-top:5px;color:green;margin-right:10px;">Added</div>
				<div style="float:left;margin:5px;height:8px;width:8px;background-color:#ffdddd;border: 1px solid #ccc;"></div>
				<div style="float:left;font-size:10px;padding-top:5px;color:red;">Removed</div>
				<div class="preview-headers"></div>
			</div>
			<br>
			<script>
				var headers_original = <?php echo json_encode($header); ?>;
				var headers_modified = <?php echo json_encode($headerdelta); ?>;
				$(".preview-headers").html(diff_lineMode(headers_original,
					headers_modified ? headers_modified : headers_original, true));
			</script>
			<?php } ?>
			<?php if ($scores && count($mail->msgscore->item) > 0) { ?>
			<table class="list">
				<thead>
					<tr>
						<th>Engine</th>
						<th>Result</th>
						<th>Signature</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$rpd[0] = 'Unknown';
				$rpd[10] = 'Suspect';
				$rpd[40] = 'Valid bulk';
				$rpd[50] = 'Bulk';
				$rpd[100] = 'Spam';
				foreach ($mail->msgscore->item as $score) {
					list($num, $text) = explode("|", $score->second);
					echo '<tr>';
					switch ($score->first) {
						case 0;
							echo '<td>SpamAssassin</td><td>'.$num.'</td><td class="semitrans">'.str_replace(',', ', ', $text).'</td>';
						break;
						case 1;
							$res = 'Ok';
							if ($text)
								$res = 'Virus';
							echo '<td>Kaspersky</td><td>'.$res.'</td><td class="semitrans">'.$text.'</td>';
						break;
						case 3;
							echo '<td>CYREN</td><td>'.$rpd[$num].'</td><td class="semitrans">'.$text.'</td>';
						break;
						case 4;
							$res = 'Ok';
							if ($text)
								$res = 'Virus';
							echo '<td>ClamAV</td><td>'.$res.'</td><td class="semitrans">'.$text.'</td>';
						break;
					}
					echo "</tr>";
				} ?>
				</tbody>
			</table>
			<?php } ?>

			<form id="actionform" method="post" action="?page=preview&node=<?php p($node) ?>&queueid=<?php p($queueid) ?>">
				<input type="hidden" name="action" id="action" value="">
				<input type="hidden" name="referer" id="referer" value="<?php p(isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER']); ?>">
			</form>
	</div>
<?php require_once('inc/footer.php'); ?>
