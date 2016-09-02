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

echo "<script type='text/javascript'>";
    echo '$(document).ready(function(){';
        echo "autosize($('textarea'));";
    echo '});';
echo '</script>';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_write_data.php') == false) {
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
        $atlColumnID = $_GET['atlColumnID'];
        if ($gibbonCourseClassID == '' or $atlColumnID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Write ATLs_all') {
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
                    $data2 = array('atlColumnID' => $atlColumnID);
                    $sql2 = 'SELECT * FROM atlColumn WHERE atlColumnID=:atlColumnID';
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
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/atl_write.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'Write').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'ATLs')."</a> > </div><div class='trailEnd'>".__($guid, 'Enter ATL Results').'</div>';
                    echo '</div>';

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    $columns = 1;

                    for ($i = 0;$i < $columns;++$i) {
                        //Column count
                        $span = 3;
                        if ($row2['gibbonRubricID'] == 'Y') {
                            ++$span;
                        }
                        if ($row2['comment'] == 'Y') {
                            ++$span;
                        }
                        if ($span == 2) {
                            ++$span;
                        }
                    }

                    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/atl_write_dataProcess.php?gibbonCourseClassID=$gibbonCourseClassID&atlColumnID=$atlColumnID&address=".$_SESSION[$guid]['address']."' enctype='multipart/form-data'>";
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

                    for ($i = 0;$i < $columns;++$i) {
                        $columnID[$i] = $row2['atlColumnID'];
                        $gibbonRubricID[$i] = $row2['gibbonRubricID'];

						$header .= "<th style='text-align: center' colspan=$span-2>";
                        $header .= "<span title='".htmlPrep($row2['description'])."'>".$row2['name'].'<br/>';
                        $header .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                        if ($row2['completeDate'] != '') {
                            $header .= __($guid, 'Marked on').' '.dateConvertBack($guid, $row2['completeDate']).'<br/>';
                        } else {
                            $header .= __($guid, 'Unmarked').'<br/>';
                        }
                        $header .= '</span><br/>';
                        $header .= '</th>';
                    }
                    $header .= '</tr>';

                    $header .= "<tr class='head'>";
                    for ($i = 0;$i < $columns;++$i) {
                        if ($row2['gibbonRubricID'] != '') {
                            $header .= "<th style='text-align: center; width: 80px'>";
                            $header .= "<span>".__($guid, 'Rubric').'</span>';
                            $header .= '</th>';
                        }
                        if ($row2['comment'] == 'Y') {
                            $header .= "<th style='text-align: center; width: 80'>";
                            $header .= "<span>".__($guid, 'Comment').'</span>';
                            $header .= '</th>';
                        }
                    }
                    $header .= '</tr>';

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
                                    $dataEntry = array('atlColumnID' => $columnID[($i)], 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                                    $sqlEntry = 'SELECT * FROM atlEntry WHERE atlColumnID=:atlColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                                    $resultEntry = $connection2->prepare($sqlEntry);
                                    $resultEntry->execute($dataEntry);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                $rowEntry = $resultEntry->fetch();

                                if ($row2['gibbonRubricID'] != '') {
                                    echo "<td style='text-align: center'>";
                                    echo "<div style='height: 20px'>";
                                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/atl_write_rubric.php&gibbonRubricID='.$row2['gibbonRubricID']."&gibbonCourseClassID=$gibbonCourseClassID&atlColumnID=$atlColumnID&gibbonPersonID=".$rowStudents['gibbonPersonID']."&type=effort&width=1100&height=550'><img style='margin-top: 3px' title='".__($guid, 'Mark Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                    echo '</div>';
                                    echo '</td>';
                                }
                                if ($row2['comment'] == 'Y') {
                                    echo "<td style='text-align: right'>";
                                        echo "<textarea name='comment".$count."' id='comment".$count."' rows=6 style='width: 330px'>".$rowEntry['comment'].'</textarea>';
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
