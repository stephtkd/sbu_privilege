<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace PrestaShop\Module\SbuPrivilegeCode\Repository;

use Doctrine\DBAL\Connection;
use PDO;

class PrivilegeCodeRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     */
    public function __construct(Connection $connection, $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * Finds customer id if such exists.
     *
     * @param int $customerId
     *
     * @return int
     */
    public function findIdByCustomer($customerId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`id_privilege_code`')
            ->from($this->dbPrefix . 'sbu_privilege_code')
            ->where('`id_customer` = :customer_id')
        ;

        $queryBuilder->setParameter('customer_id', $customerId);

        return (int) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * Gets allowed to review status by customer.
     *
     * @param int $customerId
     *
     * @return string
     */
    public function getPrivilegeCode($customerId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`privilege_code`')
            ->from($this->dbPrefix . 'sbu_privilege_code')
            ->where('`id_customer` = :customer_id')
        ;

        $queryBuilder->setParameter('customer_id', $customerId);

        return (string) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }
}
