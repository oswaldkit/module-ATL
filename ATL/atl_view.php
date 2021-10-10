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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\FamilyAdultGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_view.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Register scripts available to the core, but not included by default
    $page->scripts->add('chart');

    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if ($highestAction == 'View ATLs_all') { //ALL STUDENTS
            $page->breadcrumbs->add(__('View All ATLs'));

            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

            $form = Form::create("filter", $session->get('absoluteURL').'/index.php', 'get', 'noIntBorder fullWidth standardForm');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->setTitle(__('Choose A Student'));

            $form->addHiddenValue('q', '/modules/ATL/atl_view.php');
            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Student'));
                $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), [])
                    ->selected($gibbonPersonID)
                    ->placeholder();

            $row = $form->addRow();
                $row->addSearchSubmit($session);

            echo $form->getOutput();

            if (!empty($gibbonPersonID)) {
                echo '<h3>';
                echo __('ATLs');
                echo '</h3>';

                //Check for access
                try {
                    $dataCheck = array('gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
                    $sqlCheck = "SELECT DISTINCT gibbonPerson.* FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full' AND (dateStart IS NULL OR dateStart<= :today) AND (dateEnd IS NULL  OR dateEnd>= :today)";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                }

                if ($resultCheck->rowCount() != 1) {
                    echo Format::alert(__('The selected record does not exist, or you do not have access to it.'));
                } else {
                    echo getATLRecord($gibbonPersonID);
                }
            }
        } elseif ($highestAction == 'View ATLs_myChildrens') { //MY CHILDREN
            $page->breadcrumbs->add(__('View My Childrens\'s ATLs'));

            //Test data access field for permission
            $familyAdultGateway = $container->get(FamilyAdultGateway::class);
            $adult = $familyAdultGateway->selectBy(['gibbonPersonID' => $session->get('gibbonPersonID'), 'childDataAccess' => 'Y']);

            if ($adult->isEmpty()) {
                echo Format::alert(__('Access denied.'));
            } else {
                //Get child list
                $adult = $adult->fetch();
                $gibbonPersonID = '';
                $options = [];

                try {
                    $dataChild = array('gibbonFamilyID' => $adult['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowChild = $resultChild->fetch()) {
                    $options[$rowChild['gibbonPersonID']]=Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                }

                if (empty($options)) {
                    echo Format::alert(__('Access denied.'));
                } elseif (count($options) == 1) {
                    $gibbonPersonID = key($options);
                } else {
                    echo '<h2>';
                    echo __('Choose Student');
                    echo '</h2>';

                    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

                    $form = Form::create("filter", $session->get('absoluteURL').'/index.php', 'get', 'noIntBorder fullWidth standardForm');
                    $form->setFactory(DatabaseFormFactory::create($pdo));

                    $form->addHiddenValue('q', '/modules/ATL/atl_view.php');

                    $row = $form->addRow();
                        $row->addLabel('gibbonPersonID', __('Child'))->description('Choose the child you are registering for.');
                        $row->addSelect('gibbonPersonID')
                            ->fromArray($options)
                            ->selected($gibbonPersonID);

                    $row = $form->addRow();
                        $row->addSearchSubmit($session);

                    echo $form->getOutput();
                }

                $settingGateway = $container->get(SettingGateway::class);
                $showParentAttainmentWarning = $settingGateway->getSettingByScope('Markbook', 'showParentAttainmentWarning');
                $showParentEffortWarning = $settingGateway->getSettingByScope('Markbook', 'showParentEffortWarning');

                if (!empty($gibbonPersonID) && !empty($options)) {
                    //Confirm access to this student
                    try {
                        $dataChild = array();
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=$gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=".$session->get('gibbonPersonID')." AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                    }
                    if ($resultChild->rowCount() < 1) {
                        echo Format::alert(__('The selected record does not exist, or you do not have access to it.'));
                    } else {
                        $rowChild = $resultChild->fetch();
                        echo getATLRecord($gibbonPersonID);
                    }
                }
            }
        } else { //My ATLS
            $page->breadcrumbs->add(__('View My ATLs'));

            echo '<h3>';
            echo __('ATLs');
            echo '</h3>';

            echo getATLRecord($session->get('gibbonPersonID'));
        }
    }
}
