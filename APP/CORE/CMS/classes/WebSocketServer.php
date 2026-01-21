<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2026 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class WebSocketServer {
	
	const TIMEOUT_STATUS = 60 * 60 * 2; // Seconds
	const TIMEOUT_CLEANUP = 60; // Seconds
	const TIMEOUT_WAIT = 20; // Seconds

	protected static $class_user = 'WebSocketServerUser'; // redefine this if you want a custom user class.  The custom user class should inherit from WebSocketServerUser.
	
	public $verbose = false;
	
	protected $time_cleanup = 0;
	protected $time_status = 0;
	protected $size_buffer_max = false;
	protected $num_socket_error = false;
	protected $num_socket_error_ssl = false;
	protected $str_socket_error = false;
	protected $str_socket_error_ssl = false;
	protected $arr_sockets = [];
	protected $arr_sockets_write = [];
	protected $arr_sockets_user = [];
	protected $arr_users = [];
	protected $require_header_origin = false;
	protected $require_header_sec_websocket_protocol = false;
	protected $require_header_sec_websocket_extensions = false;
	
	protected $count_connection_attemps = 0;
	protected $count_connection_success = 0;
	protected $count_connection_refused = 0;

	function __construct($address, $port, $use_ssl = false, $size_buffer = 2048) {
	 
		$this->size_buffer_max = $size_buffer;
		
		$context = stream_context_create();
		
		$this->arr_sockets['m'] = stream_socket_server('tcp://'.$address.':'.$port.'', $this->num_socket_error, $this->str_socket_error, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context) or error('Failed to create master socket'); // $address => '0.0.0.0'/'0' for everywhere, '127.0.0.1' localhost

		if ($use_ssl) {
			
			$context = stream_context_create();
			
			$this->arr_sockets['m_ssl'] = stream_socket_server('ssl://'.$address.':'.($port+1).'', $this->num_socket_error_ssl, $this->str_socket_error_ssl, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context) or error('Failed to create master socket'); // $address => '0.0.0.0'/'0' for everywhere, '127.0.0.1' localhost

			stream_context_set_option($context, 'ssl', 'local_cert', DIR_SAFE_SITE.'cert.pem');  // SSL Cert in PEM format
			stream_context_set_option($context, 'ssl', 'local_pk', DIR_SAFE_SITE.'privkey.pem');  // SSL Cert in PEM format
			stream_context_set_option($context, 'ssl', 'passphrase', ''); // Private key Password
			stream_context_set_option($context, 'ssl', 'allow_self_signed', false);
			stream_context_set_option($context, 'ssl', 'verify_peer', false);
		}
		
		$time = time();
		
		$this->time_cleanup = $time;
		
		Mediator::attach('cleanup', false, function() {
			
			$this->cleanUp();
		});
	}
		
	abstract protected function check(); // Called to run server stuff
	abstract protected function process($user, $message); // Called immediately when the data is recieved
	abstract protected function connected($user); // Called after the handshake response is sent to the client
	abstract protected function closed($user); // Called after the connection is closed

	protected function connecting($user) {
		// Override to handle a connecting user, after the instance of the User is created, but before
		// the handshake has completed.
	}
  
	protected function send($user, $message) {
		
		$message = $this->frame($message, $user);
		
		$this->arr_sockets_write[$user->id] = $user->socket;
		$user->arr_send_messages[] = $message;
	}
	
	public function init() {
		
		while (true) {
			
			Mediator::checkState();
			
			$this->run();
			
			$this->check();
		}
	}

	public function run() {

		$arr_read = $this->arr_sockets;
		$arr_write = ($this->arr_sockets_write ?: null);
		$except = null;
		$wait = static::TIMEOUT_WAIT; // Wait x amount of seconds of no communication before continuing run()
		
		try {
			$nr_streams = stream_select($arr_read, $arr_write, $except, $wait); // Only select sockets in the next loop that have something to do
		} catch (Exception $e) {
			unset($e);
		}
		
		if ($nr_streams) {
			
			if ($arr_write) {
				
				foreach ($arr_write as $socket) {
					
					$user = $this->getUserBySocket($socket);
					
					foreach ($user->arr_send_messages as $key => $message) {
						
						$length = strlen($message);
						
						try {
							$sent = fwrite($socket, $message, $length);
						} catch (Exception $e) {
							unset($e);
						}
						
						if ($sent >= $length || $sent === false) {
							
							unset($user->arr_send_messages[$key]);
						} else {
							   
							// If not sent the entire message.
							// Get the part of the message that has not yet been sent as message
							$message = substr($message, $sent);
							
							$user->arr_send_messages[$key] = $message;
						}
						
						if (!$user->arr_send_messages) {
							unset($this->arr_sockets_write[$user->id]);
						}
						
						break; // Only do one run (message) per socket
					}
				}
			}
			
			foreach ($arr_read as $socket) {
			
				if ($socket == $this->arr_sockets['m'] || $socket == $this->arr_sockets['m_ssl']) { // Check for new clients
					
					try {
						$client = stream_socket_accept($socket, 0);
					} catch (Exception $e) {
						//error('Failed socket accept: '.$this->str_socket_error);
						unset($e);
					}
					
					if ($client) {
						
						stream_set_blocking($client, false); // Make sure to set new clients to non-blocking in case of future fread() loops 

						$user = $this->connect($client);
						
						if ($user) {
							
							$this->count_connection_attemps++;

							$this->message('Client connected. Socket: '.$client.', IP: '.$user->ip.'.');
						}
					}
				} else { // Process existing clients
					
					$nr_bytes = 0;
					
					try {
						
						$buffer = fread($socket, $this->size_buffer_max);
						$nr_bytes = strlen($buffer);
					} catch (Exception $e) {
						//error('Failed socket read: '.$this->str_socket_error);
						unset($e);
					}
				  
					if ($buffer === false) {
						
						$user = $this->getUserBySocket($socket);
						
						$this->disconnect($socket);
						$this->message('Client disconnected: Connection lost. Socket: '.$socket.', IP: '.$user->ip.'.');
					} else if ($nr_bytes > 0) {
						
						$user = $this->getUserBySocket($socket);
						
						if (!$user->handshake) {
							
							$tmp = str_replace("\r", '', $buffer);
							
							if (strpos($tmp, "\n\n") === false) {
								continue; // If the client has not finished sending the header, then wait before sending our upgrade response.
							}
							
							$this->doHandshake($user, $buffer);
						} else {
							
							$user->alive();
							
							foreach ($this->deframe($buffer, $user) as $message) {
								
								if ($user->has_sent_close) {
									
									$this->disconnect($socket);
									$this->message('Client disconnected: Sent close. Socket: '.$socket.', IP: '.$user->ip.'.');
									
									break;
								}
																
								if ($message !== false) {
									
									$this->process($user, $message); // Do: Re-check this. Should already be UTF-8.
								} else {

									do {
										
										$nr_bytes = 0;
										
										try {
											
											$buffer = fread($socket, $this->size_buffer_max);
											$nr_bytes = strlen($buffer);
										} catch (Exception $e) {
											unset($e);
										}
										 
										if ($nr_bytes > 0) {
											
											foreach ($this->deframe($buffer, $user) as $message) {
												
												if ($user->has_sent_close) {
													
													$this->disconnect($socket);
													$this->message('Client disconnected: Sent close. Socket: '.$socket.', IP: '.$user->ip.'.');
													
													$nr_bytes = 0;
													break;
												}
											
												if ($message !== false) {
													
													$this->process($user, $message);
												}
											}
										}
									} while ($nr_bytes > 0);
								}
							}
						}
					}
				}
			}
		}
		
		$time = time();
		
		if (($time - $this->time_cleanup) > static::TIMEOUT_CLEANUP) {
			
			$this->cleanUp($time);
			$this->time_cleanup = $time;
		}
		
		if (($time - $this->time_status) > static::TIMEOUT_STATUS) {
			
			$nr_clients = count($this->arr_users);
			
			msg('Status:'.EOL_1100CC
				.'	Active clients = '.$nr_clients.EOL_1100CC
				.'	Connections: attemps = '.num2String($this->count_connection_attemps).' success = '.num2String($this->count_connection_success).' refused = '.num2String($this->count_connection_refused),
			'WEBSERVICE'); // Provide status update and keep database connection alive
			
			$this->time_status = $time;
		}
	}
	
	protected function cleanUp($time = false) {
		
		foreach ($this->arr_users as $user) {
			
			if (!$time || $user->isDead($time)) {
				
				$this->disconnect($user->socket);
				
				if ($time) {
					$this->message('Client disconnected: User timed out. Socket: '.$user->socket.', IP: '.$user->ip.'.');
				} else {
					$this->message('Client disconnected: Cleanup. Socket: '.$user->socket.', IP: '.$user->ip.'.');
				}
			}
		}
	}

	protected function connect($socket) {
		
		$str_socket = (string)$socket;
		
		if (isset($this->arr_sockets_user[$str_socket])) { // Resource and user already exist
			return false;
		}
	  
		$user = new static::$class_user(uniqid('u'), $socket);
		
		$this->arr_users[$user->id] = $user;
		$this->arr_sockets[$user->id] = $socket;
		$this->arr_sockets_user[$str_socket] = $user;
		
		$this->connecting($user);
		
		return $user;
	}

	protected function disconnect($socket, $trigger_close = false) {
	
		$user = $this->getUserBySocket($socket);
	
		if ($user === null) {
			return;
		}
			
		unset($this->arr_users[$user->id], $this->arr_sockets[$user->id], $this->arr_sockets_user[(string)$user->socket]);
		
		if (!$trigger_close) {
			
			$this->closed($user);
			stream_socket_shutdown($user->socket, STREAM_SHUT_RDWR);
			fclose($user->socket);
		} else {
			
			$message = $this->frame('', $user, 'close');
			try {
				fwrite($user->socket, $message, strlen($message));
			} catch (Exception $e) {
				unset($e);
			}
		}
		
		$user->remove();
	}

	protected function doHandshake($user, $buffer) {
		
		$str_response = $user->checkHandshake($buffer);
		
		if ($str_response !== false) {
			
			fwrite($user->socket, $str_response.EOL_EXCHANGE.EOL_EXCHANGE, strlen($str_response) + 4);
			
			return;
		}
	  
		$magic_guid = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		$arr_headers = [];
		$lines = explode("\n", $buffer);
		
		foreach ($lines as $line) {
			
			if (strpos($line,":") !== false) {
				$header = explode(':', $line,2);
				$arr_headers[strtolower(trim($header[0]))] = trim($header[1]);
			} else if (stripos($line, 'get ') !== false) {
				preg_match('/GET (.*) HTTP/i', $buffer, $match);
				$arr_headers['get'] = trim($match[1]);
			}
		}
		
		$str_response = false;
		
		if (isset($arr_headers['get'])) {
			$user->path = $arr_headers['get'];
		} else {
			// Do: fail the connection
			$str_response = 'HTTP/1.1 405 Method Not Allowed';     
		}
		
		if (!isset($arr_headers['host']) || !$this->checkHost($arr_headers['host'])) {
			$str_response = 'HTTP/1.1 400 Bad Request';
		}
		if (!isset($arr_headers['upgrade']) || strtolower($arr_headers['upgrade']) != 'websocket') {
			$str_response = 'HTTP/1.1 400 Bad Request';
		} 
		if (!isset($arr_headers['connection']) || strpos(strtolower($arr_headers['connection']), 'upgrade') === FALSE) {
			$str_response = 'HTTP/1.1 400 Bad Request';
		}
		if (!isset($arr_headers['sec-websocket-key'])) {
			$str_response = 'HTTP/1.1 400 Bad Request';
		} else {

		}
		
		if (!isset($arr_headers['sec-websocket-version']) || strtolower($arr_headers['sec-websocket-version']) != 13) {
			$str_response = 'HTTP/1.1 426 Upgrade Required'.EOL_EXCHANGE.'Sec-WebSocketVersion: 13';
		}
		if (($this->require_header_origin && !isset($arr_headers['origin']) ) || ($this->require_header_origin && !$this->checkOrigin($arr_headers['origin']))) {
			$str_response = 'HTTP/1.1 403 Forbidden';
		}
		if (($this->require_header_sec_websocket_protocol && !isset($arr_headers['sec-websocket-protocol'])) || ($this->require_header_sec_websocket_protocol && !$this->checkWebsocProtocol($arr_headers['sec-websocket-protocol']))) {
			$str_response = 'HTTP/1.1 400 Bad Request';
		}
		if (($this->require_header_sec_websocket_extensions && !isset($arr_headers['sec-websocket-extensions'])) || ($this->require_header_sec_websocket_extensions && !$this->checkWebsocExtensions($arr_headers['sec-websocket-extensions']))) {
			$str_response = 'HTTP/1.1 400 Bad Request';
		}
		
		$do_pass = false;
		
		if (isset($arr_headers['cookie'])) {
			
			preg_match('/webservice_user_id=([^;]*)/i', $arr_headers['cookie'], $arr_match);
			$webservice_user_id = $arr_match[1];
			preg_match('/webservice_passkey=([^;]*)/i', $arr_headers['cookie'], $arr_match);
			$webservice_passkey = $arr_match[1];
			
			$do_pass = $user->checkPasskey($webservice_user_id, $webservice_passkey);
		}
			
		if (!$do_pass) {
			$str_response = 'HTTP/1.1 403 Forbidden';
		}

		// Done verifying the required headers and optionally required headers

		if ($str_response !== false) {
			
			fwrite($user->socket, $str_response.EOL_EXCHANGE.EOL_EXCHANGE, strlen($str_response.EOL_EXCHANGE.EOL_EXCHANGE));
			
			$this->count_connection_refused++;
		
			$this->disconnect($user->socket);

			$this->message('Client disconnected: Connection refused "'.$str_response.'". Socket: '.$user->socket.', IP: '.$user->ip.'.');
			
			return;
		}

		$user->arr_headers = $arr_headers;
		$user->handshake = $buffer;

		$websocket_key_hash = sha1($arr_headers['sec-websocket-key'].$magic_guid);

		$token = '';
		
		for ($i = 0; $i < 20; $i++) {
			$token .= chr(hexdec(substr($websocket_key_hash, $i*2, 2)));
		}
		
		$handshake_token = base64_encode($token).EOL_EXCHANGE;

		$sub_protocol = (isset($arr_headers['sec-websocket-protocol']) ? $this->processProtocol($arr_headers['sec-websocket-protocol']) : '');
		$extensions = (isset($arr_headers['sec-websocket-extensions']) ? $this->processExtensions($arr_headers['sec-websocket-extensions']) : '');

		$str_response = "HTTP/1.1 101 Switching Protocols".EOL_EXCHANGE
			."Upgrade: websocket".EOL_EXCHANGE
			."Connection: Upgrade".EOL_EXCHANGE
			."Sec-WebSocket-Accept: ".$handshake_token.$sub_protocol.$extensions.EOL_EXCHANGE;
			
		fwrite($user->socket, $str_response, strlen($str_response));
		
		$this->count_connection_success++;
		
		$this->connected($user);
	}

	protected function checkHost($hostName) {
		
		return true; // Override and return false if the host is not one that you would expect.
                 // Ex: You only want to accept hosts from the my-domain.com domain,
                 // but you receive a host from malicious-site.com instead.
	}

	protected function checkOrigin($origin) {
		return true; // Override and return false if the origin is not one that you would expect.
	}

	protected function checkWebsocProtocol($protocol) {
		return true; // Override and return false if a protocol is not found that you would expect.
	}

	protected function checkWebsocExtensions($extensions) {
		return true; // Override and return false if an extension is not found that you would expect.
	}

	protected function processProtocol($protocol) {
		return ""; // return either "Sec-WebSocket-Protocol: SelectedProtocolFromClientList\r\n" or return an empty string.  
           // The carriage return/newline combo must appear at the end of a non-empty string, and must not
           // appear at the beginning of the string nor in an otherwise empty string, or it will be considered part of 
           // the response body, which will trigger an error in the client as it will not be formatted correctly.
	}

	protected function processExtensions($extensions) {
		return ""; // return either "Sec-WebSocket-Extensions: SelectedExtensions\r\n" or return an empty string.
	}

	protected function getUserBySocket($socket) {
    
		return ($this->arr_sockets_user[(string)$socket] ?? null);
	}

	public function message($message) {
	  
		if (!$this->verbose) {
			return;
		}
		
		msg($message, 'WEBSERVICE');
	}

	protected function frame($message, $user, $message_type = 'text', $message_continues = false) {
	  
		switch ($message_type) {
			case 'continuous':
				$byte_1 = 0;
				break;
			case 'text':
				$byte_1 = ($user->is_sending_continuous) ? 0 : 1;
				break;
			case 'binary':
				$byte_1 = ($user->is_sending_continuous) ? 0 : 2;
				break;
			case 'close':
				$byte_1 = 8;
				break;
			case 'ping':
				$byte_1 = 9;
				break;
			case 'pong':
				$byte_1 = 10;
				break;
		}
		
		if ($message_continues) {
			$user->is_sending_continuous = true;
		} else {
			$byte_1 += 128;
			$user->is_sending_continuous = false;
		}

		$length = strlen($message);
		$length_field = '';
		
		if ($length < 126) {
			
			$byte_2 = $length;
		} else if ($length <= 65536) {
			
			$byte_2 = 126;
			$hex_length = dechex($length);

			if (strlen($hex_length) % 2 == 1) {
				$hex_length = '0'.$hex_length;
			} 
			$n = strlen($hex_length) - 2;
			
			for ($i = $n; $i >= 0; $i=$i-2) {
				$length_field = chr(hexdec(substr($hex_length, $i, 2))).$length_field;
			}
			while (strlen($length_field) < 2) {
				$length_field = chr(0).$length_field;
			}
		} else {
			$byte_2 = 127;
			$hex_length = dechex($length);
			if (strlen($hex_length) % 2 == 1) {
				$hex_length = '0'.$hex_length;
			} 
			$n = strlen($hex_length) - 2;
		
			for ($i = $n; $i >= 0; $i=$i-2) {
				$length_field = chr(hexdec(substr($hex_length, $i, 2))).$length_field;
			}
			while (strlen($length_field) < 8) {
				$length_field = chr(0).$length_field;
			}
		}

		return chr($byte_1).chr($byte_2).$length_field.$message;
	}
	
	protected function deframe($frame, &$user) {
				
		do {
			
			if ($user->has_received_partial_packet) {
				
				$frame = $user->buffer_partial_packet.$frame;
				$user->has_received_partial_packet = false;
			}
			
			$arr_headers = static::extractHeaders($frame);
			
			$do_pong_reply = false;
			$will_close = false;
			
			switch($arr_headers['opcode']) {
				case 0:
				case 1:
				case 2:
				break;
			case 8: // Do: close the connection
				$user->has_sent_close = true;
				break;
			case 9: // Ping received, server is a client, send pong
				$do_pong_reply = true;
			case 10: // Pong received, client responded
				break;
			default: // Do: fail connection
				$will_close = true;
				break;
			}
			
			if ($user->has_sent_close || $will_close || $this->checkRSVBits($arr_headers, $user)) { // Do: fail the connection
				break;
			}
			
			$arr_payload = static::extractPayload($frame, $arr_headers);
			
			$payload = $arr_payload[0];
			
			if ($arr_headers['length'] > strlen($payload)) {
			
				$user->has_received_partial_packet = true;
				$user->buffer_partial_packet = $frame;
				
				break;
			}
			
			$frame = $arr_payload[1]; // Leftover payload, deframe next iteration
			
			$payload = static::applyMask($arr_headers, $payload);
			
			if ($do_pong_reply) {
			
				$message_reply = $this->frame($payload, $user, 'pong');
				fwrite($user->socket, $message_reply, strlen($message_reply));
				
				continue;
			}
			
			$payload = $user->buffer_partial_frame.$payload;
			
			if ($arr_headers['fin']) {
			
				$user->buffer_partial_frame = '';
				yield $payload;
				
				continue;
			}
			
			$user->buffer_partial_frame = $payload;
			
		} while ($frame != '');
		
		yield false;
	}
	
	protected function checkRSVBits($arr_headers, $user) { // override this method if you are using an extension where the RSV bits are used.
		
		if (($arr_headers['rsv1'] + $arr_headers['rsv2'] + $arr_headers['rsv3']) > 0) {
			return true;
		}
		
		return false;
	}

	public static function extractHeaders($frame) {
		
		$int_1 = ord($frame[0]);
		$int_2 = ord($frame[1]);
		
		$arr_headers = [
			'fin' => $int_1 & 128,
			'rsv1' => $int_1 & 64,
			'rsv2' => $int_1 & 32,
			'rsv3' => $int_1 & 16,
			'opcode' => $int_1 & 15,
			'hasmask' => $int_2 & 128,
			'length' => 0,
			'mask' => ''
		];
		$arr_headers['length'] = ($int_2 >= 128 ? $int_2 - 128 : $int_2);
		
		if ($arr_headers['length'] == 126) {
			if ($arr_headers['hasmask']) {
				$arr_headers['mask'] = $frame[4].$frame[5].$frame[6].$frame[7];
			}
			$arr_headers['length'] = ord($frame[2]) * 256 + ord($frame[3]);
		} else if ($arr_headers['length'] == 127) {
			if ($arr_headers['hasmask']) {
				$arr_headers['mask'] = $frame[10].$frame[11].$frame[12].$frame[13];
			}
			$arr_headers['length'] = ord($frame[2]) * 65536 * 65536 * 65536 * 256 
						+ ord($frame[3]) * 65536 * 65536 * 65536
						+ ord($frame[4]) * 65536 * 65536 * 256
						+ ord($frame[5]) * 65536 * 65536
						+ ord($frame[6]) * 65536 * 256
						+ ord($frame[7]) * 65536 
						+ ord($frame[8]) * 256
						+ ord($frame[9]);
		} else if ($arr_headers['hasmask']) {
			$arr_headers['mask'] = $frame[2].$frame[3].$frame[4].$frame[5];
		}
		
		return $arr_headers;
	}

	public static function extractPayload($frame, $arr_headers) {
		
		$offset = 2;
		
		if ($arr_headers['hasmask']) {
			$offset += 4;
		}
		if ($arr_headers['length'] > 65535) {
			$offset += 8;
		} 
		elseif ($arr_headers['length'] > 125) {
			$offset += 2;
		}
		
		return [substr($frame, $offset, $arr_headers['length']), substr($frame, $offset + $arr_headers['length'])];
	}

	public static function applyMask($arr_headers, $payload) {
		
		$effective_mask = '';
		
		if ($arr_headers['hasmask']) {
			$mask = $arr_headers['mask'];
		} 
		else {
			return $payload;
		}
		
		while (strlen($effective_mask) < strlen($payload)) {
			$effective_mask .= $mask;
		}
		while (strlen($effective_mask) > strlen($payload)) {
			$effective_mask = substr($effective_mask, 0, -1);
		}
		
		return $effective_mask ^ $payload;
	}
}
