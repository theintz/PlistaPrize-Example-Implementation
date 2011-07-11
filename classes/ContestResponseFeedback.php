<?php
/*
 * this message is sent from the contest server to the live server in response to a ContestFeedback.
 */
class ContestResponseFeedback extends ContestMessage {
	public function __toString() {
		return 'thanks';
	}

	public function __toJSON() {
		return plista_json_encode(array('msg' => 'thanks', 'version' => self::VERSION));
	}
}
