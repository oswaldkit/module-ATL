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

use Gibbon\Services\Format;
use Gibbon\Module\Rubrics\Visualise;
use Gibbon\Domain\Rubrics\RubricGateway;
use Gibbon\Module\ATL\Domain\ATLColumnGateway;

//Module includes
require_once './modules/ATL/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo 'You do not have access to this action.';
    echo '</div>';
} else {
    // Register scripts available to the core, but not included by default
    $page->scripts->add('chart');

    require_once $session->get('absolutePath').'/modules/ATL/src/Domain/ATLColumnGateway.php';

    // Display the visualization of all ATLs
    $contextDBTable = 'atlColumn';
    $contextDBTableID = $gibbonPersonID;
    $contextDBTableIDField = 'atlColumnID';
    $contextDBTableGibbonRubricIDField = 'gibbonRubricID';
    $contextDBTableNameField = 'name';
    $contextDBTableDateField = 'completeDate';

    $rubricGateway = $container->get(RubricGateway::class);
    $studentRubricInfo = $container->get(ATLColumnGateway::class)->getATLRubricByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
    $gibbonRubricID = $studentRubricInfo['gibbonRubricID'] ?? '';
    $rubric = $rubricGateway->getByID($gibbonRubricID);

    if ($gibbonRubricID && $rubric) {
        // Get rows, columns and cells
        $rows = $rubricGateway->selectRowsByRubric($gibbonRubricID)->fetchAll();
        $columns = $rubricGateway->selectColumnsByRubric($gibbonRubricID)->fetchAll();
        $gradeScales = $rubricGateway->selectGradeScalesByRubric($gibbonRubricID)->fetchGroupedUnique();

        if (empty($rows) or empty($columns)) {
            $col->addAlert(__('The rubric is missing data and cannot be drawn.'));
        } else {
            $cells = [];
            $resultCells = $rubricGateway->selectCellsByRubric($gibbonRubricID);
            while ($rowCells = $resultCells->fetch()) {
                $cells[$rowCells['gibbonRubricRowID']][$rowCells['gibbonRubricColumnID']] = $rowCells;
            }

            // Get other uses of this rubric in this context, and store for use in visualisation
            $contexts = [];

            $dataContext = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sqlContext = "SELECT gibbonRubricEntry.*, $contextDBTable.*, gibbonRubricEntry.*, gibbonRubricCell.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class
                FROM gibbonRubricEntry
                JOIN $contextDBTable ON (gibbonRubricEntry.contextDBTableID=$contextDBTable.$contextDBTableIDField
                    AND gibbonRubricEntry.gibbonRubricID=$contextDBTable.$contextDBTableGibbonRubricIDField)
                JOIN gibbonRubricCell ON (gibbonRubricEntry.gibbonRubricCellID=gibbonRubricCell.gibbonRubricCellID)
                JOIN gibbonCourseClass ON ($contextDBTable.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE contextDBTable='$contextDBTable'
                AND gibbonRubricEntry.gibbonPersonID=:gibbonPersonID
                AND gibbonSchoolYearID=:gibbonSchoolYearID
                AND NOT $contextDBTableDateField IS NULL
                ORDER BY $contextDBTableDateField DESC";
            $resultContext = $pdo->select($sqlContext, $dataContext);

            if ($resultContext->rowCount() > 0) {
                while ($rowContext = $resultContext->fetch()) {
                    $context = $rowContext['course'].'.'.$rowContext['class'].' - '.$rowContext[$contextDBTableNameField].' ('.Format::date($rowContext[$contextDBTableDateField]).')';
                    $cells[$rowContext['gibbonRubricRowID']][$rowContext['gibbonRubricColumnID']]['context'][] = $context;

                    $contexts[] = [
                        'gibbonRubricEntry' => $rowContext['gibbonRubricEntry'],
                        'gibbonRubricID' => $rowContext['gibbonRubricID'],
                        'gibbonPersonID' => $rowContext['gibbonPersonID'],
                        'gibbonRubricCellID' => $rowContext['gibbonRubricCellID'],
                        'contextDBTable' => $rowContext['contextDBTable'],
                        'contextDBTableID' => $rowContext['contextDBTableID']
                    ];
                }
            }
            
        }

        if (!empty($contexts) && !empty($columns) && !empty($rows) && !empty($cells)) {
            require_once $session->get('absolutePath').'/modules/Rubrics/src/Visualise.php';
            $visualise = new Visualise($session->get('absoluteURL'), $container->get('page'), $gibbonPersonID.'All', $columns, $rows, $cells, $contexts);
            echo $visualise->renderVisualise();
        }
        
    }
    
    echo getATLRecord($guid, $connection2, $gibbonPersonID);
}
