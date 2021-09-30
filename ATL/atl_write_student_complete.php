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
use Gibbon\Module\ATL\Domain\ATLColumnGateway;
use Gibbon\Module\ATL\Domain\ATLEntryGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_write_student.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $atlColumnGateway = $container->get(ATLColumnGateway::class);
    $atlEntryGateway = $container->get(ATLEntryGateway::class);

    $atlColumnID = $_GET['atlColumnID'] ?? '';
    $gibbonPersonID = $session->get('gibbonPersonID');
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    //Check if atl column is for student
    if ($atlColumnGateway->isForStudent($atlColumnID, $gibbonPersonID, $gibbonSchoolYearID)) {
        //Check if student has already completed atl
        $atlEntry = $atlEntryGateway->selectBy(['gibbonPersonIDStudent' => $gibbonPersonID, 'atlColumnID' => $atlColumnID, 'complete' => 'Y']);
        if ($atlEntry->isNotEmpty()) {
            $page->addError(__('You have already completed this ATL.'));
        } else {
            $form = Form::create('ATL', $session->get('absoluteURL').'/modules/ATL/atl_write_student_completeProcess.php');
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('atlColumnID', $atlColumnID);

            $row = $form->addRow();
                $col = $row->addColumn();
                $col->addContent(__('Are you sure you want to mark this ATL as complete?'))->wrap('<strong>', '</strong>');
                $col->addContent(__('Once marked as complete, you will not be able to edit your ATL.'))
                    ->wrap('<span style="color: #cc0000"><i>', '</i></span>');

            $form->addRow()->addConfirmSubmit();

            echo $form->getOutput();
        }
    } else {
        $page->addError(__('Your request failed because you do not have access to this action.'));
    }
}