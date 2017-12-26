<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information. 
 *
 * @author    luca.ioffredo93@gmail.com
 * @copyright 2017 luca.ioffredo93@gmail.com 
 * @license   luca.ioffredo93@gmail.com
 * @category  PrestaShop Module 
 * Description
 *
 *  
 */

if(!defined('_PS_VERSION_'))
    exit;

class CreateCoupon extends Module
{
        const PERCENT = 1;
        const AMOUNT = 2;
        const FREE_SHIPPING = 3;
        const VOUCHER_PREFIX = 'CRC'; 
        protected $table_module = '';
        
	public function __construct()
	{
            $this->name = 'createcoupon';
            $this->tab = 'advertising_marketing';
            $this->version = '1.2';
            $this->bootstrap = true;
            $this->author = 'Luca Ioffredo';
            $this->need_instance = 0;		
            parent::__construct(); 
            $this->displayName = $this->l('Create Coupon');
            $this->description = $this->l('Create a coupons\'s list automatically to distribuite to your customers');
            $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');                
            $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}
		
	public function install()
	{
            $hookdefault = 'displayBackOfficeHeader';
//            if ( _PS_VERSION_ >= '1.7')
//            {
//                $hookdefault = 'displayBackOfficeCategory';
//            }
            /* If a secure key doesn't exists then I create it. This prevent execution strange of the cron */  
            return (parent::install() && $this->CreateTabs()
                    && $this->registerHook($hookdefault) 
//                    && $this->registerHook('displayHeader')
//                    && $this->registerHook('displayAdminOrder')
                    && $this->installDB() 
                    && Configuration::updateValue('CREATECOUPON_CRON_SECURE_KEY', md5( _COOKIE_KEY_.time())));
	}
        
        public function uninstall()
	{ 
            $hookdefault = 'displayBackOfficeHeader';
//            if ( _PS_VERSION_ >= '1.7')
//            {
//                $hookdefault = 'displayBackOfficeCategory';
//            }
            $idtabs = array();
            $idtabs[] = Tab::getIdFromClassName("AdminCreateCoupon");
            foreach ($idtabs as $tabid):
                if ($tabid) {
                    $tab = new Tab($tabid);
                    $tab->delete();
                }
            endforeach;
            if (!parent::uninstall() 
                    || !$this->unregisterHook($hookdefault) 
//                    || !$this->unregisterHook('displayHeader')
//                    || !$this->unregisterHook('displayAdminOrder')
                    || !$this->uninstallDB() 
                    || !Configuration::deleteByName('CREATECOUPON_CRON_SECURE_KEY')) {
                return false;
            }
            return true;
	}
		
        public function installDB()
	{
            $sql = "
            CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."createcoupon_campaign` (
                    `id_createcoupon_campaign` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
                    `name` varchar(50) NOT NULL,
                    `numbers_voucher` INT(10) NOT NULL DEFAULT '1',
                    `voucher_prefix` varchar(50) NOT NULL,
                    `voucher_amount` varchar(50) NOT NULL,
                    `voucher_amount_type` varchar(50) NOT NULL,
                    `voucher_day` varchar(50) NOT NULL,
                    `date_add` DATETIME NOT NULL, 
                    `active` tinyint(1) NOT NULL,
                    PRIMARY KEY (`id_createcoupon_campaign`),
                    INDEX index_id_createcoupon (`id_createcoupon_campaign`)
            ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8 ; 
            
            CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."createcoupon_campaign_cart_rule` (
                    `id_createcoupon_campaign_cart_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
                    `id_createcoupon_campaign` int(10) UNSIGNED,
                    `id_cart_rule` int(10) UNSIGNED,
                    `date_add` DATETIME NOT NULL, 
                    PRIMARY KEY (`id_createcoupon_campaign_cart_rule`),
                    INDEX index_id_createcoupon_campaign_cart_rule (`id_createcoupon_campaign_cart_rule`)
            ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8 ; 
                
            CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."createcoupon_campaign_shop` (
                    `id_createcoupon_campaign` int(11) NOT NULL,
                    `id_shop` int(11) NOT NULL
                ) ENGINE="._MYSQL_ENGINE_.";";
            return Db::getInstance()->Execute($sql); 
	}
        
        public function uninstallDB()
	{ 
            return 
                Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'createcoupon_campaign`;')
                    &&
                Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'createcoupon_campaign_cart_rule`;')
                    &&
                Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'createcoupon_campaign_shop`;');
	} 
        
        private function CreateTabs() 
	{
            $langs = Language::getLanguages();
            $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
            $smarttab = new Tab();
            $smarttab->class_name = "AdminCreateCoupon";
            $smarttab->module = "createcoupon";
            $smarttab->id_parent = 0;
            foreach ($langs as $l) {
                $smarttab->name[$l['id_lang']] = $this->l('Create Coupon');
            }
            $smarttab->save();
            $this->tab_id = $smarttab->id;
            return true;
        }
        
        public function getContent()
        {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCreateCoupon', false).'&token='.Tools::getAdminTokenLite('AdminCreateCoupon'));
        }
        
        public function hookDisplayBackOfficeHeader()
	{
            $this->context->controller->addCss($this->_path.'views/css/tab.css');
            $this->context->controller->addJs($this->_path.'views/js/createcoupon.js');
	}
     
	public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ( _PS_VERSION_ >= '1.7') {
            return Context::getContext()->getTranslator()->trans($string);
        } else {
            return parent::l($string, $class, $addslashes, $htmlentities);
        }
    }
	 
        public function hookDisplayHeader()
	{ 
            
	}
	 
	public function hookdisplayAdminOrder()
	{ 
            
	}
	 
}

