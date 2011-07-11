<?php
class ContestHandlerImpl implements ContestHandler {
    private static $instance;

	private function __construct() { }

	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new ContestHandlerImpl();
		}

		return self::$instance;
	}

	/*
	 * this example implementation uses a basic last in first out mechanism. the five most recent items it obeserves are
	 * stored in a csv file and merely echoed back when an impression is received.
	 */
	public function handleImpression(ContestImpression $impression) {
		$data = file_get_contents('data.txt');

		if (empty($data)) {
			throw new ContestException('could not read data file', 500);
		}

		if (strpos($data, ',') !== false) {
			$data = explode(',', $data);
		} else if (strlen($data) > 1) {
			$data = array($data);
		} else {
			$data = array(0);
		}

		$itemid = isset($impression->item->id) ? $impression->item->id : 0;

		if ($impression->recommend) {
			$result_data = array();
			$i = 0;

			foreach ($data as $data_item) {
				if ($data_item == $itemid) {
					continue;
				}

				if (++$i > $impression->limit) {
					break;
				}

				$data_object = new stdClass;
				$data_object->id = $data_item;
				
				$result_data[] = $data_object;
			}

			if ($i <= $impression->limit) {
				throw new ContestException('not enough data', 500);
			}

			$result_object = new stdClass;
			$result_object->items = $result_data;
			$result_object->team = $impression->team;

			$result = new ContestResult($result_object);
			// post the result back to the contest server
			$result->postTo('stdout');
		}

		if (!in_array($itemid, $data)) {
			if (count($data) > 10) {
				array_pop($data);
			}

			array_unshift($data, $itemid);

			$data = implode(',', $data);
			file_put_contents('data.txt', $data);
		}
	}

	public function handleFeedback(ContestFeedback $feedback) {
		// since we don't actually have an algorithm, we can simply ignore the feedback
		// unless we want to pick out the item ids to add to our list
	}

	public function handleError(ContestError $error) {
		//echo 'oh no, an error: ' . $error->getMessage();
	}
}
?>
