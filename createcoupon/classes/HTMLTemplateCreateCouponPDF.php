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
require_once (dirname(__FILE__) . '/../classes/CreateCouponCampaign.php');
require_once (dirname(__FILE__) . '/../classes/CreateCouponCampaignCartRule.php');

class HTMLTemplateCreateCouponPDF extends HTMLTemplate
{
        public $name_file = 'list.pdf';
	public $campaign;  
        public $campaing_cart_rules;
        
	public function __construct(CreateCouponCampaign $campaign, $smarty)
	{
            $this->campaign = $campaign;
            $this->smarty = $smarty;

            if($this->campaign->name !== false && Tools::strlen(trim($this->campaign->name))>0)
            {
                $this->name_file = $this->campaign->name.".pdf";
            }
            
            // header informations
            $id_lang = Context::getContext()->language->id;
            $this->title = HTMLTemplateCreateCouponPDF::l('PDF List');
            // footer informations
            $this->shop = new Shop(Context::getContext()->shop->id);
	}
 
	/**
	 * Returns the template's HTML content
	 * @return string HTML content
	 */
	public function getContent()
	{
            $this->campaing_cart_rules = array();
            $this->campaing_cart_rules['name'] = $this->campaign->name;
            $this->campaing_cart_rules['date_from'] = $this->campaign->date_from;
            $this->campaing_cart_rules['date_to'] = $this->campaign->date_to;
            $this->campaing_cart_rules['date_add'] = $this->campaign->date_add;
            $this->campaing_cart_rules['voucher_day'] = $this->campaign->voucher_day;
            $this->campaing_cart_rules['cart_rule'] = array();
            $this->campaing_cart_rules['free_shipping'] = false;
            $this->campaing_cart_rules['reduction_percent'] = false;
            $this->campaing_cart_rules['reduction_amount'] = false;
            switch((int) $this->campaign->id_discount_type)
            {
                case CreateCoupon::FREE_SHIPPING:
                    $this->campaing_cart_rules['free_shipping'] = true;
                    break;
                case CreateCoupon::PERCENT: 
                    $this->campaing_cart_rules['reduction_percent'] = $this->campaign->voucher_amount;
                    break;
                case CreateCoupon::AMOUNT:         
                    $this->campaing_cart_rules['reduction_amount'] = $this->campaign->voucher_amount;
                    break;
            }
            
            if(!empty($this->campaign->id_cart_rules))
            {
                foreach($this->campaign->id_cart_rules as $id_createcoupon_campaign_cart_rule)
                {
                    $campaign_cart_rule = new CreateCouponCampaignCartRule($id_createcoupon_campaign_cart_rule);
                    $campaign_cart_rule->getById($id_createcoupon_campaign_cart_rule);
                    $this->campaing_cart_rules['cart_rule'][] = $campaign_cart_rule->code;
                }
            } 
            $this->smarty->assign(array(
                'campaing_cart_rules' => $this->campaing_cart_rules,
                'cart_rule' => $this->campaing_cart_rules['cart_rule']
            ));

            return $this->smarty->fetch(dirname(__FILE__) . '/../views/templates/admin/createcoupon_template_content.tpl');
	}
 
	public function getLogo()
	{
            $this->smarty->assign(array(
                'custom_model' => $this->campaign,
            ));

            return $this->smarty->fetch(dirname(__FILE__) . '/../views/templates/admin/createcoupon_template_logo.tpl');
	}
 
	public function getHeader()
	{          
            $this->smarty->assign(array(
                'id_cart_rules' => $this->campaign,
            ));
 
            return $this->smarty->fetch(dirname(__FILE__) . '/../views/templates/admin/createcoupon_template_header.tpl');
	}
 
	/**
	 * Returns the template filename
	 * @return string filename
	 */
	public function getFooter()
	{
            return $this->smarty->fetch(dirname(__FILE__) . '/../views/templates/admin/createcoupon_template_footer.tpl');
	}
 
	/**
	 * Returns the template filename
	 * @return string filename
	 */
	public function getFilename()
	{
            return $this->name_file;
	}
 
	/**
	 * Returns the template filename when using bulk rendering
	 * @return string filename
	 */
	public function getBulkFilename()
	{
            return $this->name_file;
	}
}
