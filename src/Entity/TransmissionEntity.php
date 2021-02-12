<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;


class TransmissionEntity extends Entity
{
    
    use EntityIdTrait;

    /**
     * @var int
     */
    protected $orderNumber;
    

    /**
     * @var string
     */
    protected $productNumber = null;

     /**
     * @var string
     */
    protected $customerId = null;

    /**
     * @var string
     */
    protected $status = null;

    /**
     * @var string
     */
    protected $origine = null;

    /**
     * @var string
     */
    protected $destination = null;

    /**
     * @var string
     */
    protected $requestType = null;

    /**
     * @var \Datetime
     */
    protected $created_at = null;

    /**
     * @var \Datetime
     */
    protected $updated_at;

    
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

   
    public function setProductNumber($productNumber)
    {
        $this->productNumber = $productNumber;
    }

    
    public function getProductNumber()
    {
        return $this->productNumber;
    }

    
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }


    public function getCustomerId()
    {
        return $this->customerId;
    }


    public function setStatus($status)
    {
        $this->status = $status;
    }

    
    public function getStatus()
    {
        return $this->status;
    }


    public function setOrigine($origine)
    {
        $this->origine = $origine;
    }

    
    public function getOrigine()
    {
        return $this->origine;
    }
    

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    
    public function getDestination()
    {
        return $this->destination;
    }

    
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    
    public function getRequestType()
    {
        return $this->requestType;
    }

    
    public function setUpdated_at($updated_at)
    {
        $this->updated_at = date('Y-m-d H:i:s', time());
    }

    public function getUpdated_at()
    {
        return $this->updated_at;
    }

    
    public function setCreated_at($created_at)
    {
        $this->created_at = date('Y-m-d H:i:s', time());
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

}

