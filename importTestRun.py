#!/usr/bin/env python
"""importTestRun.py

Extremely custom islandora newspaper import script.

Cobbled together helper script that helps process entire lots of newspaper
issues at once into Islandora. 

Make sure islandora surrogate generation is disabled. This requires microservices to be
setup.

Not much use unless you have an import structure exactly like ours.
"""
from glob import iglob
from shutil import copy
import re
import os
import sys
import tempfile
import subprocess

def check_is_paper_dir(cur_path):
    if os.path.isfile(os.path.join(cur_path, 'metadata.php')) : return 'metadata.php'
    if os.path.isfile(os.path.join(cur_path, 'metadata.php.txt')) : return 'metadata.php.txt'
    return False

top_import_tree = '/mnt/etcimages/NDP_Test_CNN'

yes_count = 0
no_count = 0
item_counter = 0

# Loop Over Paths
for root, dirs, files in os.walk(top_import_tree):

    dirs.sort()
    for cur_dir in dirs:

        metadata_filename = check_is_paper_dir(os.path.join(root, cur_dir))
        if metadata_filename:

            # Restrict to X issues.
            max_issues_to_import = 100
            item_counter = item_counter + 1
            if item_counter > max_issues_to_import:
                sys.exit('Stopping at ' + str(max_issues_to_import) + ' Issues Imported')

            yes_count = yes_count + 1
            dirpath = tempfile.mkdtemp()

            # Copy TIF files
            for fname in iglob(os.path.join(root, cur_dir, '*.jpg')):
                copy(fname, os.path.join(dirpath, os.path.basename(fname)))

            # Read in metadata.inc.php to variable
            with open (os.path.join(root, cur_dir, metadata_filename), "r") as myfile:
                conf_file_data=myfile.read()

                # Prepend file with php tags
                conf_file_data = "<?php\n\n" + conf_file_data

                # Change SOURCE_MEDIA to ISSUE_MEDIA
                conf_file_data = conf_file_data.replace('SOURCE_MEDIA', 'ISSUE_MEDIA')

                # Re-order numbers in date function due to input / spec error
                m = re.search('mktime\((.*)\)\)', conf_file_data)
                old_date_sequence_string = m.group(1)

                date_sequence_list = old_date_sequence_string.split(',')
                month_value = date_sequence_list[4]
                date_sequence_list[4] = date_sequence_list[3]
                date_sequence_list[3] = month_value
                new_date_sequence_string = ','.join(date_sequence_list)
                new_conf_file_data = conf_file_data.replace(old_date_sequence_string, new_date_sequence_string)
                print new_conf_file_data

            # Write out old conf
            # Open a file
            fo = open(os.path.join(dirpath, 'metadata.php'), "wb")
            fo.write(new_conf_file_data);
            fo.close()

            # Is this a special issue?
            special_identifier = ''
            cur_dir_array = cur_dir.split('_')
            print cur_dir_array
            if len(cur_dir_array) > 4 :
                 matches = re.search('ISSUE_SUPPLEMENT_TITLE\', *\'(.{1,25})\'\)\;', new_conf_file_data)
                 if matches.group(1):
                     # Crush to lowercase, replace spaces with underscores and strip all non-word characters
                     # Replacing spaces with underscores THEN back to hyphens is to leverage the ease of 
                     # \W
                     special_identifier = re.sub(r'\W+', '', matches.group(1).lower().replace(' ','_')).replace('_','-')
                 else:
                     # If the above fails or the user did not enter a string in the conf file, then fallback to the label used
                     # In the metadata.
                     special_identifier = '-'.join(cur_dir_array[4:]).lower()
            print "Special Identifier :", special_identifier

            # Ready to import.
            # Run Import command
            import_command_list = [
                                  'drush',
                                  '-u', '1',
                                  '--root=/srv/www/VRE7',
                                  '--uri=http://newspapers.lib.unb.ca',
                                  'islandora_ingest_newspapers',
                                  dirpath,
                                  'islandora:1',
                                  'carletonnorthnews',
                                  'http://fedora.lib.unb.ca:8080/fedora',
                                  'fedoraAdmin',
                                  '**PASSWORD**',
                                  'NBFU',
                                  special_identifier
                   ]
            q = subprocess.Popen(import_command_list, stdout = subprocess.PIPE)
            q.wait()
            print ' '.join(import_command_list)

            # Remove temp directory tiffs
            filelist = [ f for f in os.listdir(dirpath) if f.endswith(".jpg") ]
            for f in filelist:
                os.remove(os.path.join(dirpath,f))
                pass
 
        else :
            no_count = no_count + 1
