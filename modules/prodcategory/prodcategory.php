<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProdCategory extends Module
{

    public function __construct()
    {
        $this->name = 'prodcategory';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Rafail Mourouzidis';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Product Category');
        $this->description = $this->l('Displays the categories the product belongs to in the product page.');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('PRODCATEGORY_DISPLAY_CATEGORIES', true);
        Configuration::updateValue('PRODCATEGORY_SHOW_CATEGORIES', 'hookDisplayProductAdditionalInfo');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayProductExtraContent');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PRODCATEGORY_DISPLAY_CATEGORIES');
        Configuration::deleteByName('PRODCATEGORY_SHOW_CATEGORIES');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitProdCategoryModule')) == true) {
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
        $helper->submit_action = 'submitProdCategoryModule';
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
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Where should the categories be displayed'),
                        'name' => 'PRODCATEGORY_DISPLAY_CATEGORIES',
                        'label' => $this->l('Display Hook'),
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => 'hookDisplayProductAdditionalInfo',
                                    'name' => $this->l('Additional Info')
                                ),
                                array(
                                    'id' => 'hookDisplayProductExtraContent',
                                    'name' => $this->l('Extra Content')
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Display categories'),
                        'name' => 'PRODCATEGORY_SHOW_CATEGORIES',
                        'is_bool' => true,
                        'desc' => $this->l('Select wrether the categories should be visible.'),
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
                    )
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
            'PRODCATEGORY_DISPLAY_CATEGORIES' => Configuration::get('PRODCATEGORY_DISPLAY_CATEGORIES'),
            'PRODCATEGORY_SHOW_CATEGORIES' => Configuration::get('PRODCATEGORY_SHOW_CATEGORIES')
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
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
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

    public function getProductCategories($product)
    {
        $categoryIds = $product->getCategories($product->id);
        $categories = [];
        $lang = $this->context->language->id;

        foreach ($categoryIds as $id) {
            $category = new Category($id, $lang);
            $categories[] = [
                'id' => $category->id,
                'name' => $category->name,
                'url' => $this->context->link->getCategoryLink($category->id)
            ];
        }
        return $categories;
    }

    public function hookDisplayProductExtraContent($params)
    {
        $conf = $this->getConfigFormValues();
        $display = $conf['PRODCATEGORY_SHOW_CATEGORIES'];
        $displayHook = $conf['PRODCATEGORY_DISPLAY_CATEGORIES'];
        if(!$display || $displayHook != 'hookDisplayProductExtraContent'){
            return;
        }
        $product = $params['product'];
        $categories = $this->getProductCategories($product);

        if(empty($categories)) return ;

        $this->context->smarty->assign('categories', $categories);
        var_dump($categories[0]->link);
        $templatePath = __DIR__ . '/views/templates/custom/displayCategories.tpl';
        var_dump($templatePath);
        $content = $this->context->smarty->fetch($templatePath);
        $extraContent = new PrestaShop\PrestaShop\Core\Product\ProductExtraContent();
        $extraContent->setTitle($this->l('Categories'))
            ->setContent($content);

        return [$extraContent];
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $conf = $this->getConfigFormValues();
        $display = $conf['PRODCATEGORY_SHOW_CATEGORIES'];
        $displayHook = $conf['PRODCATEGORY_DISPLAY_CATEGORIES'];
        if(!$display || $displayHook != 'hookDisplayProductAdditionalInfo'){
            return;
        }
        $product = $params['product'];
        $categories = $this->getProductCategories($product);
        $this->context->smarty->assign('categories', $categories);
    }
}
