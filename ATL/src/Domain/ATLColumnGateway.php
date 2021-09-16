<?php
namespace Gibbon\Module\ATL\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Technician Gateway
 *
 * @version v20
 * @since   v20
 */
class ATLColumnGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'atlColumn';
    private static $primaryKey = 'atlColumnID';
    private static $searchableColumns = ['atlColumnID', 'issueName', 'description'];


    
}
