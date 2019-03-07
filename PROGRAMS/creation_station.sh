#!/bin/bash

###
 # 1100CC - web application framework.
 # Copyright (C) 2019 LAB1100.
 #
 # See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 ##

COLOR_RED='\033[1;31m'
COLOR_GREEN='\033[1;32m'
COLOR_NO='\033[0m' # No Color

printf '\n'

cat << "EOF"
                0000             
             0000000             
          0000000000             
        0000000000001111         
       00000000000001111111      
     000000000000000111111111    
    00000000000000001111111111   
     00000000000000011111111111  
        000000000000000111111111 
       1110000000000000011111111 
      111111000000000000011111111
      111111110000000000001111111
      111111110000000000011111111
      111111110000000000011111111
       1111111110000000011111111 
        11111111100000111111111  
          11111111111111111111   
           11111111111111111     
            111111111111111      
              11111111111        
                 11111            
EOF

printf '\nWelcome to the '$COLOR_RED'1100CC'$COLOR_NO' Creation Station!\n\n'

# Set working path to script's location
PATH_SCRIPT=$(cd $(dirname ${BASH_SOURCE[0]}) && pwd)

cd $PATH_SCRIPT

ARR_FOLDERS=()

for FOLDER in *; do
    if [[ -d $FOLDER ]] && [[ ${FOLDER^^} != $FOLDER ]]; then
        ARR_FOLDERS+=($FOLDER)
    fi
done

ARR_FOLDERS+=('Quit')

PS3='Please pick the program you want to build: '

select FOLDER in ${ARR_FOLDERS[@]}
do
	if [[ $FOLDER = '' ]]; then
		
		continue
	elif [[ $FOLDER = 'Quit' ]]; then
		
		printf '\n'$COLOR_GREEN'Bye!'$COLOR_NO'\n'
		exit
	else
		
		PATH_SOURCE=$(readlink -f $FOLDER)
		break
	fi
done

PATH_BUILD='/tmp/1100CC/programs/build/'$FOLDER

mkdir -p $PATH_BUILD

cd $PATH_BUILD

cmake $PATH_SOURCE

make

EXIT=$?

if [[ $EXIT -ne 0 ]]; then

	make |& grep 'error:'
	
	printf '\n'$COLOR_RED'Program did not compile!'$COLOR_NO'\nExit: '$EXIT'\n'
	
	rm -rf $PATH_BUILD
	
	exit
fi

make install

rm -rf $PATH_BUILD

PATH_RUN=$(dirname $PATH_SOURCE)'/RUN/'$FOLDER

printf '\nProgram ready at: '$COLOR_GREEN$PATH_RUN$COLOR_NO'\n'

exit
