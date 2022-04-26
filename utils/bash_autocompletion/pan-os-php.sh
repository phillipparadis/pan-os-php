#!/usr/bin/env bash

######
#   THIS SCRIPT IS ONLY FOR BASH AUTOCOMPLETE
######


# © 2019 Palo Alto Networks, Inc.  All rights reserved.
#
# Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf


#working with BASH version 5

__pan-os-php_scripts()
{

	if [ -n "${ZSH_VERSION}" ]; then
	   # assume Zsh
	   echo "ZSH is not supported yet"
	   return 0
	elif [ -n "${BASH_VERSION}" ]; then
	   # assume Bash
	   #echo "${BASH_VERSION}"
	   if [[ "${BASH_VERSINFO[0]}" -lt 5 ]] ; then
	     echo "    -- PLEASE UPDATE YOUR BASH TO VERSION 5 --   "
	    return 0
	   fi
	   :
	else
	   # asume something else
	   echo "no supported SHELL"
	   return 0
	fi

	if [[ "${BASH_VERSINFO[0]}" -gt 4 ]] ; then
		# Assigned variable by _init_completion.
		#   cur    Current argument.
		#   prev   Previous argument.
		#   words  Argument array.
		#   cword  Argument array size.
		local cur prev prev2 words cword


		# path completion if space is available in filename/directory
		local IFS=$'\n'

		_get_comp_words_by_ref cur prev

		declare -a arguments
		declare -a type
		declare -a checkArray


		arguments=('type=' 'in=' 'out=' 'actions=' 'filter=' 'location=' 'loadpanoramapushedconfig' 'loadplugin=' 'help'
		 'listactions' 'listfilters' 'debugapi' 'apitimeout='
		 'shadow-apikeynohidden' 'shadow-apikeynosave' 'shadow-disableoutputformatting' 'shadow-displaycurlrequest' 'shadow-enablexmlduplicatesdeletion'
		 'shadow-ignoreinvalidaddressobjects' 'shadow-json' 'shadow-reducexml'
		 'outputformatset='
		 'stats' 'template=' 'version' )
    type=(
      "stats"
      "address" "service" "tag" "schedule" "application" "threat"
      "rule"
      "device" "securityprofile" "securityprofilegroup"
      "zone"  "interface" "virtualwire" "routing"
      "key-manager"
      "address-merger" "addressgroup-merger"
      "service-merger" "servicegroup-merger"
      "tag-merger"
      "rule-merger"
      "override-finder"
      "diff"
      "upload"
      "xml-issue"
      "appid-enabler"
      "config-size"
      "download-predefined"
      "register-ip-mgr"
      "userid-mgr"
      "xml-op-json"
      "bpa-generator"
      "playbook"
      "ironskillet-update"
      "maxmind-update"
      "util_get-action-filter"
      "software-remove"
      "traffic-log"
      "system-log"
      "gratuitous-arp"
      "software-download"
      "software-preparation"
      "license"
      "config-download-all"
      "spiffy"
      "config-commit"
      "protocoll-number-download"
      "html-merger"
    )

		checkArray=('in' 'out' 'actions' 'filter' 'location')

		prev2=${COMP_WORDS[COMP_CWORD-2]}

		if [[ "${cur}" = "=" || "${prev}" = "=" ]] ; then
			RepPatt="="
			RepBy=""
			cur2="${cur//$RepPatt/$RepBy}"

			if [[ "${prev}" = "type" || "${prev2}" = "type" \
			  ]] ; then

				compopt +o nospace
				COMPREPLY=($(compgen -W '${type[*]}' -- "${cur2}"))
			elif [[ "${prev}" = "actions" || "${prev2}" = "actions" \
			  ]] ; then

			    compopt +o nospace
			elif [[ "${prev}" = "filter" || "${prev2}" = "filter" \
			  ]] ; then

			    compopt +o nospace
			elif [[ "${prev}" = "location" || "${prev2}" = "location" \
			  ]] ; then

			    compopt +o nospace
			elif [[ "${prev}" = "loadplugin" || "${prev2}" = "loadplugin" \
			  ]] ; then

			    compopt +o nospace
			elif [[ "${prev}" = "template" || "${prev2}" = "template" \
			  ]] ; then

			    compopt +o nospace
			elif [[ "${prev}" = "apitimeout" || "${prev2}" = "apitimeout" \
			  ]] ; then

			    compopt +o nospace
			elif [[ "${checkArray[*]}" =~ ${prev}  || "${checkArray[*]}" =~ ${prev2} ]] ; then

				local IFS=$'\n'
				compopt -o filenames
		        COMPREPLY=( $(compgen -f -- ${cur2} ) )
			fi
		else
			# remove used argument from array
			local word

			prevstring=""
			for word in ${COMP_WORDS[*]}; do
				if [[ ${word} = "=" ]]; then
					case ${prevstring} in
						type*)
							unset 'arguments[0]'
							;;
						in*)
							unset 'arguments[1]'
							;;
						out*)
							unset 'arguments[2]'
							;;
					  actions*)
							unset 'arguments[3]'
							;;
						filter*)
							unset 'arguments[4]'
							;;
						location*)
							unset 'arguments[5]'
							;;
						loadplugin*)
					    unset 'arguments[7]'
							;;
						apitimeout*)
					    unset 'arguments[12]'
							;;
					 template*)
					    unset 'arguments[22]'
							;;
					esac
				else
					#arguments=( "${arguments[@]/$word}" )
					case ${word} in
					  loadpanoramapushedconfig )
							unset 'arguments[6]'
							;;
					  help )
					    unset 'arguments[8]'
							;;
						listactions )
						  unset 'arguments[9]'
							;;
					  listfilters )
					    unset 'arguments[10]'
							;;
					  debugapi )
					    unset 'arguments[11]'
							;;
					  shadow-apikeynohidden )
					    unset 'arguments[13]'
							;;
					# 'shadow-json' 'shadow-reducexml' 'stats' 'template')
					  shadow-apikeynosave )
					    unset 'arguments[14]'
							;;
					  shadow-disableoutputformatting )
					    unset 'arguments[15]'
							;;
					  shadow-displaycurlrequest )
					    unset 'arguments[16]'
							;;
					  shadow-enablexmlduplicatesdeletion )
					    unset 'arguments[17]'
							;;
					  shadow-ignoreinvalidaddressobjects )
					    unset 'arguments[18]'
							;;
					  shadow-json )
					    unset 'arguments[19]'
							;;
					  shadow-reducexml )
					    unset 'arguments[20]'
							;;
					  stats )
					    unset 'arguments[21]'
							;;
					esac
				fi
				prevstring=${word}
			done

			local arg compreply=""
			COMPREPLY=($(compgen -W '${arguments[*]}' -- "${COMP_WORDS[COMP_CWORD]}"))

			if [[ ${#COMPREPLY[*]} = 1 ]] && [[ ${COMPREPLY[0]} =~ "=" ]] ; then
				compopt -o nospace
			else
				compopt +o nospace
			fi
		fi

		#return 0
	else

		#BASH version 3

		#Todo: in=/out=/file= path completion:
		# 1) => fix found --------------do not put "/" automatically
		# 2) always show full patch not only what can be autofilled
		#both can be fixed -o filenames

		#Todo: debug / print / help does not autofill nocase/ as this was set on purpose "-o nospace"
		# 1) fix possible??

		#COMP_WORDBREAKS=${COMP_WORDBREAKS/=/}
	    #COMP_WORDBREAKS=${COMP_WORDBREAKS/@/}
	    #export COMP_WORDBREAKS

		# Assigned variable by _init_completion.
		#   cur    Current argument.
		#   prev   Previous argument.
		#   words  Argument array.
		#   cword  Argument array size.
		local cur prev words cword

		_get_comp_words_by_ref cur prev

		declare -a arguments
		arguments=('vendor=' 'file=' 'out=' 'in=' 'print ' 'debug ' 'help ' 'reducexml ' 'routetable= ' 'expedition ' 'testing ')

		vendor=('ciscoasa' 'netscreen' 'sonicwall' 'sophos' 'ciscoswitch' 'ciscoisr' 'fortinet' 'srx' 'cp-r80' 'cp' 'cp-beta' 'huawei' 'stonesoft' 'sidewinder' )


		if [[ "${cur}" =~ "vendor=" ]] ; then
			RepPatt="vendor="
			RepBy=""
			cur2="${cur//$RepPatt/$RepBy}"

			COMPREPLY=($(compgen -o nospace -W '${vendor}' -- "${cur2}"))

		elif [[ "${cur}" =~ "file=" ]] ; then
			RepPatt="file="
			RepBy=""
			cur2="${cur//$RepPatt/$RepBy}"

	        COMPREPLY=( $(compgen -o filenames -f -- ${cur2} ) )

			if [ ${#COMPREPLY[*]} = 1 ]; then
		        [ -d "$COMPREPLY" ] && LASTCHAR=/
		        COMPREPLY=$(printf %q%s "$COMPREPLY" "$LASTCHAR")
		    fi
		elif [[ "${cur}" =~ "in=" ]] ; then
			RepPatt="in="
			RepBy=""
			cur2="${cur//$RepPatt/$RepBy}"

	        COMPREPLY=( $(compgen -o filenames -f  -- ${cur2} ) )

			if [ ${#COMPREPLY[*]} = 1 ]; then
		        [ -d "$COMPREPLY" ] && LASTCHAR=/
		        COMPREPLY=$(printf %q%s "$COMPREPLY" "$LASTCHAR")
		    fi
		elif [[ "${cur}" =~ "out=" ]] ; then
			RepPatt="out="
			RepBy=""
			cur2="${cur//$RepPatt/$RepBy}"

	        COMPREPLY=( $(compgen -o filenames -f -- ${cur2} ) )

	        if [ ${#COMPREPLY[*]} = 1 ]; then
		        [ -d "$COMPREPLY" ] && LASTCHAR=/
		        COMPREPLY=$(printf %q%s "$COMPREPLY" "$LASTCHAR")
		    fi
		else
			# remove used argument from array
			local word
			for word in ${COMP_WORDS[*]}; do
				case ${word} in
					vendor=*)
						unset 'arguments[0]'
						;;
					file=*)
						unset 'arguments[1]'
						;;
					out=*)
						unset 'arguments[2]'
						;;
					in=*)
						unset 'arguments[3]'
						;;
					print )
						unset 'arguments[4]'
						;;
					debug )
						unset 'arguments[5]'
						;;
					help )
						unset 'arguments[6]'
						;;
					reducexml )
						unset 'arguments[7]'
						;;
				esac
			done

			local arg compreply=""
			#Append matched string with cur.
			for arg in ${arguments[*]}; do
				[ "${cur}}" != "${arg}" ] && compreply="${arg} ${compreply}"
			done

			COMPREPLY=($(compgen -o nospace -W '${compreply}' -- "${COMP_WORDS[COMP_CWORD]}"))
		fi
	fi
}

if [ -n "$ZSH_VERSION" ]; then
  # assume Zsh
  echo "ZSH is not supported yet"
  return 0
elif [ -n "$BASH_VERSION" ]; then
  # assume Bash
  if (( "${BASH_VERSINFO[0]}" > 4)); then
     complete -o default -F __pan-os-php_scripts pan-os-php
  else
     #Todo:
     #1) -o nospace is needed to NOT add space after "file=";
     #              but needed to add space after "debug/print/help"
     #2) -o filenames is needed to NOT add absolute path for everything related to path completion;
     #              but problem for arguments like "file=" which is autocomplete to "file\="


     #complete -F __pa_migration-parser_scripts -o nospace pa_migration-parser
     #-o bashdefault -o filenames
     complete -o nospace -F __pan-os-php_scripts pan-os-php

     #working
     #complete -o nospace -F __pa_migration-parser_scripts pa_migration-parser
  fi
  :
else
  # asume something else
  echo "no supported SHELL"
  return 0
fi



