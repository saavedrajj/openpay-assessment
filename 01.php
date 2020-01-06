<?php
/*
1. Consume the following web service. This is a dummy WS so will not return any response. You need to mock the response. See point # 2.
https://api.codingtest.com.au/product/products?brandid=153&pageNum=1&pageSize=5
*/

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();

$response = $client->request(
	'GET', 
	'https://api.codingtest.com.au/product/products/',
	[
		'query' => [
			'brandid' => 153,
			'pageNum' => 1,
			'pageSize' => 5
		]
	]
);

//var_dump($response);
echo $response->getBody();