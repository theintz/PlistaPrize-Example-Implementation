<?php
/*
 * This code demonstrates how to communicate with the contest server using the supplied classes.
 * Refer to the API documentation for more in-depth information about the various requests and responses.
 */

// this variable holds the url of the endpoint of the contest server
$server = 'http://contest.plista.com/api/api.php';

// this variable defines the type of message you want to send to the server
// available options are: start, stop, sync, trigger
$type = 'start';

// this variable holds your secret api key
$apikey = '0123456789abcdef0123456789abcdef';

// any data that needs to be passed to the api message object goes into here
$data = new stdClass;
$data->apikey = $apikey;

try {
	// create a new message
	$msg = ContestAPIMessage::createMessage($type, $data);
	// and post it to the server, fetching the response at the same time
	$resp = $msg->postTo($server);
} catch (ContestException $e) {
	// oh no, an exception
	die("exception caught: {$e->getMessage()}");
}

if (!($resp instanceof ContestMessage)) {
	// usually the server only sends back json data which gets converted into an appropriate object automatically,
	// so receiving anything but a ContestMessage means that something awful happened :)
	die("received plain text from server: $resp");
}

if ($resp instanceof ContestError) {
	// some sort of error occured, better handle it appropriately
	die("received error: {$resp->getMessage()}");
}

if (!($resp instanceof ContestAPIResponse)) {
	// the only proper response to an api request is a ContestAPIResponse message, anything else is an error
	die('received message: ' . get_class($resp));
}

// at this point everything is bueno
echo $resp;
