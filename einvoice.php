<?php
/**
 * 2007-2022 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

if (!defined('_PS_VERSION_')) {
    exit;
}
require('vendor/autoload.php');
require_once dirname(__FILE__) . '/classes/EInvoiceAddress.php';

class Einvoice extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'einvoice';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'cdigruttola';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Electronic Invoice - fields', [], 'Modules.Einvoice.Einvoice');
        $this->description = $this->trans('This module adds the new fields for E-Invoice in Customer Address', [], 'Modules.Einvoice.Einvoice');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Einvoice.Einvoice');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install(): bool
    {
        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHooks();
    }

    public function registerHooks(): bool
    {
        if (!$this->registerHook('header') ||
            !$this->registerHook('newOrder') ||
            !$this->registerHook('actionCustomerAddressFormBuilderModifier') ||
            !$this->registerHook('actionAdminAddressesFormModifier') ||
            !$this->registerHook('actionValidateCustomerAddressForm') ||
            !$this->registerHook('actionObjectAddressAddAfter') ||
            !$this->registerHook('actionObjectAddressUpdateAfter') ||
            !$this->registerHook('actionObjectAddressDeleteAfter') ||
            !$this->registerHook('actionObjectCustomerAddressAddAfter') ||
            !$this->registerHook('actionObjectCustomerAddressUpdateAfter') ||
            !$this->registerHook('actionSubmitCustomerAddressForm') ||
            !$this->registerHook('actionAfterUpdateCustomerAddressFormHandler') ||
            !$this->registerHook('actionAfterCreateCustomerAddressFormHandler') ||
            !$this->registerHook('addWebserviceResources')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall(): bool
    {

        include(dirname(__FILE__) . '/sql/uninstall.php');

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
        if (((bool)Tools::isSubmit('submitEinvoiceModule')) == true) {
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
        $helper->submit_action = 'submitEinvoiceModule';
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
                    'title' => $this->trans('Settings', [], 'Modules.Einvoice.Einvoice'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('PEC field required', [], 'Modules.Einvoice.Einvoice'),
                        'name' => 'EINVOICE_PEC_REQUIRED',
                        'is_bool' => true,
                        'desc' => $this->trans('This options set the PEC field mandatory only for Italian customer.', [], 'Modules.Einvoice.Einvoice'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Modules.Einvoice.Einvoice')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Modules.Einvoice.Einvoice')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('SDI field required', [], 'Modules.Einvoice.Einvoice'),
                        'name' => 'EINVOICE_SDI_REQUIRED',
                        'is_bool' => true,
                        'desc' => $this->trans('This options set the SDI field mandatory only for Italian customer.', [], 'Modules.Einvoice.Einvoice'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Modules.Einvoice.Einvoice')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Modules.Einvoice.Einvoice')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', [], 'Modules.Einvoice.Einvoice'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $id_shop = (int)$this->context->shop->id;
        return array(
            'EINVOICE_PEC_REQUIRED' => Configuration::get('EINVOICE_PEC_REQUIRED', null, null, $id_shop),
            'EINVOICE_SDI_REQUIRED' => Configuration::get('EINVOICE_SDI_REQUIRED', null, null, $id_shop),
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
        $id_shop = (int)$this->context->shop->id;

        $sdi_required = (int)Configuration::get('EINVOICE_PEC_REQUIRED', null, null, $id_shop);
        $pec_required = (int)Configuration::get('EINVOICE_SDI_REQUIRED', null, null, $id_shop);

        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');

        Media::addJsDef(
            array(
                'sdi_required' => (int)$sdi_required,
                'pec_required' => (int)$pec_required,
            )
        );
    }

    public function hookActionCustomerAddressFormBuilderModifier($params)
    {
        if (!$this->active) {
            return;
        }

        $id_shop = $this->context->shop->id;

        $sdi_required = Configuration::get('EINVOICE_SDI_REQUIRED', null, null, $id_shop);
        $pec_required = Configuration::get('EINVOICE_SDI_REQUIRED', null, null, $id_shop);

        $id_address = isset($params['id']) ? (int)$params['id'] : null;
        $obj = new EInvoiceAddress($id_address);

        $formBuilder = $params['form_builder'];

        $formBuilder->add(
            'sdi',
            \Symfony\Component\Form\Extension\Core\Type\TextType::class,
            [
                'label' => $this->trans('SDI Code', [], 'Modules.Einvoice.Einvoice'),
                'required' => $sdi_required,
                'constraints' => [
                    new PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml(),
                    new \Symfony\Component\Validator\Constraints\Length([
                        'max' => 7,
                        'maxMessage' => $this->trans('Max caracters allowed : 7', [], 'Modules.Einvoice.Einvoice'),
                    ]),
                ],
            ]
        );

        $params['data']['sdi'] = Tools::strtoupper((string)$obj->sdi);

        $formBuilder->add(
            'pec',
            PrestaShopBundle\Form\Admin\Type\EmailType::class,
            [
                'label' => $this->trans('PEC Address', [], 'Modules.Einvoice.Einvoice'),
                'required' => $pec_required,
                'constraints' => [
                    new PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml(),
                ],
            ]
        );

        $params['data']['pec'] = (string)$obj->pec;

        $formBuilder->add(
            'pa',
            PrestaShopBundle\Form\Admin\Type\SwitchType::class,
            [
                'label' => $this->trans('Public Administration', [], 'Modules.Einvoice.Einvoice'),
                'required' => false,
            ]
        );

        $params['data']['pa'] = (int)$obj->pa == 1;

        $formBuilder->add(
            'customertype',
            ChoiceType::class,
            [
                'choices' => [
                    $this->trans('Private', [], 'Modules.Einvoice.Einvoice') => 0,
                    $this->trans('Company', [], 'Modules.Einvoice.Einvoice') => 1,
                ],
                'required' => true,
                'label' => $this->trans('Customer Type', [], 'Modules.Einvoice.Einvoice'),
            ]
        );

        $params['data']['customertype'] = (int)$obj->customertype;

        $formBuilder->setData($params['data']);
        unset($obj);
    }

    public function hookActionAdminAddressesFormModifier($params)
    {
        $switch = 'radio';
        if (version_compare(_PS_VERSION_, '1.6', '>=') === true) {
            $switch = 'switch';
        }

        foreach ($params['fields'][0]['form']['input'] as $key => $value) {
            if ($value['name'] == 'vat_number') {
                break;
            }
        }

        $part1 = array_slice($params['fields'][0]['form']['input'], 0, $key + 1);
        $part2 = array_slice($params['fields'][0]['form']['input'], $key + 1);

        $fields = array(
            array(
                'type' => 'text',
                'label' => $this->trans('PEC Email', [], 'Modules.Einvoice.Einvoice'),
                'name' => 'pec',
                'prefix' => "<i class='icon-envelope-o'></i>",
                'class' => 'fixed-width-xxl',
                'hint' => $this->trans('Invalid characters:', [], 'Modules.Einvoice.Einvoice') . ' <>;=#{}',
            ),
            array(
                'type' => 'text',
                'label' => $this->trans('SDI Code'),
                'name' => 'sdi',
                'class' => 'fixed-width-xxl',
                'hint' => $this->trans('Invalid characters:', [], 'Modules.Einvoice.Einvoice') . ' <>;=#{}',
            ),
            array(
                'type' => $switch,
                'label' => $this->trans('Public Administration', [], 'Modules.Einvoice.Einvoice'),
                'name' => 'pa',
                'class' => 't',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->trans('Yes', [], 'Modules.Einvoice.Einvoice')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->trans('No', [], 'Modules.Einvoice.Einvoice')
                    )
                ),
            ),
            array(
                'type' => $switch,
                'label' => $this->trans('Customer Type', [], 'Modules.Einvoice.Einvoice'),
                'name' => 'customertype',
                'class' => 't',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 0,
                        'label' => $this->trans('Private', [], 'Modules.Einvoice.Einvoice')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 1,
                        'label' => $this->trans('Company', [], 'Modules.Einvoice.Einvoice')
                    )
                ),
            ),
        );

        $params['fields'][0]['form']['input'] = array_merge($part1, $fields, $part2);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $id_address = (int)$params['object']->id;
        } else {
            $id_address = (int)Tools::getValue('id_address');
        }
        $obj = new EInvoiceAddress($id_address);

        $params['fields_value']['sdi'] = Tools::strtoupper((string)$obj->sdi);
        $params['fields_value']['pec'] = (string)$obj->pec;
        $params['fields_value']['pa'] = (int)$obj->pa;
        $params['fields_value']['customertype'] = (int)$obj->customertype;
        unset($obj);
    }


    public function hookActionValidateCustomerAddressForm($params)
    {
        $is_valid = true;

        $form = $params['form'];
        $pec = $form->getField('pec');
        if (isset($pec)) {
            $pec_value = $pec->getValue();
            if (!empty($pec_value) && !Validate::isEmail($pec_value)) {
                $is_valid &= false;
                $pec->addError($this->trans('Invalid email address format', [], 'Modules.Einvoice.Einvoice'));
            }
        }

        $sdi = $form->getField('sdi');
        if (isset($sdi)) {
            $sdi_value = $sdi->getValue();
            if (!empty($sdi_value) && Tools::strlen($sdi_value) != 7) {
                $is_valid &= false;
                $sdi->addError($this->trans('Invalid SDI Code', [], 'Modules.Einvoice.Einvoice'));
            }
        }

        return $is_valid;
    }

    public function hookActionAfterUpdateCustomerAddressFormHandler($params)
    {
        $this->retrieveValuesFromFormData($params);
    }

    public function hookActionAfterCreateCustomerAddressFormHandler($params)
    {
        $this->retrieveValuesFromFormData($params);
    }

    public function hookActionSubmitCustomerAddressForm($params)
    {
        if (!isset($params['address'])) {
            return false;
        }
        if (!isset($params['object'])) {
            $params['object'] = $params['address'];
        }
        $this->setAddressParams($params);
    }

    public function hookActionObjectCustomerAddressAddAfter($params)
    {
        $this->retrieveValuesFromHttpMethod($params);
    }

    public function hookActionObjectCustomerAddressUpdateAfter($params)
    {
        $this->retrieveValuesFromHttpMethod($params);
    }

    public function hookActionObjectAddressAddAfter($params)
    {
        $this->retrieveValuesFromCustomerAddress($params);
    }

    public function hookActionObjectAddressUpdateAfter($params)
    {
        if (isset($params['object']) && !$params['object']->deleted) {
            $this->retrieveValuesFromCustomerAddress($params);
        }
    }

    public function hookActionObjectAddressDeleteAfter($params)
    {
        $id_address = (int)$params['object']->id;
        $address = new Address((int)$id_address);
        if (!$address->isUsed()) {
            $eiaddress = new EInvoiceAddress($id_address);
            $eiaddress->delete();
        }
    }

    public function hookAddWebserviceResources($params)
    {
        if (Module::isEnabled('einvoice')) {
            $def = array(
                'pec' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isGenericName'),
                'sdi' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isGenericName'),
                'pa' => array('type' => ObjectModel::TYPE_INT, 'validate' => 'isUnsignedInt'),
                'customertype' => array('type' => ObjectModel::TYPE_INT, 'validate' => 'isUnsignedInt'),
            );
            Address::$definition['fields'] = array_merge(Address::$definition['fields'], $def);
            ksort(Address::$definition['fields']);
        }
        return true;
    }


    private function setAddressParams($params)
    {
        if (!$params['object']->id) {
            return;
        }

        $datas = array();
        $datas[$params['object']->id] = array(
            'customertype' => isset($params['object']->customertype) ? (string)$params['object']->customertype : '',
            'sdi' => isset($params['object']->sdi) ? (string)$params['object']->sdi : '',
            'pec' => isset($params['object']->pec) ? (string)$params['object']->pec : '',
            'pa' => isset($params['object']->pa) ? (int)$params['object']->pa : 0
        );

        foreach ($datas as $id_address => $data) {
            $customertype = isset($data['customertype']) ? trim((string)$data['customertype']) : 0;
            $sdi = isset($data['sdi']) ? trim((string)$data['sdi']) : '';
            $pec = isset($data['pec']) ? trim((string)$data['pec']) : '';
            $pa = isset($data['pa']) ? (int)$data['pa'] : 0;

            if (!$pa && empty($sdi)) {
                $address = new Address((int)$id_address);
                if (isset($address) && $address->id) {
                    $country = new Country((int)$address->id_country);
                    if (isset($country) && (string)$country->iso_code != 'IT') {
                        if (!empty($address->company) || !empty($address->vat_number)) {
                            $sdi = 'XXXXXXX';
                        } else {
                            $sdi = '0000000';
                        }
                    } else {
                        $sdi = '0000000';
                    }
                }
            }

            $eiaddress = new EInvoiceAddress();
            if ($id_address) {
                $eiaddress = new $eiaddress($id_address);
            }
            $eiaddress->id_address = (int)$id_address;
            $eiaddress->sdi = Tools::strtoupper((string)$sdi);
            $eiaddress->pec = (string)$pec;
            $eiaddress->pa = (int)$pa;
            $eiaddress->customertype = (int)$customertype;
            $eiaddress->save();
        }
    }

    /**
     * @param $params
     * @return void
     */
    private function retrieveValuesFromHttpMethod($params): void
    {
        $customertype = (int)Tools::getValue('customertype');
        $sdi = (string)Tools::getValue('sdi');
        $pec = (string)Tools::getValue('pec');
        $pa = (int)Tools::getValue('pa');

        $params['object']->customertype = (int)$customertype;
        $params['object']->sdi = (string)$sdi;
        $params['object']->pec = (string)$pec;
        $params['object']->pa = (int)$pa;

        $this->setAddressParams($params);
    }

    /**
     * @param $params
     * @return void
     */
    private function retrieveValuesFromFormData($params): void
    {
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            if (!isset($params['object'])) {
                $params['object'] = (object)null;
            }

            $params['object']->id = (int)$params['id'];
            $params['object']->customertype = isset($params['form_data']['customertype']) ? (string)$params['form_data']['customertype'] : '';
            $params['object']->sdi = isset($params['form_data']['sdi']) ? (string)$params['form_data']['sdi'] : '';
            $params['object']->pec = isset($params['form_data']['pec']) ? (string)$params['form_data']['pec'] : '';
            $params['object']->pa = isset($params['form_data']['pa']) ? (int)$params['form_data']['pa'] : 0;

            $this->setAddressParams($params);
        }
    }

    /**
     * @param $params
     * @return void
     */
    private function retrieveValuesFromCustomerAddress($params): void
    {
        $customer_address = Tools::getValue('customer_address');
        if (isset($customer_address) && !empty($customer_address)) {
            $params['object']->customertype = (int)$customer_address['customertype'];
            $params['object']->sdi = (string)$customer_address['sdi'];
            $params['object']->pec = (string)$customer_address['pec'];
            $params['object']->pa = (int)$customer_address['pa'];
        }
        $this->setAddressParams($params);
    }


}