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

include '../../gibbon.php';

use Gibbon\Module\ATL\Domain\ATLColumnGateway;
use Gibbon\Module\ATL\Domain\ATLEntryGateway;

$URL = $session->get('absoluteURL').'/index.php?q=/modules/ATL/atl_write_student.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_write_student.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    $atlColumnGateway = $container->get(ATLColumnGateway::class);
    $atlEntryGateway = $container->get(ATLEntryGateway::class);

    $atlColumnID = $_POST['atlColumnID'] ?? '';
    $gibbonPersonID = $session->get('gibbonPersonID');
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    //Check if atl column is for student
    if ($atlColumnGateway->isForStudent($atlColumnID, $gibbonPersonID, $gibbonSchoolYearID)) {
        $data = [
            'atlColumnID' => $atlColumnID,
            'gibbonPersonIDStudent' => $gibbonPersonID,
            'complete' => 'Y',
            'gibbonPersonIDLastEdit' => $gibbonPersonID
        ];

        $atlEntry = $atlEntryGateway->selectBy(['gibbonPersonIDStudent' => $gibbonPersonID, 'atlColumnID' => $atlColumnID]);
        $atlEntry = $atlEntry->isNotEmpty() ? $atlEntry->fetch() : [];
        
        if (empty($atlEntry)) {
            if (!$atlEntryGateway->insert($data)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
        } else {
            if (!$atlEntryGateway->update($atlEntry['atlEntryID'], $data)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
        }

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit();
    } else {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit();
    }
}
