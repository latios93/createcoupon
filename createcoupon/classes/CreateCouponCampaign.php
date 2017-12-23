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

require_once (dirname(__FILE__) . '/../createcoupon.php');
require_once (dirname(__FILE__) . '/../classes/CreateCouponCampaignCartRule.php');

class CreateCouponCampaign extends ObjectModel {
	
	public $id_createcoupon_campaign;
	public $name; 
	public $active; 
	public $voucher_prefix;
	public $voucher_day;
	public $id_discount_type=false;
	public $voucher_amount;	
        public $voucher_amount_type;
        public $numbers_voucher;
        public $minimal_order;
        public $quantity_per_user;
        public $cart_rule_restriction;
        public $id_cart_rules = array();
        public $date_add;
	
	public static $definition = array(
            'table' => 'createcoupon_campaign',
            'primary' => 'id_createcoupon_campaign',
            'multilang' => false,
            'fields' => array(
                'id_createcoupon_campaign' => array(
                        'type' => ObjectModel::TYPE_INT				
                ),
                'name' => array(
                        'type' => ObjectModel::TYPE_STRING,
                        'required' => true
                ),
                'numbers_voucher' => array(
                        'type' => ObjectModel::TYPE_INT				
                ),
                'voucher_prefix' => array(
                        'type' => ObjectModel::TYPE_STRING
                ),
                'voucher_day' => array(
                        'type' => ObjectModel::TYPE_INT
                ),
                'voucher_amount_type' => array(
                        'type' => ObjectModel::TYPE_INT
                ),
                'voucher_amount' => array(
                        'type' => ObjectModel::TYPE_INT
                ),
                'date_add' => array(
                        'type' => ObjectModel::TYPE_DATE
                ),
                'active' => array(
                        'type' => ObjectModel::TYPE_BOOL,
                        'required' => true
                )
            )
	);
	
	// Override construct to link object to voucher object fields
	public function __construct($id = null, $id_lang = null, $id_shop = null)
	{
            parent::__construct($id,$id_lang,$id_shop);	
            $this->date_from = false;
            $this->date_to = false;
            if($id!==null && $id!==false && (int) $id > 0)
            {
                $this->id_cart_rules = $this->getCartRulesListById($id);
            }
	}	
	
        public function getCartRulesListById($id)
        { 
            if((int) $id > 0)
            {
                $sql  = 'SELECT CC.*, C.cart_rule_restriction, C.date_from, C.date_to, C.quantity_per_user, C.minimum_amount, '
                        . ' C.free_shipping, C.reduction_percent, C.reduction_amount, C.code, C.active '
                    . ' FROM `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` CC '
                    . ' LEFT OUTER JOIN `'._DB_PREFIX_.'cart_rule` AS C '
                    . ' ON CC.id_cart_rule = C.id_cart_rule '
                    . ' WHERE CC.id_createcoupon_campaign = '.$id;
                if( $data = (Db::getInstance()->executeS($sql))) 
                {
                    foreach($data as $campaign_cart_rule)
                    {
                        if($campaign_cart_rule['active']) 
                        {
                            $this->id_cart_rules[] = $campaign_cart_rule['id_createcoupon_campaign_cart_rule']; 
                            if($this->id_discount_type===false) 
                            {
                                $this->date_from = $campaign_cart_rule['date_from']; 
                                $this->date_to = $campaign_cart_rule['date_to']; 
                                $this->quantity_per_user = $campaign_cart_rule['quantity_per_user']; 
                                $this->minimal_order = $campaign_cart_rule['minimum_amount']; 
                                $this->cart_rule_restriction = $campaign_cart_rule['cart_rule_restriction']; 
                                if((bool) $campaign_cart_rule['free_shipping'])
                                {
                                    $this->id_discount_type = CreateCoupon::FREE_SHIPPING;
                                }
                                elseif((float) $campaign_cart_rule['reduction_amount'] > 0 )
                                {
                                    $this->id_discount_type = CreateCoupon::AMOUNT;
                                }
                                else
                                {
                                    $this->id_discount_type = CreateCoupon::PERCENT;
                                }
                            }
                        }
                    }
                    return $this->id_cart_rules;
                }
                else 
                {
                    return false;
                }
            }
            else 
            {
                return false;
            }
        }
        
	public function createCouponsList() {
            if((int) $this->numbers_voucher <= 0) {
                return false;
            }
            $CreateCoupon = new CreateCoupon();
            for($i=0;$i<(int) $this->numbers_voucher;$i++) 
            {
                $voucher = new CartRule();
                $voucher->id_discount_type = $this->id_discount_type;
                if(Tools::strlen($this->name)>0) {
                    $cart_rule_name = $this->name;
                }
                else {
                    $cart_rule_name = $CreateCoupon->l('Coupon Campaign');
                } 
                array ('1'=>$cart_rule_name, '2'=>$cart_rule_name);
                $languages = Language::getLanguages();
                $array_name = array(); 
                foreach ($languages as $language) 
                {
                    $array_name[$language['id_lang']]= $cart_rule_name;
                } 
                $voucher->name = $array_name;
                $voucher->description = $CreateCoupon->l('Coupon generated to campaign!');
                $voucher->id_currency = Configuration::get('PS_CURRENCY_DEFAULT');
                $voucher->quantity = 1;
                $voucher->quantity_per_user = $this->quantity_per_user;
                $voucher->cart_rule_restriction = true;
                $voucher->date_from = date('Y-m-d');
                $voucher->date_to = strftime('%Y-%m-%d', strtotime('+'.$this->voucher_day.' day'));
                $voucher->minimum_amount = $this->minimal_order;
                $voucher->active = true;
                $voucher->reduction_tax = true;
                
                switch((int) $voucher->id_discount_type)
                {
                    case CreateCoupon::FREE_SHIPPING:
                        $voucher->free_shipping = true;
                        break;
                    case CreateCoupon::PERCENT: 
                        $voucher->reduction_percent = $this->voucher_amount;
                        break;
                    case CreateCoupon::AMOUNT:         
                        $voucher->reduction_amount = $this->voucher_amount;
                        break;
                }
                $code_length = 5;
                $voucher->code = $this->voucher_prefix.Tools::strtoupper(Tools::passwdGen($code_length));
                while(CartRule::cartRuleExists($voucher->code))
                {
                    $voucher->code = $this->voucher_prefix.Tools::strtoupper(Tools::passwdGen($code_length++));
                }
                
                if ($voucher->add() && (int) $voucher->id > 0) 
                { 
                    $this->id_cart_rules[] = (int) $voucher->id;
                    
                }
            }
            return !empty($this->id_cart_rules);
        }

        public function saveHistoryCampaign() {
            if(empty($this->id_cart_rules)) return true;
            
            foreach($this->id_cart_rules as $id_cart_rule) {
                $campaign_cart_rule = new CreateCouponCampaignCartRule();
                $campaign_cart_rule->id_cart_rule = $id_cart_rule;
                $campaign_cart_rule->id_createcoupon_campaign = $this->id;  
                $campaign_cart_rule->save();
            }
            return true;
        }
        
        public function active($also_cart_rule=true)
        { 
            $sql = ' UPDATE `'._DB_PREFIX_.'createcoupon_campaign` AS C '
                    . ' SET `active`= NOT active '
                    . ' WHERE C.id_createcoupon_campaign = '.(int) $this->id_createcoupon_campaign.' ;';
            if($also_cart_rule===true)
            {
                $sql .= ' UPDATE `'._DB_PREFIX_.'cart_rule` AS C '
                    . ' SET `active`= (SELECT CC.active FROM `'._DB_PREFIX_.'createcoupon_campaign` AS CC WHERE CC.id_createcoupon_campaign = '.(int) $this->id_createcoupon_campaign.') '
                    . ' WHERE C.id_cart_rule IN '
                    . ' (SELECT id_cart_rule FROM `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` AS CC '
                    . ' WHERE CC.id_createcoupon_campaign = '.(int) $this->id_createcoupon_campaign.');'; 
            } 
            return (int) $this->id_createcoupon_campaign > 0 && (Db::getInstance()->execute($sql)); 
        }
}
