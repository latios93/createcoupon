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

require_once (dirname(__FILE__) . '/../../createcoupon.php');
require_once (dirname(__FILE__) . '/../../classes/CreateCouponCampaign.php');
require_once (dirname(__FILE__) . '/../../classes/CreateCouponHistory.php');
require_once (dirname(__FILE__) . '/../../classes/HTMLTemplateCreateCouponPDF.php');

class AdminCreateCouponController extends AdminController {


  public function __construct() {
        $this->name = "createcoupon";
      	$this->table = 'createcoupon';
        $this->className = 'Campaign';
        $this->module = 'createcoupon';
        $this->lang = false;
        $this->context = Context::getContext();
        $this->_defaultOrderBy = 'created';
        $this->_defaultorderWay = 'DESC';
        $this->bootstrap = true;
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        $this->options_form = array(                     
            1 =>array('id_discount_type'=> CreateCoupon::PERCENT ,'name'=>$this->l('Discount on order (%)')),  
            2 =>array('id_discount_type'=> CreateCoupon::AMOUNT ,'name'=>$this->l('Discount on order (amount)')), 
            3 =>array('id_discount_type'=> CreateCoupon::FREE_SHIPPING ,'name'=>$this->l('Free shiping')) 
        );
        if (Shop::isFeatureActive())
            Shop::addTableAssociation($this->table, array('type' => 'shop')); 
        $this->_defaultOrderBy = 'id_createcoupon_campaign';
        $this->_defaultOrderWay = 'DESC';
        $this->id_last_campaign = 0;
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->_group = 'GROUP BY id_createcoupon_campaign';
        }     
        parent::__construct();
    } 
    public function renderList() {  
        $this->context->controller->addCss(Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/views/css/css.css');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/views/js/createcoupon.js');
        $header  = '<div class="alert alert-info">
                        <p>  '. $this->l('You can create many coupon as you whish!') .' </p> 
                    </div>'; 
        if($this->id_last_campaign > 0 || isset($this->context->cookie->{$this->name.'_id_last_campaign'})) {
            if($this->id_last_campaign <= 0)
            {
                $this->id_last_campaign = $this->context->cookie->{$this->name.'_id_last_campaign'};
            }
            elseif(!isset($this->context->cookie->{$this->name.'_id_last_campaign'}))
            {
                $this->context->cookie->__set($this->name.'_id_last_campaign', $this->id_last_campaign);
            }
            return $header . $this->renderButtonCampaigns() . $this->renderCartRuleHistoryList();
//            return $header . $this->renderForm() . $this->renderButtonCampaigns() . $this->renderCartRuleHistoryList();
        }
        else {
            return $header . $this->renderForm() . $this->renderHistoryList();
        }
    }
	
    public function initToolbar() {
        parent::initToolbar();
    }
     
    public function getConfigFormValues()
    {
        return array(
            'name' => '',
            'voucher_prefix' => 'CRC',
            'number_discount' => 10,
            'id_discount_type' => $this->options_form[1],
            'quantity_per_user' => 1,
            'cart_rule_restriction' => true,
            'voucher_amount' => 10,
            'minimal_order' => 0
        ); 
    }
	
    public function renderForm() {
    
    	$id_lang = (int) Context::getContext()->language->id;
    	$categories = Category::getSimpleCategories($id_lang);
    	
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',                               
                    'label' => $this->l('Name'),         
                    'desc' => $this->l('Description of this campaign'),   
                    'name' => 'name',
                    'required' => true,        
                    'value'    => '10' 
                ),
//                array(
//                    'type' => 'text',                               
//                    'label' => $this->l('Voucher Prefix'),             
//                    'name' => 'voucher_prefix',                      
//                    'required' => false,        
//                    'value'    => 'CRC' 
//                ),
                array(
                    'type' => 'select',                               
                    'label' => $this->l('Type'),         
                    'desc' => $this->l('Choose a discount\'s type'),   
                    'name' => 'id_discount_type',                      
                    'required' => true,                               
                    'options' => array(
                        'query' => $this->options_form ,                            
                        'id' => 'id_discount_type',                            
                        'name' => 'name'                           
                    )
                ),
                array(
                    'type' => 'text',                               
                    'label' => $this->l('Quantity'),         
                    'desc' => $this->l('How many coupons do you want to generate?'),   
                    'name' => 'number_discount',  
                    'class' => 'fixed-width-xl input-number input-number-minimum',                    
                    'required' => true,        
                    'value'    => '10' 
                ),
                array(
                    'type' => 'text',                               
                    'label' => $this->l('Days'),         
                    'desc' => $this->l('How many days do you want to validate coupon?'),   
                    'name' => 'voucher_day',      
                    'class' => 'fixed-width-xl input-number input-number-minimum',                
                    'required' => true,        
                    'value'    => '10' 
                ),
                array(
                    'type' => 'text',                               
                    'label' => $this->l('Uses by customers'),         
                    'desc' => $this->l('How many time do you want that customers will use this coupons?'),   
                    'name' => 'quantity_per_user', 
                    'class' => 'fixed-width-xl input-number input-number-minimum',                     
                    'required' => true,        
                    'value'    => '10' 
                ),
                array(
                    'type'     => 'text',                             
                    'label'    => $this->l('Value'),                   
                    'name'     => 'voucher_amount',                              
                    'class'    => 'sm',  
                    'value'    => '10',
                    'class' => 'fixed-width-xl input-number input-number-minimum',
                    'required' => true,                               
                    'desc'     => $this->l('Either the monetary amount or the %, depending on Type selected above')  
                ),
                array(
                    'type'     => 'text',                             
                    'label'    => $this->l('Minimum order'),                   
                    'name'     => 'minimal_order',                              
                    'class'    => 'sm',                  
                    'class' => 'fixed-width-xl input-number',            
                    'required' => true,                               
                    'desc'     => $this->l('The minimum order amount needed to use the voucher')  
                ), 
                array(
                    'type'      => 'radio',                              
                    'label'     => $this->l('Combinable'),         
                    'desc'      => $this->l('Use this coupon with others cart rules'),    
                    'name'      => 'cart_rule_restriction',                               
                    'required'  => true,                                 
                    'class'     => 't',                                   
                    'is_bool'   => true,       
                    'values'    => array(                               
                      array(
                        'id'    => 'cart_rule_restriction',                           
                        'value' => 0,                   
                        'label' => $this->l('Yes')                   
                      ),
                      array(
                        'id'    => 'cart_rule_restriction_deactivate',
                        'value' => 1, 
                        'label' => $this->l('No')
                      )
                    ),
                  )
            ),
            'submit' => array(
                'title' => $this->l('Generate'),
                'class' => 'btn btn-default pull-right'
            )
        );
 
        // Module, token and currentIndex 
        $this->name_controller = $this->name;  
        // Language
        $this->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $this->allow_employee_form_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        // Title and toolbar
        $this->title = $this->l('Create Coupon');
        $this->show_toolbar = true;        // false -> remove toolbar
        $this->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $this->submit_action = 'submit'.$this->name;
        
        // Load current value
        $this->fields_value = $this->getConfigFormValues(); 
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association:'),
                'name' => 'checkBoxShopAssociation',
            );
        }
        if (!($BlogCategory = $this->loadObject(true)))
            return;         
        return parent::renderForm();
    }
    
    
    
    public function postProcess() 
    {
//        print_r(Tools::getAllValues()); die();
        if( Tools::getIsset('print'.$this->name) && Tools::getIsset('id') && (int) Tools::getValue('id') > 0) 
        {
            $id_campaign = (int) Tools::getValue('id');
            $campaign = new CreateCouponCampaign($id_campaign);
            $this->generatePDF($campaign, 'CreateCouponPDF');
        }
        elseif (Tools::isSubmit('submitReset'.$this->name.'_history_list'))
        { 
            $filters = $this->context->cookie->getFamily($this->name.'_history_list'.'Filter_');
            foreach ($filters AS $cookieKey => $filter)
                if (strncmp($cookieKey, $this->name.'_history_list'.'Filter_', 7 + Tools::strlen($this->name.'_history_list')) == 0)
                {
                    $key = Tools::substr($cookieKey, 7 + Tools::strlen($this->name.'_history_list'));
                    $tmpTab = explode('!', $key);
                    $key = (count($tmpTab) > 1 ? $tmpTab[1] : $tmpTab[0]);
                    if (array_key_exists($key, $this->fieldsDisplay))
                        unset($this->context->cookie->$cookieKey);
                }
            if (isset($this->context->cookie->{'submitFilter'.$this->name.'_history_list'}))
                    unset($this->context->cookie->{'submitFilter'.$this->name.'_history_list'});
            if (isset($this->context->cookie->{$this->name.'_history_list'.'Orderby'}))
                    unset($this->context->cookie->{$this->name.'_history_list'.'Orderby'});
            if (isset($this->context->cookie->{$this->name.'_history_list'.'Orderway'}))
                    unset($this->context->cookie->{$this->name.'_history_list'.'Orderway'});
            unset($_POST);
        }
        //Create coupon's list!
        if (Tools::isSubmit('submitcreatecoupon')) {
            parent::validateRules();
            if (count($this->errors))
                return false; 
            $campaign = new CreateCouponCampaign();
            $campaign->name = Tools::getValue('name'); 
            $campaign->name = $campaign->name!==false ? trim($campaign->name) : $campaign->name;
            $campaign->voucher_prefix = 'CRC';
//                $campaign->voucher_prefix = Tools::getValue('voucher_prefix');
            $campaign->voucher_amount = Tools::getValue('voucher_amount');
            $campaign->id_discount_type = Tools::getValue('id_discount_type');
            $campaign->numbers_voucher = (int) Tools::getValue('number_discount');
            $campaign->active = 1;
            $campaign->quantity_per_user = (int) Tools::getValue('quantity_per_user');
            $campaign->cart_rule_restriction = (int) Tools::getValue('cart_rule_restriction');
            $campaign->voucher_day = (int) Tools::getValue('voucher_day'); 
            $campaign->minimal_order = (int) Tools::getValue('minimal_order');
            $campaign->date_add = date('Y-m-d H:i:s', time());
            
            if(Tools::strlen(trim($campaign->name)) <= 0)
            {
                $this->errors[] = Tools::displayError('An error has occurred: insert a name!');
            }
            if((int) $campaign->numbers_voucher <= 0)
            {
                $this->errors[] = Tools::displayError('An error has occurred: insert the quantity! It has to be grater then zero!');
            }
            if((int) $campaign->voucher_day <= 0)
            {
                $this->errors[] = Tools::displayError('An error has occurred: insert day to validate coupons! It has to be grater then zero!');
            }
            if((int) $campaign->quantity_per_user <= 0)
            {
                $this->errors[] = Tools::displayError('An error has occurred: insert the quantity the a user can use about coupon! It has to be grater then zero! Default: 1.');
            }
            if((int) $campaign->voucher_amount <= 0)
            {
                $this->errors[] = Tools::displayError('An error has occurred: insert the coupon amount! It has to be grater then zero!');
            }
            if (count($this->errors))
                return false; 
            
            if (!$campaign->createCouponsList())
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t generate coupons');
            }
            if (!$campaign->save())
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t save the current object');
            }

            if (!$campaign->saveHistoryCampaign())
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t save history');
            }
            $this->id_last_campaign = 0;
            $this->deleteCookieCampaign();
//            $this->id_last_campaign = $campaign->id;
        } //OPEN A CAMPAIGN
        elseif( ( Tools::getIsset('view'.$this->name.'_history_list') || Tools::getIsset('update'.$this->name.'_history_list') ) && ( $id_campaign = (int) Tools::getValue('id_createcoupon_campaign') ) && empty($this->errors) ) {
            $this->id_last_campaign = $id_campaign;
        } //DELETE A CAMPAIGN
        elseif( Tools::getIsset('delete'.$this->name.'_history_list') && ( $id_campaign = (int) Tools::getValue('id_createcoupon_campaign') ) && empty($this->errors) ) {
            $campaign = new CreateCouponCampaign();
            $campaign->id = $id_campaign;
            if(!CreateCouponCampaignCartRule::deleteByCampaign($id_campaign)) 
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete cart rules of this campaign!');
            }
            if(!$campaign->delete()) 
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete this campaign!');
            }
        } //DELETE MULTIPLE CAMPAIGNS
        elseif( Tools::isSubmit('submitBulkdelete'.$this->name.'_history_list') && Tools::getIsset($this->name.'_history_listBox') && !empty($todelete=Tools::getValue($this->name.'_history_listBox'))) 
        {
            foreach($todelete as $id_campaign)
            {
                $campaign = new CreateCouponCampaign();
                $campaign->id = $id_campaign;
                if(!CreateCouponCampaignCartRule::deleteByCampaign($id_campaign)) 
                {
                    $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete cart rules of this campaign! ID campaign: '.$id_campaign);
                } 
                if(!$campaign->delete()) 
                {
                    $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete this campaign! ID campaign: '.$id_campaign);
                }
            }
        } //DELETE SINGLE CART RULE OF A CAMPAIGN
        elseif( Tools::getIsset('delete'.$this->name.'_history_campaign_list') && ( $id_createcoupon_campaign_cart_rule = (int) Tools::getValue('id_createcoupon_campaign_cart_rule') ) && empty($this->errors))
        {
            $id_campaign = false;
            $cart_rule = new CreateCouponCampaignCartRule();
            $cart_rule->id = $id_createcoupon_campaign_cart_rule;
            $cart_rule->id_createcoupon_campaign_cart_rule = $id_createcoupon_campaign_cart_rule;  
            if($cart_rule->getById($cart_rule->id_createcoupon_campaign_cart_rule)) 
            {
                $id_campaign = $cart_rule->id_createcoupon_campaign;
            } 
            if(!$cart_rule->delete()) 
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete this cart rule! ID campaign: '.$id_campaign.' - ID cart rule: '.$id_createcoupon_campaign_cart_rule);
            } 
            if($id_campaign!==false) 
            {
                $this->id_last_campaign = $id_campaign;
            }
        } //DELETE MULTIPLE CART RULE OF A CAMPAIGN
        elseif( Tools::isSubmit('submitBulkdelete'.$this->name.'_history_campaign_list') && Tools::getIsset($this->name.'_history_campaign_listBox') && !empty($todelete=Tools::getValue($this->name.'_history_campaign_listBox'))) 
        {
            $id_campaign = false;
            foreach($todelete as $id_createcoupon_campaign_cart_rule)
            {
                $cart_rule = new CreateCouponCampaignCartRule();
                $cart_rule->id = $id_createcoupon_campaign_cart_rule;
                $cart_rule->id_createcoupon_campaign_cart_rule = $id_createcoupon_campaign_cart_rule; 
                $loaded = $cart_rule->getById($cart_rule->id_createcoupon_campaign_cart_rule);
                if($id_campaign===false && $loaded) 
                {
                    $id_campaign = $cart_rule->id_createcoupon_campaign;
                } 
                if(!$cart_rule->delete()) 
                {
                    $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete this cart rule! ID campaign: '.$id_campaign.' - ID cart rule: '.$id_createcoupon_campaign_cart_rule);
                } 
            }
            if($id_campaign!==false) 
            {
                $this->id_last_campaign = $id_campaign;
            }
        }
        //DEACTIVE single A CAMPAIGN
        elseif( Tools::getIsset('status'.$this->name.'_history_list') && ( $id_campaign = (int) Tools::getValue('id_createcoupon_campaign') ) && empty($this->errors) )
        { 
            $campaign = new CreateCouponCampaign();
            $campaign->id = $id_campaign;
            $campaign->id_createcoupon_campaign = $id_campaign;
            if(!$campaign->active()) 
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t deactive this campaign! ID campaign: '.$id_campaign);
            }
            $this->id_last_campaign = $id_campaign;
        }
        //DEACTIVE single CART RULE OF A CAMPAIGN
        elseif( Tools::getIsset('status'.$this->name.'_history_campaign_list') && ( $id_createcoupon_campaign_cart_rule = (int) Tools::getValue('id_createcoupon_campaign_cart_rule') ) && empty($this->errors) )
        {
            $id_campaign = false;
            $cart_rule = new CreateCouponCampaignCartRule();
            $cart_rule->id = $id_createcoupon_campaign_cart_rule;
            $cart_rule->id_createcoupon_campaign_cart_rule = $id_createcoupon_campaign_cart_rule; 
            $loaded = $cart_rule->getById($cart_rule->id_createcoupon_campaign_cart_rule);
            $id_campaign = $cart_rule->id_createcoupon_campaign;
            $campaign = new CreateCouponCampaign($id_campaign);
            if(!$cart_rule->active && !$campaign->active)
            {
                $campaign->active(false);
            }
            if(!$cart_rule->active(!$cart_rule->active)) 
            {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete this cart rule! ID campaign: '.$id_campaign.' - ID cart rule: '.$id_createcoupon_campaign_cart_rule);
            } 
            if($id_campaign!==false) 
            {
                $this->id_last_campaign = $id_campaign;
            }
        }//DEACTIVE MULTIPLE CART RULE OF A CAMPAIGN
        elseif( (Tools::isSubmit('submitBulkdeactivate'.$this->name.'_history_campaign_list') || Tools::isSubmit('submitBulkactivate'.$this->name.'_history_campaign_list')) && Tools::getIsset($this->name.'_history_campaign_listBox') && !empty($todelete=Tools::getValue($this->name.'_history_campaign_listBox'))) 
        {
            $id_campaign = false;
            foreach($todelete as $id_createcoupon_campaign_cart_rule)
            {
                $cart_rule = new CreateCouponCampaignCartRule();
                $cart_rule->id = $id_createcoupon_campaign_cart_rule;
                $cart_rule->id_createcoupon_campaign_cart_rule = $id_createcoupon_campaign_cart_rule; 
                $loaded = $cart_rule->getById($cart_rule->id_createcoupon_campaign_cart_rule);                
                $state = true;
                if(Tools::isSubmit('submitBulkdeactivate'.$this->name.'_history_campaign_list'))
                {
                    $state = false;
                }           
                if($id_campaign===false && $loaded) 
                {
                    $id_campaign = $cart_rule->id_createcoupon_campaign;
                    $campaign = new CreateCouponCampaign($id_campaign);
                    if($state && !$campaign->active)
                    {
                        $campaign->active(false);
                    }
                }
                if(!$cart_rule->active($state)) 
                {
                    $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete this cart rule! ID campaign: '.$id_campaign.' - ID cart rule: '.$id_createcoupon_campaign_cart_rule);
                } 
            }
            if($id_campaign!==false) 
            {
                $this->id_last_campaign = $id_campaign;
            }
        } //DELETE COOKIE AT FIRST CLICK
        elseif (!Tools::isSubmit('submitFilter'.$this->name.'_history_campaign_list'))
        {
            $this->deleteCookieCampaign();
        }
        if(Tools::getIsset('createcoupon_return_to_campaigns'))
        {
            $this->deleteCookieCampaign();
            $this->id_last_campaign = 0; 
        }
    }
    
    public function deleteCookieCampaign()
    {
        if(isset($this->context->cookie->{$this->name.'_id_last_campaign'}))
        {
            unset($this->context->cookie->{$this->name.'_id_last_campaign'});
        }
    }
    
    public function renderButtonCampaigns()
    {
        return 
        '<form id="createcoupon_return_to_campaigns" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
            <div class="row">
                <button type="submit" value="1" id="createcoupon_return_to_campaigns" name="createcoupon_return_to_campaigns" class="btn btn-default center-block">
		    <i class="icon-AdminCreateCoupon"></i> Torna alla lista delle campagne Coupon
	        </button>
            </div>
        </form>';
    }
    
    public function renderHistoryList()
    { 
        $module = new CreateCoupon();
        $page = $this->getPage();
        $perPage = $this->getRowsPerPage(); 
        $conditions = "";
        $order_by = "id_createcoupon_campaign DESC";
        $order_way = "DESC";
        $id_createcoupon_campaign=false;
        $numbers_voucher=false;
        $voucher_amount=false;
        $name=false;
        $voucher_prefix=false;
        if(Tools::isSubmit('submitFilter'))
        {
            if(Tools::getIsset($this->name.'_history_listFilter_id_createcoupon_campaign') && is_numeric(Tools::getValue($this->name.'_history_listFilter_id_createcoupon_campaign')))
            {
                $id_createcoupon_campaign=(int) Tools::getValue($this->name.'_history_listFilter_id_createcoupon_campaign');
            }
            if(Tools::getIsset($this->name.'_history_listFilter_numbers_voucher') && is_numeric(Tools::getValue($this->name.'_history_listFilter_numbers_voucher')))
            {
                $numbers_voucher=(int) Tools::getValue($this->name.'_history_listFilter_numbers_voucher');
            }
            if(Tools::getIsset($this->name.'_history_listFilter_voucher_amount') && is_numeric(Tools::getValue($this->name.'_history_listFilter_voucher_amount')))
            {
                $voucher_amount=(int) Tools::getValue($this->name.'_history_listFilter_voucher_amount');
            }
            if(Tools::getIsset($this->name.'_history_listFilter_name') && Tools::getValue($this->name.'_history_listFilter_name')!==false)
            {
                $name=Tools::getValue($this->name.'_history_listFilter_name');
                $name = trim($name);
            }
        }
        
        $historyList = CreateCouponHistory::getHistory($id_createcoupon_campaign,$numbers_voucher,$voucher_amount,$name,$voucher_prefix);
        $fields_list = $this->getStandardFieldList();

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = array('delete', 'view'); 
        $helper->bulk_actions = array('delete'=>array('text'=>$this->l('Delete selected'), 'confirm'=>$this->l('Delete selected items?')));
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->listTotal = count($historyList);
        $helper->identifier = 'id_createcoupon_campaign';
        $helper->title = $this->l('Coupons History');
        $helper->table = $module->name."_history_list";
        $helper->_default_pagination = 50; 
        $helper->_defaultOrderBy = $order_by;
        $helper->orderWay = $order_way;
        $helper->token = Tools::getAdminTokenLite('AdminCreateCoupon');
        $helper->currentIndex = AdminController::$currentIndex;
        $historyList = $this->paginate_content($historyList, $page, $perPage); 
        return $helper->generateList($historyList, $fields_list);
    }

    public function paginate_content($content, $page = 1, $pagination = 10){
        if (count($content) > $pagination) {
             $content = array_slice($content, $pagination * ($page - 1), $pagination);
        } 
        return $content;
    }
        
    public function getPage($table=false)
    {
        if($table!==false) 
        {
            $tableName = $this->name.$table; 
        }
        else
        {
           $tableName = $this->name; 
        }
        // Check if page number was selected and return it
        if (Tools::getIsset('submitFilter'.$tableName) && (int)Tools::getValue('submitFilter'.$tableName)>0) {
            return (int)Tools::getValue('submitFilter'.$tableName);
        }
        else {
            // Check if last selected page is stored in cookie and return it
            if (isset($this->context->cookie->{'submitFilter'.$tableName})) {
                return (int)$this->context->cookie->{'submitFilter'.$tableName};
            }
            else {
                // Page was not set so we return 1
                return 1;
            }
        }
    }

    public function getRowsPerPage($default=false)
    {
        $tableName = $this->name;

        // Check if number of rows was selected and return it
        if (Tools::getIsset($tableName. '_pagination')) 
        {
            return (int)Tools::getValue($tableName. '_pagination');
        } else // Check if number of rows is stored in cookie and return it
        if (isset($this->context->cookie->{$tableName. '_pagination'})) 
        {
            return (int)$this->context->cookie->{$tableName. '_pagination'};
        } else 
        if ($default!==false && (int) $default > 0)
        {
           return $default;
        }
        else 
        {
            // Return 50 rows per page as default
            return 50;
        }
    }
        
    public function getStandardFieldList()
    {
            return array(
                'id_createcoupon_campaign' => array(
                    'title' => $this->l('ID'),
                    'width' => 60, 
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => true, 
                    'search' => true
                ),
                'numbers_voucher' => array(
                    'title' => $this->l('Voucher\'s number'),
                    'width' => 60, 
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => true, 
                    'search' => true
                ),
                'voucher_amount' => array(
                    'title' => $this->l('Voucher amount'),
                    'width' => 60, 
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => true,
                    'filter' => false,
                    'search' => true,
                ),
                'name' => array(
                    'title' => $this->l('Name'),
                    'width' => 140,  
                    'type' => 'text',
                    'orderby' => true,
                    'filter' => false,
                    'search' => true,
                ),
                'voucher_prefix' => array(
                    'title' => $this->l('Voucher Prefix'),
                    'width' => 100, 
                    'orderby' => true,
                    'filter' => false,
                    'search' => true
                ),
                'date_add' => array(
                    'title' => $this->l('Date'),
                    'width' => 140,
                    'search' => false,
                    'type' => 'text',
                ),
                'active' => array(
                    'title' => $this->l('Status'),
                    'width' => '70',
                    'align' => 'center',
                    'active' => 'status',
                    'type' => 'bool',
                    'orderby' => false,
                    'filter' => false,
                    'search' => false
                ),
                'print' => array(
                    'title' => $this->l('Print campaign'),
                    'align' =>'text-center',
                    'search' => false,
                    'orderby' => false,
                    'filter' => false,
                    'callback' => 'printCampaign',
               )
            );
    }
	
    public function renderCartRuleHistoryList()
    {  
        $conditions = "";
        $order_by = "id_createcoupon_campaign_cart_rule DESC";
        $order_way = "DESC";
        $historyList = CreateCouponCampaignCartRule::getHistory($this->id_last_campaign);
        $fields_list = array(
                'id_createcoupon_campaign_cart_rule' => array(
                    'title' => $this->l('ID'),
                    'width' => 100, 
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => false, 
                    'search' => false
                ),
                'id_createcoupon_campaign' => array(
                    'title' => $this->l('ID Campaign'),
                    'width' => 140, 
                    'align' => 'center',
//                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => false, 
                    'search' => false
                ),
                'id_cart_rule' => array(
                    'title' => $this->l('ID Voucher'),
                    'width' => 140, 
                    'align' => 'center',
//                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => false,
                    'filter' => false,
                    'search' => false,
                ),
                'code' => array(
                    'title' => $this->l('Code'),
                    'width' => 140,  
                    'align' => 'center',
                    'type' => 'text',
                    'orderby' => false,
                    'filter' => false,
                    'search' => false,
                ),
                'quantity_per_user' => array(
                    'title' => $this->l('Quantity'),
                    'width' => 100, 
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                    'type' => 'text',
                    'orderby' => false, 
                    'search' => false
                ),
                'active' => array(
                    'title' => $this->l('Status'),
                    'width' => '70',
                    'align' => 'center',
                    'active' => 'status',
                    'type' => 'bool',
                    'orderby' => false,
                    'filter' => false,
                    'search' => false
                )
            );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = array('delete');
        $helper->bulk_actions = array(
            'delete'=>array(
                'text'=>$this->l('Delete selected'), 'confirm'=>$this->l('Delete selected items?')
            ),
            'activate'=>array(
                'text'=>$this->l('Activate selected'), 'confirm'=>$this->l('Activate selected items?')
            ),
            'deactivate'=>array(
                'text'=>$this->l('Deactivate selected'), 'confirm'=>$this->l('Deactive selected items?')
            )
            ); 
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->listTotal = count($historyList);
        $helper->identifier = 'id_createcoupon_campaign_cart_rule';
        $helper->title = $this->l('Campaign Coupons History');
        $helper->table = $this->name."_history_campaign_list";
        $helper->_default_pagination = 20; 
        $helper->_defaultOrderBy = $order_by;
        $helper->orderWay = $order_way;
        $helper->token = Tools::getAdminTokenLite('AdminCreateCoupon');
        $helper->currentIndex = AdminController::$currentIndex;
        $page = $this->getPage("_history_campaign_list");
        $perPage = $this->getRowsPerPage($helper->_default_pagination); 
        $historyList = $this->paginate_content($historyList, $page, $perPage); 
        return $helper->generateList($historyList, $fields_list);
    }        

    public function printCampaign($id)
    {
        return 
            '<span class="btn-group-action">
                <span class="btn-group">
                    <a class="btn btn-default" href="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminCreateCoupon').'&print'.$this->table.'&id='.$id.'"><i class="icon-search-plus"></i>&nbsp;'.$this->l('Print').'
                    </a>
                </span>
            </span>';
    }
    
    public function generatePDF($object, $template)
    {
        $pdf = new PDF($object, $template, Context::getContext()->smarty);
        $pdf->render();
    }
    
    public function getBaseURL()
    {
            return (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
                    .$this->context->shop->domain.$this->context->shop->getBaseURI();
    }
}
