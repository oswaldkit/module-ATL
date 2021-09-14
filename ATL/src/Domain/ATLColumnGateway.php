<?php

namespace Gibbon\Module\ATL\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * ATL Column Gateway
 *
 * @version v20
 * @since   v20
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
                'atlColumnID', 'gibbonCourseClassID', 'groupingID', 'name', 'description', 'gibbonRubricID', 'complete', 'completeDate', 'gibbonPersonIDCreator', 'gibbonPersonIDLastEdit'
            ])
            ->where('atlColumn.gibbonCourseClassID = :gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);

        return $this->runQuery($query, $criteria);
    }
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryATLColumnsByStudent(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
              'atlColumn.name' 
            ])
            ->leftJoin('atlEntry', 'atlColumn.atlColumnID=atlEntry.atlColumnID AND atlEntry.gibbonPersonIDStudent = :gibbonPersonID')
            ->leftJoin('gibbonCourseClass', 'atlColumn.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID')
            ->where('gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID')
            ->where("gibbonCourseClassPerson.role = 'Student'")
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }
    
}
