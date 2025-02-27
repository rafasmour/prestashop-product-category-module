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
    public function install()
    {
        Configuration::updateValue('PRODCATEGORY_DISPLAY', true);
        Configuration::updateValue('PRODCATEGORY_HOOK', 'hookDisplayProductAdditionalInfo');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayProductExtraContent') &&
            $this->registerHook('displayProductAdditionalInfo');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PRODCATEGORY_DISPLAY');
        Configuration::deleteByName('PRODCATEGORY_HOOK');

        return parent::uninstall() &&
            $this->unregisterHook('header') &&
            $this->unregisterHook('displayBackOfficeHeader') &&
            $this->unregisterHook('displayProductExtraContent') &&
            $this->unregisterHook('displayProductAdditionalInfo');
    }
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitProdCategoryModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }
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
                        'name' => 'PRODCATEGORY_HOOK',
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
                        'label' => $this->l('Display Categories'),
                        'name' => 'PRODCATEGORY_DISPLAY',
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
    protected function getConfigFormValues()
    {
        return array(
            'PRODCATEGORY_DISPLAY' => Configuration::get('PRODCATEGORY_DISPLAY'),
            'PRODCATEGORY_HOOK' => Configuration::get('PRODCATEGORY_HOOK')
        );
    }
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function getProductCategories($product)
    {
        $categoryIds = $product->getProductCategories($product->id);
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
        $display = $conf['PRODCATEGORY_DISPLAY'];
        $displayHook = $conf['PRODCATEGORY_HOOK'];
        if(!$display || $displayHook !== 'hookDisplayProductExtraContent'){
            return [];
        }
        echo('hey');
        $product = $params['product'];
        $categories = $this->getProductCategories($product);
        if(empty($categories)) return [];

        $this->context->smarty->assign('categories', $categories);
        $templatePath = $this->local_path . '/views/templates/custom/displayCategoriesExtra.tpl';
        $content = $this->context->smarty->fetch($templatePath);
        $extraContent = new PrestaShop\PrestaShop\Core\Product\ProductExtraContent();
        $extraContent->setTitle($this->l('Categories'))
            ->setContent($content);

        return [$extraContent];
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $conf = $this->getConfigFormValues();
        $display = $conf['PRODCATEGORY_DISPLAY'];
        $displayHook = $conf['PRODCATEGORY_HOOK'];
        if(!$display || $displayHook !== 'hookDisplayProductAdditionalInfo'){
            return [];
        }
        /**
         * in displayProductAdditionalInfo
         * product of params is an instance of ProductLazyArray
         * that's why We take the Id and then make a new Product Object
         * var_dump($params['product']);
         */
        $prodId = $params['product']->id;
        $product = new Product($prodId);
        $categories = $this->getProductCategories($product);

        if(empty($categories)) return [];
        $this->context->smarty->assign('additionalInfo', true);
        $this->context->smarty->assign('categories', $categories);
        $templatePath = $this->local_path . '/views/templates/custom/displayCategoriesAdditional.tpl';
        return $this->context->smarty->fetch($templatePath);
    }
}
