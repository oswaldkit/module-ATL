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
along with this rogram. If not, see <http://www.gnu.org/licenses/>.
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
    $atlColumnGateway = $container->get(ATLColumnGateway::class);

    if (!$atlColumnGateway->exists($atlColumnID)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
    } else {
        //Let's go!
        $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module').'/atl_manage_deleteProcess.php');
        $form->addHiddenValue('atlColumnID', $atlColumnID);
        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
        echo $form->getOutput();
    }
}
