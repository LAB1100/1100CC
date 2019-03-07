cmake_minimum_required (VERSION 3.0)

project(LAB1100CC)

# Prepare library
add_library(LAB1100CC INTERFACE)

target_include_directories(LAB1100CC INTERFACE ${CMAKE_CURRENT_SOURCE_DIR})
