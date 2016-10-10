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

function getATLRecord($guid, $connection2, $gibbonPersonID)
{
    $output = '';

    //Get school years in reverse order
    try {
        $dataYears = array('gibbonPersonID' => $gibbonPersonID);
        $sqlYears = "SELECT * FROM gibbonSchoolYear JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (status='Current' OR status='Past') AND gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC";
        $resultYears = $connection2->prepare($sqlYears);
        $resultYears->execute($dataYears);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultYears->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'There are no records to display.');
        $output .= '</div>';
    } else {
        $results = false;
        while ($rowYears = $resultYears->fetch()) {
            //Get and output ATLs
            try {
                $dataATL = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                $sqlATL = "SELECT atlColumn.*, atlEntry.*, gibbonCourse.name AS course, gibbonCourseClass.nameShort AS class, gibbonPerson.dateStart FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN atlColumn ON (atlColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN atlEntry ON (atlEntry.atlColumnID=atlColumn.atlColumnID) JOIN gibbonPerson ON (atlEntry.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND atlEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' AND gibbonCourseClass.reportable='Y' AND gibbonCourseClassPerson.reportable='Y' ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                $resultATL = $connection2->prepare($sqlATL);
                $resultATL->execute($dataATL);
            } catch (PDOException $e) {
                $output .= "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultATL->rowCount() > 0) {
                $results = true;
                $output .= '<h4>';
                $output .= $rowYears['name'];
                $output .= '</h4>';
                $output .= "<table cellspacing='0' style='width: 100%'>";
                $output .= "<tr class='head'>";
                $output .= "<th style='width: 350px'>";
                $output .= 'Assessment';
                $output .= '</th>';
                $output .= '</th>';
                $output .= "<th style='text-align: center'>";
                $output .= __($guid, 'Rubric');
                $output .= '</th>';
                $output .= '</tr>';

                $count = 0;
                while ($rowATL = $resultATL->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    $output .= "<tr class=$rowNum>";
                    $output .= '<td>';
                    $output .= "<span title='".htmlPrep($rowATL['description'])."'><b><u>".$rowATL['course'].'<br/>'.$rowATL['name'].'</u></b></span><br/>';
                    $output .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                    if ($rowATL['completeDate'] != '') {
                        $output .= 'Marked on '.dateConvertBack($guid, $rowATL['completeDate']).'<br/>';
                    } else {
                        $output .= 'Unmarked<br/>';
                    }
                    $output .= '</span><br/>';
                    $output .= '</td>';
                    if ($rowATL['gibbonRubricID'] == '') {
                        $output .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $output .= __($guid, 'N/A');
                        $output .= '</td>';
                    } else {
                        $output .= "<td style='text-align: center'>";
                        $output .= "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/ATL/atl_view_rubric.php&gibbonRubricID='.$rowATL['gibbonRubricID'].'&gibbonCourseClassID='.$rowATL['gibbonCourseClassID'].'&atlColumnID='.$rowATL['atlColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                        $output .= '</td>';
                    }

                    $output .= '</tr>';
                }

                $output .= '</table>';
            }
        }
        if ($results == false) {
            $output .= "<div class='error'>";
            $output .= __($guid, 'There are no records to display.');
            $output .= '</div>';
        }
    }

    return $output;
}

function sidebarExtra($guid, $connection2, $gibbonCourseClassID, $mode = 'manage', $highestAction = '')
{
    $output = '';

    $output .= '<h2>';
    $output .= __($guid, 'View Classes');
    $output .= '</h2>';

    $selectCount = 0;
    $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
    $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>";
    $output .= '<tr>';
    $output .= "<td style='width: 190px'>";
    if ($mode == 'write') {
        $output .= "<input name='q' id='q' type='hidden' value='/modules/ATL/atl_write.php'>";
    } else {
        $output .= "<input name='q' id='q' type='hidden' value='/modules/ATL/atl_manage.php'>";
    }
    $output .= "<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:161px'>";
    if ($mode == 'write' or ($mode == 'manage' and $highestAction == 'Manage ATLs_all')) { //Full listing
        $output .= "<option value=''></option>";
        try {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        } catch (PDOException $e) {
        }
        $output .= "<optgroup label='--".__($guid, 'My Classes')."--'>";
        while ($rowSelect = $resultSelect->fetch()) {
            $selected = '';
            if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
                $selected = 'selected';
                ++$selectCount;
            }
            $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
        }
        $output .= '</optgroup>';

        if ($mode == 'manage') {
            try {
                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            $output .= "<optgroup label='--".__($guid, 'All Classes')."--'>";
            while ($rowSelect = $resultSelect->fetch()) {
                $selected = '';
                if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
                    $selected = 'selected';
                    ++$selectCount;
                }
                $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
            }
            $output .= '</optgroup>';
        }
    } else {
        try {
            $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND gibbonDepartmentStaff.role='Coordinator' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        } catch (PDOException $e) {
        }
        $output .= "<optgroup label='--".__($guid, 'Departmental Classes')."--'>";
        while ($rowSelect = $resultSelect->fetch()) {
            $selected = '';
            if ($gibbonCourseClassID != '') {
                if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
                    $selected = 'selected';
                    ++$selectCount;
                }
            }
            $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
        }
        $output .= '</optgroup>';
    }
    $output .= '</select>';
    $output .= '</td>';
    $output .= "<td class='right'>";
    $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '</table>';
    $output .= '</form>';

    return $output;
}
