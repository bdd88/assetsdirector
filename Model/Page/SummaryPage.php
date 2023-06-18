<?php
namespace Bdd88\AssetsDirector\Model\Page;

use NexusFrame\Database\MySql\MySql;

class SummaryPage
{
    public function __construct(private MySql $mySql)
    {
    }

    public function exec(): array
    {
        $output = array();

        $electronicFundSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'netAmount', 'total')
            ->addMatch('type', 'isEqual', ['ELECTRONIC_FUND', 'JOURNAL'])
            ->addMatch('description', 'notEqual', 'MARK TO THE MARKET')
            ->getResults()
        ;
        $output['totalFunding'] = (float) $electronicFundSum[0]['total'];

        $dividendOrInterestSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'netAmount', 'total')
            ->addMatch('type', 'isEqual', 'DIVIDEND_OR_INTEREST')
            ->getResults()
        ;
        $output['dividendAndInterest'] = (float) $dividendOrInterestSum[0]['total'];

        $feesSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'regFee', 'total')
            ->getResults()
        ;
        $output['fees'] = (float) $feesSum[0]['total'];

        $transactionsCount = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('COUNT', 'type', 'total')
            ->addMatch('type', 'isEqual', 'TRADE')
            ->getResults()
        ;
        $output['transactionCount'] = (int) $transactionsCount[0]['total'];

        $equityReturnsSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'netAmount', 'total')
            ->addMatch('assetType', 'isEqual', 'EQUITY')
            ->getResults()
        ;
        $output['equityReturns'] = (float) $equityReturnsSum[0]['total'];

        $optionReturnsSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'netAmount', 'total')
            ->addMatch('assetType', 'isEqual', 'OPTION')
            ->getResults()
        ;
        $output['optionReturns'] = (float) $optionReturnsSum[0]['total'];

        $equitiesPurchasedSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'amount', 'total')
            ->addMatch('description', 'isEqual', 'BUY TRADE')
            ->getResults()
        ;
        $output['equitiesPurchased'] = (int) $equitiesPurchasedSum[0]['total'];

        $equitiesSoldSum = $this->mySql->read()
            ->table('transactions')
            ->columnFunction('SUM', 'amount', 'total')
            ->addMatch('description', 'isEqual', 'SELL TRADE')
            ->getResults()
        ;
        $output['equitiesSold'] = (int) $equitiesSoldSum[0]['total'];

        $output['equitiesOutstanding'] = bcsub($output['equitiesPurchased'], $output['equitiesSold'], 0);
        $output['tradeReturns'] = bcadd($output['equityReturns'], $output['optionReturns'], 2);
        $output['totalReturns'] = bcadd($output['tradeReturns'], $output['dividendAndInterest'], 2);
        $output['percentReturn'] = bcdiv($output['totalReturns'], $output['totalFunding'], 4);
        $output['percentReturnReadable'] = bcmul($output['percentReturn'], 100, 2);
        $output['returnPerTransaction'] = bcdiv($output['totalReturns'], $output['transactionCount'], 2);

        return $output;
    }
}
