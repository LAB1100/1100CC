
/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

#include <sstream>
#include <vector>
#include <string>
#include <chrono>
#include <thread>

#include "rapidjson/document.h"
#include "rapidjson/pointer.h"

#include "server_http.hpp"

namespace LAB1100CC {
	
	using HttpServer = SimpleWeb::Server<SimpleWeb::HTTP>;
}

#include "program.hpp"
#include "shed.hpp"

static void show_usage(const std::string name) {
	
    std::cerr << "Usage:" << std::endl
		<< "\t" << name << " <option(s)>" << std::endl
		<< "Options:" << std::endl
		<< "\t-h, --help\t\tShow this help message" << std::endl
		<< "\t-p, --port PORT\t\tSpecify the port" << std::endl
	;
}

namespace LAB1100CC {
	
	class Program {
		
		protected:
			
			static void handleSignal(const int signum) {
				
				exit(signum);
			}

		public:
				
			unsigned int port;
				
			void run() {

				// HTTP-server at (port) using 1 default thread
				HttpServer server;
				server.config.address = "127.0.0.1";
				server.config.port = port;
				server.config.timeout_content = 60*60;
				
				LAB1100CC::Jobs jobs;

				server.resource["^/hello$"]["POST"] = [&jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
					
					LAB1100CC::Jobs::type_timeout timeout = 0;
										
					std::thread work_thread([&jobs, response, request](const LAB1100CC::Jobs::type_identifier &identifier) {
						
						ProgramClient client(response);
						
						try {

							std::ostringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const rapidjson::Value* json_data = rapidjson::Pointer("/data").Get(document);
							const auto& str_data = json_data->GetString();

							client.openChunk();

							client.sendChunk("Hi! Look what I got from you:");
							
							client.sendChunk(str_data);
							
							client.closeChunk();
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
						
						jobs.remove(identifier);
					}, identifier);
					
					jobs.add(identifier, work_thread, timeout);
					
					work_thread.detach();
				};

				server.default_resource["GET"] = [](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					response->write(SimpleWeb::StatusCode::client_error_bad_request, "Could not open path: "+request->path);
				};

				server.on_error = [](std::shared_ptr<HttpServer::Request> /*request*/, const SimpleWeb::error_code & /*ec*/) {
					// Handle errors here
					// Note that connection timeouts will also call this handle with ec set to SimpleWeb::errc::operation_canceled
				};

				std::thread server_thread([&server]() {
					
					server.start();
				});
				
				server_thread.detach();
				
				std::cout << "Server running on port " << port << std::endl;
				
				std::signal(SIGINT, handleSignal);
				
				while (true) {
					
					std::this_thread::sleep_for(std::chrono::seconds(1));
					
					jobs.check();
					
					// Output statistics
					std::cout << "{\"statistics\": " << jobs.getStatistics() << "}" << std::endl;
				}
			}
	};
}

int main(int argc, char** argv) {
	
	if (argc < 3) { // No valid arguments
		
		show_usage(argv[0]);
		return 1;
	}

	//std::vector <std::string> sources;
	unsigned int port;
	
	std::string str_arg;
	
	for (int i = 1; i < argc; i++) {
		
		str_arg = argv[i];
		
		if (str_arg == "-h" || str_arg == "--help") {
			
			i++;
			
			show_usage(str_arg);
			return 0;
			
		} else if (str_arg == "-p" || str_arg == "--port") {
			
			i++;
			
			if (i == argc) { // No arguments left
				
				show_usage(str_arg);
				return 1;
			}
			
			port = std::atoi(argv[i]);
		}
	}
	
	LAB1100CC::Program program;
	
	program.port = port;
	
	program.run();
}
