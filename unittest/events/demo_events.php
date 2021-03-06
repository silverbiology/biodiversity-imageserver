<?php
require_once('phpBIS.php');

$sdk = new phpBIS('{yourKey}', 'http://bis.silverbiology.com/dev/resources/api');

$eventId = $_REQUEST['eventId'];

$imageList = $sdk->listImagesByEvent($eventId);

if(!$imageList) {
	echo $sdk->lastError['code']. ' : ' . $sdk->lastError['msg'];
	exit;
}

$processTime = $imageList['processTime'];

$url = array();
$imid = array();
if(is_array($imageList['imageIds']) && count($imageList['imageIds'])) {
	foreach($imageList['imageIds'] as $imageId) {
		$imid[] = $imageId;
		$url[] = $sdk->getURL('ID', $imageId, 'l');
	}
}
$listEvents = $sdk->listEvents(0, 1, $eventId, null, null, null, null);
$processTime += $listEvents['processTime'];
$listEventTypes = $sdk->listEventTypes(0, 1, $listEvents['results'][0]['eventTypeId'], null, null, null, null);
$processTime += $listEventTypes['processTime'];

?>
<HTML>
<HEAD>
<TITLE>Events - Demo - <?php echo $listEvents['results'][0]['title']; ?></TITLE>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script type="text/javascript" src="coin-slider.min.js"></script>
<link rel="stylesheet" href="coin-slider-styles.css" type="text/css" />
<script type="text/javascript">
	$(document).ready(function() {
		$('#coin-slider').coinslider({width:700, height: 500});
	});
</script>

</HEAD>
<BODY>
Load Time : <span id="loadTime"></span>
<br />
<H3 align="center"><?php echo $listEvents['results'][0]['title']; ?></H3>
<BR />
<div style="text-align:center;"><?php echo 'Geo Location : '.$listEvents['results'][0]['admin_0'].', '.$listEvents['results'][0]['country']; ?></div>
<div id='coin-slider' style="margin-left:auto; margin-right:auto;">
	<?php
	for($i=0;$i<count($url);$i++) {
	$imgAttr = $sdk->listImageAttributes($imid[$i]);
	$processTime += $imgAttr['processTime'];
	?>
	<a href="#">
		<img src='<?php echo $url[$i];  ?>' width="600" height="400" >
		<span>
		<?php
		if(is_array($imgAttr['data'])) {
		foreach($imgAttr['data'] as $attr) {
			echo $attr['key']. ' : ';
			$cflag = 0;
			foreach($attr['values']	as $val) {
				if($cflag) echo ', ';
				echo $val['value'];
				$cflag++;
			}
			echo '<br />';
		}}
		//echo '<br />Image Id : '.$imid[$i];
		?>
		</span>
	</a>
	<?php
	}
	?>
</div>
<script type="text/javascript">
	document.getElementById("loadTime").innerHTML = '<?php printf("%.5f", $processTime ); ?>' + ' s';
</script>
</BODY>
</HTML>