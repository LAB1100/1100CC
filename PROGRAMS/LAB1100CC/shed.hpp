
/**
 * 1100CC - web application framework.
 * Copyright (C) 2019 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

#ifndef LAB1100CCSHED
#define LAB1100CCSHED

#include <vector>
#include <string>
#include <sstream>
#include <iomanip>

namespace LAB1100CCShed {
	
	class ExecutionTimer {
			
		protected:
			
			typedef std::chrono::steady_clock type_timer;
			
			type_timer::time_point time_start;
			type_timer::time_point time_end;
		
		public:
		
			void start() {
				
				time_start = type_timer::now();
			}
			
			void stop() {
				
				time_end = type_timer::now();
			}
			
			std::string getTime() {
				
				std::string str_time = std::to_string(std::chrono::duration<double, std::milli> (time_end - time_start).count())+" ms";
				
				return str_time;
			}
	};	

	static std::vector<std::string> explode(const std::string &str, const std::string &delimiter) {
		
		std::vector<std::string> arr;
		
		int length_str = str.length();
		int length_delimiter = delimiter.length();
		
		int i = 0;
		int k = 0;
		
		while (i < length_str) {
			
			int j=0;
			
			while (i+j < length_str && j < length_delimiter && str[i+j] == delimiter[j]) {
				
				j++;
			}
				
			if (j == length_delimiter) { // Found delimiter

				arr.push_back(str.substr(k, i-k));
				
				i += length_delimiter;
				k = i;
			} else {
				
				i++;
			}
		}
		
		arr.push_back(str.substr(k, i-k));
		
		return arr;
	}
	
	static std::string escapeJSON(const std::string &s) {
		
		std::ostringstream o;
		
		for (auto c = s.cbegin(); c != s.cend(); c++) {
			
			if (*c == '"' || *c == '\\' || ('\x00' <= *c && *c <= '\x1f')) {
				o << "\\u"
				<< std::hex << std::setw(4) << std::setfill('0') << (int)*c;
			} else {
				o << *c;
			}
		}
		
		return o.str();
	}
}

#endif
