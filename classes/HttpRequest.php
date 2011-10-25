<?php
/*
 * a http connector with special support for ContestMessages
 *
 */
class HttpRequest {
	private $host;
	private $port;
	private $path;
	private $connection = null;
	private $headers = array();

	private $callback = null;
	private $response = null;
	private $buffer = null;

	private $headers_read = false;
	private $headers_continue = false;
	private $transfer_chunked = false;
	private $expected_length = 0;

	const CONTENT_TYPE = 'application/json';
	const ENCODING = 'utf-8';

	const MAX_RESPONSE_SIZE = 0xFFFF; // 64 kB

	public function __construct($url, $callback = null) {
		if (empty($url)) {
			throw new HttpException("url cannot be empty");
		}

		$url_parts = parse_url($url);

		$this->host = $url_parts['host'];
		$this->port = (empty($url_parts['port']) ? 80 : $url_parts['port']);
		$this->path = $url_parts['path'];

		$this->headers['Host'] = $this->host;
		$this->headers['Connection'] = 'close';
		$this->headers['Cache-Control'] = 'no-cache';

		if (is_callable($callback)) {
			$this->callback = $callback;
		}
	}

	public function __destruct() {
		try {
			$this->disconnect();
		} catch (Exception $e) { }
	}

	private function connect() {
		if ($this->connection != null) {
			// already connected, simply return
			return;
		}

		$errno = '';
		$errstr = '';

		$this->connection = @fsockopen($this->host, $this->port, $errno, $errstr, PLISTA_CONTEST_TIMEOUT);

		if ($this->connection === false) {
			throw new HttpException("error during connect: $errstr", ($errno == SOCKET_ETIMEDOUT));
		}

		// this is apparently not implemented at all
		//stream_encoding($this->connection, $this->encoding);
		// streams are opened in blocking mode, so no reason to set it to blocking again
		//stream_set_blocking($this->connection, 1);

		stream_set_timeout($this->connection, (int)(floor(PLISTA_CONTEST_TIMEOUT)), (int)((PLISTA_CONTEST_TIMEOUT - floor(PLISTA_CONTEST_TIMEOUT)) * 1000000));

		$this->response = null;
		$this->buffer = null;
		$this->headers_read = false;
		$this->headers_continue = false;
		$this->transfer_chunked = false;
		$this->expected_length = 0;
	}

	private function disconnect() {
		if ($this->connection == null) {
			// already disconnected, simply return
			return;
		}

		@fclose($this->connection);
		$this->connection = null;

		if ($this->buffer != null) {
			// something messed up
			throw new HttpException("buffer not empty at disconnect");
		}
	}

	public function post($data, $fetch_response = true, $path = null) {
		if (empty($data)) {
			throw new HttpException("no data given", false);
		}

		$contenttype = 'application/x-www-form-urlencoded';
		
		if (is_string($data)) {
			$data = urlencode($data);
		} else if (is_array($data)) {
			$data = http_build_query($data);
		} else if ($data instanceof ContestMessage) {
			$data = StringUtil::plista_json_encode($data);
			
			$contenttype = 'application/json';
		}

		if (!is_string($data)) {
			throw new HttpException("wrong datatype", false);
		}

		$encoding = mb_detect_encoding($data);

		if ($encoding != self::ENCODING) {
			$data = mb_convert_encoding($data, self::ENCODING, $encoding);
		}

		if (empty($path)) {
			$path = $this->path;
		}

		// set header values
		$this->headers['Content-Type'] = $contenttype . '; charset=' . self::ENCODING;
		$this->headers['Content-Length'] = mb_strlen($data);
		/*$this->headers['Accept'] = self::CONTENT_TYPE;
		$this->headers['Accept-Charset'] = self::ENCODING;*/

		// build request
		$request = "POST $path HTTP/1.1\r\n";

		foreach ($this->headers as $header => $value) {
			$request .= "$header: $value\r\n";
		}

		$request .= "\r\n$data";

		// and send it
		$this->sendRequest($request, $fetch_response);

		if ($fetch_response) {
			// fetch and parse response
			$resp = $this->receiveResponse();

			return $resp;
		}
	}

	public function get($path = null) {
		if (empty($path)) {
			$path = $this->path;
		}

		// set header values
		/*$this->headers['Accept'] = self::CONTENT_TYPE;
		$this->headers['Accept-Charset'] = self::ENCODING;*/

		// build request
		$request = "GET $path HTTP/1.1\r\n";

		foreach ($this->headers as $header => $value) {
			$request .= "$header: $value\r\n";
		}

		$request .= "\r\n";

		// and send it
		$this->sendRequest($request);

		// fetch and parse response
		$resp = $this->receiveResponse();

		return $resp;
	}

	private function sendRequest($request, $keep_connected = true) {
		// connect the socket
		$this->connect();

		// write out data
		$result = @fwrite($this->connection, $request);

		if ($result === false || $result != mb_strlen($request)) {
			$this->disconnect();
			throw new HttpException("write to socket failed", false);
		}

		if (!$keep_connected) {
			$this->disconnect();
		}
	}

	private function receiveResponse() {
		$time_start = microtime(true);
		$keep_reading = true;
		$response_size = 0;

		while ($keep_reading) {
			$time_left = PLISTA_CONTEST_TIMEOUT - (microtime(true) - $time_start);

			// check for overall timeout
			if ($time_left <= 0.0) {
				throw new HttpException("global timeout during read", true);
			}

			// read response
			$ready = @stream_select($a = array($this->connection), $b = array(), $c = array(),
				(int)(floor($time_left)), (int)(($time_left - floor($time_left)) * 1000000));

			// check for eof
			if (@feof($this->connection)) {
				break;
			}

			if (!$ready) {
				$this->disconnect();
				throw new HttpException("local timeout during read", true);
			}

			// read single line
			$line = @fgets($this->connection, self::MAX_RESPONSE_SIZE);

			if ($line === false) {
				$this->disconnect();
				throw new HttpException("read from socket failed", false);
			}

			$response_size += count($line);

			if ($response_size > self::MAX_RESPONSE_SIZE) {
				$this->disconnect();
				throw new HttpException("response was longer than max length", false);
			}

			// call parser method and determine whether to continue the loop
			$keep_reading = $this->parseLine($line);
		}

		$this->disconnect();

		return $this->response;
	}

	private function parseLine($msg) {
		// check encoding
		$encoding = mb_detect_encoding($msg);

		if ($encoding != self::ENCODING) {
			$msg = mb_convert_encoding($msg, self::ENCODING, $encoding);
		}

		// process headers
		if (!$this->headers_read) {
			// an empty line marks the beginning of the content
			if (mb_strlen(trim($msg)) < 1) {
				if (!$this->headers_continue) {
					$this->headers_read = true;
				}

				$this->headers_continue = false;
			}

			// set flag for 100 response code, the next blank line will be ignored
			if (preg_match('/^HTTP\\/1.1\\s100\\sContinue$/i', trim($msg))) {
				$this->headers_continue = true;
			}

			// set flag for chunked transfer encoding
			if (preg_match('/^Transfer-Encoding:\\s?chunked$/i', trim($msg))) {
				if (!$this->headers_continue) {
					$this->transfer_chunked = true;
					$this->expected_length = 0;
				}
			}

			// test for content length
			$matches = array();
			if (preg_match('/^Content-Length:\\s?(\\d+)$/i', trim($msg), $matches)) {
				if (!$this->headers_continue) {
					$this->expected_length = (int)$matches[1];
				}
			}

			return true;
		}

		if ($this->expected_length > 0 && mb_strlen(trim($msg)) != $this->expected_length) {
			// assume the next line(s) will fix this
			$this->buffer .= $msg;

			if (mb_strlen($this->buffer) < $this->expected_length) {
				// wait for next message
				return true;
			}

			// reset buffer and truncate if neccessary, continue with message
			$msg = mb_substr($this->buffer, 0, $this->expected_length);
			$this->buffer = null;
		}

		if ($this->transfer_chunked) {
			 // we have already read the chunk size, so $msg contains the content
			if ($this->expected_length > 0) {
				// all we have to do is reset the flag
				$this->expected_length = 0;
			// beginning of content or we just read a line of content, so $msg contains a chunk size
			} else {
				// strip away possible extensions and comments
				if (mb_strpos($msg, ';') > 1) {
					$msg = mb_substr($msg, 0, mb_strpos($msg, ';'));
				}

				// check for end of content, discard footers
				if ($msg == '0') {
					return false;
				}

				$this->expected_length = hexdec($msg);

				return true;
			}
		}

		// try to parse line into ContestMessage
		if (mb_substr($msg, 0, 1) == '{') {
			try {
				$msg = ContestMessage::fromJSON($msg);
			} catch (ContestException $e) {
				try {
					// it may theoretically also be an api message, so we test for that as well
					$msg = ContestAPIMessage::fromJSON($msg);
				} catch (ContestException $e) { }
			}
		}

		// call callback with newly generated message
		if (is_callable($this->callback)) {
			try {
				// return return value from callback
				return call_user_func($this->callback, $msg);
			} catch (Exception $e) {
				$this->disconnect();
				throw new HttpException($e);
			}
		}

		if (is_array($this->response)) {
			$this->response[] = $msg;
		} else if (!empty($this->response)) {
			if ($msg instanceof ContestMessage) {
				$this->response = array($this->response, $msg);
			} else {
				$this->response .= $msg;
			}
		} else {
			$this->response = $msg;
		}

		return true;
	}
}
