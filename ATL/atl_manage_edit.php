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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/ATL/atl_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
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
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
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
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $row = $result->fetch();
                    $row2 = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/atl_manage.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'Manage').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'ATLs')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Column').'</div>';
                    echo '</div>';

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/atl_manage_editProcess.php?atlColumnID=$atlColumnID&gibbonCourseClassID=$gibbonCourseClassID&address=".$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">
							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Basic Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Class') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'This value cannot be changed.') ?></i></span>
								</td>
								<td class="right">
									<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<?php echo htmlPrep($row['course']).'.'.htmlPrep($row['class']) ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="name" id="name" maxlength=20 value="<?php echo htmlPrep($row2['name']) ?>" type="text" style="width: 300px">
                                    <script type="text/javascript">
										var name2=new LiveValidation('name');
										name2.add(Validate.Presence);
									 </script>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Description') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="description" id="description" maxlength=1000 value="<?php echo htmlPrep($row2['description']) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var description=new LiveValidation('description');
										description.add(Validate.Presence);
									 </script>
								</td>
							</tr>

							<tr class='break'>
								<td colspan=2>
									<h3>
										<?php echo __($guid, 'Assessment')  ?>
									</h3>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Rubric'); ?></b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'Choose predefined rubric, if desired.') ?></i></span>
								</td>
								<td class="right">
									<select name="gibbonRubricID" id="gibbonRubricID" style="width: 302px">
										<option></option>
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelectWhere = '';
                                            $years = explode(',', $row['gibbonYearGroupIDList']);
                                            foreach ($years as $year) {
                                                $dataSelect[$year] = "%$year%";
                                                $sqlSelectWhere .= " AND gibbonYearGroupIDList LIKE :$year";
                                            }
                                            $sqlSelect = "SELECT * FROM gibbonRubric WHERE active='Y' AND scope='School' $sqlSelectWhere ORDER BY category, name";
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$label = '';
											if ($rowSelect['category'] == '') {
												$label = $rowSelect['name'];
											} else {
												$label = $rowSelect['category'].' - '.$rowSelect['name'];
											}
											$selected = '';
											if ($row2['gibbonRubricID'] == $rowSelect['gibbonRubricID']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonRubricID']."'>$label</option>";
										}
                                        ?>
									</select>
								</td>
							</tr>

                            <tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Access') ?></h3>
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
								<td class="right">
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

							<?php
                            if ($row2['groupingID'] != '') {
                                //Check for grouped columns
                                try {
                                    $dataGrouped = array('groupingID' => $row2['groupingID'], 'atlColumnID' => $row2['atlColumnID']);
                                    $sqlGrouped = "SELECT atlColumn.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM atlColumn JOIN gibbonCourseClass ON (atlColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE groupingID=:groupingID AND NOT atlColumnID=:atlColumnID ORDER BY course, class";
                                    $resultGrouped = $connection2->prepare($sqlGrouped);
                                    $resultGrouped->execute($dataGrouped);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultGrouped->rowCount() >= 1) {
                                    ?>
									<tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Related Columns') ?></h3>
											<p>
												<?php echo __($guid, 'This column is part of a set of columns: would you like to extend these changes to those columns?');
                                    		?>
											</p>
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Class') ?></b><br/>
										</td>
										<td class="right">
											<?php
                                            echo "<fieldset style='border: none'>";
                                    		?>
											<script type="text/javascript">
												$(function () {
													$('.checkall').click(function () {
														$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
													});
												});
											</script>
											<?php
                                            echo __($guid, 'All/None')." <input type='checkbox' class='checkall'><br/>";
											$yearGroups = getYearGroups($connection2);
											if ($yearGroups == '') {
												echo '<i>'.__($guid, 'No year groups available.').'</i>';
											} else {
												$count = 0;
												while ($rowGrouped = $resultGrouped->fetch()) {
													echo $rowGrouped['course'].'.'.$rowGrouped['class']." <input type='checkbox' name='gibbonCourseClassID[]' value='".$rowGrouped['gibbonCourseClassID']."'><br/>";
												}
											}
											echo '</fieldset>';
											?>
										</td>
									</tr>
									<?php

                                }
                            }
                    		?>
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?><br/>
									</i></span>
								</td>
								<td class="right">
									<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php

                }
            }

            //Print sidebar
            $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'manage', $highestAction);
        }
    }
}
?>
