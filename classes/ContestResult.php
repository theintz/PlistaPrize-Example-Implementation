<?php
/*
 * this message is created on the team server in response to a ContestImpression. it is then sent to the contest server, which
 * in turn forwards it to the live server.
 */
class ContestResult extends ContestMessage {
	private $items;

	public function __construct($data) {
		parent::__construct($data);

		if (empty($data)) {
			throw new ContestException("no data given", 400);
		}

		if (!isset($data->items) || !is_array($data->items)) {
			throw new ContestException("no item data", 400);
		}

		$this->items = array();

		foreach ($data->items as $item) {
			if (!isset($item->id)) {
				throw new ContestException('need item id', 400);
			}

			$this->items[] = $item;
		}

		if (isset($data->team)) {
			if (!is_numeric($data->team->id)) {
				throw new ContestException('only numeric ids are allowed', 400);
			}

			$this->setTeam($data->team);
		}
	}

	public function __toArray() {
		return array(
			'items' => $this->getItemIdsAsString()
		) + parent::__toArray();
	}

	public function __toJSON() {
		$struct = array();

		$struct['msg'] = 'result';

		foreach ($this->items as $item) {
			$struct['items'][] = array('id' => $item->id);
		}

		if ($this->team != null) {
			$struct['team']['id'] = $this->team->id;
		}

		$struct['version'] = self::VERSION;

		return plista_json_encode($struct);
	}

	public function getItemIdsAsString() {
		if (count($this->items) < 1) {
			return null;
		}
		
		$ret = '';

		foreach ($this->items as $item) {
			$ret .= $item->id . ',';
		}

		return substr($ret, 0, -1);
	}
}
