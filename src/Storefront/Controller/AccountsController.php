<?php

namespace Emakers\TransmissionPlugin\Storefront\Controller;

use Emakers\TransmissionPlugin\Services\ExactRequirements;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Emakers\TransmissionPlugin\Entity\TransmissionEntity;
use Emakers\TransmissionPlugin\Entity\TransmissionEntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class AccountsController extends StorefrontController
{
    	/**
     	* @Route("/accounts", name="frontend.accounts", options={"seo"="false"}, defaults={"csrf_protected"=false, "XmlHttpRequest"=true}, methods={"GET", "POST"})
     	*/
    	public function accounts(RequestInterface $request): Response
    	{
        	/* @var EntityRepositoryInterface $customerRepository */
        	$customerRepository = $this->container->get('customer.repository');

        	/* @var EntityRepositoryInterface $transmissionRepository */
        	$transmissionRepository = $this->container->get('transmission.repository');

        	$content = $request->getBody()->getContents();

	}


	private function getCustomerData($customerGuid, $accessToken)
    	{
        $urlGetCustomer      = "https://start.exactonline.be/api/v1/291548/crm/Accounts?\$filter=ID eq guid'". $customerGuid ."'";
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
        @$dom->loadHTML($data);
	
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

		/* @var EntityRepositoryInterface $customerRepository 
                        $customerRepository = $this->container->get('customer.repository');

                        $customerRepository->create(
                        [
                                [
                                'groupId' => '45367459986264000000000000000000',
                                'defaultPaymentMethodId' => '196731AA63504B48AEA192B7D281BD70',
                                'firstName' => 'Lorem',
                                'lastName' => 'Ipsum',
                                'email' => 'loremipsum@ipsu.com',
                                'salesChannelId' => '98432DEF39FC4624B33213A56B8C944D',
                                'defaultBillingAddressId' => 'C0EA4E23300C4E35AEA8F6F930783B3D',
                                'defaultShippingAddressId' => 'C0EA4E23300C4E35AEA8F6F930783B3D',
                                'salutationId' => 'A41C97C73D904B998809FDF6433E3D98',
                                'customerNumber' => '10003',
                                ],
                        ],
                        \Shopware\Core\Framework\Context::createDefaultContext()
                        );*/

}
