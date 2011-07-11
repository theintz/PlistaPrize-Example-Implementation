<?php
/*
 * this message is sent from the contest server to the live server in response to a ContestImpression.
 */
class ContestResponseImpression extends ContestMessage {
	public function __toString() {
		return 'processing';
	}

	public function __toJSON() {
		return StringUtil::plista_json_encode(array('msg' => 'processing', 'version' => self::VERSION));
	}
}
