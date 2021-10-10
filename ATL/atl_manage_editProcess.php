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

use Gibbon\Module\ATL\Domain\ATLColumnGateway;

include '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/ATL/'; 

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_edit.php') == false) {
    //Fail 0
    $URL .= 'atl_manage.php&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    $gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
    $atlColumnID = $_POST['atlColumnID'] ?? '';

    $atlColumnGateway = $container->get(ATLColumnGateway::class);
    $atlColumn = $atlColumnGateway->getByID($atlColumnID);
    //Proceed!
    //Check if school year specified
    if (empty($atlColumn) || $atlColumn['gibbonCourseClassID'] != $gibbonCourseClassID) {
        //Fail1
        $URL .= 'atl_manage.php&return=error1';
        header("Location: {$URL}");
        exit();
    } else {
        $URL .= "atl_manage_edit.php&atlColumnID=$atlColumnID&gibbonCourseClassID=$gibbonCourseClassID";

        //Validate Inputs
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $gibbonRubricID = $_POST['gibbonRubricID'] ?? $atlColumn['gibbonRubricID'];
        $completeDate = $_POST['completeDate'] ?? '';
        if ($completeDate == '') {
            $completeDate = null;
            $complete = 'N';
        } else {
            $completeDate = dateConvert($guid, $completeDate);
            $complete = 'Y';
        }
        $forStudents = $_POST['forStudents'] ?? $atlColumn['forStudents'];
        $gibbonPersonIDLastEdit = $session->get('gibbonPersonID');

        if (empty($name) || empty($description) || empty($forStudents)) {
            //Fail 3
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        } else {
            $data = [
                'name'                      => $name,
                'description'               => $description,
                'gibbonRubricID'            => $gibbonRubricID,
                'completeDate'              => $completeDate,
                'forStudents'               => $forStudents,
                'complete'                  => $complete,
                'gibbonPersonIDLastEdit'    => $gibbonPersonIDLastEdit
            ];

            if (!$atlColumnGateway->update($atlColumnID, $data)) {
                //Fail 6
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                //Success 0
                $URL .= '&return=success0';
                header("Location: {$URL}");
                exit();
            }
        }
    }
}
