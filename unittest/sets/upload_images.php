<?php
/* 
Important : Code for uploading images using SDK. Running this code multiple times will lead to duplicate entries in database.
*/

require_once('phpBIS.php');

$time_start = microtime(true);
$sdk = new phpBIS('{yourkey}', 'http://bis.silverbiology.com/dev/resources/api');


/* List of images to be uploaded */
$imageUrls = array(
		  'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s1.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s2.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s3.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s4.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s5.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s6.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s7.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s8.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/s9.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/sA.jpg'
		, 'http://bis.silverbiology.com/dev/demo_page_sets/sample_images/sB.jpg'
		 );

/* Display image URL(s) to prevent unintented uploads */
if((!isset($_GET['confirm'])) || (isset($_GET['confirm']) && $_GET['confirm']!='true')) {
	if(is_array($imageUrls) && count($imageUrls)) {
		$i = 1;
		foreach($imageUrls as $imageUrl) {
			echo $i++ . ') ' . $imageUrl . '<br />';
		}
		echo "<br /><br />To confirm and upload, reload the page with an extra parameter 'confirm=true'";
	} else {
		echo 'No images to upload. See source code.';
	}
	exit;
}

/* Upload each image and add barcode */
$data = array();
if(is_array($imageUrls) && count($imageUrls)) {
	foreach($imageUrls as $imageUrl) {
		$barcode = uniqid('');
		$result = $sdk->addImageFromURL($imageUrl, 2, '/jun22/'.$barcode);
		$sdk->addBarcodeToImage($result['image_id'], $barcode);
		$tmp['image_id'] = $result['image_id'];
		$tmp['barcode'] = $barcode;
		$tmp['url'] = $imageUrl;
		$data[] = $tmp;
	}
} else {
	echo 'No images to upload, See source code.';
	exit;
}

header('Content-type: application/json');
print(json_encode(array("success" => true, "process_time" => microtime(true) - $time_start, "total_images" => count($data), "data" => $data)));

?>
