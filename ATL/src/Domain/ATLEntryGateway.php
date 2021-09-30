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
    
}
