<?php
class ContestException extends Exception {
	private $error;

	public function __construct($message, $code = 500) {
		if ($message instanceof ContestError) {
			parent::__construct($message->getMessage(), $message->getCode());

			$this->error = $message;
		} else if ($message instanceof Exception) {
			parent::__construct($message->getMessage() . ' Code: '. $message->getCode(), $code, $message);
		} else {
			parent::__construct($message, $code);
		}
	}

	public function getError() {
		if ($this->error == null) {
			$data = new stdClass;
			$data->error = 'Exception: ' . $this->getMessage();
			$data->code = $this->getCode();

			$this->error = new ContestError($data);
		}

		return $this->error;
	}
}
