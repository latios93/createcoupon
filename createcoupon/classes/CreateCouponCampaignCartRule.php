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

class CreateCouponCampaignCartRule extends ObjectModel {
	
	public $id_createcoupon_campaign_cart_rule;
	public $id_createcoupon_campaign; 
	public $id_cart_rule;  
        public $date_add;
	
	public static $definition = array(
            'table' => 'createcoupon_campaign_cart_rule',
            'primary' => 'id_createcoupon_campaign_cart_rule',
            'multilang' => false,
            'fields' => array(
                'id_createcoupon_campaign_cart_rule' => array(
                    'type' => ObjectModel::TYPE_INT				
                ), 
                'id_createcoupon_campaign' => array(
                    'type' => ObjectModel::TYPE_INT				
                ), 
                'id_cart_rule' => array(
                    'type' => ObjectModel::TYPE_INT				
                ),
                'date_add' => array(
                    'type' => ObjectModel::TYPE_DATE
                )                
            )
	);
	
	// Override construct to link object to voucher object fields
	public function __construct($id = null, $id_lang = null, $id_shop = null)
	{
            parent::__construct($id,$id_lang,$id_shop);	
            $this->code = false;
            $this->active = false;
            $this->loaded = false;
	}	
	
        public function active($state=false)
        {
            $state = (int) $state;
            $sql  = 'UPDATE `'._DB_PREFIX_.'cart_rule` AS C '
                    . ' SET `active`= '.$state
                    . ' WHERE C.id_cart_rule = '.(int) $this->id_cart_rule;
            return $this->loaded && (int) $this->id_cart_rule > 0 && (Db::getInstance()->execute($sql)); 
        }
        
        public function delete() 
        {
            if((int) $this->id_createcoupon_campaign_cart_rule > 0 && (int) $this->id_createcoupon_campaign > 0)
            {
                $cartrule = new CartRule();
                $cartrule->id = $this->id_cart_rule;
                $sql  = 'UPDATE `'._DB_PREFIX_.'createcoupon_campaign` AS C '
                        . ' SET `numbers_voucher`= (SELECT COUNT(*) FROM `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` AS CC WHERE C.id_createcoupon_campaign = CC.id_createcoupon_campaign) '
                        . ' WHERE C.id_createcoupon_campaign = '.(int) $this->id_createcoupon_campaign;
                return $cartrule->delete() && parent::delete() && (Db::getInstance()->execute($sql));
            }
            else 
            {
                return false;
            }
        }
        
        public static function deleteByCampaign($id_createcoupon_campaign)
        {
            if((int) $id_createcoupon_campaign > 0)
            {
                $list = CreateCouponCampaignCartRule::getByCampaign($id_createcoupon_campaign);
                if($list) 
                {
                    $res = true;
                    foreach($list as $campaign_cart_rule) 
                    {
                        $cart_rule = new CreateCouponCampaignCartRule();
                        $cart_rule->id = $campaign_cart_rule['id_createcoupon_campaign_cart_rule'];
                        $cart_rule->id_createcoupon_campaign_cart_rule = $campaign_cart_rule['id_createcoupon_campaign_cart_rule'];
                        $cart_rule->id_cart_rule = $campaign_cart_rule['id_cart_rule'];
                        $cart_rule->date_add = $campaign_cart_rule['date_add']; 
                        $cart_rule->id_createcoupon_campaign = $campaign_cart_rule['id_createcoupon_campaign']; 

                        $cart_rule->active = $campaign_cart_rule['active']; 
                        $cart_rule->code = $campaign_cart_rule['code']; 
                        $cart_rule->loaded = true;
                        $res = $cart_rule->delete();
                    }
                    return $res;
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
        
        public static function getByCampaign($id_createcoupon_campaign) 
        { 
            if((int) $id_createcoupon_campaign > 0)
            {
                $sql  = 'SELECT CC.*, C.code, C.active '
                    . ' FROM `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` CC '
                    . ' LEFT OUTER JOIN `'._DB_PREFIX_.'cart_rule` AS C '
                    . ' ON CC.id_cart_rule = C.id_cart_rule '
                    . ' WHERE CC.id_createcoupon_campaign = '.$id_createcoupon_campaign;
                return (Db::getInstance()->executeS($sql));
            }
            else 
            {
                return false;
            }
        }
        
        public function getById($id) 
        {
            $this->id_createcoupon_campaign_cart_rule = $id;
            if((int) $this->id_createcoupon_campaign_cart_rule > 0)
            {
                $sql  = 'SELECT CC.*, C.code, C.active '
                    . ' FROM `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` CC '
                    . ' LEFT OUTER JOIN `'._DB_PREFIX_.'cart_rule` AS C '
                    . ' ON CC.id_cart_rule = C.id_cart_rule '
                    . ' WHERE CC.id_createcoupon_campaign_cart_rule = '.$this->id_createcoupon_campaign_cart_rule;
                if( $data = (Db::getInstance()->executeS($sql))) 
                {
                    $this->date_add = $data[0]['date_add'];
                    $this->id_cart_rule = $data[0]['id_cart_rule'];
                    $this->id_createcoupon_campaign = $data[0]['id_createcoupon_campaign'];
                    $this->id_createcoupon_campaign_cart_rule = $data[0]['id_createcoupon_campaign_cart_rule'];
                    
                    $this->active = $data[0]['active']; 
                    $this->code = $data[0]['code']; 
                    $this->loaded = true;
                    return true;
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
        
        public function getCampaign() 
        {
            if((int) $this->id_createcoupon_campaign_cart_rule > 0)
            {
                $sql  = 'SELECT C.* '
                    . ' FROM `'._DB_PREFIX_.'createcoupon_campaign` AS C'
                    . ' JOIN `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` AS CC '
                    . ' ON CC.id_createcoupon_campaign  = C.id_createcoupon_campaign  '
                    . ' WHERE CC.id_createcoupon_campaign_cart_rule = '.$this->id_createcoupon_campaign_cart_rule;
                return (Db::getInstance()->executeS($sql));
            }
            else 
            {
                return false;
            }
        }
        
        public static function getHistory($id_last_campaign=false)
	{
            $where = "";
            if($id_last_campaign!==false && (int) $id_last_campaign > 0) 
            {
                $where = " WHERE id_createcoupon_campaign = $id_last_campaign ";
            }
            $sql  = 'SELECT CC.*, C.quantity_per_user, C.code, C.active '
                    . ' FROM `'._DB_PREFIX_.'createcoupon_campaign_cart_rule` AS CC '
                    . ' LEFT OUTER JOIN `'._DB_PREFIX_.'cart_rule` AS C '
                    . ' ON CC.id_cart_rule = C.id_cart_rule '
                    . $where
                    . ' ORDER BY id_createcoupon_campaign_cart_rule DESC';
            return (Db::getInstance()->executeS($sql));
	}
}
