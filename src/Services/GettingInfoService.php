<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Services;


use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checknut\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;


class GettingInfoService
{

    public function getInfo($orderId, $orderNumber, $orderObject, $container, $type)
    {
	if ($type == 'Invoice')
	{
            	$customerId   = $orderObject->getOrderCustomer()->getCustomerId();
		$shippingCost = $orderObject->getShippingCosts()->getTotalPrice();
		$taxStatus    = $orderObject->getPrice()->getTaxStatus();
		if ($taxStatus != 'net')
		{
			$taxPct = array_keys($orderObject->getShippingCosts()->getCalculatedTaxes()->getElements())[0];
                	//$taxValue = $orderObject->getShippingCosts()->getCalculatedTaxes()->getElements()[$taxPct]->getTax();
			$taxObject = $orderObject->getShippingCosts()->getCalculatedTaxes()->getElements()[$taxPct];
                        $taxValue = 0;

                        if (property_exists($taxObject, 'tax'))
                        {
                                $taxValue = $taxObject->getTax();
                        }

                	$shippingCost = $shippingCost - $taxValue;
		}
	}
	else{
		$customerId   = $orderObject->first()->getOrderCustomer()->getCustomerId();
		$taxStatus    = $orderObject->first()->getPrice()->getTaxStatus();
		$shippingCost = $orderObject->first()->getShippingCosts()->getTotalPrice();

                if ($taxStatus != 'net')
                {
                        $taxPct = array_keys($orderObject->first()->getShippingCosts()->getCalculatedTaxes()->getElements())[0];
                        //$taxValue = $orderObject->first()->getShippingCosts()->getCalculatedTaxes()->getElements()[$taxPct]->getTax();
			$taxObject = $orderObject->first()->getShippingCosts()->getCalculatedTaxes()->getElements()[$taxPct];
			$taxValue = 0;

                        if (property_exists($taxObject, 'tax'))
                        {
				$taxValue = $taxObject->getTax();
                        }

			$shippingCost = $shippingCost - $taxValue;	
                }
	}

        /* @var EntityRepositoryInterface $customerRepository */
        $customerRepository = $container->get('customer.repository');
        $customerObject	    = $customerRepository->search( new Criteria([ $customerId]), \Shopware\Core\Framework\Context::createDefaultContext() );

        $groupId = $customerObject->first()->getGroupId();
        $email   = $customerObject->first()->getEmail();

        $billingAddressId  = $customerObject->first()->getDefaultBillingAddressId();
        $shippingAddressId = $customerObject->first()->getDefaultShippingAddressId();

        /* @var EntityRepositoryInterface $addressRepository */
        $addressRepository      = $container->get('customer_address.repository');

        $billingAddressObject   = $addressRepository->search( new Criteria([ $billingAddressId]), \Shopware\Core\Framework\Context::createDefaultContext() );
        $shippingAddressObject 	= $addressRepository->search( new Criteria([ $shippingAddressId]), \Shopware\Core\Framework\Context::createDefaultContext() );

        $billingCountryId  = $billingAddressObject->first()->getCountryId();
        $shippingCountryId = $shippingAddressObject->first()->getCountryId();

        /* @var EntityRepositoryInterface $countryRepository */
        $countryRepository      = $container->get('country.repository');

        $billingCountryObject   = $countryRepository->search( new Criteria([ $billingCountryId]), \Shopware\Core\Framework\Context::createDefaultContext() );
        $billingIso		= $billingCountryObject->first()->getIso();
        $billingCountry 	= $billingCountryObject->first()->getName();

        $shippingCountryObject  = $countryRepository->search( new Criteria([ $shippingCountryId]), \Shopware\Core\Framework\Context::createDefaultContext() );
        $taxFree        	= $shippingCountryObject->first()->getTaxFree();
        $shippingIso            = $shippingCountryObject->first()->getIso();
        $shippingCountry        = $shippingCountryObject->first()->getName();

        $shipToCompany 	= $shippingAddressObject->first()->getCompany();
        $shipToName 	= substr($shippingAddressObject->first()->getFirstName() . " " . $shippingAddressObject->first()->getLastName() ,0,50);


        $addresses = [];
        $addresses['shipping']['company']       = $shippingAddressObject->first()->getCompany();
        $addresses['shipping']['firstName']     = $shippingAddressObject->first()->getFirstName();
        $addresses['shipping']['lastName']     	= $shippingAddressObject->first()->getLastName();
        $addresses['shipping']['vatId']         = $shippingAddressObject->first()->getVatId();
        $addresses['shipping']['address']       = substr($shippingAddressObject->first()->getStreet() . " " . $shippingAddressObject->first()->getAdditionalAddressLine1(),0,50);
        $addresses['shipping']['zipCode']       = $shippingAddressObject->first()->getZipCode();
        $addresses['shipping']['city']          = $shippingAddressObject->first()->getCity();
        $addresses['shipping']['country']       = $shippingCountry;
        $addresses['shipping']['iso']		= $shippingIso;
        $addresses['shipping']['phoneNumber'] 	= $shippingAddressObject->first()->getPhoneNumber();

        $addresses['billing']['company']      	= $billingAddressObject->first()->getCompany();
        $addresses['billing']['firstName']      = $billingAddressObject->first()->getFirstName();
        $addresses['billing']['lastName']      	= $billingAddressObject->first()->getLastName();
        $addresses['billing']['vatId']     	= $billingAddressObject->first()->getVatId();
        $addresses['billing']['address']        = substr($billingAddressObject->first()->getStreet() . " " . $billingAddressObject->first()->getAdditionalAddressLine1(),0,50);
        $addresses['billing']['zipCode']        = $billingAddressObject->first()->getZipCode();
        $addresses['billing']['city']           = $billingAddressObject->first()->getCity();
        $addresses['billing']['country']        = $billingCountry;
        $addresses['billing']['iso']            = $billingIso;
        $addresses['billing']['phoneNumber']    = $billingAddressObject->first()->getPhoneNumber();

	if ($type == 'Invoice')
	{
         	 $articles = $orderObject->getLineItems();
	}
	else{
		$articles = $orderObject->first()->getLineItems();
	}

        $customArticles = [];
        $discountTotal = 0;
        $credit = [];
        $i = 0;
        foreach ($articles as $article)
        {
            $productType  = $article->getType();
            $price = $article->getPrice()->getUnitPrice();
            if ($price < 0 && $productType = 'promotion')
            {
		$discount = $price;

		if ($taxStatus != 'net')
		{
			if (!$taxPct)
			{
				$taxPct = array_keys($article->getPrice()->getCalculatedTaxes()->getElements())[0];
			}

			$taxValue = $article->getPrice()->getCalculatedTaxes()->getElements()[$taxPct]->getTax();
			$discount = $price - $taxValue;
		}

		$discountTotal = $discountTotal + $discount;
		
            }

            elseif ($price < 0 && $productType == 'credit')
            {
                $credit[$i]['value'] = $price;
                $credit[$i]['name']  = $article->getLabel();
                $i++;
            }

            $id = $article->getIdentifier();
            $label = $article->getLabel();
            for ($x=0;$x<10;$x++)
            {
                  if (substr($label, 0, 1) === ''.$x.'')
                  {
                      $customArticles[$id]['details'] = $label;
                  }
            }
        }

	if ($taxPct == '21')
        {
                $otherTaxCode = 5;
        }
        elseif ($taxPct == '6')
        {
                $otherTaxCode = 1;
        }

	if (!$otherTaxCode)
	{
		$otherTaxCode = 5;
	}

        return
        [
        'groupId'	 => $groupId,
        'email'	      	 => $email,
        'addresses'	 => $addresses,
        'taxFree'	 => $taxFree,
        'discountTotal'	 => $discountTotal,
        'credit'	 => $credit,
        'articles'       => $articles,
        'customArticles' => $customArticles,
        'orderId'        => $orderId,
        'orderNumber'    => $orderNumber,
        'articles'	 => $articles,
        'shippingCost'	 => $shippingCost,
	'taxStatus'	 => $taxStatus,
	'otherTaxCode'   => $otherTaxCode
        ];
    }



}

