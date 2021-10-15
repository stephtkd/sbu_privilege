<?php

/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Stéphane Burlet
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

ini_set("error_log", "./php-error.log");

//use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\Module\SbuPrivilegeCode\Entity\PrivilegeCode;
use PrestaShop\Module\SbuPrivilegeCode\Exception\CannotCreatePrivilegeCodeException;
use PrestaShop\Module\SbuPrivilegeCode\Exception\CannotDeletePrivilegeCodeValueException;
use PrestaShop\Module\SbuPrivilegeCode\Exception\CannotUpdatePrivilegeCodeValueException;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class Sbu_privilege extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'sbu_privilege';
        $this->tab = 'others';
        $this->version = '1.2.3';
        $this->author = 'Stéphane Burlet';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Manage privileges');
        $this->description = $this->l('Manages privilege codes. Every commercial will receive a privilege code. They give this code to their customers. The customers, when they sign in, will be asked for this privilege code. This privilege code allows the commercial to receive commissions on every sale from its customers. The customer should be placed into a group "Privileged Customer" and cart rules should be affected to this group. If the customer is a professionel, then he should be placed into a group "Privileged professional". The configuration determines which group will be the "Sponsor" group. Recommanded : "Sales Manager".');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Manage privilege? This will delete all privilege codes.');

        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);

        $this->is_module_setup();
        return true;
    }


    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        error_log($this->name." - install " . $this->name . " : " . $this->version);

        Configuration::updateValue('SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID', null);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('additionalCustomerFormFields') &&
            $this->registerHook('actionObjectCustomerUpdateAfter') &&
            $this->registerHook('actionObjectCustomerAddAfter') &&
            $this->registerHook('actionCustomerGridDefinitionModifier') &&
            $this->registerHook('actionCustomerGridQueryBuilderModifier') &&
            $this->registerHook('actionCustomerFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateCustomerFormHandler') &&
            $this->registerHook('actionAfterUpdateCustomerFormHandler') &&
            //          $this->registerHook('displayCustomerAccountForm') &&
            $this->registerHook('actionObjectCustomerDeleteBefore');
    }

    public function uninstall()
    {
        error_log($this->name." - uninstall " . $this->name . " : " . $this->version);

        Configuration::deleteByName('SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    //    public function hookdisplayCustomerAccountForm($params)
    //    {
    //echo "AdditionalCustomerFormFields - BURLET";
    //$a="displayCustomerAccountForm - BURLET - ";
    //$a=$a.print_r($params,true);
    //error_log($a);
    /*echo "<pre>";
        print_r($params);
        echo "</pre>";*/
    //    }

        /**
     * check if the module is set up or not
     *
     * @return boolean
     */
    private function is_module_setup()
    {
        $SALES_MANAGER_GROUP_ID=Configuration::get('SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID');
        if ( empty($SALES_MANAGER_GROUP_ID))
        {
            error_log("Attention : Module privilege non configuré !");
            return false;
        }
        else
            return true;
    }

    /**
     * Add additionnel field (privilege_code) in customer registration form in FO and in my account>>my information in FO
     * @param type $params
     */
    public function hookAdditionalCustomerFormFields($params)
    {
        //echo "AdditionalCustomerFormFields - BURLET";
        //$a="AdditionalCustomerFormFields - BURLET - ";
        //$a=$a.print_r($params,true);
        //error_log($a);
        /*echo "<pre>";
        print_r($params);
        echo "</pre>";*/
        return [
            (new FormField)
                ->setName('sales_manager')
                ->setType('checkbox')
                //->setRequired(true) Décommenter pour rendre obligatoire
                ->setLabel($this->l('Sign up to become a sales manager (I certify I\'m 18 years old or above)')),
            (new FormField)
                ->setName('privilege_code')
                ->setType('text')
                //->setRequired(true) Décommenter pour rendre obligatoire
                ->setLabel($this->l('Privilege Code'))
        ];
    }

    /**
     * Customer update in FO or BO
     * params['object'] de la forme :
     * Customer Object
     *   (
     *       [id] => 33
     *       [id_shop] => 1
     *       [id_shop_group] => 1
     *       [secure_key] => e616d3582f20b2885a9fa063a684c1c6
     *       [note] => 
     *       [id_gender] => 1
     *       [id_default_group] => 3
     *       [id_lang] => 1
     *       [lastname] => burl
     *       [firstname] => steph
     *       [birthday] => 0000-00-00
     *       [email] => stephtkd@yahoo.frr
     *       [newsletter] => 0
     *       [ip_registration_newsletter] => 
     *       [newsletter_date_add] => 0000-00-00 00:00:00
     *       [optin] => 
     *       [website] => 
     *       [company] => 
     *       [siret] => 
     *       [ape] => 
     */
    public function hookactionObjectCustomerUpdateAfter($params)
    {
        error_log($this->name." - hookactionObjectCustomerUpdateAfter : customerId=".$params['object']->id);
        $idCustomer = (int)$params['object']->id;
        $this->writeModuleValues($idCustomer);
    }

    /**
     * Customer add in FO or BO
     */
    public function hookactionObjectCustomerAddAfter($params)
    {
        error_log($this->name." - hookactionObjectCustomerAddAfter : customerId=".$params['object']->id);
        $idCustomer = (int)$params['object']->id;
        $this->writeModuleValues($idCustomer);
    }

    /**
     * Mutualiser la fonction avec updateCustomerPrivilegeCode (pas possible)
     * Appelée lors d'une modif dans le FO ou le BO
     *
     * @param integer $customerId
     * @return void
     */
    public function writeModuleValues(int $customerId)
    {
        error_log($this->name." - writeModuleValues - customerId=$customerId - privilege_code=".Tools::getValue('privilege_code')." - sales_manager=".Tools::getValue('sales_manager'));
        // ATTENTION : getValue marche dans le FO mais pas dans le BO (ex : quand on modifie un customer)
        $PrivilegeCodeValue = trim(Tools::getValue('privilege_code'));
        $SalesManagerValue = (int)trim(Tools::getValue('sales_manager'));

        /*
        $query = 'UPDATE `'._DB_PREFIX_.'sbu_privilege` priv '
            .' SET  priv.`privilege_code` = "'.pSQL($PrivilegeCodeValue).'"'
            .' WHERE priv.id_customer = '.(int)$customerId;
        
        Db::getInstance()->execute($query);*/


        // Visiblement la ligne suivante ne marche pas dans le contexte FO, donc je recherche le PrivilegeCodeId autrement
        //$PrivilegeCodeId = $this->get('ps_sbu_privilege.repository.privilege_code')->findIdByCustomer($customerId);

        $sql = new DbQuery();
        $sql->select('`id_privilege_code`')
            ->from('sbu_privilege_code')
            ->where('`id_customer` = ' . pSQL($customerId));
        //$sql->setParameter('customer_id', $customerId);

        //$PrivilegeCodeId=Db::getInstance()->executeS($sql);
        $PrivilegeCodeId = Db::getInstance()->getValue($sql);

        //return (int) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
        //  return Db::getInstance()->executeS($sql);
//        error_log("PrivilegeCodeId = ".$PrivilegeCodeId);

        //error_log("PrivilegeCodeValue = ".$PrivilegeCodeValue);


        $privilegeCode = new PrivilegeCode($PrivilegeCodeId);
        error_log("privilegeCode (id=".$privilegeCode->id.", privilege_code=".$privilegeCode->privilege_code.", sales_manager=".$privilegeCode->sales_manager.")");
        //error_log("privilegeCode = ".print_r($privilegeCode,true));
        // En testant $privilegeCode->id, ça marche que je vienne du FO ou du BO (car Tools::getValue ne marche pas en venant du BO)
        if (0 >= $privilegeCode->id) {
            error_log("je crée un nouveau privilege_code : customerId = $customerId; privilege-code = $PrivilegeCodeValue; Sales-manager = $SalesManagerValue");
            $privilegeCode = $this->createPrivilegeCode($customerId,$PrivilegeCodeValue,$SalesManagerValue);
        }
        else {
            error_log("modification d'un privilege_code existant : customerId = $customerId; privilege-code = $PrivilegeCodeValue; Sales-manager = $SalesManagerValue");
            $privilegeCode->privilege_code = $PrivilegeCodeValue;
            $privilegeCode->sales_manager = $SalesManagerValue;
            error_log("privilegeCode (id=".$privilegeCode->id.", privilege_code=".$privilegeCode->privilege_code.", sales_manager=".$privilegeCode->sales_manager.")");

            try {
                if (false === $privilegeCode->update()) {
                    $msg = $this->l('Failed to change privilege code with id');
                    throw new CannotUpdatePrivilegeCodeValueException(
                        sprintf('%s %s', $msg, $privilegeCode->id)
                    );
                }
            } catch (PrestaShopException $exception) {
                throw new CannotUpdatePrivilegeCodeValueException(
                    $this->l('An unexpected error occurred when updating privilege code')
                );
            }
        }

        $this->affectPrivilegeGroup($customerId,$PrivilegeCodeValue);
    }

    /**
     * Function to assign the correct group according to the privilege code
     * if the privilege-code is known :
     *      - affect to group "client privilégié" (id 5) if no SIREN is given (private customer)
     *      - affect to group "professionnel privilégié" (id 4) if SIREN or company name is given (professionnal customer)
     * @param int $customerId
     * @param string $PrivilegeCodeValue
     */
    public function affectPrivilegeGroup( int $customerId, string $PrivilegeCodeValue) 
    {
        error_log($this->name." - affectPrivilegeGroup(customerId=$customerId, PrivilegeCodeValue=$PrivilegeCodeValue)");
        if (! $this->is_module_setup()) return true;
        if (empty($PrivilegeCodeValue)) return true;
        $SALES_MANAGER_GROUP_ID=(int)Configuration::get('SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID', null);

        $sql = new DbQuery();
        //SELECT
        //  pc.privilege_code,
        //  pc.id_customer,
        //  cg.id_group
        //FROM
        //  ps_sbu_privilege_code pc,
        //  ps_customer cu,
        //  ps_customer_group cg
        //where
        //  pc.privilege_code = "TUTU"
        //  and pc.id_customer != 31
        //  and pc.id_customer = cu.id_customer
        //  and cu.id_customer = cg.id_customer
        //  and cg.id_group in (6,7);
        $sql->select('CONCAT(cu.firstname," ",cu.lastname) as nom')
            ->from('sbu_privilege_code', 'pc')
            ->from('customer', 'cu')
            ->from('customer_group', 'cg')
            ->where('pc.privilege_code = "' . pSQL($PrivilegeCodeValue) .'"')
            ->where('pc.id_customer != ' . pSQL($customerId))
            ->where('pc.id_customer = cu.id_customer')
            ->where('cu.id_customer = cg.id_customer')
            ->where('cg.id_group in ('.pSQL($SALES_MANAGER_GROUP_ID).')');         //6 représente l'id des groupes des parrains (responsables des ventes)

        //$PrivilegeCodeId=Db::getInstance()->executeS($sql);
        $sponsors = Db::getInstance()->executeS($sql);
        if (sizeof($sponsors) != 0)
        {
            // On a un parrain
            $listeSponsors="";
            $i=0;
            foreach($sponsors as $sponsor)
            {
                if ($i!=0)
                {
                    $listeSponsors.=", ";
                }
                $listeSponsors.=$sponsor["nom"];
                $i++;
            }
            error_log("Parrain trouvé : $listeSponsors");
            // Il faut maintenant déterminer si le client est pro ou particulier. Pour ça je regarde son code SIRET ou company
            $sql = new DbQuery();
            $sql->select('`company`')
            ->select('`siret`')
            ->from('customer')
            ->where('id_customer = ' . pSQL($customerId));
            $companies = Db::getInstance()->executeS($sql);
            //error_log(print_r($companies,true));
            $company=$companies[0]["company"];
            $siret=$companies[0]["siret"];
            if ( $company == "" && $siret == "" )
            {
                // Il faut alors lui affecter le groupe_id 5 (Client privilégié)
                $sql = \Db::getInstance();
                $sql->insert('customer_group', [ 
                    'id_customer' => (int) pSQL($customerId), 
                    'id_group' => 5
                ],
                true,
                true,
                Db::INSERT_IGNORE);
                error_log("Ajout du groupe d'id 5 pour le client d'id $customerId");
            }
            else
            {
                error_log("Le client est un professionnel. Je ne fais rien pour le moment.");
            }
        }
        else
        {
            error_log("Aucun parrain trouvé avec ce code privilege ($PrivilegeCodeValue)");
        }

//        error_log("Je trouve $numRows privilege(s) code identiques au mien ($PrivilegeCodeValue)");

    }


    /**
     * Hook allows to modify Customers grid definition.
     * Add column privilege_code ans sales_manager in admin customers grid in BO
     */
    public function hookActionCustomerGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];
        //$a=$a.print_r($definition->getColumns(),true);
        error_log($this->name." - hookActionCustomerGridDefinitionModifier");

        // Add columns
        $ColumnPrivilegeCode = new DataColumn('privilege_code');
        $ColumnPrivilegeCode->setName($this->l('Privilege Code'));
        $ColumnPrivilegeCode->setOptions([
            'field' => 'privilege_code',
        ]);
        $columns = $definition->getColumns();
        $columns->addAfter('company', $ColumnPrivilegeCode);

        $ColumnSalesManager = new DataColumn('sales_manager');
        $ColumnSalesManager->setName($this->l('Sales Manager'));
        $ColumnSalesManager->setOptions([
            'field' => 'sales_manager',
        ]);
        $columns = $definition->getColumns();
        $columns->addAfter('privilege_code', $ColumnSalesManager);

        // Add filters
        $filterPrivilegeCode = new Filter('privilege_code', TextType::class);
        $filterPrivilegeCode->setAssociatedColumn('privilege_code');
        $filterPrivilegeCode->setTypeOptions([
            'required' => false,
        ]);
        /** @var FilterCollectionInterface $filters */
        $filters = $definition->getFilters();
        $filters->add($filterPrivilegeCode);

        $filterSalesManager = new Filter('sales_manager', TextType::class);
        $filterSalesManager->setAssociatedColumn('sales_manager');
        $filterSalesManager->setTypeOptions([
            'required' => false,
        ]);
        /** @var FilterCollectionInterface $filters */
        $filters = $definition->getFilters();
        $filters->add($filterSalesManager);
    }

    /**
     * Hook allows to modify Customers query builder and add custom sql statements.
     * Query column privilege_code ans sales_manager in admin customers list in BO
     */
    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        error_log($this->name." - hookActionCustomerGridQueryBuilderModifier");

        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $searchQueryBuilder->addSelect('priv.`privilege_code` AS `privilege_code`')->addSelect('priv.`sales_manager` AS `sales_manager`');
        //->from(_DB_PREFIX_.'customer');
        $searchQueryBuilder->leftJoin(
            'c',
            '`' . _DB_PREFIX_ . 'sbu_privilege_code`',
            'priv',
            'priv.`id_customer` = c.`id_customer`'
        );
        //error_log($searchQueryBuilder->getSQL());

        /*$countQueryBuilder = $params['count_query_builder'];
        // So the pagination and the number of customers
        // retrieved will be right.
        $countQueryBuilder->addSelect('priv.privilege_code')->addSelect('priv.`sales_manager`');
        $countQueryBuilder->leftJoin(
            'c',
            '`' . _DB_PREFIX_ . 'sbu_privilege_code`',
            'priv',
            'priv.`id_customer` = c.`id_customer`'
        );
        */
        /* Gestion du tri */
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        if ('privilege_code' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('priv.`privilege_code`', $searchCriteria->getOrderWay());
        }
        if ('sales_manager' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('priv.`sales_manager`', $searchCriteria->getOrderWay());
        }

        /* Gestion des filtres */
        //        error_log(print_r($searchCriteria->getFilters(),true));
        $filters = $searchCriteria->getFilters();
        foreach ($filters as $filterName => $filterValue) {
            if ('privilege_code' === $filterName) {
                $searchQueryBuilder->andWhere('priv.`privilege_code` LIKE :param_privilege_code');
                $searchQueryBuilder->setParameter('param_privilege_code', "%" . $filterValue . "%");
                //if (isset($strictComparisonFilters[$filterName])) {
                //    $alias = $strictComparisonFilters[$filterName];
                //    $searchQueryBuilder->andWhere("$alias LIKE :$filterName");
                //    $searchQueryBuilder->setParameter($filterName, '%'.$filterValue.'%');
                //continue;
            }
            if ('sales_manager' === $filterName) {
                $searchQueryBuilder->andWhere('priv.`sales_manager` LIKE :param_sales_manager');
                $searchQueryBuilder->setParameter('param_sales_manager', "%" . $filterValue . "%");
            }
        }
    }

    /**
     * Hook allows to modify Customers form and add additional form fields as well as modify or add new data to the forms.
     * Add column privilege_code and sales_manager in admin customers form in BO
     * Called when we edit a customer in BO ans after saving when editing a customer in BO (so called twice)
     *
     * Array params
     *  (
     *      [0] => _ps_version
     *      [1] => request
     *      [2] => route
     *      [3] => form_builder
     *      [4] => data
     *      [5] => id
     *      [6] => cookie
     *      [7] => cart
     *      [8] => altern
     *  )

     * @param array $params
     */
    public function hookActionCustomerFormBuilderModifier(array $params)
    {
        error_log($this->name." - hookActionCustomerFormBuilderModifier");
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];

        $formBuilder->add('privilege_code', TextType::class, [
            'label' => $this->l('Privilege Code'),
            'required' => false,
        ]);
        $formBuilder->add('sales_manager', TextType::class, [
            'label' => $this->l('Sales Manager'),
            'required' => false,
        ]);

        /*
        SwitchType::class, [
            'label' => $this->l('Privilege Code'),
            'required' => false,
        ]);*/

        $privilegeCodeValue = "";
        $salesManagerValue = "";

        if (null !== $params['id']) {
            $privilegeCodeValue = $this->get('ps_sbu_privilege.repository.privilege_code')->getPrivilegeCode((int) $params['id']);
            $salesManagerValue = $this->get('ps_sbu_privilege.repository.privilege_code')->getSalesManager((int) $params['id']);
        }
        $params['data']['privilege_code'] = $privilegeCodeValue;
        $params['data']['sales_manager'] = $salesManagerValue;

        //error_log('param[data] = '.print_r($params['data'],true));
        //$params['data']['privilege_code'] = "toto";

        $formBuilder->setData($params['data']);
    }

/**
 * Hook called after saving when editing a customer in BO
 * params de la forme : Array
    * (
    *     [0] => _ps_version
    *     [1] => request
    *     [2] => route
    *     [3] => id
    *     [4] => form_data
    *     [5] => cookie
    *     [6] => cart
    *     [7] => altern
    * )
 *
 * @param array $params
 * @return void
 */
    public function hookActionAfterUpdateCustomerFormHandler(array $params)
    {
        error_log($this->name." - hookActionAfterUpdateCustomerFormHandler : ".print_r($params['route'],true));
        $this->updateCustomerPrivilegeCode($params);
    }

/**
 * Hook called after saving when creating a customer in BO
 * params de la forme : Array
    * (
    *     [0] => _ps_version
    *     [1] => request
    *     [2] => route
    *     [3] => id
    *     [4] => form_data
    *     [5] => cookie
    *     [6] => cart
    *     [7] => altern
    * )
 *
 * @param array $params
 * @return void
 */
public function hookActionAfterCreateCustomerFormHandler(array $params)
    {
        error_log($this->name." - hookActionAfterCreateCustomerFormHandler");
        $this->updateCustomerPrivilegeCode($params);
    }

    /*    celui-là ne marche pas (pas appelé ?)
public function hookActionAfterDeleteCustomerFormHandler(array $params)
    {
        $a="hookActionAfterDeleteCustomerFormHandler - BURLET - ";
        error_log($a);
        //$this->updateCustomerPrivilegeCode($params);
        return true;
    }*/

    public function hookActionObjectCustomerDeleteBefore(array $params)
    {
        error_log($this->name." - hookActionObjectCustomerDeleteBefore");
        $this->deleteCustomerPrivilegeCode($params);
    }

    public function hookActionCategoryFormBuilderModifier_OLD(array $params)
    {
        //error_log("ActionCategoryFormBuilderModifier - BURLET");
        //error_log(print_r($params['data'], true));
        //Récupération du form builder
        /** @var \Symfony\Component\Form\FormBuilder $formBuilder */
        $formBuilder = $params['form_builder'];


        //Ajout de notre champ spécifique
        $formBuilder->add(
            $this->name . '_newfield1',
            //Cf génériques symonfy https://symfony.com/doc/current/reference/forms/types.html
            // et spécificiques prestashop https://devdocs.prestashop.com/1.7/development/components/form/types-reference/
            \Symfony\Component\Form\Extension\Core\Type\TextType::class,
            [
                'label' => $this->l('Custom field 1'), //Label du champ
                'required' => false, //Requis ou non
                'constraints' => [ //Contraintes du champs
                    //cf. génériques symfony : https://symfony.com/doc/current/reference/constraints.html
                    // Ou vous pouvez écrire la votre cf. https://symfony.com/doc/current/validation/custom_constraint.html
                    new \Symfony\Component\Validator\Constraints\Length([
                        'max' => 20,
                        'maxMessage' => $this->l('Max caracters allowed : 20'),
                    ]),
                ],
                //La valeur peut être setée ici
                'data' => 'test valeur', //Valeur du champ
                // Texte d'aide
                'help' => $this->l('help text 2')
            ]
        );

        //Ou surchargée ici
        $params['data'][$this->name . '_newfield1'] = 'Custom value 1';

        //Ajout d'un champ langue
        $formBuilder->add(
            $this->name . '_newfield_lang',
            // cf. https://devdocs.prestashop.com/1.7/development/components/form/types-reference/
            \PrestaShopBundle\Form\Admin\Type\TranslatableType::class,
            [
                'label' => $this->l('Custom field Lang'), //Label du champ
                'required' => false, //Requis ou non
                'type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class // OU TextAreaType::class
            ]
        );
        //Définition des données du champ langue
        $languages = Language::getLanguages(true);
        foreach ($languages as $lang) {
            $params['data'][$this->name . '_newfield_lang'][$lang['id_lang']] = 'Custom value for lang ' . $lang['iso_code'];
        }

        //On peut également changer facilement la donnée de n'importe quel autre champ du formulaire
        $params['data']['active'] = false;

        //Il faut bien penser à mettre cette ligne pour mettre à jour les données au formulaire
        $formBuilder->setData($params['data']);
    }

    /**
     * @param array $params
     *
     * @throws \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException
     */
    private function deleteCustomerPrivilegeCode(array $params)
    {
        $customerId = (int)$params['object']->id;
        /** @var array $customerFormData */
        /*
        $customerFormData = $params['form_data'];
        $PrivilegeCodeValue = (string) $customerFormData['privilege_code'];
        */
        $PrivilegeCodeId = $this->get('ps_sbu_privilege.repository.privilege_code')->findIdByCustomer($customerId);
        //        error_log("delete customerId = $customerId - privilegecodeId = " . print_r($PrivilegeCodeId, true));
        if ($PrivilegeCodeId != 0) {
            $privilegeCode = new PrivilegeCode($PrivilegeCodeId);

            try {
                if (false === $privilegeCode->delete()) {
                    $msg = $this->l('Failed to change privilege code with id');
                    throw new CannotDeletePrivilegeCodeValueException(
                        sprintf('%s %s', $msg, $privilegeCode->id)
                    );
                }
            } catch (PrestaShopException $exception) {
                throw new CannotDeletePrivilegeCodeValueException(
                    $this->l('An unexpected error occurred when updating privilege code')
                );
            }
        }
    }


    /**
     * Called by hook hookActionAfterUpdateCustomerFormHandler or hookActionAfterCreateCustomerFormHandler (after editing a customer in BO)
     * params de la forme : Array
    * (
    *     [0] => _ps_version
    *     [1] => request
    *     [2] => route
    *     [3] => id
    *     [4] => form_data
    *     [5] => cookie
    *     [6] => cart
    *     [7] => altern
    * )
     * @param array $params
     *
     * @throws \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException
     */
    private function updateCustomerPrivilegeCode(array $params)
    {
        error_log($this->name." - updateCustomerPrivilegeCode avec param[id] = ".$params['id']);
        $customerId = $params['id'];
        /** @var array $customerFormData */
        $customerFormData = $params['form_data'];
        $PrivilegeCodeValue = (string) $customerFormData['privilege_code'];
        $SalesManagerValue = (int) $customerFormData['sales_manager'];
        
        $PrivilegeCodeId = $this->get('ps_sbu_privilege.repository.privilege_code')->findIdByCustomer($customerId);

        $privilegeCode = new PrivilegeCode($PrivilegeCodeId);
        error_log("privilegeCode (id=".$privilegeCode->id.", privilege_code=".$privilegeCode->privilege_code.", sales_manager=".$privilegeCode->sales_manager.")");

        if (0 >= $privilegeCode->id) {
            $privilegeCode = $this->createPrivilegeCode($customerId);
        }
        $privilegeCode->privilege_code = $PrivilegeCodeValue;
        $privilegeCode->sales_manager = $SalesManagerValue;
        error_log("privilegeCode (id=".$privilegeCode->id.", privilege_code=".$privilegeCode->privilege_code.", sales_manager=".$privilegeCode->sales_manager.")");

        try {
            if (false === $privilegeCode->update()) {
                $msg = $this->l('Failed to change privilege code with id');
                throw new CannotUpdatePrivilegeCodeValueException(
                    sprintf('%s %s', $msg, $privilegeCode->id)
                );
            }
        } catch (PrestaShopException $exception) {
            throw new CannotUpdatePrivilegeCodeValueException(
                $this->l('An unexpected error occurred when updating privilege code')
            );
        }
    }

    /**
     * Creates a PrivilegeCode.
     *
     * @param int $customerId
     * @param string $privilege_code
     * @param int $sales_manager
     *
     * @return PrivilegeCode
     *
     * @throws CannotCreatePrivilegeCodeException
     */
    protected function createPrivilegeCode(int $customerId, string $privilege_code = "", int $sales_manager = 0)
    {
        try {
            $privilegeCode = new PrivilegeCode();
            $privilegeCode->id_customer = $customerId;
            $privilegeCode->privilege_code = $privilege_code;
            $privilegeCode->sales_manager = $sales_manager;
            error_log("Je sauvegarde le PrivilegeCode (customerId=$customerId, privilege_code=$privilege_code, sales_manager=$sales_manager) en BDD");
            if (false === $privilegeCode->save()) {
                $msg = $this->l('An error occurred when creating privilegeCode with customer id');
                throw new CannotCreatePrivilegeCodeException(
                    sprintf(
                        '%s %s',
                        $msg,
                        $customerId
                    )
                );
            }
        } catch (PrestaShopException $exception) {
            $msg = $this->l('An error occurred when creating privilegeCode with customer id');
            throw new CannotCreatePrivilegeCodeException(
                sprintf(
                    '%s %s',
                    $msg,
                    $customerId
                ),
                0,
                $exception
            );
        }

        return $privilegeCode;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitSbu_privilegeModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSbu_privilegeModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-group"></i>',
                        'desc' => $this->l('Select group for sales managers'),
                        'name' => 'SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID',
                        'label' => $this->l('Group for sales managers'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID' => Configuration::get('SBU_PRIVILEGE_SALES_MANAGER_GROUP_ID', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function debug($txt)
    {
        echo "<pre>";
        print_r($txt);
        echo "</pre>";
    }
}
