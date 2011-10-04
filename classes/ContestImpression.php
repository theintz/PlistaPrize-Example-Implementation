<?php
/*
 * this message is created on the live server, then sent to the contest server and from there on forwarded to all teams.
 */
class ContestImpression extends ContestMessage {
	protected $logId;
	
	protected $client;
	protected $domain;
	protected $item;
	protected $category;

	protected $timeout;
	protected $recommend;
	protected $limit;

	public function __construct($data) {
		parent::__construct($data);

		if (empty($data)) {
			throw new ContestException("no data given", 400);
		}

		if (!isset($data->id)) {
			throw new ContestException("no id given", 400);
		}

		if (!is_numeric($data->id)) {
			throw new ContestException('only numeric ids are allowed', 400);
		}

		$this->logId = $data->id;

		if (!isset($data->client)) {
			throw new ContestException("no client data", 400);
		}

		if (!is_numeric($data->client->id)) {
			throw new ContestException('only numeric ids are allowed', 400);
		}

		$this->client = $data->client;

		if (!isset($data->domain)) {
			throw new ContestException("no domain data", 400);
		}

		if (!is_numeric($data->domain->id)) {
			throw new ContestException('only numeric ids are allowed', 400);
		}

		$this->domain = $data->domain;

		if (isset($data->item)) {
			if (!isset($data->item->id)) {
				throw new ContestException('need item id', 400);
			}

			$this->item = $data->item;
		}

		if (isset($data->context)) {
			if (isset($data->context->category)) {
				if (!is_numeric($data->context->category->id)) {
					throw new ContestException('only numeric ids are allowed', 400);
				}

				$this->category = $data->context->category;
			}
		}

		if (!isset($data->config)) {
			throw new ContestException("no config data", 400);
		}
		
		if (isset($data->config->team)) {
			if (!is_numeric($data->config->team->id)) {
				throw new ContestException('only numeric ids are allowed', 400);
			}

			$this->setTeam($data->config->team);
		}

		if (isset($data->config->timeout)) {
			$this->timeout = $data->config->timeout;
		}

		if (isset($data->config->recommend)) {
			$this->recommend = $data->config->recommend;
		}

		if (isset($data->config->limit)) {
			$this->limit = $data->config->limit;
		} else {
			throw new ContestException("limit is required", 400);
		}
	}

	public function __toArray() {
		return array(
			'id' => $this->logId,
			'client' => $this->client->id,
			'domain' => $this->domain->id,
			'item' => ($this->item == null ? 'null' : $this->item->id),
			'category' => ($this->category == null ? 'null' : $this->category->id),
			'timeout' => $this->timeout,
			'limit' => $this->limit,
		) + parent::__toArray();
	}

	public function __toJSON() {
		$struct = array();

		$struct['msg'] = 'impression';
		$struct['id'] = ($this->logId != null ? $this->logId : 'null');

		$struct['client']['id'] = $this->client->id;
		$struct['domain']['id'] = $this->domain->id;

		if ($this->item != null) {
			$struct['item']['id'] = $this->item->id;
			$struct['item']['title'] = (isset($this->item->title) ? $this->item->title : null);
			$struct['item']['url'] = (isset($this->item->url) ? $this->item->url : null);
			$struct['item']['created'] = (isset($this->item->created) ? $this->item->created : null);
			$struct['item']['text'] = (isset($this->item->text) ? $this->item->text : null);
			$struct['item']['img'] = (isset($this->item->img) ? $this->item->img : null);
			$struct['item']['recommendable'] = (isset($this->item->recommendable) ? ($this->item->recommendable) : true);
		}

		if ($this->category != null) {
			$struct['context']['category']['id'] = $this->category->id;
		}

		$struct['config']['timeout'] = $this->timeout;
		$struct['config']['recommend'] = $this->recommend;
		$struct['config']['limit'] = $this->limit;

		if ($this->team != null) {
			$struct['config']['team']['id'] = $this->team->id;
		}

		$struct['version'] = self::VERSION;

		return plista_json_encode($struct);
	}

	public function getResponse() {
		$resp = self::createMessage('processing');
		$resp->setTeam($this->team);

		return $resp;
	}
	
	public function __get($name) {
		if (!in_array($name, array('client', 'domain', 'item', 'category', 'timeout', 'recommend', 'limit', 'team'))) {
			return null;
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if (!in_array($name, array('client', 'domain', 'item', 'category', 'timeout', 'recommend', 'limit', 'team'))) {
			return false;
		}

		return parent::__isset($name);
	}
}
