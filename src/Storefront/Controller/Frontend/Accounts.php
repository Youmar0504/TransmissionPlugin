<?php

use TransmissionPlugin\Controllers\Frontend\Services\ExactDataService;

/**
 * Class Shopware_Controllers_Frontend_Accounts
 *
 * Endpoint to receive the webhooks when Accounts are updated in Exact
 */
class Shopware_Controllers_Frontend_Accounts extends Enlight_Controller_Action
{
    protected $resource  = null;

    public function init()
    {
        $this->resource  = \Shopware\Components\Api\Manager::getResource('Customer');
    }

    public function indexAction()
    {
        $content = $this->Request()->getRawBody();
        $file    = 'custom/plugins/TransmissionPlugin/Resources/webhooks/accountWebhook.txt';

        if ($content == '')
        {
            file_put_contents($file, " Raw Body is empty !");
        }
	else
        {
            file_put_contents($file, $content);

            $decodedWebhook  = json_decode(file_get_contents($file), true);
            $action          = $decodedWebhook['Content']['Action'];
            $customerGuid    = $decodedWebhook['Content']['Key'];
            $currentDivision = $decodedWebhook['Content']['Division'];
            $now = Date('Y-m-d H:i:s');
            $accessToken     = (new ExactDataService)->accessToken();
	    $customerData    = $this->getCustomerData($customerGuid, $accessToken);
	    
	    $getCustomerId = "SELECT id FROM shopware.s_user where email =?";
            $customerId    = Shopware()->Db()->fetchOne($getCustomerId, array($customerData['email'])); 
	    if ($customerId == '')
	    {
		$this->createCustomer($customerData);	
	    }
    	    $this->updateCustomer($customerId, $customerData);

	}
	
	die('have to die');
    }

    private function getCustomerData($customerGuid, $accessToken)
    {
        $urlGetCustomer      = "https://start.exactonline.be/api/v1/393999/crm/Accounts?\$filter=ID eq guid'". $customerGuid ."'";
        $headersGetCustomer  = array('Authorization: Bearer ' .$accessToken. '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlGetCustomer);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetCustomer);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $dom = new \DOMDocument();
        $dom->loadHTML($data);
	
	$emailObject    = $dom->getElementsByTagName('email');
	$email          = $emailObject[0]->nodeValue;
	
	$nameObject     = $dom->getElementsByTagName('name');
	$name	        = $nameObject[1]->nodeValue;

	$groupObject    = $dom->getElementsByTagName('classification5');
	$groupGuid      = $groupObject[0]->nodeValue;
	$group  = 'EK';

	if ($groupGuid != '')
        {
                $classifications = $this->getClassifications($groupGuid, $accessToken);
		$group	    	 = substr($classifications['value'], 0, 3);
        }
	
	$newsletterObject = $dom->getElementsByTagName('classification6');
	$newsletterGuid   = $newsletterObject[0]->nodeValue;
	$newsletter	  = 'Newsletter B2B EN';
	if ($newsletterGuid != '')
	{
		$classifications = $this->getClassifications($newsletterGuid, $accessToken);
		$newsletter	 = $classifications['value'];
	}
	
	$addressObject1 = $dom->getElementsByTagName('addressline1');
	$address1	= $addressObject1[0]->nodeValue;

	$addressObject2 = $dom->getElementsByTagName('addressline2');
        $address2       = $addressObject2[0]->nodeValue;

	$addressObject3 = $dom->getElementsByTagName('addressline3');
        $address3       = $addressObject3[0]->nodeValue;

	$cityObject	= $dom->getElementsByTagName('city');
	$city		= $cityObject[0]->nodeValue;

	$countryObject	= $dom->getElementsByTagName('country');
	$countryIso	= $countryObject[0]->nodeValue;
	
	$getCountry 	= "Select id from shopware.s_core_countries where countryiso =?";
        $countryId	= Shopware()->Db()->fetchOne($getCountry, $countryIso);

	$postCodeObject = $dom->getElementsByTagName('postcode');
        $postCode       = $postCodeObject[0]->nodeValue;

	$phoneObject	= $dom->getElementsByTagName('phone');
	$phone		= $phoneObject[0]->nodeValue;

	$vatObject	= $dom->getElementsByTagName('vatnumber');
	$vatNumber	= $vatObject[0]->nodeValue;
	
	$createDateObject	= $dom->getElementsByTagName('created');
	$creationDate		= $createDateObject[0]->nodeValue;

	$updateDateObject       = $dom->getElementsByTagName('modified');
        $updateDate	        = $updateDateObject[0]->nodeValue;

	return [
		'email'	       => $email,
		'name'	       => $name,
		'group'        => $group,
		'newsletter'   => $newsletter,
		'address1'     => $address1,
		'address2'     => $address2,
		'address3'     => $address3,
		'city'	       => $city,
		'countryId'    => $countryId,
		'postCode'     => $postCode,
		'phone'	       => $phone,
		'vatNumber'    => $vatNumber,
		'creationDate' => $creationDate,
		'updateDate'   => $updateDate
	];
    }
	

    private function getClassifications($classificationGuid, $accessToken)
    {
        $urlGetCustomerGroup      = "https://start.exactonline.be/api/v1/393999/crm/AccountClassifications?\$filter=ID eq guid'". $classificationGuid ."'";
        $headersGetCustomerGroup  = array('Authorization: Bearer ' .$accessToken. '');
        
	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlGetCustomerGroup);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetCustomerGroup);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $dom = new \DOMDocument();
        $dom->loadHTML($data);
	
	$valueObject = $dom->getElementsByTagName('code');
	$value	     = $valueObject[0]->nodeValue;
	
	return
	[
		'value' => $value
	];      
    }

    
    private function createCustomer($customerData)
    {
	$now = Date('Y-m-d H:i:s');
	
	$params = [];
	
	$params['changed']                                 = $customerData['updateDate'];
        $params['firstLogin']                              = $customerData['creationDate'];
	$params['salutation']					   = 'mr/ms';
	$params['firstname']                                       = $customerData['name'][0];
	$params['lastname']                                        = $customerData['name'][1];
	$params['email']                                           = $customerData['email'];
	$params['groupKey']					   = $customerData['group'];
	$params['newsletter']					   = $customerData['newsletter'];
	$params['defaultBillingAddress']['firstname']              = $customerData['name'][0];
	$params['defaultBillingAddress']['lastname']               = $customerData['name'][1];
        $params['defaultBillingAddress']['firstname']              = $customerData['name'][0];
	$params['defaultBillingAddress']['salutation']             = 'mr/ms';
        $params['defaultBillingAddress']['lastname']               = $customerData['name'][1];
        $params['defaultBillingAddress']['street']                 = $customerData['address1'];
        $params['defaultBillingAddress']['zipcode']                = $customerData['postCode'];
        $params['defaultBillingAddress']['city']                   = $customerData['city'];
        $params['defaultBillingAddress']['phone']                  = $customerData['phone'];
        $params['defaultBillingAddress']['vatNumber']              = $customerDate['vatNumber'];
        $params['defaultBillingAddress']['additionalAddressLine1'] = $customerData['address1'];
        $params['defaultBillingAddress']['additionalAddressLine2'] = $customerData['address2'];
	$params['defaultBillingAddress']['country']		   = $customerData['countryId'];
	$this->resource->create($params);

	$insertLogs = 'INSERT INTO s_transmission (customerId, origine, destination, requestType, status, creationdate) values (?, ?, ?, ?, ?, ?)';
        Shopware()->Db()->executeQuery($insertLogs, [ $customerId, 'Exact', 'Shopware', 'Customer Creation','OK', $now]);
    }

    private function updateCustomer($customerId, $customerData)
    {
	$now = Date('Y-m-d H:i:s');
	$getCustomerId = "SELECT id FROM shopware.s_user where email =?";
        $customerId    = Shopware()->Db()->fetchOne($getCustomerId, array($customerData['email'])); 
	
	$params	= [];
	$params['changed'] 				   	   = $customerData['updateDate'];
	$params['firstLogin'] 				           = $customerData['creationDate'];
	$params['firstname']	      				   = $customerData['name'][0];
	$params['lastname']	      				   = $customerData['name'][1];
	$params['groupKey']					   = $customerData['group'];
	$params['defaultBillingAddress']['firstname'] 		   = $customerData['name'][0];
	$params['defaultBillingAddress']['lastname']  		   = $customerData['name'][1];
	$params['defaultBillingAddress']['street']    		   = $customerData['address1'];
 	$params['defaultBillingAddress']['zipcode']   		   = $customerData['postCode'];
	$params['defaultBillingAddress']['city']      		   = $customerData['city'];
	$params['defaultBillingAddress']['phone']     		   = $customerData['phone'];
	$params['defaultBillingAddress']['vatNumber'] 		   = $customerDate['vatNumber'];
	$params['defaultBillingAddress']['additionalAddressLine1'] = $customerData['address2'];
	$params['defaultBillingAddress']['additionalAddressLine2'] = $customerData['address3'];
	$params['defaultBillingAddress']['country']              = $customerData['countryId'];
	

	$this->resource->update($customerId, $params);

	$insertLogs = 'INSERT INTO s_transmission (customerId, origine, destination, requestType, status, creationdate) values (?, ?, ?, ?, ?, ?)';
        Shopware()->Db()->executeQuery($insertLogs, [ $customerId, 'Exact', 'Shopware', 'Customer Update','OK', $now]);
    }

}
