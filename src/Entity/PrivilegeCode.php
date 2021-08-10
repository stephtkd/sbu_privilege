<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace PrestaShop\Module\SbuPrivilegeCode\Entity;

use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

/**
 * This entity database state is managed by PrestaShop ObjectModel
 */
class PrivilegeCode extends ObjectModel
{
    /**
     * @var int
     */
    public $id_customer;

    /**
     * @var string
     */
    public $privilege_code;

    public static $definition = [
        'table' => 'sbu_privilege_code',
        'primary' => 'id_privilege_code',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'privilege_code' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 45],
        ],
    ];
}
