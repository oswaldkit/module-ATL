<?php

namespace Gibbon\Module\ATL\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * ATL Column Gateway
 *
 * @version v22
 * @since   v22
 */
class ATLColumnGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'atlColumn';
    private static $primaryKey = 'atlColumnID';
    private static $searchableColumns = ['atlColumnID', 'name', 'description'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryATLColumnsByClass(QueryCriteria $criteria, $gibbonCourseClassID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'atlColumnID', 'gibbonCourseClassID', 'name', 'description', 'gibbonRubricID', 'complete', 'completeDate', 'gibbonPersonIDCreator', 'gibbonPersonIDLastEdit'
            ])
            ->where('atlColumn.gibbonCourseClassID = :gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);

        return $this->runQuery($query, $criteria);
    }
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryATLColumnsByStudent(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $notComplete = false)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
              'atlColumn.atlColumnID', 'atlColumn.gibbonRubricID', 'atlColumn.gibbonCourseClassID', 'atlEntry.atlEntryID', 'atlColumn.name', 'atlColumn.description', 'atlColumn.complete', 'atlColumn.completeDate', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'atlEntry.complete as entryComplete'
            ])
            ->leftJoin('atlEntry', 'atlColumn.atlColumnID=atlEntry.atlColumnID AND atlEntry.gibbonPersonIDStudent = :gibbonPersonID')
            ->leftJoin('gibbonCourseClass', 'atlColumn.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID')
            ->where("atlColumn.forStudents = 'Y'")
            ->where('atlColumn.completeDate >= :today')
            ->where('gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID')
            ->where("gibbonCourseClassPerson.role = 'Student'")
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('completeDate', date('Y-m-d'))
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if ($notComplete) {
            $query->where("(atlEntry.complete = 'N' OR atlEntry.complete IS NULL)");
        }

        return $this->runQuery($query, $criteria);
    }
    
    public function isForStudent($atlColumnID, $gibbonPersonID, $gibbonSchoolYearID) {
        $select = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
              'atlColumn.atlColumnID'
            ])
            ->leftJoin('gibbonCourseClass', 'atlColumn.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID')
            ->where('atlColumn.atlColumnID = :atlColumnID')
            ->where("atlColumn.forStudents = 'Y'")
            ->where('gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID')
            ->where("gibbonCourseClassPerson.role = 'Student'")
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('atlColumnID', $atlColumnID)
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $result = $this->runSelect($select);
        return $result->isNotEmpty();
    }

}
