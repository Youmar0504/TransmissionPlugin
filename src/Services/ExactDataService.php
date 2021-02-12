<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Services;

use Emakers\TransmissionPlugin\Services\GettingInfoService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Tax\TaxCollection;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Checkout\Cart\Order\Transformer\LineItemTransformer;

class ExactDataService
{
    public function accessToken()
    {
        $decodedTokensAfterUpdate = json_decode(file_get_contents('/var/www/rsca/custom/plugins/TransmissionPlugin/src/Resources/tokens/tokens.txt'), true);
        $accessToken              = $decodedTokensAfterUpdate['access_token'];

        return $accessToken;
    }

     public function createContact($orderInfo, $orderNumber, $accessToken, $customerId,  $transmissionLogRepository)
     {

        $urlCreateContact     = "https://start.exactonline.be/api/v1/291548/crm/Contacts";
        $headersCreateContact = array(
                                    'Authorization: Bearer '. $accessToken .'',
                                    'Content-Type: application/json'
                                     );
	$company = '';

        if ($orderInfo['addresses']['shipping']['company'])
        {
                $company = addslashes($orderInfo['addresses']['shipping']['company']);
        }

        $firstName  = addslashes($orderInfo['addresses']['shipping']['firstName']);
        $lastName = addslashes($orderInfo['addresses']['shipping']['lastName']);

        $contactInfo = "{
                                'Account': '". $customerId ."',
                                'FirstName': '". $company ." ". $firstName ."',
                                'LastName': '". $lastName ."',
                                'Email': '". $orderInfo['email'] ."',
                                'Phone': '". $orderInfo['addresses']['shipping']['phoneNumber'] ."'
                    }";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlCreateContact);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersCreateContact);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $contactInfo);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $dom = new \DOMDocument();
        @$dom->loadHTML($data);

        $contactIdObject = $dom->getElementsByTagName('id');
        $contactId       = $contactIdObject[1]->nodeValue;

        $status = $this->checkStatus($statusCode);

        $transmissionLogRepository->create(
                            [
                                [
                                    'orderNumber'       => intval($orderNumber),
                                    'status'            => $status,
                                    'targetUrl'         => $urlCreateContact,
                                    'request'           => $contactInfo,
                                    'response'          => $data,
                                    'requestType'       => 'Create Contact'
                                ],
                            ],
                            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        if ($statusCode != 200 && $statusCode != 201)
        {
            exit();
        }

        return $contactId;
    }


    public function createAddress($orderInfo, $orderNumber, $accessToken, $customerId, $transmissionLogRepository)
    {
        $shippingAddress        = addslashes($orderInfo['addresses']['shipping']['address']);
        $city                   = addslashes($orderInfo['addresses']['shipping']['city']);
        $postCode               = $orderInfo['addresses']['shipping']['zipCode'];
        $country                = $orderInfo['addresses']['shipping']['country'];
        $countryIso             = $orderInfo['addresses']['shipping']['iso'];
        $phone                  = $orderInfo['addresses']['shipping']['phoneNumber'];
        if (!$phone)
        {
                $phone = '+322 588 09 11';
        }

        $urlCreateAddress       = "https://start.exactonline.be/api/v1/291548/crm/Addresses";
        $headersCreateAddress   = array(
                                    'Authorization: Bearer '. $accessToken .'',
                                    'Content-Type: application/json'
                                  );

        $addressInfo = "{
                            'Account': '". $customerId ."',
                            'Type': '4',
                            'AddressLine1': '". $shippingAddress ."',
                            'City': '". $city ."',
                            'Country': '". $countryIso ."',
                            'Postcode': '". $postCode ."',
                            'Phone': '". $phone ."'
                        }";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlCreateAddress);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersCreateAddress);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $addressInfo);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $dom = new \DOMDocument();
        @$dom->loadHTML($data);
        $addressIdObject = $dom->getElementsByTagName('id');
        $addressIdFull   = $addressIdObject[0]->nodeValue;

        $addressId       = explode("'", $addressIdFull)[1];

        $status = $this->checkStatus($statusCode, CURLOPT_POSTFIELDS, $addressInfo);

        $transmissionLogRepository->create(
                            [
                                [
                                    'orderNumber'       => $orderNumber,
                                    'status'            => $status,
                                    'targetUrl'         => $urlCreateAddress,
                                    'request'           => $addressInfo,
                                    'response'          => $data,
                                    'requestType'       => 'Addresses'
                                ],
                            ],
                            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        if ($statusCode != 200 && $statusCode != 201)
        {
            exit();
        }

        return $addressId;

    }

    public function getShippingMethod($orderNumber, $accessToken, $shippingMethod, $transmissionLogRepository)
    {
        $urlGetShippingMethod     = "https://start.exactonline.be/api/v1/291548/sales/ShippingMethods?\$filter=Description eq '". $shippingMethod ."'";
        $headersGetShippingMethod = array('Authorization: Bearer '. $accessToken .'');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlGetShippingMethod);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetShippingMethod);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $dom = new \DOMDocument();
        @$dom->loadHTML($data);

        $shippingMethodObject = $dom->getElementsByTagName('id');
        $shippingMethodGuid = $shippingMethodObject[2]->nodeValue;

	$status = $this->checkStatus($statusCode, CURLOPT_POSTFIELDS, $addressInfo);

        $transmissionLogRepository->create(
                            [
                                [
                                    'orderNumber'       => $orderNumber,
                                    'status'            => $status,
                                    'targetUrl'         => $urlGetShippingMethod,
                                    'request'           => 'Getting Shipping Method',
                                    'response'          => $data,
                                    'requestType'       => 'Shipping Method'
                                ],
                            ],
                            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        if ($statusCode != 200 && $statusCode != 201)
        {
            exit();
        }

        return $shippingMethodGuid;
    }

    public function getItems($orderInfo, $orderNumber, $accessToken, $transmissionLogRepository, $container)
    {
        //To have the correct format for custom products
        $articles = LineItemTransformer::transformFlatToNested($orderInfo['articles']);

        /* @var EntityRepositoryInterface $productRepository */
        //$productRepository = $container->get('product.repository');

        $items = [];
        $i = 0;

        foreach($articles as $article) {
            if ($article->getType() == 'dvsn_pseudo_product')
            {
                $items[$i]['id']        = '18d54225-6701-4150-b3e4-42b9cf78784c';
                $items[$i]['number']    = $article->getPayload()['productNumber'];
		        $items[$i]['quantity']  = 1;
                $items[$i]['price']     = 0;
                $items[$i]['isCustom']  = 'pseudo';
            }

            elseif ($article->getType() != 'promotion' && $article->getType() != 'dvsn_pseudo_product')
            {

                if ($article->getType() == 'product') {
                        //$items[$i]['productId'] = $article->getId();
                        $items[$i]['number']    = $article->getPayload()['productNumber'];
                        $itemNumber             = $article->getPayload()['productNumber'];
                        $items[$i]['taxId']     = $article->getPayload()['taxId'];
                }
                else
                {
                    foreach ($article->getChildren() as $lineItem){
                        if ($lineItem->getType() == 'product') {
                                //$items[$i]['productId'] = $lineItem->getReferencedId();
                                $items[$i]['number']    = $lineItem->getPayload()['productNumber'];
                                $itemNumber             = $lineItem->getPayload()['productNumber'];
                                $items[$i]['taxId']     = $lineItem->getPayload()['taxId'];
                        }
                    }
                }
                if (strtok($itemNumber, '.') == 'SHCR')
                {
                    $itemNumber = substr($itemNumber, 5);
                }

                $urlGetItem     = "https://start.exactonline.be/api/v1/291548/logistics/Items?\$select=ID,Description&\$filter=Code eq '". $itemNumber ."'";
                $headersGetItem = array('Authorization: Bearer ' . $accessToken . '');

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlGetItem);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersGetItem);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $data = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $dom = new \DOMDocument();
                @$dom->loadHTML($data);

                $itemObject         = $dom->getElementsByTagName('id');
                $itemId             = $itemObject[2]->nodeValue;

                $nameObject         = $dom->getElementsByTagName('description');
                $name               = $nameObject[0]->nodeValue;

                $taxId = $items[$i]['taxId'];

                /* @var EntityRepositoryInterface $taxRepository */
                $taxRepository  = $container->get('tax.repository');
                $taxObject      = $taxRepository->search( new Criteria([ $taxId ]), \Shopware\Core\Framework\Context::createDefaultContext() );
                $taxRate        = $taxObject->first()->getTaxRate();

                if ($article->getType() == 'product')
                {
                    $items[$i]['id']        = $itemId;
                    $items[$i]['name']      = $name;
                    $items[$i]['quantity']  = $article->getQuantity();
		    $taxObject		    = $article->getPrice()->getCalculatedTaxes()->getElements()[$taxRate];
		    $taxValue 		    = 0;

		    if (property_exists($taxObject, 'tax'))
		    {
                    	$taxValue = ($taxObject->getTax() / $article->getQuantity());
		    }

                    $items[$i]['isCustom']  = 0;
		    if ($orderInfo['taxStatus'] == 'net')
                    {
                    	$items[$i]['price']     = round($article->getPrice()->getUnitPrice(), 4);
                    }
                    else
                    {
                    	$items[$i]['price']     = round(($article->getPrice()->getUnitPrice() - $taxValue), 4);
                    }
                }
                else
                {
                    $n=0;
                    foreach ($article->getChildren() as $lineItem){
                        if ($lineItem->getType() == 'product') {
                            $items[$i]['id']        = $itemId;
                            $items[$i]['name']      = $name;
                            $items[$i]['quantity']  = $article->getQuantity();
			    $items[$i]['isCustom']  = 0;
			    $taxObject              = $article->getPrice()->getCalculatedTaxes()->getElements()[$taxRate];
			    $taxValue 		    = 0;
			    if (property_exists($taxObject, 'tax'))
			    {
                            	$taxValue               = $taxObject->getTax();
			    }

			    if ($orderInfo['taxStatus'] == 'net')
			    {
				$items[$i]['price']     = round($article->getPrice()->getUnitPrice(), 4);
			    }
			    else
			    {
                            	$items[$i]['price']     = round(($article->getPrice()->getUnitPrice() - $taxValue), 4);
			    }

                        }
                        else
                        {
                            $countElements = count($lineItem->getChildren());
                            if ($countElements != 0)
                            {
                                $elements = $lineItem->getChildren()->getElements();
                                $id       = array_keys($elements)[0];
                                $items[$i]['details'] = $orderInfo['customArticles'][$id]['details'];
                            }

                            if ($n == 0)
                            {
                                $customName = $lineItem->getPayload()['value'];
                            }
                            else
                            {
                                $customNumber = $lineItem->getPayload()['value'];
                            }
                            $n++;

                            $items[$i]['isCustom']      = 1;
                            if (isset($customNumber))
                            {
                                $items[$i]['customNumber']  = $customNumber;
                            }

                            if (isset($customName))
                            {
                                    $items[$i]['customName']    = $customName;
                            }

                        }
                    }
                }

                $items[$i]['taxRate'] = $taxRate;


                $i++;

                $status = $this->checkStatus($statusCode);

                $transmissionLogRepository->create(
                            [
                                [
                                    'orderNumber'       => intval($orderNumber),
                                    'status'            => $status,
                                    'targetUrl'         => $urlGetItem,
                                    'request'           => 'Getting Item Details',
                                    'response'          => $data,
                                    'requestType'       => 'Items'
                                ],
                            ],
                            \Shopware\Core\Framework\Context::createDefaultContext()
                );
		
            }
        }

        return $items;
    }


    private function checkStatus($statusCode)
    {
        if ($statusCode == 201 || $statusCode == 200)
        {
            $status = 'OK';
        }
        else
        {
            $status = 'NOK';
        }

        return $status;
   }

}
