<?php
/*
 * this message is sent from a team server to the contest server.
 */
class ContestAPIMessage extends ContestMessage {
	protected $apikey;

	public function  __construct($data) {
		parent::__construct(null);

		if (!isset($data->apikey)) {
			throw new ContestException('api key missing', 400);
		}
		
		$this->apikey = $data['apikey'];
	}

	public static function createMessage($msg, $data = null) {
		switch ($msg) {
			case 'start':
			case 'stop':
			case 'sync':
			case 'trigger':
				$classname = 'ContestAPI' . ucfirst($msg);
				return new $classname($data);
			default:
				//return parent::createMessage($msg, $data);
				throw new ContestException("unknown message type", 400);
		}
	}

	public function getResponse() {
		return new ContestAPIResponse();
	}
}

class ContestAPIStart extends ContestAPIMessage {
	public function __toString() {
		return 'start' . PHP_EOL .
			'apikey: ' . $this->apikey;
	}

	public function __toJSON() {
		$struct = array();
		$struct['msg'] = 'start';
		$struct['apikey'] = $this->apikey;
		$struct['version'] = self::VERSION;

		return plista_json_encode($struct);
	}
}

class ContestAPIStop extends ContestAPIMessage {
	public function __toString() {
		return 'stop' . PHP_EOL .
			'apikey: ' . $this->apikey;
	}

	public function __toJSON() {
		$struct = array();
		$struct['msg'] = 'stop';
		$struct['apikey'] = $this->apikey;
		$struct['version'] = self::VERSION;

		return plista_json_encode($struct);
	}
}

class ContestAPISync extends ContestAPIMessage {
	private $startid;

	public function __construct($data) {
		parent::__construct($data);

		if (!isset($data->startid)) {
			throw new ContestException('start id missing', 400);
		}

		if (!is_numeric($data->startid)) {
			throw new ContestException('only numeric ids are allowed', 400);
		}

		$this->startid = $data->startid;
	}

	public function __toString() {
		return 'sync' . PHP_EOL .
			'apikey: ' . $this->apikey . PHP_EOL .
			'startid: ' . $this->startid;
	}

	public function __toJSON() {
		$struct = array();
		$struct['msg'] = 'sync';
		$struct['apikey'] = $this->apikey;
		$struct['startid'] = $this->startid;
		$struct['version'] = self::VERSION;
		
		return plista_json_encode($struct);
	}
}

class ContestAPITrigger extends ContestAPIMessage {
	private $type;

	public function __construct($data) {
		parent::__construct($data);

		if (isset($data->type)) {
			if (!in_array($data->type, array('impression', 'feedback', 'error'))) {
				throw new ContestException('unsupported type', 400);
			}

			$this->type = $data->type;
		} else {
			$this->type = 'impression';
		}
	}

	public function __toString() {
		return 'trigger' . PHP_EOL .
			'team: ' . $this->team->id . PHP_EOL .
			'type: ' . $this->type;
	}

	public function __toJSON() {
		$struct = array();
		$struct['msg'] = 'trigger';
		$struct['team'] = $this->team->id;
		$struct['type'] = $this->type;
		$struct['version'] = self::VERSION;

		return plista_json_encode($struct);
	}
}

class ContestAPIResponse extends ContestAPIMessage {
	// don't call parent constructor, since it expects an api key which we don't have.
	public function __construct() { }
	
	public function __toString() {
		return 'ok';
	}

	public function __toJSON() {
		return plista_json_encode(array('msg' => 'ok'));
	}
}
