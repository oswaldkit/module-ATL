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
use Gibbon\Module\ATL\Domain\ATLEntryGateway;

include '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/ATL/';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_write_data.php') == false) {
    //Fail 0
    $URL .= 'atl_manage.php&return=error0';
    header("Location: {$URL}");
} else {
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $atlColumnID = $_GET['atlColumnID'] ?? '';

    $atlColumnGateway = $container->get(ATLColumnGateway::class);
    $atlColumn = $atlColumnGateway->getByID($atlColumnID);

    if (empty($atlColumn)) {
        //Fail 2
        $URL .= 'atl_manage.php&return=error2';
        header("Location: {$URL}");
        exit();
    } else {
        $URL .= "atl_write_data.php&atlColumnID=$atlColumnID&gibbonCourseClassID=$gibbonCourseClassID";
        $name = $atlColumn['name'];
        $count = $_POST['count'];
        $partialFail = false;

        if ($atlColumn['forStudents'] == 'Y') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        }

        $atlEntryGateway = $container->get(ATLEntryGateway::class);

        for ($i = 1; $i <= $count; ++$i) {
            $gibbonPersonIDStudent = $_POST["$i-gibbonPersonID"];
            //Complete
            $completeValue = $_POST["complete$i"] ?? 'N';
            $gibbonPersonIDLastEdit = $session->get('gibbonPersonID');

            $data = [
                'atlColumnID' => $atlColumnID,
                'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                'complete' => $completeValue,
                'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit,
            ];

            $atlEntry = $atlEntryGateway->selectBy(['atlColumnID' => $atlColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent]);

            if ($atlEntry->isNotEmpty()) {
                $atlEntry = $atlEntry->fetch();
                $partialFail |= !$atlEntryGateway->update($atlEntry['atlEntryID'], $data);
            } else {
                $partialFail |= !$atlEntryGateway->insert($data);
            }
        }

        //Update column
        $completeDate = $_POST['completeDate'];
        if (empty($completeDate)) {
            $data = [
                'completeDate' => null,
                'complete' => 'N'
            ];
        } else {
            $data = [
                'completeDate' => dateConvert($guid, $completeDate),
                'complete' => 'Y'
            ];
        }

        $partialFail |= !$atlColumnGateway->update($atlColumnID, $data);

        //Return!
        if ($partialFail) {
            //Fail 3
            $URL .= '&return=error3';
        } else {
            //Success 0
            $URL .= '&return=success0';
        }
        header("Location: {$URL}");
        exit();
    }
}
