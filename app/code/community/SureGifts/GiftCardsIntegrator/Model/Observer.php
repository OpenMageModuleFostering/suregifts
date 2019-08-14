<?php
/**
 * Our class name should follow the directory structure of
 * our Observer.php model, starting from the namespace,
 * replacing directory separators with underscores.
 * i.e. app/code/local/SmashingMagazine/
 *                     LogProductUpdate/Model/Observer.php
 */
class SureGifts_GiftCardsIntegrator_Model_Observer
{
    /**
     * Magento passes a Varien_Event_Observer object as
     * the first parameter of dispatched events.
     */

    public  function updateNotice(Varien_Event_Observer $observer){

     // $this->loadLayout();  
      $msg = Mage::getStoreConfig('sg_section/sg_group/sg_notice',Mage::app()->getStore());
     
        //die($mode);
      if (!empty($msg )){
           $message= $msg.'.    <a href="http://www.suregifts.com.ng" target="_blank">Suregifts.com</a>';
           
          }else{
             $message= 'enter your <a href="http://www.suregifts.com.ng" target="_blank">Suregifts.com</a> giftcard code';
          }
      Mage::getSingleton('core/session')->addNotice($message);

    }


    public function processCoupon(Varien_Event_Observer $observer)
    {
       
        $username = Mage::getStoreConfig('sg_section/sg_group/sg_username',Mage::app()->getStore());
        //$mode = Mage::getStoreConfig('sg_section/sg_group/sg_mode',Mage::app()->getStore());
        $coupon_code = $_REQUEST['coupon_code'];
        $remove = $_REQUEST['remove'];
        if($remove == 0){
          $coupon_value = $this -> validateCouponCode($coupon_code);
          if ($coupon_value != 0 ){
          $this -> generateRule("Suregifts giftcard",$coupon_code,$coupon_value);
         // Mage::getSingleton('core/session')->getMessages(true); 
          }
        }
        else{
          //Mage::getSingleton('core/session')->getMessages(true); 
        }
        


        //die($observer);
    }

    public function validateCouponCode($the_coupon_code){
        $coupon_value = 0;
        $username = Mage::getStoreConfig('sg_section/sg_group/sg_username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('sg_section/sg_group/sg_password',Mage::app()->getStore());
        $website_host = Mage::getStoreConfig('sg_section/sg_group/sg_websitehost',Mage::app()->getStore());
        $mode = Mage::getStoreConfig('sg_section/sg_group/sg_mode',Mage::app()->getStore());
        
       
        $auth = $username.':'.$password;

       if ($mode == 1 ){
            $ch = curl_init("https://stagging.oms-suregifts.com/api/voucher/?vouchercode=".$the_coupon_code); 
          }else{
            $ch = curl_init("https://oms-suregifts.com/api/voucher/?vouchercode=".$the_coupon_code); 
          }

          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
          curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                  "Authorization: Basic ".base64_encode($auth),
                    )
              );
          $response = curl_exec($ch);
          $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
          curl_close($ch);
        
          try {
          $res = json_decode($response, true);
          Mage::log($res['AmountToUse']  , null, 'mode.log');
          if ($res['AmountToUse'] != 0){
          
          $data = array( 
          "AmountToUse" => $res['AmountToUse'] , 
        //"AmountToUse" => 2000,
          "VoucherCode" => $the_coupon_code,
          "WebsiteHost" => $website_host
          );  

        $data_string = json_encode($data);                                                                                   
         
        if ($mode == 1 ){
          $ch = curl_init('https://stagging.oms-suregifts.com/api/voucher');
          }else{
           $ch = curl_init('https://oms-suregifts.com/api/voucher');
        }
                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        //,
            'Content-Length: ' . strlen($data_string),
            "Authorization: Basic ".base64_encode($auth),
             )
          );
        $result = curl_exec($ch);
        $coupon_res = json_decode($result, true);

        $coupon_res_code = $coupon_res['Response'];

        Mage::log($coupon_res, null, 'salesrule.log');
        if ($coupon_res_code == "00"){
          $coupon_value = $res['AmountToUse'] ;
          //$coupon_value = 2000 ;
          return $coupon_value;
        } else { 
          $coupon_value = 0;
          return $coupon_value; }
      }
        
        else { 
          $coupon_value = 0;
          return $coupon_value; }
      }
      catch (Exception $e) {
        Mage::log($e->getMessage());
         }
    }

    public function generateRule($name = null, $coupon_code = null, $discount = 0)
    {
      if ($name != null && $coupon_code != null)
      {
        $rule = Mage::getModel('salesrule/rule');
        $customer_groups = array(0, 1, 2, 3);
        $rule->setName($name)
          ->setDescription($name)
          ->setFromDate('')
          ->setCouponType(2)
          ->setCouponCode($coupon_code)
          ->setUsesPerCustomer(1)
          ->setUsesPerCoupon(1)
          ->setCustomerGroupIds($customer_groups) //an array of customer grou pids
          ->setIsActive(1)
          ->setConditionsSerialized('')
          ->setActionsSerialized('')
          ->setStopRulesProcessing(0)
          ->setIsAdvanced(1)
          ->setProductIds('')
          ->setSortOrder(0)
          ->setSimpleAction('cart_fixed')
          ->setDiscountAmount($discount)
          ->setDiscountQty(null)
          ->setDiscountStep(0)
          ->setSimpleFreeShipping('0')
          ->setApplyToShipping('1')
          ->setIsRss(0)
          ->setWebsiteIds(array(1));

       /* $item_found = Mage::getModel('salesrule/rule_condition_product_found')
          ->setType('salesrule/rule_condition_product_found')
          ->setValue(1) // 1 == FOUND
          ->setAggregator('all'); // match ALL conditions
        $rule->getConditions()->addCondition($item_found);
        $conditions = Mage::getModel('salesrule/rule_condition_product')
          ->setType('salesrule/rule_condition_product')
          ->setAttribute('')
          ->setOperator('==')
          ->setValue($sku);
        $item_found->addCondition($conditions);*/

       /* $actions = Mage::getModel('salesrule/rule_condition_product')
          ->setType('salesrule/rule_condition_product')
          ->setAttribute('')
          ->setOperator('==')
          ->setValue($sku);
        $rule->getActions()->addCondition($actions);*/

        try {
          $rule->save();
         } catch (Exception $e) {
        Mage::log($e->getMessage());
         }
      }
    }
}