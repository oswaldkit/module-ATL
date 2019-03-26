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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

//USAGE
//Ideally this script should be run shortly after midnight, to alert users to columns that have just gone live

require getcwd().'/../../../gibbon.php';

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) { if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}


//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') {
    echo __('This script cannot be run from a browser, only via CLI.')."\n\n";
} else {
    //SCAN THROUGH ALL ATLS GOING LIVE TODAY
    try {
        $data = array('completeDate' => date('Y-m-d'));
        $sql = 'SELECT atlColumn.*, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course FROM atlColumn JOIN gibbonCourseClass ON (atlColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE completeDate=:completeDate';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        try {
            $dataPerson = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
            $sqlPerson = "SELECT * FROM gibbonCourseClassPerson WHERE (role='Teacher' OR role='Student') AND gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.reportable='Y'";
            $resultPerson = $connection2->prepare($sqlPerson);
            $resultPerson->execute($dataPerson);
        } catch (PDOException $e) {
        }

        while ($rowPerson = $resultPerson->fetch()) {
            if ($rowPerson['role'] == 'Teacher') {
                $notificationText = sprintf(__('Your ATL column for class %1$s has gone live today.'), $row['course'].'.'.$row['class']);
                setNotification($connection2, $guid, $rowPerson['gibbonPersonID'], $notificationText, 'ATL', '/index.php?q=/modules/ATL/atl_write.php&gibbonCourseClassID='.$row['gibbonCourseClassID']);
            } else {
                $notificationText = sprintf(__('You have new ATL assessment feedback for class %1$s.'), $row['course'].'.'.$row['class']);
                setNotification($connection2, $guid, $rowPerson['gibbonPersonID'], $notificationText, 'ATL', '/index.php?q=/modules/ATL/atl_view.php');
            }
        }
    }
}
