<?php
/*
 * this message is sent from the contest server to the live server in response to a ContestImpression.
 */
class ContestResponseImpression extends ContestMessage {
	public function __toString() {
		return 'processing';
	}

	public function __toJSON() {
		if (!empty($this->team)) {
			$_team = new stdClass;
			$_team->id = $this->team->id;
		}

		return plista_json_encode(array(
			'msg' => 'processing',
			'version' => self::VERSION,
			'team' => (isset($_team) ? $_team : null)
		));
	}
}
