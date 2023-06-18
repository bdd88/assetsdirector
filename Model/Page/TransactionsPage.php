<?php
namespace Bdd88\AssetsDirector\Model\Page;

use NexusFrame\Database\MySql\MySql;
use NexusFrame\Webpage\Controller\Session;

class TransactionsPage
{
    public function __construct(private MySql $mySql, private Session $session)
    {
    }

    public function exec(): array
    {
        $transactions = $this->mySql->read()
            ->table('transactions')
            ->addMatch('account_id', 'isEqual', $this->session->getId())
            ->addMatch('type', 'isEqual', 'TRADE')
            ->addMatch('assetType', 'isEqual', 'EQUITY')
            ->addMatch('symbol', 'isEqual', 'AAPL')
            ->addMatch('transactionDate', 'isBetween', ['2022-02-17T00:00:00+0000', '2022-02-18T00:00:00+0000'])
            ->addSort('orderDate', 'ASC')
            ->getResults()
        ;
        return array('transactions' => $transactions);
    }
}
