<?php

namespace Gibbon\Module\ATL\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * ATL Entry Gateway
 *
 * @version v22
 * @since   v22
 */
class ATLEntryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'atlEntry';
    private static $primaryKey = 'atlEntryID';
    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryATLsByStudent(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSchoolYear.name as yearName', 'gibbonCourse.gibbonSchoolYearID', 'gibbonCourse.name as courseName', 'atlColumn.name as ATLName', 'atlColumn.description as ATLDescription', 'atlColumn.completeDate', 'atlColumn.gibbonRubricID', 'atlColumn.gibbonCourseClassID', 'atlColumn.atlColumnID'
            ])
            ->leftJoin('atlColumn', 'atlEntry.atlColumnID=atlColumn.atlColumnID')
            ->leftJoin('gibbonCourseClass', 'atlColumn.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID AND atlEntry.gibbonPersonIDStudent = gibbonCourseClassPerson.gibbonPersonID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonSchoolYear', 'gibbonCourse.gibbonSchoolYearID = gibbonSchoolYear.gibbonSchoolYearID')
            ->where('atlEntry.gibbonPersonIDStudent = :gibbonPersonID')
            ->where('completeDate <= :today')
            ->where("gibbonCourseClass.reportable = 'Y'")
            ->where("gibbonCourseClassPerson.reportable = 'Y'")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('today', date('Y-m-d'));

        $criteria->addFilterRules([
            'gibbonSchoolYearID' => function($query, $gibbonSchoolYearID) {
                return $query->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }
    
    public function createATLEntries($atlColumnID, $gibbonCourseClassID, $gibbonPersonID) {
        $data = ['atlColumnID' => $atlColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d')];
        $sql = "INSERT INTO atlEntry (atlColumnID, gibbonPersonIDStudent, complete, gibbonPersonIDLastEdit)
                SELECT :atlColumnID as atlColumnID, gibbonPerson.gibbonPersonID, 'N' as complete, :gibbonPersonID as gibbonPersonIDLastEdit
                FROM gibbonCourseClassPerson
                INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID = gibbonPerson.gibbonPersonID)
                WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL  OR dateEnd>=:today)
                AND role = 'Student'
        ";
    
        return $this->db()->insert($sql, $data);
    }

}
