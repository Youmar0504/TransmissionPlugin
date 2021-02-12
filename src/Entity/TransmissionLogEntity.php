<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;


class TransmissionLogEntity extends Entity
{
    
    use EntityIdTrait;

    /**
     * @var int
     */
    protected $orderNumber;
    

    /**
     * @var string
     */
    protected $requestType = null;

    /**
     * @var string
     */
    protected $targetUrl = null;

    /**
     * @var longText
     */
    protected $request = null;

    /**
     * @var longText
     */

    protected $response = null;

    /**
     * @var string
     */
    protected $status = null; 

    /**
     * @var \Datetime
     */
    protected $created_at = null;

    
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    
    public function getStatus()
    {
        return $this->status;
    }


    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;
    }

    
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }
    

    public function setRequest($request)
    {
        $this->request = $request;
    }

    
    public function getRequest()
    {
        return $this->request;
    }

    
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    
    public function getRequestType()
    {
        return $this->requestType;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }


    public function getResponse()
    {
        return $this->response;
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

