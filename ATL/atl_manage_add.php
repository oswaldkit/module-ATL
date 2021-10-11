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

use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_add.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    if ($gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        //TODO: Implement this when the function returns reportable, or has reportable flag
        //$courseGateway = $container->get(CourseGateway::class);
        //$class = $courseGateway->getCourseClassByID($gibbonCourseClassID);
        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $page->addError(__('A database error has occured.'));
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $class = $result->fetch();

            $page->breadcrumbs
              ->add(__('Manage {courseClass} ATLs', ['courseClass' => Format::courseClassName($class['course'], $class['class'])]), 'atl_manage.php', ['gibbonCourseClassID' => $gibbonCourseClassID])
              ->add(__('Add Multiple Columns'));

            $form = Form::create('ATL', $session->get('absoluteURL').'/modules/ATL/atl_manage_addProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);

            $form->addRow()->addHeading(__('Basic Information'));

            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sql = "SELECT gibbonYearGroup.name as groupBy, gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonYearGroup ON (gibbonCourse.gibbonYearGroupIDList LIKE concat( '%', gibbonYearGroup.gibbonYearGroupID, '%' )) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY gibbonYearGroup.sequenceNumber, name";

            $row = $form->addRow();
                $col = $row->addColumn();
                $col->addLabel('gibbonCourseClassIDMulti', __('Class'));
                $multiSelect = $col->addMultiSelect('gibbonCourseClassIDMulti')
                    ->isRequired();

                $multiSelect
                    ->source()
                    ->fromQuery($pdo, $sql, $data, 'groupBy')
                    ->selected($gibbonCourseClassID);

                //TODO: This should probably be core functionality
                $multiSelect->destination()->fromArray(array_fill_keys(array_keys($multiSelect->source()->getOptions()), []));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired()->maxLength(20);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextField('description')->isRequired()->maxLength(1000);

            $form->addRow()->addHeading(__('Assessment'));

            $row = $form->addRow();
                $row->addLabel('gibbonRubricID', __('Rubric'));
                $rubrics = $row->addSelectRubric('gibbonRubricID', $class['gibbonYearGroupIDList'], $class['gibbonDepartmentID']);

                // Look for and select an Approach to Learning rubric
                $rubrics->selected(array_reduce($rubrics->getOptions(), function ($result, $items) {
                    foreach ($items as $key => $value) {
                        $result = (stripos($value, 'Approach to Learning') === false) ? $result : $key;
                    }
                    return $result;
                }, false));

            $form->addRow()->addHeading(__('Access'));

            $row = $form->addRow();
                $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'))->append('<br/>'.__('3. This will act as a due date if for students.'));
                $row->addDate('completeDate');

            $row = $form->addRow();
                $row->addLabel('forStudents', __('For Students?'))->description(__('Is this column meant to be filled out by students?'));
                $row->addYesNo('forStudents')
                    ->selected('N')
                    ->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
    //Print sidebar
    $session->set('sidebarExtra', sidebarExtra($gibbonCourseClassID, 'manage'));
}
