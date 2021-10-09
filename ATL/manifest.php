<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name           = 'ATL';
$description    = 'The ATL module allows schools to run a program of Approaches To Learning assessments, based on a rubric.';
$entryURL       = 'atl_write.php';
$type           = 'Additional';
$category       = 'Assess';
$version        = '1.6.00';
$author         = 'Ross Parker';
$url            = 'http://rossparker.org';

//Module tables
$moduleTables[] = "CREATE TABLE `atlColumn` (
    `atlColumnID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `gibbonCourseClassID` int(8) unsigned zerofill NOT NULL,
    `name` varchar(20) NOT NULL,
    `description` text NOT NULL,
    `gibbonRubricID` int(8) unsigned zerofill DEFAULT NULL,
    `complete` enum('N','Y') NOT NULL,
    `completeDate` date DEFAULT NULL,
    `forStudents` enum('Y','N') NOT NULL DEFAULT 'N',
    `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
    `gibbonPersonIDLastEdit` int(10) unsigned zerofill NOT NULL,
    PRIMARY KEY (`atlColumnID`),
    KEY `gibbonCourseClassID` (`gibbonCourseClassID`),
    KEY `gibbonRubricID` (`gibbonRubricID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `atlEntry` (
    `atlEntryID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `atlColumnID` int(10) unsigned zerofill NOT NULL,
    `gibbonPersonIDStudent` int(10) unsigned zerofill NOT NULL,
    `complete` enum('Y','N') NOT NULL DEFAULT 'N',
    `gibbonPersonIDLastEdit` int(10) unsigned zerofill NOT NULL,
    PRIMARY KEY (`atlEntryID`),
    KEY (`atlColumnID`),
    KEY (`gibbonPersonIDStudent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

//Action rows
$actionRows[] = [
    'name'                      => 'Manage ATLs_all',
    'precedence'                => '0',
    'category'                  => 'Manage & Assess',
    'description'               => 'Allows privileged users to create and manage ATL columns.',
    'URLList'                   => 'atl_manage.php, atl_manage_add.php, atl_manage_edit.php, atl_manage_delete.php',
    'entryURL'                  => 'atl_manage.php',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N'
];

$actionRows[] = [
    'name'                      => 'Write ATLs_myClasses',
    'precedence'                => '0',
    'category'                  => 'Manage & Assess',
    'description'               => 'Allows teachers to enter ATL assessment data to columns in their classes.',
    'URLList'                   => 'atl_write.php, atl_write_data.php',
    'entryURL'                  => 'atl_write.php',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N'
];

$actionRows[] = [
    'name'                      => 'Write ATLs_all',
    'precedence'                => '1',
    'category'                  => 'Manage & Assess',
    'description'               => 'Allows privileged users to enter ATL assessment data to columns in all classes.',
    'URLList'                   => 'atl_write.php, atl_write_data.php',
    'entryURL'                  => 'atl_write.php',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N'
];

$actionRows[] = [
    'name'                      => 'View ATLs_mine',
    'precedence'                => '0',
    'category'                  => 'View',
    'description'               => 'Allows students to view their own ATL results.',
    'URLList'                   => 'atl_view.php',
    'entryURL'                  => 'atl_view.php',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N'
];

$actionRows[] = [
    'name'                      => 'View ATLs_myChildrens',
    'precedence'                => '1',
    'category'                  => 'View',
    'description'               => "Allows parents to view their childrens' ATL results.",
    'URLList'                   => 'atl_view.php',
    'entryURL'                  => 'atl_view.php',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'Y',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'N'
];

$actionRows[] = [
    'name'                      => 'View ATLs_all',
    'precedence'                => '2',
    'category'                  => 'View',
    'description'               => 'Allows staff to see ATL results for all children.',
    'URLList'                   => 'atl_view.php',
    'entryURL'                  => 'atl_view.php',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N'
];

$actionRows[] = [
    'name'                      => 'Fill ATLs',
    'precedence'                => '0',
    'category'                  => 'View',
    'description'               => 'Allows students to enter ATL assessment data to columns in their classes.',
    'URLList'                   => 'atl_write_student.php',
    'entryURL'                  => 'atl_write_student.php',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N'
];

//Hooks
$array = [
  'sourceModuleName'    =>  'ATL',
  'sourceModuleAction'  =>  'View ATLs_all',
  'sourceModuleInclude' =>  'hook_studentProfile_atlView.php'
];
$hooks[] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'ATL', 'Student Profile', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

$array = [
  'sourceModuleName'    =>  'ATL',
  'sourceModuleAction'  =>  'View ATLs_myChildrens',
  'sourceModuleInclude' =>  'hook_parentalDashboard_atlView.php'
];
$hooks[] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'ATL', 'Parental Dashboard', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

$array = [
  'sourceModuleName'    =>  'ATL'
  'sourceModuleAction'  =>  'View ATLs_mine',
  'sourceModuleInclude' =>  'hook_studentDashboard_atlView.php'
];
$hooks[] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'ATL', 'Student Dashboard', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

