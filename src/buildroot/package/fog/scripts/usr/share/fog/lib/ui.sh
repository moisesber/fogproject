#!/bin/bash

### BEGIN VARIABLES
# Used to detect if functions are loaded
UI_SH="true"
### END VARIABLES

### BEGIN ENVIRONMENT SETUP
# If a sub shell gets invoked and we lose kernel vars this will reimport them
$(for var in $(cat /proc/cmdline); do echo export $var | grep =; done)
### END ENVIRONMENT SETUP

# Clears screen if debug mode is enabled
# No arguments expected
clearScreen() {
	if [ "$mode" != "debug" ]; then
		for i in $(seq 0 99); do
			echo 
		done
	fi
}

# Pauses if debug mode is enabled
# No arguments expected
debugPause() {
	if [ -n "$isdebug" -o "$mode" == "debug" ]; then
		echo 'Press [Enter] key to continue.'
		read -p "$*"
	fi
}

# Displays FOG banner and server version
# No arguments expected
displayBanner() {
	local version=`wget -q -O - http://${web}service/getversion.php`
	local border="  +--------------------------------------------------------------------------+"

	echo -e "${border}\n"
	echo "                         ..#######:.    ..,#,..     .::##::."
	echo "                    .:######          .:;####:......;#;.."
	echo "                    ...##...        ...##;,;##::::.##..."
	echo "                       ,#          ...##.....##:::##     ..::"
	echo "                       ##    .::###,,##.   . ##.::#.:######::."
	echo "                    ...##:::###::....#. ..  .#...#. #...#:::."
	echo "                    ..:####:..    ..##......##::##  ..  #"
	echo "                        #  .      ...##:,;##;:::#: ... ##.."
	echo "                       .#  .       .:;####;::::.##:::;#:.."
	echo "                        #                     ..:;###.."
	echo -e "\n                         Free Computer Imaging Solution"
	echo -e "                                 Version ${version}\n"
	echo "$border"
	echo "   Credits:"
	echo "   http://fogproject.org/Credits"
	echo "   Released under GPL Version 3"
	echo -e "${border}\n\n"
}

dots() {
    max=45
    if [ -n "$1" ]; then
		n=`expr $max - ${#1}`
		echo -n " * ${1:0:max}"
		if [ "$n" -gt 0 ]; then
			for i in $(seq $n); do
				printf %s .
			done
		fi
	fi
}


# Displays the top half of an error banner with an optional message
# Only called by handleError()
# $1: Warning message [Optional]
handleErrorTopUI() {
	local borderTB=" #############################################################################"
	local borderE=" #                                                                           #"

	echo -e "\n$borderTB"
	echo "$borderE"
	echo " #                     An error has been detected!                           #"
	echo "$borderE"
	echo "$borderTB"
	echo -e "\n\n $1\n\n"
}

# Displays the bottom half of an error banner
# Only called by handleError()
# No arguments expected
handleErrorBotUI() {
	local borderTB=" #############################################################################"
	local borderE=" #                                                                           #"

	echo -e "\n\n$borderTB"
	echo "$borderE"
	echo " #                  Computer will reboot in 1 minute.                        #"
	echo "$borderE"
	echo "$borderTB"
	sleep 60
	debugPause
	exit 0
}

# Displays a warning banner with an optional message
# $1: Warning message [Optional]
handleWarning() {
	local borderTB=" #############################################################################"
	local borderE=" #                                                                           #"

	echo -e "\n$borderTB"
	echo "$borderE"
	echo " #                     A warning has been detected!                           #"
	echo "$borderE"
	echo "$borderTB"
	echo -e "\n\n $1\n\n"
	echo "$borderTB"
	echo "$borderE"
	echo " #                  Will continue in 1 minute.                               #"
	echo "$borderE"
	echo "$borderTB"
	sleep 60
	debugPause
}

# Converts a number of seconds to a string of seconds, minutes, or days
# $1: Seconds
sec2String() {
	if [ $1 -lt 60 ]; then
		echo -n "$1 sec"
	else
		if [ $1 -lt 3600 ]; then
			val=$(expr $1 "/" 60)
			echo -n "$val min"
		else
			if [ $1 -lt 216000 ]; then
				val=$(expr $1 "/" 3600)
				echo -n "$val hours"
			else
				val=$(expr $1 "/" 216000)
				echo -n "$val days"
			fi
		fi
	fi
}
