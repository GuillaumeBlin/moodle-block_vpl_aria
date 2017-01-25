Moodle 2.0+ block allowing aria support for vpl feedback
================

Synthesis
------------

This project allows to add a block to any VPL activity in order to allow simple browse of VPL evaluation informations.
  
Installation
------------

To install the plugin using git, execute the following commands in the root of your Moodle install:

    git clone https://github.com/GuillaumeBlin/moodle-block_vpl_aria.git your_moodle_root/blocks/vpl_aria
    
Or, extract the following zip in `your_moodle_root/blocks/` as follows:

    cd your_moodle_root/blocks/
    wget https://github.com/GuillaumeBlin/moodle-block_vpl_aria/archive/master.zip
    unzip -j master.zip -d vpl_aria

Use
------------

For each VPL activity you want the ARIA capabilities to be available, add the "VPL aria capabilities" using "Add a block" in the edit mode.
In the configuration of the "VPL aria capabilities block", the option "Display on page types" should be set to "mod-vpl-forms-*"
The text Regex on grade, Regex on comments and Regex on execution should content possible replace statements regarding the output of the respective VPL outputs. For example, you may remove the non informative lines containing +----+ motifs by specifying .replace(/\+-*\+/g,'') as a regex for Regex on comments

Authors and Contributors
------------

In 2017, Guillaume Blin (@GuillaumeBlin).
