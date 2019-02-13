<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

abstract class WebSocketServer {
	
	const TIMEOUT_STATUS = 60 * 60 * 2; // Seconds
	const TIMEOUT_CLEANUP = 60; // Seconds
	const TIMEOUT_WAIT = 20; // Seconds

	protected static $class_user = 'WebSocketUser'; // redefine this if you want a custom user class.  The custom user class should inherit from WebSocketUser.
	
	protected $time_cleanup = 0;
	protected $time_status = 0;
	protected $size_buffer_max = false;
	protected $nr_socket_error = false;
	protected $nr_socket_error_ssl = false;
	protected $str_socket_error = false;
	protected $str_socket_error_ssl = false;
	protected $arr_sockets = [];
	protected $arr_sockets_write = [];
	protected $arr_sockets_user = [];
	protected $arr_users = [];
	protected $require_header_origin = false;
	protected $require_header_sec_websocket_protocol = false;
	protected $require_header_sec_websocket_extensions = false;
	
	protected $verbose = false;
	
	protected $count_connection_attemps = 0;
	protected $count_connection_success = 0;
	protected $count_connection_refused = 0;

	function __construct($address, $port, $size_buffer = 2048) {
	 
		$this->size_buffer_max = $size_buffer;
		
		$context = stream_context_create();
		
		$this->arr_sockets['m'] = stream_socket_server('tcp://'.$address.':'.$port.'', $this->nr_socket_error, $this->str_socket_error, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context) or error('Failed to create master socket'); // $address => '0.0.0.0'/'0' for everywhere, '127.0.0.1' localhost

		if (getLabel('https', 'D', true)) {
			
			$context = stream_context_create();
			
			$this->arr_sockets['m_ssl'] = stream_socket_server('ssl://'.$address.':'.($port+1).'', $this->nr_socket_error_ssl, $this->str_socket_error_ssl, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context) or error('Failed to create master socket'); // $address => '0.0.0.0'/'0' for everywhere, '127.0.0.1' localhost

			stream_context_set_option($context, 'ssl', 'local_cert', DIR_SAFE.DIR_HOME.'/cert.pem');  // SSL Cert in PEM format
			stream_context_set_option($context, 'ssl', 'local_pk', DIR_SAFE.DIR_HOME.'/privkey.pem');  // SSL Cert in PEM format
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

							$this->msg('Client connected. Socket: '.$client.', IP: '.$user->ip.'.');
						}
					}
				} else { // Process existing clients
					
					try {
						$buffer = fread($socket, $this->size_buffer_max);
					} catch (Exception $e) {
						//error('Failed socket read: '.$this->str_socket_error);
						unset($e);
					}
					
					$nr_bytes = strlen($buffer);
				  
					if ($nr_bytes === 0) {
						
						$user = $this->getUserBySocket($socket);
						
						$this->disconnect($socket);
						$this->msg('Client disconnected: Connection lost. Socket: '.$socket.', IP: '.$user->ip.'.');
					} else if ($buffer) {
						
						$user = $this->getUserBySocket($socket);
						
						if (!$user->handshake) {
							
							$tmp = str_replace("\r", '', $buffer);
							
							if (strpos($tmp, "\n\n") === false) {
								continue; // If the client has not finished sending the header, then wait before sending our upgrade response.
							}
							
							$this->doHandshake($user, $buffer);
						} else {
							
							$user->alive();
							
							$message = $this->deframe($buffer, $user);
							
							if ($message !== false) {
								
								if ($user->has_sent_close) {
									
									$this->disconnect($socket);
									$this->msg('Client disconnected: Sent close. Socket: '.$socket.', IP: '.$user->ip.'.');
								} else {
									
									$this->process($user, $message); // Do: Re-check this. Should already be UTF-8.
								}
							} else {

								do {
									
									try {
										$buffer = fread($socket, $this->size_buffer_max);
									} catch (Exception $e) {
										unset($e);
									}
									
									$nr_bytes = strlen($buffer);
									 
									if ($nr_bytes > 0) {
																			
										$message = $this->deframe($buffer, $user);
										
										if ($message !== false) {
											
											if ($user->has_sent_close) {
												
												$this->disconnect($socket);
												$this->msg('Client disconnected: Sent close. Socket: '.$socket.', IP: '.$user->ip.'.');
											} else {
												
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
		
		$time = time();
		
		if (($time - $this->time_cleanup) > static::TIMEOUT_CLEANUP) {
			
			$this->cleanUp($time);
			$this->time_cleanup = $time;
		}
		
		if (($time - $this->time_status) > static::TIMEOUT_STATUS) {
			
			$nr_clients = count($this->arr_users);
			
			msg('Status:'.PHP_EOL
				.'	Active clients = '.$nr_clients.PHP_EOL
				.'	Connections: Attemps = '.nr2String($this->count_connection_attemps).' Success = '.nr2String($this->count_connection_success).' Refused = '.nr2String($this->count_connection_refused),
			'WEBSERVICE'); // Provide status update and keep database connection alive
			
			$this->time_status = $time;
		}
	}
	
	protected function cleanUp($time = false) {
		
		foreach ($this->arr_users as $user) {
			
			if (!$time || $user->isDead($time)) {
				
				$this->disconnect($user->socket);
				if ($time) {
					$this->msg('Client disconnected: User timed out. Socket: '.$user->socket.', IP: '.$user->ip.'.');
				} else {
					$this->msg('Client disconnected: Cleanup. Socket: '.$user->socket.', IP: '.$user->ip.'.');
				}
			}
		}
	}

	protected function connect($socket) {
		
		$str_socket = (string)$socket;
		
		if ($this->arr_sockets_user[$str_socket]) { // Resource and user already exist
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
	
		if ($user !== null) {
			
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
	}

	protected function doHandshake($user, $buffer) {
	  
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
		
		if (isset($arr_headers['get'])) {
			$user->path = $arr_headers['get'];
		} else {
			// Do: fail the connection
			$handshake_response = 'HTTP/1.1 405 Method Not Allowed\r\n\r\n';     
		}
		
		if (!isset($arr_headers['host']) || !$this->checkHost($arr_headers['host'])) {
			$handshake_response = 'HTTP/1.1 400 Bad Request';
		}
		if (!isset($arr_headers['upgrade']) || strtolower($arr_headers['upgrade']) != 'websocket') {
			$handshake_response = 'HTTP/1.1 400 Bad Request';
		} 
		if (!isset($arr_headers['connection']) || strpos(strtolower($arr_headers['connection']), 'upgrade') === FALSE) {
			$handshake_response = 'HTTP/1.1 400 Bad Request';
		}
		if (!isset($arr_headers['sec-websocket-key'])) {
			$handshake_response = 'HTTP/1.1 400 Bad Request';
		} else {

		}
		
		if (!isset($arr_headers['sec-websocket-version']) || strtolower($arr_headers['sec-websocket-version']) != 13) {
			$handshake_response = 'HTTP/1.1 426 Upgrade Required\r\nSec-WebSocketVersion: 13';
		}
		if (($this->require_header_origin && !isset($arr_headers['origin']) ) || ($this->require_header_origin && !$this->checkOrigin($arr_headers['origin']))) {
			$handshake_response = 'HTTP/1.1 403 Forbidden';
		}
		if (($this->require_header_sec_websocket_protocol && !isset($arr_headers['sec-websocket-protocol'])) || ($this->require_header_sec_websocket_protocol && !$this->checkWebsocProtocol($arr_headers['sec-websocket-protocol']))) {
			$handshake_response = 'HTTP/1.1 400 Bad Request';
		}
		if (($this->require_header_sec_websocket_extensions && !isset($arr_headers['sec-websocket-extensions'])) || ($this->require_header_sec_websocket_extensions && !$this->checkWebsocExtensions($arr_headers['sec-websocket-extensions']))) {
			$handshake_response = 'HTTP/1.1 400 Bad Request';
		}
		
		preg_match('/webservice_user_id=([^;]*)/i', $arr_headers['cookie'], $match);
		$webservice_user_id = $match[1];
		preg_match('/webservice_passkey=([^;]*)/i', $arr_headers['cookie'], $match);
		$webservice_passkey = $match[1];
		$pass = $user->checkPasskey($webservice_user_id, $webservice_passkey);
			
		if (!$pass) {
			$handshake_response = 'HTTP/1.1 403 Forbidden';
		}

		// Done verifying the required headers and optionally required headers

		if (isset($handshake_response)) {
			
			fwrite($user->socket, $handshake_response, strlen($handshake_response));
			
			$this->count_connection_refused++;
		
			$this->disconnect($user->socket);

			$this->msg('Client disconnected: Connection refused "'.$handshake_response.'". Socket: '.$user->socket.', IP: '.$user->ip.'.');
			
			return;
		}

		$user->arr_headers = $arr_headers;
		$user->handshake = $buffer;

		$websocket_key_hash = sha1($arr_headers['sec-websocket-key'].$magic_guid);

		$token = '';
		
		for ($i = 0; $i < 20; $i++) {
			$token .= chr(hexdec(substr($websocket_key_hash, $i*2, 2)));
		}
		
		$handshake_token = base64_encode($token) . "\r\n";

		$sub_protocol = (isset($arr_headers['sec-websocket-protocol'])) ? $this->processProtocol($arr_headers['sec-websocket-protocol']) : "";
		$extensions = (isset($arr_headers['sec-websocket-extensions'])) ? $this->processExtensions($arr_headers['sec-websocket-extensions']) : "";

		$handshake_response = "HTTP/1.1 101 Switching Protocols\r\n"
			."Upgrade: websocket\r\n"
			."Connection: Upgrade\r\n"
			."Sec-WebSocket-Accept: $handshake_token$sub_protocol$extensions\r\n";
			
		fwrite($user->socket, $handshake_response, strlen($handshake_response));
		
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
    
		return ($this->arr_sockets_user[(string)$socket] ?: null);
	}

	public function msg($message) {
	  
		if ($this->verbose) {
			msg($message, 'WEBSERVICE');
		}
	}

	protected function frame($message, $user, $message_type = 'text', $message_continues = false) {
	  
		switch ($message_type) {
			case 'continuous':
				$b1 = 0;
				break;
			case 'text':
				$b1 = ($user->is_sending_continuous) ? 0 : 1;
				break;
			case 'binary':
				$b1 = ($user->is_sending_continuous) ? 0 : 2;
				break;
			case 'close':
				$b1 = 8;
				break;
			case 'ping':
				$b1 = 9;
				break;
			case 'pong':
				$b1 = 10;
				break;
		}
		
		if ($message_continues) {
			$user->is_sending_continuous = true;
		} else {
			$b1 += 128;
			$user->is_sending_continuous = false;
		}

		$length = strlen($message);
		$length_field = '';
		if ($length < 126) {
			
			$b2 = $length;
		} else if ($length <= 65536) {
			
			$b2 = 126;
			$hex_length = dechex($length);
			//$this->msg("Hex Length: $hex_length");
			if (strlen($hex_length)%2 == 1) {
				$hex_length = '0' . $hex_length;
			} 
			$n = strlen($hex_length) - 2;
			
			for ($i = $n; $i >= 0; $i=$i-2) {
				$length_field = chr(hexdec(substr($hex_length, $i, 2))) . $length_field;
			}
			while (strlen($length_field) < 2) {
				$length_field = chr(0) . $length_field;
			}
		} else {
			$b2 = 127;
			$hex_length = dechex($length);
			if (strlen($hex_length)%2 == 1) {
				$hex_length = '0' . $hex_length;
			} 
			$n = strlen($hex_length) - 2;
		
			for ($i = $n; $i >= 0; $i=$i-2) {
				$length_field = chr(hexdec(substr($hex_length, $i, 2))) . $length_field;
			}
			while (strlen($length_field) < 8) {
				$length_field = chr(0) . $length_field;
			}
		}

		return chr($b1) . chr($b2) . $length_field . $message;
	}

	protected function deframe($message, &$user) {
	  
		//echo $this->strtohex($message);
		$arr_headers = $this->extractHeaders($message);
		$do_pong_reply = false;
		$will_close = false;
		
		switch($arr_headers['opcode']) {
			case 0:
			case 1:
			case 2:
			break;
		case 8:
			// Do: close the connection
			$user->has_sent_close = true;
			return "";
		case 9: // Ping received, server is a client, send pong
			$do_pong_reply = true;
		case 10: // Pong received, client responded
			break;
		default:
			//$this->disconnect($user); // Do: fail connection
			$will_close = true;
			break;
		}
		
		if ($user->has_received_partial_packet) {
			$message = $user->received_buffer_partial . $message;
			$user->has_received_partial_packet = false;
			return $this->deframe($message, $user);
		}
		
		if ($this->checkRSVBits($arr_headers, $user)) {
			return false;
		}
		
		if ($will_close) {
			// Do: fail the connection
			return false;
		}
		
		$payload = $user->received_message_partial.$this->extractPayload($message, $arr_headers);
		
		if ($do_pong_reply) {
			
			$reply = $this->frame($payload, $user, 'pong');
			fwrite($user->socket, $reply, strlen($reply));
			
			return false;
		}
		if (extension_loaded('mbstring')) {
			
			if ($arr_headers['length'] > mb_strlen($this->applyMask($arr_headers, $payload))) {
				
				$user->has_received_partial_packet = true;
				$user->received_buffer_partial = $message;
				
				return false;
			}
		} else {
			if ($arr_headers['length'] > strlen($this->applyMask($arr_headers, $payload))) {
				
				$user->has_received_partial_packet = true;
				$user->received_buffer_partial = $message;
				
				return false;
			}
		}
		
		$payload = $this->applyMask($arr_headers, $payload);
		
		if ($arr_headers['fin']) {
			$user->received_message_partial = "";
			return $payload;
		}
		
		$user->received_message_partial = $payload;
		
		return false;
	}

	protected function extractHeaders($message) {
		
		$arr_headers = [
			'fin' => $message[0] & chr(128),
			'rsv1' => $message[0] & chr(64),
			'rsv2' => $message[0] & chr(32),
			'rsv3' => $message[0] & chr(16),
			'opcode' => ord($message[0]) & 15,
			'hasmask' => $message[1] & chr(128),
			'length' => 0,
			'mask' => ""
		];
		$arr_headers['length'] = (ord($message[1]) >= 128) ? ord($message[1]) - 128 : ord($message[1]);
		
		if ($arr_headers['length'] == 126) {
			if ($arr_headers['hasmask']) {
				$arr_headers['mask'] = $message[4] . $message[5] . $message[6] . $message[7];
			}
			$arr_headers['length'] = ord($message[2]) * 256 + ord($message[3]);
		} else if ($arr_headers['length'] == 127) {
			if ($arr_headers['hasmask']) {
				$arr_headers['mask'] = $message[10] . $message[11] . $message[12] . $message[13];
			}
			$arr_headers['length'] = ord($message[2]) * 65536 * 65536 * 65536 * 256 
						+ ord($message[3]) * 65536 * 65536 * 65536
						+ ord($message[4]) * 65536 * 65536 * 256
						+ ord($message[5]) * 65536 * 65536
						+ ord($message[6]) * 65536 * 256
						+ ord($message[7]) * 65536 
						+ ord($message[8]) * 256
						+ ord($message[9]);
		} else if ($arr_headers['hasmask']) {
			$arr_headers['mask'] = $message[2] . $message[3] . $message[4] . $message[5];
		}
		//echo $this->strtohex($message);
		return $arr_headers;
	}

	protected function extractPayload($message, $arr_headers) {
		
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
		
		return substr($message, $offset);
	}

	protected function applyMask($arr_headers, $payload) {
		
		$effective_mask = "";
		
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
		
	protected function checkRSVBits($arr_headers, $user) { // override this method if you are using an extension where the RSV bits are used.
		
		if (ord($arr_headers['rsv1']) + ord($arr_headers['rsv2']) + ord($arr_headers['rsv3']) > 0) {
			//$this->disconnect($user); // Do: fail connection
			return true;
		}
		
		return false;
	}

	protected function strtohex($str) {
		
		$str_out = "";
		
		for ($i = 0; $i < strlen($str); $i++) {
			$str_out .= (ord($str[$i])<16) ? "0" . dechex(ord($str[$i])) : dechex(ord($str[$i]));
			$str_out .= " ";
			if ($i%32 == 7) {
			$str_out .= ": ";
			}
			if ($i%32 == 15) {
			$str_out .= ": ";
			}
			if ($i%32 == 23) {
			$str_out .= ": ";
			}
			if ($i%32 == 31) {
			$str_out .= "\n";
			}
		}
		return $str_out . "\n";
	}
}
