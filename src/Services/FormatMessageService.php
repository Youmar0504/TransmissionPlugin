<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Services;

use Emakers\TransmissionPlugin\Services\ExactDataService;
use Emakers\TransmissionPlugin\Services\SendMessageService;

class FormatMessageService
{
	public function formatOrderMessageExact($orderInfo, $orderNumber, $shippingMethod, $transmissionLogRepository, $container)
	{
		$now 		 	    = date("Y-m-d H:i:s");
		$orderId 	 	    = $orderInfo['orderId'];
		$accessToken	 	= (new ExactDataService)->accessToken();
		$customer        	= '836d8dd2-1b88-4ce4-98a5-d3fa34e8d43b';
		$contactPerson	 	= (new ExactDataService)->createContact($orderInfo, $orderNumber, $accessToken, $customer, $transmissionLogRepository);
		$shippingAddress	= (new ExactDataService)->createAddress($orderInfo, $orderNumber, $accessToken, $customer, $transmissionLogRepository);
		$shippingMethodGuid	= (new ExactDataService)->getShippingMethod($orderNumber, $accessToken, $shippingMethod, $transmissionLogRepository);
		$items		 		= (new ExactDataService)->getItems($orderInfo, $orderNumber, $accessToken, $transmissionLogRepository, $container);
		$itemLines	 		= '';
		$orderDiscount 		= '';

		if ($orderInfo['discountTotal'] != 0)
		{
			$discount = $orderInfo['discountTotal'];
			$orderDiscount = "'AmountDiscountExclVat': '". str_replace(',', '.', $discount) ."',";
		}

		//Preparing the order with items
		foreach ($items as $item) {
			if ($item['isCustom'] == 0)
			{
				$itemLines .= "{ 'Item': '".$item['id']."', 'Description': '". $item['number'] ."' , 'UnitPrice': ". $item['price'] .", 'Quantity': ". $item['quantity'] ."},";
			}
			else
			{
				if (array_key_exists('details', $item))
				{
					$customization = $item['details'];
				}
				else
				{
					$customization = $item['customNumber'] .' - '. strtoupper($item['customName']) ;
				}
				$itemLines .= "{ 'Item': '".$item['id']."', 'Notes': 'personalize:Shirt | personalize:". $customization ."', 'Description': '". $item['number'] ." | personalize:Shirt | personalize:". $customization ."', 'UnitPrice': ". $item['price'] .", 'Quantity': ". $item['quantity'] ."},";
			}
		}
		$itemLinesReady = substr($itemLines, 0, -1);

		$headers = array(
				'Authorization: Bearer ' . $accessToken . '',
				'Content-Type: application/json'
			);

		$contentOrder = "
		{
			'OrderNumber' : '". $orderNumber ."',
			'OrderDate': '". $now ."',
			'OrderedBy' : '". $customer ."',
			'Description': '". $orderNumber ."',
			".$orderDiscount."
			'OrderedByContactPerson': '". $contactPerson ."',
			'DeliverToContactPerson': '". $contactPerson ."',
			'InvoiceToContactPerson': '". $contactPerson ."',
			'ShippingMethod': '". $shippingMethodGuid ."',
			'DeliveryAddress': '". $shippingAddress ."',
			'WarehouseID': 'e8362d57-8646-4b5e-abdb-f258ecc54c47',
			'SalesOrderLines':
			[
				". $itemLinesReady ."
			]
		}";

		return
        	[
				'contentOrder' 	=> $contentOrder,
				'headers' 	   	=> $headers,
				'orderId'	   	=> $orderId
		];
	}
    public function formatInvoiceMessageExact($orderInfo, $entryNumber, $orderObject, $transmissionLogRepository, $container)
    {
        $entryId 	= $orderInfo['orderId'];
        $accessToken	= (new ExactDataService)->accessToken();
        $customer      	= '836d8dd2-1b88-4ce4-98a5-d3fa34e8d43b';
        $items		= (new ExactDataService)->getItems($orderInfo, $entryNumber, $accessToken, $transmissionLogRepository, $container);
        $entryLines	= '';

	//credit notes
        if ($orderInfo['credit'])
        {
            foreach($orderInfo['credit'] as $credit)
            {
                $entryCredit = "{ 'AmountFC':". $credit['value'] .", 'VATCode': '0', 'GLAccount': '82b25abb-d11c-48aa-bd58-5b91a86ee8fb', 'Description': '". $credit['name']."'},";
                $entryLines .= $entryCredit;
            }
        }

        if ($orderInfo['discountTotal'] != 0)
        {
            $discount = $orderInfo['discountTotal'];
            $entryDiscount = "{ 'AmountFC':". $discount .", 'VATCode': '". $orderInfo['otherTaxCode'] . "', 'GLAccount': '82b25abb-d11c-48aa-bd58-5b91a86ee8fb', 'Description': 'Discount amount'},";
        }

        //Preparing the salesEntry with Items
        foreach ($items as $item) {
            $vatCode = $this->getVatCode($item['taxId'], $orderInfo['addresses']['shipping']['iso'], $orderInfo['groupId'], $orderInfo['taxFree']);
            $totalPrice = round($item['price'] * $item['quantity'], 4);
            if ($item['isCustom'] == 0)
            {
                $entryLines .= "{ 'Quantity':". $item['quantity'] .", 'AmountFC': ".$totalPrice .", 'GLAccount': '82b25abb-d11c-48aa-bd58-5b91a86ee8fb', 'VATCode': '". $vatCode ."', 'Description': '". $item['number'] ."'},";
            }
            else
            {
                if (array_key_exists('details', $item))
                {
                    $customization = $item['details'];
                }
                else
                {
                    $customization = $item['customNumber'] .' - '. strtoupper($item['customName']);
                }
                $entryLines .= "{ 'AmountFC': ".$totalPrice .", 'GLAccount': '82b25abb-d11c-48aa-bd58-5b91a86ee8fb', 'Notes': 'personalize:Shirt | personalize:". $customization ."', 'VATCode': '". $vatCode ."', 'Description': '". $item['number'] ." | personalize:Shirt | personalize:". $customization ."'},";
            }
        }

        if ($orderInfo['discountTotal'] != 0)
        {
            $entryLines .= $entryDiscount;
        }
	
	$entryLinesReady = substr($entryLines, 0, -1);
	

	if ($orderInfo['shippingCost'] != 0)
	{
        	$entryLinesReady .= ",{ 'AmountFC': ". $orderInfo['shippingCost'] .", 'GLAccount': '82b25abb-d11c-48aa-bd58-5b91a86ee8fb', 'Description': 'Shipping Costs', 'VATCode': '". $orderInfo['otherTaxCode'] . "'}";
	}

        $headersEntryLines = array(
            'Authorization: Bearer ' . $accessToken . '',
            'Content-Type: application/json'
        );

        $contentEntryLines = "
		{
			'EntryNumber'    : '". $entryNumber ."',
			'Customer'       : '". $customer ."',
			'Journal'        : '715',
			'Description'    : '". $orderObject->getOrderNumber() ."',
			'SalesEntryLines':
			[
				". $entryLinesReady ."
			]
		}";

        return
            [
                'headersEntryLines'	=> $headersEntryLines,
                'contentEntryLines'	=> $contentEntryLines,
                'entryId'	   		=> $entryId
            ];
    }


	private function getVatCode($taxId, $country, $groupId, $taxFree)
	{
		//Normal Customer
		if ($groupId == 'cfbd5018d38d41d8adca10d94fc8bdd6')
		{
			//IN THE EU
			if (!$taxFree)
			{
				//21%
				if ($taxId == 'c787382c03c34544bafd934e3bbd5164')
				{
					$code = '5';
				}
				//6%
				elseif ($taxId == '8114d6c893f448408ffacb1c409d4522')
				{
					$code = '1';
				}
				//0%
				else
				{
					$code = '0%';
				}
			}
			//OUT OF EU
			else
			{
				//Export
				$code = 'E';
			}

		}
		//Business Customer
		elseif ($groupId == '45367459986264000000000000000000')
		{
			//IN THE EU
			if (!$taxFree)
			{
				if ($country == 'BE')
                        	{
                                	//21%
                                	if ($taxId == 'c787382c03c34544bafd934e3bbd5164')
                                	{
                                       		 $code = '5';
                                	}
                                	//6%
                                	elseif ($taxId == '8114d6c893f448408ffacb1c409d4522')
                                	{
                                       		 $code = '1';
                                	}
                                	//0%
                                	else
                                	{
                                       		$code = '0%';
                                	}
                        	}
				//OUT OF Belgium
				else
				{
					$code = 'B';
				}
			}
			//OUT OF EU
			else
			{
				//Export
                                $code = 'E';
			}
		}

		return $code;
	}

}

