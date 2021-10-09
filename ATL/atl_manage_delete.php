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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Module\ATL\Domain\ATLColumnGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_delete.php') == false) {
    //Acess denied
   $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $atlColumnID = $_GET['atlColumnID'] ?? '';
    if (empty($gibbonCourseClassID) || empty($atlColumnID)) {
        $page->addError(__('You have not specified one or more required parameters.'));;
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
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $atlColumnGateway = $container->get(ATLColumnGateway::class);
 
            if (!$atlColumnGateway->exists($atlColumnID)) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Let's go!
                $row = $result->fetch();

                $page->breadcrumbs
                    ->add(__('Manage {courseClass} ATLs', ['courseClass' => $row['course'].'.'.$row['class']]), 'atl_manage.php', ['gibbonCourseClassID' => $gibbonCourseClassID])
                    ->add(__('Delete Column'));

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module').'/atl_manage_deleteProcess.php?atlColumnID='.$atlColumnID);
                $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                echo $form->getOutput();
            }
        }

        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($gibbonCourseClassID, 'manage'));
    }
}
