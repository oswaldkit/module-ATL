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
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/cfa_write_data.php&cfaColumnID=$cfaColumnID&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/CFA/cfa_write_data.php') == false) {
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
                $data = array('cfaColumnID' => $cfaColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT * FROM cfaColumn WHERE cfaColumnID=:cfaColumnID AND gibbonCourseClassID=:gibbonCourseClassID';
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
                $attachmentCurrent = $row['attachment'];
                $name = $row['name' ];
                $count = $_POST['count'];
                $partialFail = false;
                $attainment = $row['attainment'];
                $gibbonScaleIDAttainment = $row['gibbonScaleIDAttainment'];
                $effort = $row['effort'];
                $gibbonScaleIDEffort = $row['gibbonScaleIDEffort'];
                $comment = $row['comment'];
                $uploadedResponse = $row['uploadedResponse'];
                $gibbonPlannerEntryID = null;
                if ($_POST['gibbonPlannerEntryID'] != '') {
                    $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
                }

                for ($i = 1;$i <= $count;++$i) {
                    $gibbonPersonIDStudent = $_POST["$i-gibbonPersonID"];
                    //Attainment
                    if ($attainment == 'N' or $gibbonScaleIDAttainment == '') {
                        $attainmentValue = null;
                        $attainmentDescriptor = null;
                        $attainmentConcern = null;
                    } else {
                        $attainmentValue = $_POST["$i-attainmentValue"];
                    }
                    //Effort
                    if ($effort == 'N' or $gibbonScaleIDEffort == '') {
                        $effortValue = null;
                        $effortDescriptor = null;
                        $effortConcern = null;
                    } else {
                        $effortValue = $_POST["$i-effortValue"];
                    }
                    //Comment
                    if ($comment != 'Y') {
                        $commentValue = null;
                    } else {
                        $commentValue = $_POST["comment$i"];
                    }
                    $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];
                    $wordpressCommentPushID = null;
                    $wordpressCommentPushAction = null;
                    if (isset($_POST["$i-wordpressCommentPush"])) {
                        $wordpressCommentPushID = substr($_POST["$i-wordpressCommentPush"], 0, strpos($_POST["$i-wordpressCommentPush"], '-'));
                        $wordpressCommentPushAction = substr($_POST["$i-wordpressCommentPush"], (strpos($_POST["$i-wordpressCommentPush"], '-') + 1));
                    }

                    //SET AND CALCULATE FOR ATTAINMENT
                    if ($attainment == 'Y' and $gibbonScaleIDAttainment != '') {
                        //Without personal warnings
                        $attainmentConcern = 'N';
                        $attainmentDescriptor = '';
                        if ($attainmentValue != '') {
                            $lowestAcceptableAttainment = $_POST['lowestAcceptableAttainment'];
                            $scaleAttainment = $_POST['scaleAttainment'];
                            try {
                                $dataScale = array('attainmentValue' => $attainmentValue, 'scaleAttainment' => $scaleAttainment);
                                $sqlScale = 'SELECT * FROM gibbonScaleGrade JOIN gibbonScale ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE value=:attainmentValue AND gibbonScaleGrade.gibbonScaleID=:scaleAttainment';
                                $resultScale = $connection2->prepare($sqlScale);
                                $resultScale->execute($dataScale);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            if ($resultScale->rowCount() != 1) {
                                $partialFail = true;
                            } else {
                                $rowScale = $resultScale->fetch();
                                $sequence = $rowScale['sequenceNumber'];
                                $attainmentDescriptor = $rowScale['descriptor'];
                            }

                            if ($lowestAcceptableAttainment != '' and $sequence != '' and $attainmentValue != '') {
                                if ($sequence > $lowestAcceptableAttainment) {
                                    $attainmentConcern = 'Y';
                                }
                            }
                        }
                    }

                    //SET AND CALCULATE FOR EFFORT
                    if ($effort == 'Y' and $gibbonScaleIDEffort != '') {
                        $effortConcern = 'N';
                        $effortDescriptor = '';
                        if ($effortValue != '') {
                            $lowestAcceptableEffort = $_POST['lowestAcceptableEffort'];
                            $scaleEffort = $_POST['scaleEffort'];
                            try {
                                $dataScale = array('effortValue' => $effortValue, 'scaleEffort' => $scaleEffort);
                                $sqlScale = 'SELECT * FROM gibbonScaleGrade JOIN gibbonScale ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE value=:effortValue AND gibbonScaleGrade.gibbonScaleID=:scaleEffort';
                                $resultScale = $connection2->prepare($sqlScale);
                                $resultScale->execute($dataScale);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            if ($resultScale->rowCount() != 1) {
                                $partialFail = true;
                            } else {
                                $rowScale = $resultScale->fetch();
                                $sequence = $rowScale['sequenceNumber'];
                                $effortDescriptor = $rowScale['descriptor'];
                            }

                            if ($lowestAcceptableEffort != '' and $sequence != '' and $effortValue != '') {
                                if ($sequence > $lowestAcceptableEffort) {
                                    $effortConcern = 'Y';
                                }
                            }
                        }
                    }

                    $time = time();
                    //Move attached file, if there is one
                    if ($uploadedResponse == 'Y') {
                        if (@$_FILES["response$i"]['tmp_name'] != '') {
                            //Check for folder in uploads based on today's date
                            $path = $_SESSION[$guid]['absolutePath'];
                            if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                                mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                            }
                            $unique = false;
                            $count2 = 0;
                            while ($unique == false and $count2 < 100) {
                                $suffix = randomPassword(16);
                                $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_Uploaded Response_$suffix".strrchr($_FILES["response$i"]['name'], '.');
                                if (!(file_exists($path.'/'.$attachment))) {
                                    $unique = true;
                                }
                                ++$count2;
                            }

                            if (!(move_uploaded_file($_FILES["response$i"]['tmp_name'], $path.'/'.$attachment))) {
                                $partialFail = true;
                            }
                        } else {
                            $attachment = null;
                            if (isset($_POST["response$i"])) {
                                $attachment = $_POST["response$i"];
                            }
                        }
                    } else {
                        $attachment = null;
                    }

                    $selectFail = false;
                    try {
                        $data = array('cfaColumnID' => $cfaColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                        $sql = 'SELECT * FROM cfaEntry WHERE cfaColumnID=:cfaColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                        $selectFail = true;
                    }
                    if (!($selectFail)) {
                        if ($result->rowCount() < 1) {
                            try {
                                $data = array('cfaColumnID' => $cfaColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'attainmentValue' => $attainmentValue, 'attainmentDescriptor' => $attainmentDescriptor, 'attainmentConcern' => $attainmentConcern, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'effortConcern' => $effortConcern, 'comment' => $commentValue, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit);
                                $sql = 'INSERT INTO cfaEntry SET cfaColumnID=:cfaColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, attainmentValue=:attainmentValue, attainmentDescriptor=:attainmentDescriptor, attainmentConcern=:attainmentConcern, effortValue=:effortValue, effortDescriptor=:effortDescriptor, effortConcern=:effortConcern, comment=:comment, response=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        } else {
                            $row = $result->fetch();
                            //Update
                            try {
                                $data = array('cfaColumnID' => $cfaColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'attainmentValue' => $attainmentValue, 'attainmentDescriptor' => $attainmentDescriptor, 'attainmentConcern' => $attainmentConcern, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'effortConcern' => $effortConcern, 'comment' => $commentValue, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'cfaEntryID' => $row['cfaEntryID']);
                                $sql = 'UPDATE cfaEntry SET cfaColumnID=:cfaColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, attainmentValue=:attainmentValue, attainmentDescriptor=:attainmentDescriptor, attainmentConcern=:attainmentConcern, effortValue=:effortValue, effortDescriptor=:effortDescriptor, effortConcern=:effortConcern, comment=:comment, response=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE cfaEntryID=:cfaEntryID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Update column
                $completeDate = $_POST['completeDate'];
                if ($completeDate == '') {
                    $completeDate = null;
                    $complete = 'N';
                } else {
                    $completeDate = dateConvert($guid, $completeDate);
                    $complete = 'Y';
                }
                try {
                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'completeDate' => $completeDate, 'complete' => $complete, 'cfaColumnID' => $cfaColumnID);
                    $sql = 'UPDATE cfaColumn SET gibbonPlannerEntryID=:gibbonPlannerEntryID, completeDate=:completeDate, complete=:complete WHERE cfaColumnID=:cfaColumnID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                //Return!
                if ($partialFail == true) {
                    //Fail 3
                    $URL .= '&return=error3';
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
