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


$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/atl_manage_add.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_add.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=error5';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        $gibbonCourseClassIDMulti = null;
        if (isset($_POST['gibbonCourseClassIDMulti'])) {
            $gibbonCourseClassIDMulti = $_POST['gibbonCourseClassIDMulti'];
            $gibbonCourseClassIDMulti = array_unique($gibbonCourseClassIDMulti);
        }
        $name = $_POST['name'];
        $description = $_POST['description'];
        $gibbonRubricID = $_POST['gibbonRubricID'];
        $completeDate = $_POST['completeDate'];
        if ($completeDate == '') {
            $completeDate = null;
            $complete = 'N';
        } else {
            $completeDate = dateConvert($guid, $completeDate);
            $complete = 'Y';
        }
        $forStudents = $_POST['forStudents'] ?? '';
        $gibbonPersonIDCreator = $session->get('gibbonPersonID');
        $gibbonPersonIDLastEdit = $session->get('gibbonPersonID');

        //Lock markbook column table
        try {
            $sqlLock = 'LOCK TABLES atlColumn WRITE';
            $resultLock = $connection2->query($sqlLock);
        } catch (PDOException $e) {
            //Fail 2
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Get next groupingID
        try {
            $sqlGrouping = 'SELECT DISTINCT groupingID FROM atlColumn WHERE NOT groupingID IS NULL ORDER BY groupingID DESC';
            $resultGrouping = $connection2->query($sqlGrouping);
        } catch (PDOException $e) {
            //Fail 2
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $rowGrouping = $resultGrouping->fetch();
        if (is_null($rowGrouping['groupingID'])) {
            $groupingID = 1;
        } else {
            $groupingID = ($rowGrouping['groupingID'] + 1);
        }

        if (is_array($gibbonCourseClassIDMulti) == false or is_numeric($groupingID) == false or $groupingID < 1 or $name == '' or $description == '' or $forStudents == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit();
        } else {
            $partialFail = false;

            $atlColumnGateway = $container->get(ATLColumnGateway::class);

            $data = [
                'groupingID' => $groupingID,
                'name' => $name,
                'description' => $description,
                'gibbonRubricID' => $gibbonRubricID,
                'completeDate' => $completeDate,
                'forStudents' => $forStudents,
                'complete' => $complete,
                'gibbonPersonIDCreator' => $gibbonPersonIDCreator,
                'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit
            ];
            foreach ($gibbonCourseClassIDMulti as $gibbonCourseClassIDSingle) {
                //Write to database
                $data['gibbonCourseClassID'] = $gibbonCourseClassIDSingle;
                $partialFail |= empty($atlColumnGateway->insert($data));
            }

            //Unlock module table
            try {
                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
            }

            if ($partialFail) {
                $URL .= '&return=error6';
            } else {
                $URL .= '&return=success0';
            }
            header("Location: {$URL}");
            exit();
        }
    }
}
