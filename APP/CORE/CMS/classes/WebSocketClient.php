<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2023 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebSocketClient {
	
	const TIMEOUT_STATUS = 60 * 60 * 2; // Seconds
	const TIMEOUT_CLEANUP = 60; // Seconds
	const TIMEOUT_HANDSHAKE = 10; // Seconds
	const TIMEOUT_WAIT = 20; // Seconds
	
	public $opened; // Called after the handshake response is sent to the client
	public $process; // Called immediately when the data is recieved
	public $closed; // Called after the connection is closed
	
	public $verbose = false;
	
	protected $address;
	protected $port;
	protected $use_ssl;
	
	protected $time_cleanup = 0;
	protected $time_status = 0;
	protected $size_buffer_max = false;
	protected $num_socket_error = false;
	protected $str_socket_error = false;
	protected $socket = false;
	
	protected $do_write = false;
	protected $handshake = false;
	protected $has_received_partial_packet = false;
	protected $buffer_partial_packet = '';
	protected $buffer_partial_frame = '';
	
	protected $is_sending_continuous = false;
	protected $arr_send_messages = [];
	
	protected $timeout_alive = 300; // Seconds (5 minutes)
	protected $time_alive = false;
	protected $has_sent_close = false;
	
	function __construct($address, $port, $use_ssl = false, $size_buffer = 2048) {
	 
		$this->address = $address;
		$this->port = $port;
		$this->use_ssl = $use_ssl;
		$this->size_buffer_max = $size_buffer;
				
		Mediator::attach('cleanup', false, function() {
			
			$this->cleanUp();
		});
	}
	
	public function open() {
		
		$context = stream_context_create();
		
		if ($use_ssl) {
			
			$this->socket = stream_socket_client('ssl://'.$this->address.':'.$this->port.'', $this->num_socket_error, $this->str_socket_error, static::TIMEOUT_WAIT, STREAM_CLIENT_CONNECT, $context) or error('Failed to create master socket'); // $address => '0.0.0.0'/'0' for everywhere, '127.0.0.1' localhost

			stream_context_set_option($context, 'ssl', 'allow_self_signed', false);
			stream_context_set_option($context, 'ssl', 'verify_peer', false);
		} else {
			
			$this->socket = stream_socket_client('tcp://'.$this->address.':'.$this->port.'', $this->num_socket_error, $this->str_socket_error, static::TIMEOUT_WAIT, STREAM_CLIENT_CONNECT, $context) or error('Failed to create master socket'); // $address => '0.0.0.0'/'0' for everywhere, '127.0.0.1' localhost
		}
		
		stream_set_blocking($this->socket, false); // Make sure to set to non-blocking in case of future fread() loops 
				
		$this->do_write = false;
		$this->has_received_partial_packet = false;
		$this->buffer_partial_packet = '';
		$this->buffer_partial_frame = '';
	
		$this->is_sending_continuous = false;
		$this->arr_send_messages = [];
		
		$time = time();
		
		$this->time_cleanup = $time;
	}

	public function sendHandshake($message) {
		
		$message = $message.EOL_EXCHANGE.EOL_EXCHANGE;
		
		$this->do_write = true;
		$this->arr_send_messages[] = $message;
		
		$time_connect = microtime(true);
		
		while (!$this->handshake) { // Send handshake and listen confirm
			
			if ((microtime(true) - $time_connect) > static::TIMEOUT_HANDSHAKE) {
				error(getLabel('msg_socket_server_handshake_timeout'));
			}
			
			$this->run();
		}
	}
	
	public function send($message) {
		
		$message = $this->frame($message);
		
		$this->do_write = true;
		$this->arr_send_messages[] = $message;
	}
	
	public function run() {

		$arr_read = [$this->socket];
		$arr_write = ($this->do_write ? [$this->socket] : null);
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
										
					foreach ($this->arr_send_messages as $key => $message) {
						
						$length = strlen($message);
						
						try {
							$sent = fwrite($socket, $message, $length);
						} catch (Exception $e) {
							unset($e);
						}
						
						if ($sent >= $length || $sent === false) {
							
							unset($this->arr_send_messages[$key]);
						} else {
							   
							// If not sent the entire message.
							// Get the part of the message that has not yet been sent as message
							$message = substr($message, $sent);
							
							$this->arr_send_messages[$key] = $message;
						}
						
						if (!$this->arr_send_messages) {
							$this->do_write = false;
						}
					}
				}
			}
			
			foreach ($arr_read as $socket) {
				
				$nr_bytes = 0;
				
				try {
					
					$buffer = fread($socket, $this->size_buffer_max);
					$nr_bytes = strlen($buffer);
				} catch (Exception $e) {
					//error('Failed socket read: '.$this->str_socket_error);
					unset($e);
				}

				if ($buffer === false) {
										
					$this->disconnect($socket);
					$this->msg('Server disconnected: Connection lost. IP: '.$this->address.'.');
				} else if ($nr_bytes > 0) {
					
					if (!$this->handshake) {
						
						$tmp = str_replace("\r", '', $buffer);
						
						if (strpos($tmp, "\n\n") === false) {
							continue; // If the client has not finished sending the header, then wait before sending our upgrade response.
						}
						
						$this->doHandshake($buffer);
					} else {
										
						$this->alive();
						
						foreach ($this->deframe($buffer, $user) as $message) {
							
							if ($this->has_sent_close) {
								
								$this->disconnect($socket);
								$this->msg('Server disconnected: Sent close. IP: '.$this->address.'.');
								
								break;
							}
						
							if ($message !== false) {
								
								call_user_func($this->process, $message); // Do: Re-check this. Should already be UTF-8.
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
											
											if ($this->has_sent_close) {
												
												$this->disconnect($socket);
												$this->msg('Server disconnected: Sent close. IP: '.$this->address.'.');
												
												$nr_bytes = 0;
												break;
											}
										
											if ($message !== false) {
												
												call_user_func($this->process, $message);
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
						
			// Provide status update if needed
			
			$this->time_status = $time;
		}
	}
	
	public function alive() {
		
		$this->time_alive = time();
	}
	
	public function isDead($time) {
		
		if (($time - $this->time_alive) > $this->timeout_alive) {
			return true;
		}
		
		return false;
	}
	
	protected function cleanUp($time = false) {
					
		if (!$time || $this->isDead($time)) {
			
			$this->disconnect(($time ? false : true)); // Soft-close vs hard-close
			
			if ($time) {
				$this->msg('Server disconnected: Server timed out. IP: '.$this->address.'.');
			} else {
				$this->msg('Server disconnected: Cleanup. IP: '.$this->address.'.');
			}
		}
	}
	
	public function close() {
		
		$this->disconnect(true);
	}
	
	protected function disconnect($trigger_close = false) {
		
		if (!$this->socket) {
			return;
		}
					
		if (!$trigger_close) {
			
			call_user_func($this->closed);
			stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
			fclose($this->socket);
		} else {
			
			$message = $this->frame('', 'close');
			try {
				fwrite($this->socket, $message, strlen($message));
			} catch (Exception $e) {
				unset($e);
			}
		}
			
		$this->socket = false;
	}
	
	protected function doHandshake($buffer) {
		
		if (trim($buffer) != 'welcome') {
			
			return false;
		}
		
		$this->handshake = true;
		
		call_user_func($this->opened);
	}

	public function msg($message) {
	  
		if (!$this->verbose) {
			return;
		}
		
		msg($message, 'WEBSOCKETCLIENT');
	}

	protected function frame($message, $message_type = 'text', $message_continues = false) {
	  
		switch ($message_type) {
			case 'continuous':
				$byte_1 = 0;
				break;
			case 'text':
				$byte_1 = ($this->is_sending_continuous) ? 0 : 1;
				break;
			case 'binary':
				$byte_1 = ($this->is_sending_continuous) ? 0 : 2;
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
			$this->is_sending_continuous = true;
		} else {
			$byte_1 += 128;
			$this->is_sending_continuous = false;
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
	
	protected function deframe($frame) {
			
		do {
			
			if ($this->has_received_partial_packet) {
				
				$frame = $this->buffer_partial_packet.$frame;
				$this->has_received_partial_packet = false;
			}
			
			$arr_headers = WebSocketServer::extractHeaders($frame);
			
			$do_pong_reply = false;
			$will_close = false;
			
			switch($arr_headers['opcode']) {
				case 0:
				case 1:
				case 2:
				break;
			case 8: // Do: close the connection
				$this->has_sent_close = true;
				break;
			case 9: // Ping received, server is a client, send pong
				$do_pong_reply = true;
			case 10: // Pong received, client responded
				break;
			default: // Do: fail connection
				$will_close = true;
				break;
			}
			
			if ($this->has_sent_close || $will_close || $this->checkRSVBits($arr_headers)) { // Do: fail the connection
				break;
			}
			
			$arr_payload = WebSocketServer::extractPayload($frame, $arr_headers);
			
			$payload = $arr_payload[0];
			
			if ($arr_headers['length'] > strlen($payload)) {
			
				$this->has_received_partial_packet = true;
				$this->buffer_partial_packet = $frame;
				
				break;
			}
			
			$frame = $arr_payload[1]; // Leftover payload, deframe next iteration
			
			$payload = WebSocketServer::applyMask($arr_headers, $payload);
			
			if ($do_pong_reply) {
			
				$message_reply = $this->frame($payload, 'pong');
				fwrite($this->socket, $message_reply, strlen($message_reply));
				
				continue;
			}
			
			$payload = $this->buffer_partial_frame.$payload;
			
			if ($arr_headers['fin']) {
			
				$this->buffer_partial_frame = '';
				yield $payload;
				
				continue;
			}
			
			$this->buffer_partial_frame = $payload;
			
		} while ($frame != '');
		
		yield false;
	}
	
	protected function checkRSVBits($arr_headers) { // override this method if you are using an extension where the RSV bits are used.
		
		if (($arr_headers['rsv1'] + $arr_headers['rsv2'] + $arr_headers['rsv3']) > 0) {
			return true;
		}
		
		return false;
	}
}
