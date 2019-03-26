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

use Gibbon\Forms\Form;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        $atlColumnID = $_GET['atlColumnID'] ?? '';
        if ($gibbonCourseClassID == '' or $atlColumnID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                try {
                    $data2 = array('atlColumnID' => $atlColumnID);
                    $sql2 = 'SELECT * FROM atlColumn WHERE atlColumnID=:atlColumnID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $class = $result->fetch();
                    $values = $result2->fetch();

                    $page->breadcrumbs
                        ->add(__('Manage {courseClass} ATLs', ['courseClass' => $row['course'].'.'.$row['class']]), 'atl_manage.php', ['gibbonCourseClassID' => $gibbonCourseClassID])
                        ->add(__('Edit Column'));

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    $form = Form::create('ATL', $_SESSION[$guid]['absoluteURL'].'/modules/ATL/atl_manage_editProcess.php?atlColumnID='.$atlColumnID.'&gibbonCourseClassID='.$gibbonCourseClassID.'&address='.$_SESSION[$guid]['address']);
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $form->addRow()->addHeading(__('Basic Information'));

                    $row = $form->addRow();
                        $row->addLabel('className', __('Class'));
                        $row->addTextField('className')->readonly()->setValue(htmlPrep($class['course']).'.'.htmlPrep($class['class']));

                    $row = $form->addRow();
                        $row->addLabel('name', __('Name'));
                        $row->addTextField('name')->isRequired()->maxLength(20);

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextField('description')->isRequired()->maxLength(1000);

                    $form->addRow()->addHeading(__('Assessment'));

                    $data = array('gibbonYearGroupIDList' => $class['gibbonYearGroupIDList'], 'gibbonDepartmentID' => $class['gibbonDepartmentID'], 'rubrics' => __('Rubrics'));
                    $sql = "SELECT CONCAT(scope, ' ', :rubrics) as groupBy, gibbonRubricID as value, 
                            (CASE WHEN category <> '' THEN CONCAT(category, ' - ', gibbonRubric.name) ELSE gibbonRubric.name END) as name 
                            FROM gibbonRubric 
                            JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonRubric.gibbonYearGroupIDList))
                            WHERE gibbonRubric.active='Y' 
                            AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList) 
                            AND (scope='School' OR (scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID))
                            GROUP BY gibbonRubric.gibbonRubricID
                            ORDER BY scope, category, name";

                    $row = $form->addRow();
                        $row->addLabel('gibbonRubricID', __('Rubric'));
                        $row->addSelect('gibbonRubricID')->fromQuery($pdo, $sql, $data, 'groupBy')->placeholder();

                    $form->addRow()->addHeading(__('Access'));

                    $row = $form->addRow();
                        $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                        $row->addDate('completeDate');

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }

            //Print sidebar
            $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'manage', $highestAction);
        }
    }
}
