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

//use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\Module\SbuPrivilegeCode\Entity\PrivilegeCode;
use PrestaShop\Module\SbuPrivilegeCode\Exception\CannotCreatePrivilegeCodeException;
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
        $this->version = '1.1.0';
        $this->author = 'Stéphane Burlet';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Manage privileges',[],'Modules.Sbu_privilege.Admin');
        $this->description = $this->getTranslator()->trans('Manages privilege codes. Every commercial in a special group will receive (from the webmaster) a privilege code. They give this code to their customers. The customers, when they sign in, will be asked for this privilege code (new field "privilege_code" in customer table). This privilege code allows the commercial to receive commissions on every sale from its customers. It\'s better to affect the customer into a group "Privileged Customer" and affect to this group cart rules. The configuration determines which group will be the "Commercial" group. Recommanded : "Commercial".',[],'Modules.Sbu_privilege.Admin');

        $this->confirmUninstall = $this->getTranslator()->trans('Are you sure you want to uninstall privilege? This will delete all privilege codes.',[],'Modules.Sbu_privilege.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
    }

    /**
     * This function is required in order to make module compatible with new translation system.
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }


    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        error_log("install ".$this->name." : ".$this->version);

        Configuration::updateValue('SBU_PRIVILEGE_COMMERCIAL_GROUP_ID', null);

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
            $this->registerHook('actionObjectCustomerDeleteBefore') ;
    }

    public function uninstall()
    {
        error_log("uninstall ".$this->name." : ".$this->version);

        Configuration::deleteByName('SBU_PRIVILEGE_COMMERCIAL_GROUP_ID');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Add additionnel field (privilege_code) in customer registration form in FO
     * @param type $params
     */
    public function hookAdditionalCustomerFormFields($params) {
        //echo "AdditionalCustomerFormFields - BURLET";
        //$a="AdditionalCustomerFormFields - BURLET - ";
        //$a=$a.print_r($params,true);
        //error_log($a);
        /*echo "<pre>";
        print_r($params);
        echo "</pre>";*/
        return [
                    (new FormField)
                    ->setName('privilege_code')
                    ->setType('text')
                    //->setRequired(true) Décommenter pour rendre obligatoire
                    ->setLabel($this->l('Privilege Code'))
        ];
    }

    /**
     * Customer update in FO
     */
    public function hookactionObjectCustomerUpdateAfter($params)
    {
        $idCustomer = (int)$params['object']->id;
        $this->writeModuleValues($idCustomer);
    }

    /**
     * Customer add in FO
     */
    public function hookactionObjectCustomerAddAfter($params)
    {
        $idCustomer = (int)$params['object']->id;
        $this->writeModuleValues($idCustomer);
    }

    /**
     * Mutualiser la fonction avec updateCustomerPrivilegeCode (pas possible)
     *
     * @param integer $customerId
     * @return void
     */
    public function writeModuleValues(int $customerId)
    {
        //error_log("writeModuleValues - $customerId - ".Tools::getValue('privilege_code'));
        $PrivilegeCodeValue=Tools::getValue('privilege_code');

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
        ->where('`id_customer` = '.pSQL($customerId));
        //$sql->setParameter('customer_id', $customerId);

        //$PrivilegeCodeId=Db::getInstance()->executeS($sql);
        $PrivilegeCodeId=Db::getInstance()->getValue($sql);
        
        //return (int) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
        //  return Db::getInstance()->executeS($sql);
        //error_log("PrivilegeCodeId = ".$PrivilegeCodeId);
        //error_log("PrivilegeCodeValue = ".$PrivilegeCodeValue);

    
        $privilegeCode = new PrivilegeCode($PrivilegeCodeId);
        //error_log("privilegeCode = ".print_r($privilegeCode,true));
        if (0 >= $privilegeCode->id) {
            //error_log("je crée un nouveau privilege_code");
            $privilegeCode = $this->createPrivilegeCode($customerId);
        }
        $privilegeCode->privilege_code = $PrivilegeCodeValue;
        //error_log("privilegeCode = ".print_r($privilegeCode,true));

        try {
            if (false === $privilegeCode->update()) {
                throw new CannotUpdatePrivilegeCodeValueException(
                    sprintf('Failed to change privilege code with id "%s"', $privilegeCode->id)
                );
            }
        } catch (PrestaShopException $exception) {
            throw new CannotUpdatePrivilegeCodeValueException(
                'An unexpected error occurred when updating privilege code'
            );
        }
    }
    /**
     * Hook allows to modify Customers grid definition.
     * Add column privilege_code in admin customers grid in BO
     */
    public function hookActionCustomerGridDefinitionModifier(array $params)
    {
        //$a="hookActionCustomerGridDefinitionModifier - BURLET - ";
        //echo "$a";
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];
        //$a=$a.print_r($definition->getColumns(),true);
        //error_log($a);

        // Add column
        $ColumnPrivilegeCode = new DataColumn('privilege_code');
        $ColumnPrivilegeCode->setName($this->l('Privilege Code'));
        $ColumnPrivilegeCode->setOptions([
                'field' => 'privilege_code',
        ]);
        $columns = $definition->getColumns();
        $columns->addAfter('company', $ColumnPrivilegeCode);

        // Add filter
        $filterPrivilegeCode = new Filter('privilege_code', TextType::class);
        $filterPrivilegeCode->setAssociatedColumn('privilege_code');
        /** @var FilterCollectionInterface $filters */
        $filters = $definition->getFilters();
        $filters->add($filterPrivilegeCode);    
    }
    
    /**
     * Hook allows to modify Customers query builder and add custom sql statements.
     * Query column privilege_code in admin customers list in BO
     */
    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        //$a="hookActionCustomerGridQueryBuilderModifier - BURLET - ";
        //echo "$a";
        //error_log($a);

        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $searchQueryBuilder->addSelect('priv.`privilege_code` AS `privilege_code`');
                       //->from(_DB_PREFIX_.'customer');
        $searchQueryBuilder->leftJoin(
            'c',
            '`' . _DB_PREFIX_ . 'sbu_privilege_code`',
            'priv',
            'priv.`id_customer` = c.`id_customer`'
        );
      
        $countQueryBuilder = $params['count_query_builder'];
        // So the pagination and the number of customers
        // retrieved will be right.
        $countQueryBuilder->addSelect('priv.privilege_code');
        $countQueryBuilder->leftJoin(
            'c',
            '`' . _DB_PREFIX_ . 'sbu_privilege_code`',
            'priv',
            'priv.`id_customer` = c.`id_customer`'
        );
                             
        /** @var SearchCriteriaInterface $searchCriteria */        
        $searchCriteria = $params['search_criteria'];

        if ('privilege_code' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('priv.`privilege_code`', $searchCriteria->getOrderWay());
        }

        //        error_log(print_r($searchCriteria->getFilters(),true));
        $filters = $searchCriteria->getFilters();
        foreach ($filters as $filterName => $filterValue) {
            if ('privilege_code' === $filterName) {
                $searchQueryBuilder->andWhere('priv.`privilege_code` = :param_privilege_code');
                $searchQueryBuilder->setParameter('param_privilege_code', $filterValue);
            //if (isset($strictComparisonFilters[$filterName])) {
            //    $alias = $strictComparisonFilters[$filterName];
            //    $searchQueryBuilder->andWhere("$alias LIKE :$filterName");
            //    $searchQueryBuilder->setParameter($filterName, '%'.$filterValue.'%');
                //continue;
            }
        }      
    }

    /**
     * Hook allows to modify Customers form and add additional form fields as well as modify or add new data to the forms.
     * Add column privilege_code in admin customers form in BO
     *
     * @param array $params
     */
    public function hookActionCustomerFormBuilderModifier(array $params)
    {
        //error_log($this->name." - hookActionCustomerFormBuilderModifier - BURLET");
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        
        $formBuilder->add('privilege_code', TextType::class, [
            'label' => $this->l('Privilege Code'),
            'required' => false,
        ]);
        
        /*
        SwitchType::class, [
            'label' => $this->l('Privilege Code'),
            'required' => false,
        ]);*/

        $result = "";
        if (null !== $params['id']) {
            $result = $this->get('ps_sbu_privilege.repository.privilege_code')->getPrivilegeCode((int) $params['id']);
        }
        $params['data']['privilege_code'] = $result;

        //$params['data']['privilege_code'] = "toto";

        $formBuilder->setData($params['data']);
    }


    public function hookActionAfterUpdateCustomerFormHandler(array $params)
    {
        //$a="hookActionAfterUpdateCustomerFormHandler - BURLET - ";
        // error_log($a);
        $this->updateCustomerPrivilegeCode($params);
    }

    public function hookActionAfterCreateCustomerFormHandler(array $params)
    {
        //$a="hookActionAfterCreateCustomerFormHandler - BURLET - ";
        // error_log($a);
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
        $a="hookActionObjectCustomerDeleteBefore - BURLET - ";
        error_log($a);
        //$this->updateCustomerPrivilegeCode($params);
        return true;
    }
    
    public function hookActionCategoryFormBuilderModifier_OLD(array $params)
    {
        error_log("ActionCategoryFormBuilderModifier - BURLET");
        error_log(print_r($params['data'],true));
        //Récupération du form builder
        /** @var \Symfony\Component\Form\FormBuilder $formBuilder */
        $formBuilder = $params['form_builder'];
 
 
        //Ajout de notre champ spécifique
        $formBuilder->add($this->name . '_newfield1',
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
        $formBuilder->add($this->name . '_newfield_lang',
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
        foreach ( $languages as $lang){
            $params['data'][$this->name . '_newfield_lang'][$lang['id_lang']] = 'Custom value for lang '.$lang['iso_code'];
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
    private function updateCustomerPrivilegeCode(array $params)
    {
        $customerId = $params['id'];
        /** @var array $customerFormData */
        $customerFormData = $params['form_data'];
        $PrivilegeCodeValue = (string) $customerFormData['privilege_code'];

        $PrivilegeCodeId = $this->get('ps_sbu_privilege.repository.privilege_code')->findIdByCustomer($customerId);

        $privilegeCode = new PrivilegeCode($PrivilegeCodeId);
        if (0 >= $privilegeCode->id) {
            $privilegeCode = $this->createPrivilegeCode($customerId);
        }
        $privilegeCode->privilege_code = $PrivilegeCodeValue;

        try {
            if (false === $privilegeCode->update()) {
                throw new CannotUpdatePrivilegeCodeValueException(
                    sprintf('Failed to change privilege code with id "%s"', $privilegeCode->id)
                );
            }
        } catch (PrestaShopException $exception) {
            throw new CannotUpdatePrivilegeCodeValueException(
                'An unexpected error occurred when updating privilege code'
            );
        }
    }

    /**
     * Creates a PrivilegeCode.
     *
     * @param int $customerId
     *
     * @return PrivilegeCode
     *
     * @throws CannotCreatePrivilegeCodeException
     */
    protected function createPrivilegeCode(int $customerId, string $privilege_code ="")
    {
        try {
            $privilegeCode = new PrivilegeCode();
            $privilegeCode->id_customer = $customerId;
            $privilegeCode->privilege_code = $privilege_code;

            if (false === $privilegeCode->save()) {
                throw new CannotCreatePrivilegeCodeException(
                    sprintf(
                        'An error occurred when creating privilegeCode with customer id "%s"',
                        $customerId
                    )
                );
            }
        } catch (PrestaShopException $exception) {
            throw new CannotCreatePrivilegeCodeException(
                sprintf(
                    'An unexpected error occurred when creating privilegeCode with customer id "%s"',
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

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
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
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
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
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'SBU_PRIVILEGE_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'SBU_PRIVILEGE_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'SBU_PRIVILEGE_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Select group for commercial'),
                        'name' => 'SBU_PRIVILEGE_COMMERCIAL_GROUP_ID',
                        'label' => $this->l('Group for commercials'),
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
            'SBU_PRIVILEGE_LIVE_MODE' => Configuration::get('SBU_PRIVILEGE_LIVE_MODE', true),
            'SBU_PRIVILEGE_ACCOUNT_EMAIL' => Configuration::get('SBU_PRIVILEGE_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'SBU_PRIVILEGE_ACCOUNT_PASSWORD' => Configuration::get('SBU_PRIVILEGE_ACCOUNT_PASSWORD', null),
            'SBU_PRIVILEGE_COMMERCIAL_GROUP_ID' => Configuration::get('SBU_PRIVILEGE_COMMERCIAL_GROUP_ID', null),
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
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
}
