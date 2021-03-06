<?php
namespace WebSocket;

use Exception;

class WebSocketException extends Exception {}

/**
 * An trait used in both the server and client classes.
 *
 * It handles all encoding / decoding / masking / socket operations.
 *
 * https://github.com/vakata/websocket
 */
trait Base {
	/**
	 * all available opcodes
	 * @var array
	 */
	protected static $opcodes = [
		'continuation' => 0,
		'text' => 1,
		'binary' => 2,
		'close' => 8,
		'ping' => 9,
		'pong' => 10
	];
	/**
	 * buffer size for all operations in bytes (defaults to 4096)
	 * @var integer
	 */
	protected static $fragmentSize = 4096;
	/**
	 * the magic key used to generate websocket keys (per specs)
	 * @var string
	 */
	protected static $magic = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
	
	/**
	 * Send data to a socket in clear form (basically fwrite)
	 * @param  resource    &$socket the socket to write to
	 * @param string $data    the data to send
	 * @return bool             was the send successful
	 */
	public function sendClear($socket, string $data) : bool {
		return @fwrite($socket, $data) > 0;
	}
	/**
	 * Send data to a socket.
	 * @param  resource  &$socket the socket to send to
	 * @param  string  $data    the data to send
	 * @param  string  $opcode  one of the opcodes (defaults to "text")
	 * @param  boolean $masked  should the data be masked (per specs the server should not mask, defaults to false)
	 * @return bool           was the send successful
	 */
	public function sendData($socket, string $data, string $opcode = 'text', bool $masked = false): bool {
		while (strlen($data)) {
			try {
				$temp = substr($data, 0, static::$fragmentSize);
				$data = substr($data, static::$fragmentSize);
				if ($data === false) {
					$data = '';
				}
				$temp = $this->encode($temp, $opcode, $masked, strlen($data) === 0);
				if (!is_resource($socket) || get_resource_type($socket) !== 'stream') {
					return false;
				}
				$meta = stream_get_meta_data($socket);
				if ($meta['timed_out']) {
					return false;
				}
				if (@fwrite($socket, $temp) === false) {
					return false;
				}
				$opcode = 'continuation';
			} catch (WebSocketException $e) {
				break;
			}
		}
		return true;
	}
	/**
	 * Read clear data from a socket (basically a fread).
	 * @param  resource       &$socket the socket to read from
	 * @return string                the data that was read
	 */
	public function receiveClear(&$socket) : string {
		$data = '';
		$read = static::$fragmentSize;
		do {
			$buff = fread($socket, $read);
			if ($buff === false) return '';
			$data .= $buff;
			$meta = stream_get_meta_data($socket);
			$read = min((int) $meta['unread_bytes'], static::$fragmentSize);
			usleep(1000);
		} while (!feof($socket) && (int) $meta['unread_bytes'] > 0);
		if (strlen($data) === 1) {
			$data .= $this->receiveClear($socket);
		}
		return $data;
	}
	/**
	 * Read data from a socket (in websocket format)
	 * @param resource  &$socket the socket to read from
	 * @return string           the read data (decoded)
	 * @throws WebSocketException
	 */
	public function receive(&$socket) : string {
		$data = fread($socket, 2);
		if ($data === false) {
			throw new WebSocketException('Could not receive data');
		}
		if (strlen($data) === 1) {
			$data .= fread($socket, 1);
		}
		if (strlen($data) < 2) {
			throw new WebSocketException('Could not receive data');
		}
		$final = (bool) (ord($data[0]) & 1 << 7);
		//$rsv1 = (bool) (ord($data[0]) & 1 << 6);
		//$rsv2 = (bool) (ord($data[0]) & 1 << 5);
		//$rsv3 = (bool) (ord($data[0]) & 1 << 4);
		$opcode = ord($data[0]) & 31;
		$masked = (bool) (ord($data[1]) >> 7);
		
		$payload = '';
		$length = ord($data[1]) & 127; // Bits 1-7 in byte 1
		if ($length > 125) {
			$temp = $length === 126 ? fread($socket, 2) : fread($socket, 8);
			if ($temp === false) {
				throw new WebSocketException('Could not receive data');
			}
			$length = '';
			for ($i = 0; $i < strlen($temp); ++$i) {
				$length .= sprintf('%08b', ord($temp[$i]));
			}
			$length = bindec($length);
		}
		$mask = '';
		if ($masked) {
			$mask = fread($socket, 4);
			if ($mask === false) {
				throw new WebSocketException('Could not receive mask data');
			}
		}
		if ($length > 0) {
			$temp = '';
			do {
				$buff = fread($socket, min($length, static::$fragmentSize));
				if ($buff === false) {
					throw new WebSocketException('Could not receive data');
				}
				$temp .= $buff;
			} while (strlen($temp) < $length);
			if ($masked) {
				for ($i = 0; $i < $length; ++$i) {
					$payload .= ($temp[$i] ^ $mask[$i % 4]);
				}
			} else {
				$payload = $temp;
			}
		}
		if ($opcode === static::$opcodes['close']) {
			throw new WebSocketException('Client disconnect');
		}
		return $final ? $payload : $payload.$this->receive($socket);
	}
	protected function encode($data, $opcode = 'text', $masked = true, $final = true): string {
		$length = strlen($data);
		$head = $final ? '1' : '0';
		$head .= '000';
		$head .= sprintf('%04b', static::$opcodes[$opcode]);
		$head .= $masked ? '1' : '0';
		if ($length > 65535) {
			$head .= decbin(127);
			$head .= sprintf('%064b', $length);
		} elseif ($length > 125) {
			$head .= decbin(126);
			$head .= sprintf('%016b', $length);
		} else {
			$head .= sprintf('%07b', $length);
		}
		$frame = '';
		foreach (str_split($head, 8) as $binstr) {
			$frame .= chr((int)bindec($binstr));
		}
		$mask = '';
		if ($masked) {
			for ($i = 0; $i < 4; ++$i) {
				$mask .= chr(rand(0, 255));
			}
			$frame .= $mask;
		}
		for ($i = 0; $i < $length; ++$i) {
			$frame .= ($masked === true) ? $data[$i] ^ $mask[$i % 4] : $data[$i];
		}
		return $frame;
	}
}
