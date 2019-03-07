
/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

#ifndef LAB1100CCPROGRAM
#define LAB1100CCPROGRAM

#include <vector>
#include <tuple>
#include <string>
#include <chrono>
#include <thread>
#include <pthread.h>
#include <mutex>
#include <unordered_map>

#include "shed.hpp"

namespace LAB1100CC {
	
	class ProgramClient {
		
		public:
			
			std::shared_ptr<HttpServer::Response> response;
			
			ProgramClient (std::shared_ptr<HttpServer::Response> response) : response(response) {}
						
			void sendMessage(const std::string &str) {
				
				response->write(str);
			}
			
			void openChunk() {
					
				SimpleWeb::CaseInsensitiveMultimap header;
				header.emplace("Transfer-Encoding", "chunked");
				response->write(header);
			}
			
			void sendChunk(const std::string &str) {
				
				std::ostringstream hex_stream;
				hex_stream << std::hex << (str.length() + 1);
				std::string str_output = hex_stream.str() + "\r\n" + str + "\n\r\n";
				
				response->write(str_output.c_str(), str_output.length());
				response->send();
			}
			
			void closeChunk() {
				
				response->write("0\r\n\r\n", 5);
			}
			
			void sendError(const std::exception &e) {
				
				response->write(SimpleWeb::StatusCode::server_error_internal_server_error, "Error while executing request: "+std::string(e.what()));
			}
			
			void sendErrorUncaught(const std::exception_ptr &e_ptr) {
				
				response->write(SimpleWeb::StatusCode::server_error_internal_server_error, "Error while executing request: "+std::string(e_ptr ? e_ptr.__cxa_exception_type()->name() : "unknown"));
			}
	};

	class Jobs {
		
		public:
		
			typedef unsigned int type_identifier;
			typedef unsigned int type_timeout;
		
		protected:

			typedef std::chrono::steady_clock type_timer;
			typedef std::unordered_map<type_identifier, std::tuple<pthread_t, type_timer::time_point, type_timeout>> type_map_threads;
			
			std::mutex lock_list;
			unsigned int count_threads = 0;
			unsigned int count_timeouts = 0;
			type_map_threads map_threads;
		
		public:

			void check() {
				
				auto time = type_timer::now();
				std::vector<decltype(map_threads)::key_type> arr_remove;

				for (const auto& map_entry : map_threads) {
					
					auto time_start = std::get<1>(map_entry.second);
					auto timeout = std::get<2>(map_entry.second);
					
					if (timeout == 0) {
						
						arr_remove.emplace_back(map_entry.first);
					} else if ((time_start + std::chrono::seconds(timeout)) < time) {
						
						auto thread = std::get<0>(map_entry.second);
					
						pthread_cancel(thread);
						count_timeouts++;

						arr_remove.emplace_back(map_entry.first);
					}
				}
				
				std::lock_guard<std::mutex> guard(lock_list);
				
				for (auto&& key : arr_remove) {
					
					map_threads.erase(key);
				}
			}
			
			type_identifier getIdentifier() {
				
				count_threads++;
				type_identifier identifier = count_threads;
				
				return identifier;
			}

			void add(const type_identifier &identifier, std::thread &thread, type_timeout &timeout) {

				std::lock_guard<std::mutex> guard(lock_list);
				
				auto time = type_timer::now();
										
				map_threads[identifier] = std::make_tuple(thread.native_handle(), time, timeout);
			}
			
			void remove(const type_identifier &identifier) {
				
				std::lock_guard<std::mutex> guard(lock_list);
										
				auto map_entry = map_threads.find(identifier);
				
				if (map_entry != map_threads.end()) {
					
					std::get<2>(map_entry->second) = 0; // Remove timeout
				}
			}
			
			std::string getStatistics() {
				
				std::string str = "{\"jobs\": " + std::to_string(count_threads) +", \"timeouts\": " + std::to_string(count_timeouts) + "}";
				
				return str;
			}
	};
}

#endif
