moodle-mod_tracker
==================

Ticketting tracker for Moodle used as user support or bug report

Component class : Moodle Activity Module

Version : 2.x (branch "master")

Version 2.3 : New branch MOODLE_23 for those additional adjustments

Original record
=======================

Used for tracking issues.
By Clifford Tham.
http://docs.moodle.org/en/Student_projects/Integrated_bug_tracker

REVIEWED TRACKER MODULE
=======================

By Valery Fremaux.
http://ateliers.moodlelab.fr/course/view.php?id=58

This module is a complete redraw of the former tracker module. 

It provides a complete ticket tracking activity with following features :

- Customizable ticket form submission
- Commentable ticket records
- Ticket assignation management
- Ticket priority
- Rich ticket listing using flexible table
- "My tickets" list as author
- "My assigned ticket" list
- Closed or solved tickets separate archive
- Multicriteria search engine
- Search query personal memo
- Watch management
- Notification service (parametric)
- Full backup/restore implementation
- Tracking activity reports

Release note : 2012101700
===========================
Fix backup restore inconsistancies
Add VALIDATED status

Release note : 2014010100
===========================
Adds a full preset system to choose major tracker usecase that setup state list, thanks message and role overrides for the module :
- Bug tracker
- User support
- Task Distribution
- Fully customizable states and thanks message

Adds a strict workflow control for internal roles

Cleans mod_form with advanced params.