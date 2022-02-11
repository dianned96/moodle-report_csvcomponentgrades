moodle-report_componentgrades
=============================

Export_csvcomponentgrades is a plugin that exports grades from an assignment for the following grading methods, Rubric and Marking Guide, into a CSV file.

Once installed, as a user who has permission to grade the assignment, navigate to an assignment that uses a Rubric or a Marking Guide grading method. In the Assignment Administration menu, a new menu item will appear: "Export Marking Guide Grades (.csv)" or "Export Rubric Grades (.csv)" as appropriate; click it to download a CSV file containing that assignment's component grades for all students in the course. Note: the download is NOT an Excel spreadsheet. 

In the CSV file, the information exported is both the students’ information (first name, last name, username, and student ID) together with their respective grades. 

Note: When installing this plugin, a settings window, which is a non-standard post-installation step will appear. The default settings to show groups in the report is checked 'no' while the show student id in the report is checked 'yes'. These settings should remain as is, until further development of the feature. 

For developers, note, the sample.csv file is the sample file of what the data looks like while the sampleextended.csv file is actual data mapping the sample file. 

Further development of this plugin would be accessible in the following GitHub repository. https://github.com/dianned96/moodle-report_csvcomponentgrades 

Further documentation of this plugin would be accessible in the following GitHub link  -> https://github.com/dianned96/moodle-report_csvcomponentgrades/wiki 

Participation, reporting issues, bugs, making feature requests, or suggesting other types of improvements are encouraged. These can be reported in the following GitHub link -> https://github.com/dianned96/moodle-report_csvcomponentgrades/issues 

Install from the Moodle.org plugins database at https://moodle.org/plugins  
