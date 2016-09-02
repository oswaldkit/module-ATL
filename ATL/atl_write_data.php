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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Get alternative header names
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

echo "<script type='text/javascript'>";
    echo '$(document).ready(function(){';
        echo "autosize($('textarea'));";
    echo '});';
echo '</script>';

if (isActionAccessible($guid, $connection2, '/modules/CFA/cfa_write_data.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $cfaColumnID = $_GET['cfaColumnID'];
        if ($gibbonCourseClassID == '' or $cfaColumnID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Write CFAs_all') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClass.reportable='Y' ";
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.reportable='Y' ";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                try {
                    $data2 = array('cfaColumnID' => $cfaColumnID);
                    $sql2 = 'SELECT * FROM cfaColumn WHERE cfaColumnID=:cfaColumnID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo 'The selected column does not exist, or you do not have access to it.';
                    echo '</div>';
                } else {
                    //Let's go!
                    $row = $result->fetch();
                    $row2 = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/cfa_write.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'Write').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'CFAs')."</a> > </div><div class='trailEnd'>".__($guid, 'Enter CFA Results').'</div>';
                    echo '</div>';

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    $columns = 1;

                    //Get list of acceptable file extensions
                    try {
                        $dataExt = array();
                        $sqlExt = 'SELECT * FROM gibbonFileExtension';
                        $resultExt = $connection2->prepare($sqlExt);
                        $resultExt->execute($dataExt);
                    } catch (PDOException $e) {
                    }
                    $ext = '';
                    while ($rowExt = $resultExt->fetch()) {
                        $ext = $ext."'.".$rowExt['extension']."',";
                    }

                    for ($i = 0;$i < $columns;++$i) {
                        //Column count
                        $span = 2;
                        if ($row2['attainment'] == 'Y') {
                            ++$span;
                        }
                        if ($row2['effort'] == 'Y') {
                            ++$span;
                        }
                        if ($row2['comment'] == 'Y' or $row2['uploadedResponse'] == 'Y') {
                            ++$span;
                        }
                        if ($span == 2) {
                            ++$span;
                        }
                    }

                    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/cfa_write_dataProcess.php?gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=$cfaColumnID&address=".$_SESSION[$guid]['address']."' enctype='multipart/form-data'>";
                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";

                    $header = '';
                    $header .= "<tr class='break'>";
                    $header .= '<td colspan='.($span).'>';
                    $header .= '<h3>Assessment Data</h3>';
                    $header .= '</td>';
                    $header .= '</tr>';
                    $header .= "<tr class='head'>";
                    $header .= '<th rowspan=2>';
                    $header .= __($guid, 'Student');
                    $header .= '</th>';

                    $columnID = array();
                    $attainmentID = array();
                    $effortID = array();
                    $submission = false;

                    for ($i = 0;$i < $columns;++$i) {
                        $columnID[$i] = $row2['cfaColumnID'];
                        $attainmentID[$i] = $row2['gibbonScaleIDAttainment'];
                        $effortID[$i] = $row2['gibbonScaleIDEffort'];
                        $gibbonRubricIDAttainment[$i] = null;
                        if (isset($row['gibbonRubricIDAttainment'])) {
                            $gibbonRubricIDAttainment[$i] = $row['gibbonRubricIDAttainment'];
                        }
                        $gibbonRubricIDEffort[$i] = null;
                        if (isset($row['gibbonRubricIDEffort'])) {
                            $gibbonRubricIDEffort[$i] = $row['gibbonRubricIDEffort'];
                        }

						//WORK OUT IF THERE IS SUBMISSION
						if ($row2['gibbonPlannerEntryID'] != null) {
							try {
								$dataSub = array('gibbonPlannerEntryID' => $row2['gibbonPlannerEntryID']);
								$sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
								$resultSub = $connection2->prepare($sqlSub);
								$resultSub->execute($dataSub);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}

							if ($resultSub->rowCount() == 1) {
								$submission = true;
								$rowSub = $resultSub->fetch();
								$homeworkDueDateTime = $rowSub['homeworkDueDateTime'];
								$lessonDate[$i] = $rowSub['date'];
							}
						}

                        $header .= "<th style='text-align: center' colspan=$span-2>";
                        $header .= "<span title='".htmlPrep($row2['description'])."'>".$row2['name'].'<br/>';
                        $header .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                        if ($row2['completeDate'] != '') {
                            $header .= __($guid, 'Marked on').' '.dateConvertBack($guid, $row2['completeDate']).'<br/>';
                        } else {
                            $header .= __($guid, 'Unmarked').'<br/>';
                        }
                        if ($row2['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$row2['attachment'])) {
                            $header .= "<a title='".__($guid, 'Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row2['attachment']."'>".__($guid, 'More info').'</a>';
                        }
                        $header .= '</span><br/>';
                        $header .= '</th>';
                    }
                    $header .= '</tr>';

                    $header .= "<tr class='head'>";
                    for ($i = 0;$i < $columns;++$i) {
                        if ($submission == true) {
                            $header .= "<th style='text-align: center; max-width: 30px'>";
                            $header .= "<span title='".__($guid, 'Submitted Work')."'>".__($guid, 'Sub').'</span>';
                            $header .= '</th>';
                        }
                        if ($row2['attainment'] == 'Y') {
                            $header .= "<th style='text-align: center; width: 30px'>";
                            $scale = '';
                            if ($attainmentID[$i] != '') {
                                try {
                                    $dataScale = array('gibbonScaleID' => $attainmentID[$i]);
                                    $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                    $resultScale = $connection2->prepare($sqlScale);
                                    $resultScale->execute($dataScale);
                                } catch (PDOException $e) {
                                }
                                if ($resultScale->rowCount() == 1) {
                                    $rowScale = $resultScale->fetch();
                                    $scale = ' - '.$rowScale['name'];
                                    if ($rowScale['usage'] != '') {
                                        $scale = $scale.': '.$rowScale['usage'];
                                    }
                                }
                                @$gibbonScaleIDAttainment = $rowScale['gibbonScaleID'];
                                $header .= "<input name='scaleAttainment' id='scaleAttainment' value='".$attainmentID[$i]."' type='hidden'>";
                                $header .= "<input name='lowestAcceptableAttainment' id='lowestAcceptableAttainment' value='".@$rowScale['lowestAcceptable']."' type='hidden'>";
                            }
                            if ($attainmentAlternativeName != '' and $attainmentAlternativeNameAbrev != '') {
                                $header .= "<span title='".$attainmentAlternativeName.htmlPrep($scale)."'>".$attainmentAlternativeNameAbrev.'</span>';
                            } else {
                                $header .= "<span title='".__($guid, 'Attainment').htmlPrep($scale)."'>".__($guid, 'Att').'</span>';
                            }
                            $header .= '</th>';
                        }
                        if ($row2['effort'] == 'Y') {
                            $header .= "<th style='text-align: center; width: 30px'>";
                            $scale = '';
                            if ($effortID[$i] != '') {
                                try {
                                    $dataScale = array('gibbonScaleID' => $effortID[$i]);
                                    $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                    $resultScale = $connection2->prepare($sqlScale);
                                    $resultScale->execute($dataScale);
                                } catch (PDOException $e) {
                                }
                                $scale = '';
                                if ($resultScale->rowCount() == 1) {
                                    $rowScale = $resultScale->fetch();
                                    $scale = ' - '.$rowScale['name'];
                                    if ($rowScale['usage'] != '') {
                                        $scale = $scale.': '.$rowScale['usage'];
                                    }
                                }
                                $gibbonScaleIDEffort = $rowScale['gibbonScaleID'];
                                $header .= "<input name='scaleEffort' id='scaleEffort' value='".$effortID[$i]."' type='hidden'>";
                                $header .= "<input name='lowestAcceptableEffort' id='lowestAcceptableEffort' value='".$rowScale['lowestAcceptable']."' type='hidden'>";
                            }
                            if ($effortAlternativeName != '' and $effortAlternativeNameAbrev != '') {
                                $header .= "<span title='".$effortAlternativeName.htmlPrep($scale)."'>".$effortAlternativeNameAbrev.'</span>';
                            } else {
                                $header .= "<span title='".__($guid, 'Effort').htmlPrep($scale)."'>".__($guid, 'Eff').'</span>';
                            }
                            $header .= '</th>';
                        }
                        if ($row2['comment'] == 'Y' or $row2['uploadedResponse'] == 'Y') {
                            $header .= "<th style='text-align: center; width: 80'>";
                            $header .= "<span title='".__($guid, 'Comment')."'>".__($guid, 'Com').'</span>';
                            $header .= '</th>';
                        }
                    }
                    $header .= '</tr>';

                    ?>
					<tr class='break'>
						<?php
						echo '<td colspan='.($span).'>';
                    		?>
							<h3>Settings</h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Submitted Homework ') ?></b><br/>
							<?php $row2['gibbonCourseClassID'] ?>
						</td>
						<td class="right"  colspan="<?php echo $span - 1 ?>">
							<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" style="width: 332px">
								<?php
								try {
									$dataSelect = array('gibbonCourseClassID' => $row2['gibbonCourseClassID']);
									$sqlSelect = "SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND homeworkSubmission='Y' ORDER BY date DESC, timeStart DESC, name";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								echo "<option value=''></option>";
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['gibbonPlannerEntryID'] == $row2['gibbonPlannerEntryID']) {
										$selected = 'selected ';
									}
									echo "<option $selected class='".$rowSelect['gibbonUnitID'].'-'.$rowSelect['gibbonHookID']."' value='".$rowSelect['gibbonPlannerEntryID']."'>".htmlPrep($rowSelect['name']).'</option>';
								}
								?>
							</select>
						</td>
					</tr>

					<?php
					echo $header;
                    $count = 0;
                    $rowNum = 'odd';
                    try {
                        $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
                        $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonCourseClassPerson.reportable='Y'  ORDER BY surname, preferredName";
                        $resultStudents = $connection2->prepare($sqlStudents);
                        $resultStudents->execute($dataStudents);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultStudents->rowCount() < 1) {
                        echo '<tr>';
                        echo '<td colspan='.($columns + 1).'>';
                        echo '<i>'.__($guid, 'There are no records to display.').'</i>';
                        echo '</td>';
                        echo '</tr>';
                    } else {
                        while ($rowStudents = $resultStudents->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

							//COLOR ROW BY STATUS!
							echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo "<div style='padding: 2px 0px'>".($count).") <b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID'].'&subpage=Markbook#'.$gibbonCourseClassID."'>".formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true).'</a><br/></div>';
                            echo '</td>';

                            for ($i = 0;$i < $columns;++$i) {
                                $row = $result->fetch();

                                try {
                                    $dataEntry = array('cfaColumnID' => $columnID[($i)], 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                                    $sqlEntry = 'SELECT * FROM cfaEntry WHERE cfaColumnID=:cfaColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                                    $resultEntry = $connection2->prepare($sqlEntry);
                                    $resultEntry->execute($dataEntry);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                $rowEntry = $resultEntry->fetch();
                                if ($submission == true) {
                                    echo "<td style='text-align: left ; width: 40px'>";
                                    try {
                                        $dataWork = array('gibbonPlannerEntryID' => $row2['gibbonPlannerEntryID'], 'gibbonPersonID' => $rowEntry['gibbonPersonIDStudent']);
                                        $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                        $resultWork = $connection2->prepare($sqlWork);
                                        $resultWork->execute($dataWork);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($resultWork->rowCount() > 0) {
                                        $rowWork = $resultWork->fetch();

                                        if ($rowWork['status'] == 'Exemption') {
                                            $linkText = __($guid, 'Exe');
                                        } elseif ($rowWork['version'] == 'Final') {
                                            $linkText = __($guid, 'Fin');
                                        } else {
                                            $linkText = __($guid, 'Dra').$rowWork['count'];
                                        }

                                        $style = '';
                                        $status = __($guid, 'On Time');
                                        if ($rowWork['status'] == 'Exemption') {
                                            $status = __($guid, 'Exemption');
                                        } elseif ($rowWork['status'] == 'Late') {
                                            $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                                            $status = __($guid, 'Late');
                                        }

                                        if ($rowWork['type'] == 'File') {
                                            echo "<span title='".$rowWork['version'].". $status. ".__($guid, 'Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__($guid, 'on').' '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."' $style><a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                                        } elseif ($rowWork['type'] == 'Link') {
                                            echo "<span title='".$rowWork['version'].". $status. ".__($guid, 'Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__($guid, 'on').' '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                        } else {
                                            echo "<span title='$status. ".__($guid, 'Recorded at').' '.substr($rowWork['timestamp'], 11, 5).' '.__($guid, 'on').' '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."' $style>$linkText</span>";
                                        }
                                    } else {
                                        if (date('Y-m-d H:i:s') < $homeworkDueDateTime) {
                                            echo "<span title='".__($guid, 'Pending')."'>".__($guid, 'Pen').'</span>';
                                        } else {
                                            if ($rowStudents['dateStart'] > $lessonDate[$i]) {
                                                echo "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>";
                                            } else {
                                                if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') {
                                                    echo "<span title='".__($guid, 'Incomplete')."' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__($guid, 'Inc').'</span>';
                                                } else {
                                                    echo "<span title='".__($guid, 'Not submitted online')."'>".__($guid, 'NA').'</span>';
                                                }
                                            }
                                        }
                                    }
                                    echo '</td>';
                                }
                                if ($row2['attainment'] == 'Y') {
                                    echo "<td style='text-align: center'>";
									//Create attainment grade select
									if ($row2['gibbonScaleIDAttainment'] != '') {
										echo "<select name='$count-attainmentValue' id='$count-attainmentValue' style='width:50px'>";
										try {
											$dataSelect = array('gibbonScaleID' => $gibbonScaleIDAttainment);
											$sqlSelect = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
											$resultSelect = $connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										} catch (PDOException $e) {
										}
										echo "<option value=''></option>";
										$sequence = '';
										$descriptor = '';
										while ($rowSelect = $resultSelect->fetch()) {
											if ($rowEntry['attainmentValue'] == $rowSelect['value']) {
												echo "<option selected value='".htmlPrep($rowSelect['value'])."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
											} else {
												echo "<option value='".htmlPrep($rowSelect['value'])."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
											}
										}
										echo '</select>';
									}
                                    echo "<div style='height: 20px'>";
                                    if ($row2['gibbonRubricIDAttainment'] != '') {
                                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/cfa_write_rubric.php&gibbonRubricID='.$row2['gibbonRubricIDAttainment']."&gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=$cfaColumnID&gibbonPersonID=".$rowStudents['gibbonPersonID']."&type=attainment&width=1100&height=550'><img style='margin-top: 3px' title='".__($guid, 'Mark Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                    }
                                    echo '</div>';
                                    echo '</td>';
                                }
                                if ($row2['effort'] == 'Y') {
                                    echo "<td style='text-align: center'>";
                                    if ($row2['gibbonScaleIDEffort'] != '') {
                                        echo "<select name='$count-effortValue' id='$count-effortValue' style='width:50px'>";
                                        try {
                                            $dataSelect = array('gibbonScaleID' => $gibbonScaleIDEffort);
                                            $sqlSelect = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
                                        echo "<option value=''></option>";
                                        $sequence = '';
                                        $descriptor = '';
                                        while ($rowSelect = $resultSelect->fetch()) {
                                            if ($rowEntry['effortValue'] == $rowSelect['value']) {
                                                echo "<option selected value='".htmlPrep($rowSelect['value'])."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
                                            } else {
                                                echo "<option value='".htmlPrep($rowSelect['value'])."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
                                            }
                                        }
                                        echo '</select>';
                                    }
                                    echo "<div style='height: 20px'>";
                                    if ($row2['gibbonRubricIDEffort'] != '') {
                                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/cfa_write_rubric.php&gibbonRubricID='.$row2['gibbonRubricIDEffort']."&gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=$cfaColumnID&gibbonPersonID=".$rowStudents['gibbonPersonID']."&type=effort&width=1100&height=550'><img style='margin-top: 3px' title='".__($guid, 'Mark Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                    }
                                    echo '</div>';
                                    echo '</td>';
                                }
                                if ($row2['comment'] == 'Y' or $row2['uploadedResponse'] == 'Y') {
                                    echo "<td style='text-align: right'>";
                                    if ($row2['comment'] == 'Y') {
                                        echo "<textarea name='comment".$count."' id='comment".$count."' rows=6 style='width: 330px'>".$rowEntry['comment'].'</textarea>';
                                    }
                                    if ($row2['uploadedResponse'] == 'Y') {
                                        if ($rowEntry['response'] != '') {
                                            echo "<input type='hidden' name='response$count' id='response$count' value='".$rowEntry['response']."'>";
                                            echo "<div style='width: 330px; float: right'><a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>".__($guid, 'Uploaded Response')."</a> <a href='".$_SESSION[$guid]['absoluteURL']."/modules/CFA/cfa_write_data_responseDeleteProcess.php?gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=$cfaColumnID&gibbonPersonID=".$rowStudents['gibbonPersonID']."' onclick='return confirm(\"".__($guid, 'Are you sure you want to delete this record? Unsaved changes will be lost.')."\")'><img style='margin-bottom: -8px' id='image_75_delete' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/></div>";
                                        } else {
                                            echo "<input style='max-width: 228px; margin-top: 5px' type='file' name='response$count' id='response$count'>";
                                            ?>
											<script type="text/javascript">
												var <?php echo "response$count" ?>=new LiveValidation('<?php echo "response$count" ?>');
												<?php echo "response$count" ?>.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
											</script>
											<?php

                                        }
                                    }
                                    echo '</td>';
                                }
                                echo "<input name='$count-gibbonPersonID' id='$count-gibbonPersonID' value='".$rowStudents['gibbonPersonID']."' type='hidden'>";
                            }
                            echo '</tr>';
                        }
                    }
                    ?>
					<tr class='break'>
						<?php
						echo '<td colspan='.($span).'>';
                    		?>
							<h3>Assessment Complete?</h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Go Live Date') ?></b><br/>
							<span style="font-size: 90%"><i><?php echo __($guid, '1. Format') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
                    		?><br/><?php echo __($guid, '2. Column is hidden until date is reached.') ?></i></span>
						</td>
						<td class="right" colspan="<?php echo $span - 1 ?>">
							<input name="completeDate" id="completeDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row2['completeDate']) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var completeDate=new LiveValidation('completeDate');
								completeDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
									echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#completeDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<?php
						echo "<td style='text-align: left'>";
						echo getMaxUpload(true);
						echo '</td>';
						echo "<td class='right' colspan=".($span - 1).'>';
						?>
						<input name="count" id="count" value="<?php echo $count ?>" type="hidden">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">

					</td>
				</tr>
				<?php
				echo '</table>';
				echo '</form>';
			}
		}
	}

	//Print sidebar
	$_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'write');
    }
}
?>
