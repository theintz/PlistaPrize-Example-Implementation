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

		if (isset($data->code)) {
			if (!is_numeric($data->code)) {
				throw new ContestException('only numeric codes are allowed', 400);
		}

			$this->code = $data->code;
		}

		if (isset($data->team)) {
			if (!is_numeric($data->team->id)) {
				throw new ContestException('only numeric ids are allowed', 400);
			}

			$this->team = $data->team;
		}
	}

	public function __toArray() {
		return array(
			'message' => $this->getMessage(),
			'code' => $this->getCode(),
		) + parent::__toArray();
	}

	public function __toJSON() {
		if (!empty($this->team)) {
			$_team = new stdClass;
			$_team->id = $this->team->id;
		}

		return plista_json_encode(array(
			'error' => $this->message,
			'code' => $this->code,
			'version' => self::VERSION,
			'team' => (isset($_team) ? $_team : null)
		));
	}

	public function getMessage() {
		return $this->message;
	}

	public function getCode() {
		return $this->code;
	}

	public function postBack() {
		if (!headers_sent()) {
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

		parent::postBack();
	}
}
