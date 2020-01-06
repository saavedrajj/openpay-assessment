<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Express Newspapers rendition checker</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
</head>

<body>
	<div class="container">
		<h1>Express Newspapers rendition checker</h1>
		<?php
		include_once "credentials.php";
		$authString = "{$clientId}:{$clientSecret}";
		$bcToken = base64_encode($authString);
		$sizeFrames = 0;
		$sizeAudio = 0;

		# OAuth API: Get access_token  ********************************************************************************
		$cAccessToken = curl_init();
		curl_setopt_array($cAccessToken, array(
			CURLOPT_URL => "https://oauth.brightcove.com/v4/access_token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 360,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "grant_type=client_credentials",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Basic " . $bcToken . "",
				"Cache-Control: no-cache"
			),
		));
		$rAccessToken = curl_exec($cAccessToken);
		$eAccessToken = curl_error($cAccessToken);
		curl_close($cAccessToken);
		if ($eAccessToken) {  
			echo "cURL Error #:" . $eAccessToken; 
		} 
		else {
			$jAccessToken = json_decode($rAccessToken);
			$accessToken = $jAccessToken->access_token;
		}

		# CMS API: Total of videos ************************************************************************************				
		$cTotalVideos = curl_init();

		curl_setopt_array($cTotalVideos, array(
			CURLOPT_URL => "https://cms.api.brightcove.com/v1/accounts/" . $accountId . "/counts/videos/?q=created_at:" . $_POST['from'] . ".." . $_POST['to'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 360,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . $accessToken . "",
				"Cache-Control: no-cache",
				"Content-Type: application/json"
			),
		));

		$rTotalVideos = curl_exec($cTotalVideos);
		$eTotalVideos = curl_error($cTotalVideos);

		curl_close($cTotalVideos);
		if ($eTotalVideos) {
			echo "cURL Error #:" . $eTotalVideos;
		} else {
			$jTotalVideos = json_decode($rTotalVideos, true);
			$totalVideos = $jTotalVideos["count"];  
		}

		# Get Offset Iterations *******************************************************************************************
		$offsetIterations = $totalVideos / 100;
		if(is_int($offsetIterations)){}
			else {
				$offsetIterations = intval($offsetIterations) + 1;
			}
			?>

			<h2>Videos from <?php echo $_POST["from"];?> to <?php echo $_POST["to"];?></h2>
			<hr>
			<p>Videos with no renditions:</p>
			<div class="alert alert-danger" role="alert"><span id="no_rendition"></span></div>
			<p>Videos with wrong assets:</p>
			<div class="alert alert-danger" role="alert"><span id="wrong_asset"></span></div>
			<hr>
			<?php
			# Get videos in data range *********************************************************************************/
			$currentOffset = 0;

			for($i = 0; $i <= $offsetIterations - 1; $i++) { 
				$cVideo = curl_init();

				curl_setopt_array($cVideo, array(

					CURLOPT_URL => "https://cms.api.brightcove.com/v1/accounts/" . $accountId . "/videos/?q=created_at:" . $_POST['from'] . ".." . $_POST['to'] . "&offset=" . $currentOffset . "&limit=100&sort=created_at",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 360,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => array(
						"Authorization: Bearer " . $accessToken . "",
						"Cache-Control: no-cache",
						"Content-Type: application/json"
					),
				));

				$rVideo = curl_exec($cVideo);
				$eVideo = curl_error($cVideo);

				curl_close($cVideo);

				if ($eVideo) {
					echo "cURL Error #:" . $eVideo;
				} else {

					$jVideo = json_decode($rVideo, true);

					$videoTypeCount = 0;

					foreach($jVideo as $v) {
						$videoId = $jVideo[$videoTypeCount]['id'];							
						$deliveryType = $jVideo[$videoTypeCount]['delivery_type'];   
						$createdAt = $jVideo[$videoTypeCount]['created_at'];   

						# Dynamic Ingest Videos	*******************************************************************************
						if ($deliveryType=="static_origin" || $deliveryType=="unknown") { 

							$cDIVideo = curl_init();

							curl_setopt_array($cDIVideo, array(
								CURLOPT_URL => "https://cms.api.brightcove.com/v1/accounts/" . $accountId . "/videos/" . $videoId . "/assets/",
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 360,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "GET",
								CURLOPT_HTTPHEADER => array(
									"Authorization: Bearer " . $accessToken . "",
									"Cache-Control: no-cache",
									"Content-Type: application/json"
								),
							));

							$rDIVideo = curl_exec($cDIVideo);
							$eDIVideo = curl_error($cDIVideo);

							curl_close($cDIVideo);

							if ($eDIVideo) {  
								echo "cURL Error #:" . $eDIVideo; 
							} 
							else {
								$jDIVideo = json_decode($rDIVideo, true);

								if (empty($jDIVideo)) {
									?>
									<script>
										no_rendition.innerHTML += "<?php echo $videoId ?>" + " created at "+ "<?php echo $createdAt ?>" +"<br/>";							    
									</script>
									<?php
								} else {

									$DeliveryTypeCount = 0;
									foreach($jDIVideo as $v) {

										$assetId = $jDIVideo[$DeliveryTypeCount]['id'];												


										$frameHeight = $jDIVideo[$DeliveryTypeCount]['frame_height'];
										$frameWidth = $jDIVideo[$DeliveryTypeCount]['frame_width'];

										if ($frameWidth == $sizeFrames || $frameHeight == $sizeFrames) {

											?>
											<script>
												wrong_asset.innerHTML += "videoid: " +"<?php echo $videoId ?>" + " | " + "assetid: " + "<?php echo $assetId ?>" +"<br/>";	
											</script>
											<?php	
										}

										$DeliveryTypeCount++;									    
									}
								}


							}

						} 

						# Dynamic Delivery Videos *****************************************************************************
						if ($deliveryType=="dynamic_origin") { 
							$cDDVideo = curl_init();
							curl_setopt_array($cDDVideo, array(
								CURLOPT_URL => "https://cms.api.brightcove.com/v1/accounts/" . $accountId . "/videos/" . $videoId . "/assets/dynamic_renditions/",
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => "",
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 360,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => "GET",
								CURLOPT_HTTPHEADER => array(
									"Authorization: Bearer " . $accessToken . "",
									"Cache-Control: no-cache",
									"Content-Type: application/json"
								),
							));

							$rDDVideo = curl_exec($cDDVideo);
							$eDDVideo = curl_error($cDDVideo);

							curl_close($cDDVideo);

							if ($eDDVideo) {  
								echo "cURL Error #:" . $eDDVideo; 
							} 
							else {
								$jDDVideo = json_decode($rDDVideo, true);
								if (empty($jDDVideo)) {
									?>
									<script>
										no_rendition.innerHTML += "<?php echo $videoId ?>" + " created at "+ "<?php echo $createdAt ?>" +"<br/>";							    
									</script>
									<?php
								} else {
									$DeliveryTypeCount = 0;
									foreach($jDDVideo as $v) {

										$renditionId = $jDDVideo[$DeliveryTypeCount]['rendition_id'];	
										$createdAt= $jDDVideo[$DeliveryTypeCount]['created_at'];	
										$frameWidth= $jDDVideo[$DeliveryTypeCount]['frame_width'];
										$frameHeight= $jDDVideo[$DeliveryTypeCount]['frame_height'];		
										$mediaType= $jDDVideo[$DeliveryTypeCount]['media_type'];
										$size= $jDDVideo[$DeliveryTypeCount]['size'];																																
										#echo "rendition_id: " . $renditionId . "<br>";
										#echo "created_at: " . $createdAt . "<br>";		
										#echo "frame_width: " . $frameWidth . "<br>";	
										#echo "frame_height: " . $frameHeight . "<br>";	
										#echo "size: " . $size . "<br>";

										if ($mediaType=="video") {
											if ($frameWidth==$sizeFrames || $frameHeight==$sizeFrames) {
												?>
												<script>
													wrong_asset.innerHTML += "videoid: " +"<?php echo $videoId ?>" + " | " + "renditionId: " + "<?php echo $renditionId ?>" +"<br/>";	
												</script>
												<?php	
											}
										}
										if ($mediaType=="audio") {
											if ($size==$sizeAudio) {
												?>
												<script>
													wrong_asset.innerHTML += "videoid: " +"<?php echo $videoId ?>" + " | " + "renditionId: " + "<?php echo $renditionId ?>" +"<br/>";	
												</script>
												<?php	
											}
										}
										$DeliveryTypeCount++;									    
									}
								}
							}
						}
						$videoTypeCount++; 
					}
				}
				$currentOffset+=100;
			}
			?>
		</div>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
	</body>
	</html>