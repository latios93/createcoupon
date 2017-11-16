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

class CreateCouponHistory extends ObjectModel { 
	public $id_createcoupon_campaign;
	public $id_cart_rule;
	public $code; 
	public $date_add; 
	
	public static $definition = array(	
            'table' => 'createcoupon_campaign',
            'primary' => 'id_createcoupon_campaign',
            'multilang' => false,
            'fields' => array(
                'id_createcoupon_campaign' => array(
                    'type' => ObjectModel::TYPE_INT				
                ),
                'voucher_amount' => array(
                    'type' => ObjectModel::TYPE_STRING				
                ),
                'voucher_prefix' => array(
                    'type' => ObjectModel::TYPE_STRING,
                    'required' => true
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
	}
	
	public static function getHistory($id_createcoupon_campaign=false,$numbers_voucher=false,$voucher_amount=false,$name=false,$voucher_prefix=false)
	{
            $where = "";
            $conditions = array();
            if($id_createcoupon_campaign!==false && (int) $id_createcoupon_campaign > 0 )
            {
                $conditions[] = " id_createcoupon_campaign = ".(int) $id_createcoupon_campaign." ";
            }
            if($numbers_voucher!==false && (int) $numbers_voucher > 0 )
            {
                $conditions[] = " numbers_voucher = ".(int) $numbers_voucher." ";
            }
            if($voucher_amount!==false && (int) $voucher_amount > 0 )
            {
                $conditions[] = " voucher_amount = ".(int) $voucher_amount." ";
            }
            if($name!==false && Tools::strlen($name)  > 0 )
            {
                $conditions[] = " name LIKE '%".Db::getInstance()->escape($name)."%' ";
            }
            if($voucher_prefix!==false && Tools::strlen($voucher_prefix) > 0 )
            {
                $conditions[] = " voucher_prefix LIKE '%".Db::getInstance()->escape($voucher_prefix)."%' ";
            }
            if(!empty($conditions)) 
            {
                $where = " WHERE ".implode(" AND ",$conditions);
            }
            $sql  = 'SELECT C.*, C.id_createcoupon_campaign AS print FROM `'._DB_PREFIX_.'createcoupon_campaign` AS C '.$where.' ORDER BY C.id_createcoupon_campaign DESC';
            return (Db::getInstance()->executeS($sql));		
	}
	
}
