<?php
/*
 * this message can be generated on any of the three participants (live, contest or team servers), and also be sent to any one.
 */
class ContestError extends ContestMessage {
	private $message;
	private $code;

	public function __construct($data) {
		parent::__construct($data);

		if (!isset($data->error)) {
			throw new ContestException('no error message given', 400);
		}

		$this->message = $data->error;
		$this->code = (isset($data->code) ? $data->code : 500);
	}

	public function __toJSON() {
		return plista_json_encode(array("error" => $this->message));
	}

	public function __toString() {
		return 'error: ' . $this->message . PHP_EOL .
			'code: ' . $this->code;
	}

	public function getMessage() {
		return $this->message;
	}

	public function getCode() {
		return $this->code;
	}

	public function postTo($target, $fetch_response = false, $callback = null) {
		if ($target == 'stdout' && !headers_sent()) {
			switch ($this->code) {
				case 400: $code_str = '400 Bad Request';
					break;
				case 401: $code_str = '401 Unauthorized';
					break;
				case 500: $code_str = '500 Internal Server Error';
					break;
				default: $code_str = '200 OK';
					break;
		}

			header("HTTP/1.1 $code_str");
		}

		parent::postTo($target, false, $callback);
	}
}
