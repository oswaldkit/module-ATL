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

use Gibbon\Services\Format;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        } else {
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonCourseClassID = $row['gibbonCourseClassID'];
            }
        }
        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo 'Manage ATL';
            echo '</h1>';
            echo "<div class='warning'>";
            echo __('Use the class listing on the right to choose a ATL to edit.');
            echo '</div>';
        } else {
            //Check existence of and access to this class.
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __('Manage ATL');
                echo '</h1>';
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                $page->breadcrumbs->add(__('Manage {courseClass} ATLs', ['courseClass' => $row['course'].'.'.$row['class']]));

                if (isset($_GET['deleteReturn'])) {
                    $deleteReturn = $_GET['deleteReturn'];
                } else {
                    $deleteReturn = '';
                }
                $deleteReturnMessage = '';
                $class = 'error';
                if (!($deleteReturn == '')) {
                    if ($deleteReturn == 'success0') {
                        $deleteReturnMessage = __('Your request was completed successfully.');
                        $class = 'success';
                    }
                    echo "<div class='$class'>";
                    echo $deleteReturnMessage;
                    echo '</div>';
                }

                //Add multiple columns
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/atl_manage_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Add Multiple Columns')."<img style='margin-left: 5px' title='".__('Add Multiple Columns')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
                echo '</div>';

                //Get teacher list
                $teaching = false;
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() > 0) {
                    echo '<h3>';
                    echo __('Teachers');
                    echo '</h3>';
                    echo '<ul>';
                    while ($row = $result->fetch()) {
                        echo '<li>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</li>';
                        if ($row['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                            $teaching = true;
                        }
                    }
                    echo '</ul>';
                }

                //Print mark
                echo '<h3>';
                echo __('ATL Columns');
                echo '</h3>';

                //Set pagination variable
                $page = 1;
                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                }
                if ((!is_numeric($page)) or $page < 1) {
                    $page = 1;
                }

                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT * FROM atlColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY completeDate DESC, name';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __('There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __('Name');
                    echo '</th>';
                    echo '<th>';
                    echo __('Date<br/>Complete');
                    echo '</th>';
                    echo '<th>';
                    echo __('Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($row = $result->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo '<b>'.$row['name'].'</b><br/>';
                        echo '</td>';
                        echo '<td>';
                        if ($row['complete'] == 'Y') {
                            echo dateConvertBack($guid, $row['completeDate']);
                        }
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/atl_manage_edit.php&gibbonCourseClassID=$gibbonCourseClassID&atlColumnID=".$row['atlColumnID']."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module']."/atl_manage_delete.php&gibbonCourseClassID=$gibbonCourseClassID&atlColumnID=".$row['atlColumnID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/atl_write_data.php&gibbonCourseClassID=$gibbonCourseClassID&atlColumnID=".$row['atlColumnID']."'><img title='".__('Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                        echo '</td>';
                        echo '</tr>';

                        ++$count;
                    }
                    echo '</table>';
                }
            }
        }
    }

    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'manage', $highestAction);
}
