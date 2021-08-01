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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

//use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction;
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
        $this->version = '1.0.1';
        $this->author = 'Stéphane Burlet';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Manage privileges');
        $this->description = $this->l('Manages privilege codes. Every commercial in a special group will receive (from the webmaster) a privilege code. They give this code to their customers. The customers, when they sign in, will be asked for this privilege code (new field "privilege_code" in customer table). This privilege code allows the commercial to receive commissions on every sale from its customers. It\'s better to affect the customer into a group "Privileged Customer" and affect to this group cart rules. The configuration determines which group will be the "Commercial" group. Recommanded : "Commercial".');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall privilege? This will delete all privilege codes.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        error_log("install ".$this->version);

        Configuration::updateValue('SBU_PRIVILEGE_COMMERCIAL_GROUP_ID', null);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('additionalCustomerFormFields') &&
            $this->registerHook('actionCustomerGridDefinitionModifier') &&
            $this->registerHook('actionCustomerGridQueryBuilderModifier');
    }

    public function uninstall()
    {
        error_log("uninstall ".$this->version);

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
     * Add column privilege_code in admin customers list in BO
     */
    public function hookActionCustomerGridDefinitionModifier(array $params)
    {
        $a="hookActionCustomerGridDefinitionModifier - BURLET - ";
        //echo "$a";
        $definition = $params['definition'];
        //$a=$a.print_r($definition->getColumns(),true);
        error_log($a);

        // Add column
        $columns = $definition->getColumns();
        $ColumnPrivilegeCode = new DataColumn('privilege_code');
        $ColumnPrivilegeCode->setName($this->l('Privilege Code'));
        $ColumnPrivilegeCode->setOptions([
                'field' => 'privilege_code',
        ]);
        $columns->addAfter('company', $ColumnPrivilegeCode);

        // Add filter
        /** @var FilterCollectionInterface $filters */
        $filters = $definition->getFilters();
        $filterPrivilegeCode = new Filter('privilege_code', TextType::class);
        $filterPrivilegeCode->setAssociatedColumn('privilege_code');
        $filters->add($filterPrivilegeCode);    
    }
    
    /**
     * Query column privilege_code in admin customers list in BO
     */
    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        $a="hookActionCustomerGridQueryBuilderModifier - BURLET - ";
        //echo "$a";
        error_log($a);


        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];
        $searchQueryBuilder->addSelect('c.privilege_code');
                       //->from(_DB_PREFIX_.'customer');

        $countQueryBuilder = $params['count_query_builder'];
        // So the pagination and the number of customers
        // retrieved will be right.
        $countQueryBuilder->addSelect('c.privilege_code');
        //->from(_DB_PREFIX_.'customer');
                             
        /** @var SearchCriteriaInterface $searchCriteria */        
        $searchCriteria = $params['search_criteria'];
        $strictComparisonFilters = [
            'privilege_code' => 'privilege_code',
        ];
        //        error_log(print_r($searchCriteria->getFilters(),true));
        $filters = $searchCriteria->getFilters();
        foreach ($filters as $filterName => $filterValue) {
            if (isset($strictComparisonFilters[$filterName])) {
                $alias = $strictComparisonFilters[$filterName];
                $searchQueryBuilder->andWhere("$alias LIKE :$filterName");
                $searchQueryBuilder->setParameter($filterName, '%'.$filterValue.'%');
                continue;
            }
        }      
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
