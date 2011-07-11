<?php
require_once 'config.php';

// $handler variable is an implementation of the interface ContestHandler. put your application logic there.
$handler = ContestHandlerImpl::getInstance();

// read entire message body into a variable
$msg = file_get_contents("php://input");

// the message may arrive url encoded
$msg = urldecode($msg);

try {
	// parse plain json into a ContestMessage
	$msg = ContestMessage::fromJSON($msg);

	if ($msg instanceof ContestImpression) {
		// call the handler method, which is also responsible for posting the data back to the contest server
		$handler->handleImpression($msg);
	} else if ($msg instanceof ContestFeedback) {
		// no response required here
		$handler->handleFeedback($msg);
	} else if ($msg instanceof ContestError) {
		// yup, it's an error
		$handler->handleError($msg);
	} else {
		// we don't know how to handle anything else
		$e = new ContestException('unknown message type: ' . get_class($msg));
		$e->getError()->postTo('stdout');
		die();
	}
} catch (ContestException $e) {
	$e->getError()->postTo('stdout');
	die();
}