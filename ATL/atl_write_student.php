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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add('Fill ATLs');
if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_write_student.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $page->scripts->add('chart');
    $atlColumnGateway = $container->get(ATLColumnGateway::class);
    $criteria = $atlColumnGateway->newQueryCriteria()
        ->sortBy('completeDate');

    $gibbonPersonID = $session->get('gibbonPersonID');
    $atlColumns = $atlColumnGateway->queryATLColumnsByStudent($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID, 'N');

    $table = DataTable::createPaginated('studentATLs', $criteria);

    $table->addColumn('course', __('Class'))
        ->format(Format::using('courseClassName', ['course', 'class']));
    
    $table->addColumn('name', __('ATL'));

    $table->addColumn('completeDate', __('Due Date'))
        ->format(Format::using('date', ['completeDate']));

    $table->addActionColumn()
        ->addParam('gibbonRubricID')
        ->addParam('gibbonCourseClassID')
        ->addParam('gibbonPersonID', $gibbonPersonID)
        ->addParam('atlColumnID')
        ->addParam('type', 'effort')
        ->format(function ($column, $actions) {
            $actions->addAction('data', __('Enter Data'))
                ->setURL('/modules/ATL/atl_write_rubric.php')
                ->setIcon('markbook')
                ->modalWindow(1100, 550);

            $actions->addAction('complete', __('Complete'))
                ->setURL('/modules/ATL/atl_write_student_complete.php')
                ->setIcon('iconTick')
                ->modalWindow();
        });

    echo $table->render($atlColumns);
}