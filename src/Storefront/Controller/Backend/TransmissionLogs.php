<?php

use TransmissionPlugin\Models\Transmission;
use TransmissionPlugin\Models\Logs;

class Shopware_Controllers_Backend_TransmissionLogs extends Shopware_Controllers_Backend_Application
{
    protected $model = Transmission::class;
    protected $alias = 'transmission';

    protected function getListQuery()
    {
        $builder = parent::getListQuery();

        $builder->leftJoin('transmission.logs', 'logs');
        $builder->addSelect(array('logs'));

        return $builder;
    }

    protected function getDetailQuery($id)
    {
        $builder = parent::getDetailQuery($id);

        $builder->leftJoin('transmission.logs', 'logs')
                ->addSelect('logs');

        return $builder;
    }

    protected function getAdditionalDetailData(array $data)
    {
        $data['logs'] = $this->getLogs($data['orderNumber']);
        return $data;
    }

    protected function getLogs($orderNumber)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select(array('transmission', 'logs'))
            ->from(Transmission::class, 'transmission')
            ->innerJoin('transmission.logs', 'logs')
            ->where('transmission.orderNumber = :id')
            ->setParameter('id', $orderNumber);

        $paginator = $this->getQueryPaginator($builder);

        $data = $paginator->getIterator()->current();
        return $data['logs'];
    }
}
