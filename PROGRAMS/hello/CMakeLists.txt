cmake_minimum_required (VERSION 3.0)

project(hello)

get_filename_component(PARENT_DIR ${CMAKE_CURRENT_SOURCE_DIR} DIRECTORY)

set(BOOST_ROOT ${PARENT_DIR}/LIBRARIES/boost)
set(Boost_NO_SYSTEM_PATHS ON)

add_compile_options(-std=c++11 -Wall -Wextra -Wsign-conversion -O3 -march=native)

# Include the LAB1100CC library
add_subdirectory(${PARENT_DIR}/LAB1100CC ${CMAKE_CURRENT_BINARY_DIR}/LAB1100CC)

# Include the simple-web-server library
add_subdirectory(${PARENT_DIR}/SERVICES/web_server ${CMAKE_CURRENT_BINARY_DIR}/SERVICES/web_server)

# Include the RapidJSON header files
include_directories(${PARENT_DIR}/LIBRARIES/rapidjson/include)

find_package(Boost COMPONENTS system thread REQUIRED)

# Prepare program
add_executable(hello program.cpp)

# Link libraries to program
target_link_libraries(hello LAB1100CC)
target_link_libraries(hello simple-web-server)

target_link_libraries(hello ${Boost_LIBRARIES})
target_include_directories(hello PRIVATE ${Boost_INCLUDE_DIR})

# Install the program

set_target_properties(hello PROPERTIES INSTALL_RPATH_USE_LINK_PATH TRUE)

install(TARGETS hello DESTINATION ${PARENT_DIR}/RUN)
