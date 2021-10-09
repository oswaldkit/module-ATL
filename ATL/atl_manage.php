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

use Gibbon\Services\Format;
use Gibbon\Module\ATL\Domain\ATLColumnGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get class variable
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    if (empty($gibbonCourseClassID)) {
        try {
            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            $gibbonCourseClassID = $row['gibbonCourseClassID'];
        } 
    }

    echo '<h1>';
    echo __('Manage ATLs');
    echo '</h1>';
    
    $courseGateway = $container->get(CourseGateway::class);
    $class = $courseGateway->getCourseClassByID($gibbonCourseClassID);
    if (empty($class)) {
        $page->breadcrumbs->add(__('Manage ATLs'));
        echo Format::alert(__('Use the class listing on the right to choose a ATL to edit.'), 'warning');
    } else {
        $page->breadcrumbs->add(__('Manage {courseClass} ATLs', ['courseClass' => $class['courseName'].'.'.$class['name']]));

        //Get teacher list
        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, gibbonCourseClassPerson.reportable FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE (role='Teacher' OR role='Assistant') AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() > 0) {
            echo '<h3>';
            echo __('Teachers');
            echo '</h3>';
            echo '<ul>';
            while ($row = $result->fetch()) {
                if ($row['reportable'] != 'Y') continue;

                echo '<li>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</li>';
            }
            echo '</ul>';
        }
        //TABLE
        $atlColumnGateway = $container->get(ATLColumnGateway::class);
        $atlColumnData = $atlColumnGateway->selectBy(['gibbonCourseClassID' => $gibbonCourseClassID])->fetchAll();
        
        $table = DataTable::create('atlColumns');
        $table->setTitle('ATL Columns');

        $table->addHeaderAction('add', __('Add Multiple Columns'))
            ->displayLabel()
            ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
            ->setURL('/modules/ATL/atl_manage_add.php')
            ->setIcon('page_new_multi');
        
        $table->addColumn('name', __('Name'));
        $table->addColumn('completeDate', __('Date Complete'));
        $table->addColumn('forStudents', __('For Students?'))
            ->format(Format::using('yesNo', ['forStudents']));
        $table->addActionColumn()
            ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
            ->addParam('atlColumnID')
            ->format(function ($row, $actions) use ($session) {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/' . $session->get('module') . '/atl_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/' . $session->get('module') . '/atl_manage_delete.php');
                    
                if ($row['forStudents'] == 'N') {
                    $actions->addAction('enterData', __('Enter Data'))
                            ->setURL('/modules/' . $session->get('module') . '/atl_write_data.php')
                            ->setIcon('markbook');
                }

            });
        
        echo $table->render($atlColumnData);
    }

    //Print sidebar
    $session->set('sidebarExtra', sidebarExtra($gibbonCourseClassID, 'manage'));
}
