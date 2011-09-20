<?php
/*
 * this message is created on the live server, then sent to the contest server and from there on forwarded to all teams.
 */
class ContestFeedback extends ContestMessage {
	private $client;
	private $domain;
	private $source;
	private $target;
	private $category;
	
	public function __construct($data) {
		parent::__construct($data);

		if (empty($data)) {
			throw new ContestException("no data given", 400);
		}

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

		if (!isset($data->source)) {
			throw new ContestException("no source item data", 400);
		}

		if (!isset($data->source->id)) {
			throw new ContestException('need item id', 400);
		}

		$this->source = $data->source;

		if (!isset($data->target)) {
			throw new ContestException("no target item data", 400);
		}

		if (!isset($data->target->id)) {
			throw new ContestException('need item id', 400);
		}

		$this->target = $data->target;

		if (isset($data->context)) {
			if (isset($data->context->category)) {
				if (!is_numeric($data->context->category->id)) {
					throw new ContestException('only numeric ids are allowed', 400);
				}

				$this->category = $data->context->category;
			}
		}

		// feedbacks may or may not have a team
		if (isset($data->config)) {
			if (isset($data->config->team)) {
				if (!is_numeric($data->config->team->id)) {
					throw new ContestException('only numeric ids are allowed', 400);
				}

				$this->setTeam($data->config->team);
			}
		}
	}

	public function __toArray() {
		return array(
			'client' => $this->client->id,
			'domain' => $this->domain->id,
			'source' => $this->source->id,
			'target' => $this->target->id,
			'category' => ($this->category == null ? 'null' : $this->category->id),
		) + parent::__toArray();
	}

	public function __toJSON() {
		$struct = array();

		$struct['msg'] = 'feedback';

		$struct['client']['id'] = $this->client->id;
		$struct['domain']['id'] = $this->domain->id;
		$struct['source']['id'] = $this->source->id;
		$struct['target']['id'] = $this->target->id;

		if ($this->category != null) {
			$struct['context']['category']['id'] = $this->category->id;
		}

		if ($this->team != null) {
			$struct['config']['team']['id'] = $this->team->id;
		}

		$struct['version'] = self::VERSION;

		return StringUtil::plista_json_encode($struct);
	}

	public function getResponse() {
		return self::createMessage('thanks');
	}
	
	public function __get($name) {
		if (!in_array($name, array('client', 'domain', 'source', 'target', 'category'))) {
			return null;
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if (!in_array($name, array('client', 'domain', 'source', 'target', 'category'))) {
			return false;
		}

		return parent::__isset($name);
	}
}
