<?php
class HttpException extends ContestException {
	private $timeout;

	public function __construct($message, $timeout = false) {
		parent::__construct($message);

		$this->timeout = $timeout;
	}

	public function isTimeout() {
		return $this->timeout;
	}
}