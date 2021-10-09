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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\ATL\Domain\ATLEntryGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

function getATLRecord($gibbonPersonID)
{
    global $container, $session;
    $output = '';

    $atlEntryGateway = $container->get(ATLEntryGateway::class);
    $criteria = $atlEntryGateway->newQueryCriteria(true)
        ->sortBy('gibbonSchoolYear.sequenceNumber', 'DESC')
        ->sortBy('completeDate', 'DESC')
        ->sortBy(['courseName'])
        ->fromPOST();

    $atls = $atlEntryGateway->queryATLsByStudent($criteria, $gibbonPersonID);

    $gibbonSchoolYearFilter = array_reduce($atls->toArray(), function ($group, $atl) {
        $group['gibbonSchoolYearID:' . $atl['gibbonSchoolYearID']] = __('School Year') . ': ' . $atl['yearName'];
        return $group;
    }, []);

    $table = DataTable::createPaginated('atlView', $criteria);

    $table->addMetaData('filterOptions', $gibbonSchoolYearFilter);

    $table->addColumn('yearName', __('School Year'))
        ->sortable('gibbonSchoolYear.sequenceNumber');

    $table->addColumn('courseName', __('Course'));

    $table->addColumn('assessment', __('Assessment'))
        ->sortable('completeDate')
        ->description(__('Marked on'))
        ->format(function($atl) {
            $output = '';

            $output .= Format::tag($atl['ATLName'], '', $atl['ATLDescription']);
            $output .= '</br>';
            if (empty($atl['completeDate'])) {
                $output .= __('N/A');
            } else {
                $output .= Format::small(Format::dateReadable($atl['completeDate']));
            }
            return $output;
        });

    $table->addActionColumn()
        ->addParam('gibbonCourseClassID') 
        ->addParam('atlColumnID')
        ->addParam('gibbonRubricID')
        ->addParam('gibbonPersonID', $gibbonPersonID)
        ->format(function($atl, $actions) use ($session) {
            $actions->addAction('enterData', __('View Rubric'))
                ->addParam('type', 'effort')
                ->modalWindow(1100, 500)
                ->setURL('/modules/' . $session->get('module') . '/atl_view_rubric.php')
                ->setIcon('markbook');
        });


    $output .= $table->render($atls);

    return $output;
}

function sidebarExtra($gibbonCourseClassID, $mode = 'manage')
{
    $output = '';

    $output .= '<div class="column-no-break">';
    $output .= '<h2>';
    $output .= __('View Classes');
    $output .= '</h2>';

    $selectCount = 0;

    global $pdo, $session;

    $form = Form::create('classSelect', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/'.($mode == 'write'? 'atl_write.php' : 'atl_manage.php'));
    $form->setClass('smallIntBorder w-full');

    $row = $form->addRow();
        $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))
            ->selected($gibbonCourseClassID)
            ->placeholder()
            ->setClass('fullWidth');
        $row->addSubmit(__('Go'));

    $output .= $form->getOutput();
    $output .= '</div>';

    return $output;
}
