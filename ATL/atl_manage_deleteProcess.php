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

$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$atlColumnID = $_POST['atlColumnID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/ATL/atl_manage.php&gibbonCourseClassID=' . $gibbonCourseClassID;

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_delete.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    //Proceed!
    $atlColumnGateway = $container->get(ATLColumnGateway::class);

    if (!$atlColumnGateway->exists($atlColumnID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit();
    } else {
        //Write to database
        if (!$atlColumnGateway->delete($atlColumnID)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $atlEntryGateway = $container->get(ATLEntryGateway::class);
        if (!$atlEntryGateway->deleteWhere(['atlColumnID' => $atlColumnID])) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
            exit();
        }

        //Success 0
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit();
    }
}
