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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
try {
    $connection2 = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getMessage();
}

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$cfaColumnID = $_GET['cfaColumnID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/cfa_manage_edit.php&cfaColumnID=$cfaColumnID&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/CFA/cfa_manage_edit.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        //Fail 0
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        if (empty($_POST)) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Check if school year specified
            if ($cfaColumnID == '' or $gibbonCourseClassID == '') {
                //Fail1
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    if ($highestAction == 'Manage CFAs_all') { //Full manage
                        $data = array('cfaColumnID' => $cfaColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT * FROM cfaColumn WHERE cfaColumnID=:cfaColumnID AND gibbonCourseClassID=:gibbonCourseClassID';
                    } else {
                        $data = array('cfaColumnID' => $cfaColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT * FROM cfaColumn JOIN gibbonCourseClass ON (cfaColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE cfaColumnID=:cfaColumnID AND cfaColumn.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Coordinator'";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    if ($highestAction == 'Manage CFAs_all') { //Full manage
                        //Validate Inputs
                        $name = $_POST['name'];
                        $description = $_POST['description'];
                        //Sort out attainment
                        $attainment = $_POST['attainment'];
                        if ($attainment == 'N') {
                            $gibbonScaleIDAttainment = null;
                            $gibbonRubricIDAttainment = null;
                        } else {
                            if ($_POST['gibbonScaleIDAttainment'] == '') {
                                $gibbonScaleIDAttainment = null;
                            } else {
                                $gibbonScaleIDAttainment = $_POST['gibbonScaleIDAttainment'];
                            }
                            if ($_POST['gibbonRubricIDAttainment'] == '') {
                                $gibbonRubricIDAttainment = null;
                            } else {
                                $gibbonRubricIDAttainment = $_POST['gibbonRubricIDAttainment'];
                            }
                        }
                        //Sort out effort
                        $effort = $_POST['effort'];
                        if ($effort == 'N') {
                            $gibbonScaleIDEffort = null;
                            $gibbonRubricIDEffort = null;
                        } else {
                            if ($_POST['gibbonScaleIDEffort'] == '') {
                                $gibbonScaleIDEffort = null;
                            } else {
                                $gibbonScaleIDEffort = $_POST['gibbonScaleIDEffort'];
                            }
                            if ($_POST['gibbonRubricIDEffort'] == '') {
                                $gibbonRubricIDEffort = null;
                            } else {
                                $gibbonRubricIDEffort = $_POST['gibbonRubricIDEffort'];
                            }
                        }
                        $comment = $_POST['comment'];
                        $uploadedResponse = $_POST['uploadedResponse'];
                        $completeDate = $_POST['completeDate'];
                        if ($completeDate == '') {
                            $completeDate = null;
                            $complete = 'N';
                        } else {
                            $completeDate = dateConvert($guid, $completeDate);
                            $complete = 'Y';
                        }
                        $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];

                        $time = time();
                        //Move attached file, if there is one
                        if ($_FILES['file']['tmp_name'] != '') {
                            //Check for folder in uploads based on today's date
                            $path = $_SESSION[$guid]['absolutePath'];
                            if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                                mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                            }
                            $unique = false;
                            $count = 0;
                            while ($unique == false and $count < 100) {
                                $suffix = randomPassword(16);
                                $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_$suffix".strrchr($_FILES['file']['name'], '.');
                                if (!(file_exists($path.'/'.$attachment))) {
                                    $unique = true;
                                }
                                ++$count;
                            }

                            if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$attachment))) {
                                //Fail 5
                                $URL .= '&return=error5';
                                header("Location: {$URL}");
                            }
                        } else {
                            $attachment = $row['attachment'];
                        }

                        if ($name == '' or $description == '') {
                            //Fail 3
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        } else {
                            //Write to database
                            try {
                                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'name' => $name, 'description' => $description, 'attainment' => $attainment, 'gibbonScaleIDAttainment' => $gibbonScaleIDAttainment, 'effort' => $effort, 'gibbonScaleIDEffort' => $gibbonScaleIDEffort, 'gibbonRubricIDAttainment' => $gibbonRubricIDAttainment, 'gibbonRubricIDEffort' => $gibbonRubricIDEffort, 'comment' => $comment, 'uploadedResponse' => $uploadedResponse, 'completeDate' => $completeDate, 'complete' => $complete, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'cfaColumnID' => $cfaColumnID);
                                $sql = 'UPDATE cfaColumn SET gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, gibbonRubricIDEffort=:gibbonRubricIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, attachment=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE cfaColumnID=:cfaColumnID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }

                            //Success 0
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        }
                    } else {
                        //Validate Inputs
                        $description = $_POST['description'];
                        //Sort out attainment
                        if ($_POST['gibbonRubricIDAttainment'] == '') {
                            $gibbonRubricIDAttainment = null;
                        } else {
                            $gibbonRubricIDAttainment = $_POST['gibbonRubricIDAttainment'];
                        }
                        $completeDate = $_POST['completeDate'];
                        if ($completeDate == '') {
                            $completeDate = null;
                            $complete = 'N';
                        } else {
                            $completeDate = dateConvert($guid, $completeDate);
                            $complete = 'Y';
                        }
                        $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];
                        $groupingID = $row['groupingID'];
                        $time = time();
                        //Move attached file, if there is one
                        if ($_FILES['file']['tmp_name'] != '') {
                            //Check for folder in uploads based on today's date
                            $path = $_SESSION[$guid]['absolutePath'];
                            if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                                mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                            }
                            $unique = false;
                            $count = 0;
                            while ($unique == false and $count < 100) {
                                $suffix = randomPassword(16);
                                $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $row['name'])."_$suffix".strrchr($_FILES['file']['name'], '.');
                                if (!(file_exists($path.'/'.$attachment))) {
                                    $unique = true;
                                }
                                ++$count;
                            }

                            if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$attachment))) {
                                //Fail 5
                                $URL .= '&return=error5';
                                header("Location: {$URL}");
                            }
                        } else {
                            $attachment = $row['attachment'];
                        }

                        if ($description == '') {
                            //Fail 3
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        } else {
                            //Write to database
                            try {
                                $data = array('description' => $description, 'gibbonRubricIDAttainment' => $gibbonRubricIDAttainment, 'completeDate' => $completeDate, 'complete' => $complete, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'cfaColumnID' => $cfaColumnID);
                                $sql = 'UPDATE cfaColumn SET description=:description, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, completeDate=:completeDate, complete=:complete, attachment=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE cfaColumnID=:cfaColumnID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }

                            //ATTEMPT TO UPDATE LINKED COLUMNS
                            $partialFail = false;
                            if (isset($_POST['gibbonCourseClassID'])) {
                                if (is_array($_POST['gibbonCourseClassID'])) {
                                    $gibbonCourseClassIDs = $_POST['gibbonCourseClassID'];
                                    foreach ($gibbonCourseClassIDs as $gibbonCourseClassID2) {
                                        //Write to database
                                        try {
                                            $data = array('description' => $description, 'gibbonRubricIDAttainment' => $gibbonRubricIDAttainment, 'completeDate' => $completeDate, 'complete' => $complete, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'groupingID' => $groupingID, 'gibbonCourseClassID' => $gibbonCourseClassID2);
                                            $sql = 'UPDATE cfaColumn SET description=:description, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, completeDate=:completeDate, complete=:complete, attachment=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE groupingID=:groupingID AND gibbonCourseClassID=:gibbonCourseClassID';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                        }
                                    }
                                }
                            }

                            if ($partialFail == true) {
                                //Fail 6
                                $URL .= '&return=error6';
                                header("Location: {$URL}");
                            } else {
                                //Success 0
                                $URL .= '&return=success0';
                                header("Location: {$URL}");
                            }
                        }
                    }
                }
            }
        }
    }
}
