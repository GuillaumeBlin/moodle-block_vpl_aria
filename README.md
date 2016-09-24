# moodle-block_quiz_gg
Moodle 2.0+ block allowing quiz group grading
================

Synthesis
------------

This project allows to add a block to any wanted quiz in order to allow group grading regarding this quiz. 
It only work if the current user is part of a unique group. If it is so, when an attempt of the quiz is done, all the information of this attempt are also stored for the other members of the group. Somehow, it simulates the fact that the quiz was answered by a group of users.
It is inspired on the moodle-quiz_simulate moodle activity plugin of (@jamiepratt) and is using modified functions of this original great contribution in order to duplicate the attempt rather generating some new attempts.
  
Installation
------------

To install the plugin using git, execute the following commands in the root of your Moodle install:

    git clone https://github.com/GuillaumeBlin/moodle-block_quiz_gg.git your_moodle_root/blocks/quizz_gg
    
Or, extract the following zip in `your_moodle_root/blocks/` as follows:

    cd your_moodle_root/blocks/
    wget https://github.com/GuillaumeBlin/moodle-block_quiz_gg/archive/master.zip
    unzip -j master.zip -d quizz_gg

Use
------------

For each quiz you want to allow the group grading mode, add the "Quiz group grading block" using "Add a block" in the edit mode.
In "Quiz administration > Edit settings > Appearance", the option "Show blocks during quiz attempts" should be set to "yes"
In the configuration of the "Quiz group grading block", the option "Display on page types" should be set to "Any quiz module page"

Authors and Contributors
------------

In 2016, Guillaume Blin (@GuillaumeBlin) based on James Pratt (@jamiepratt) moodle-quiz_simulate project.
