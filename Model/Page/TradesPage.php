<?php
namespace Bdd88\AssetsDirector\Model\Page;

use Bdd88\AssetsDirector\Model\Logic\Calendar;
use NexusFrame\Database\MySql\MySql;
use NexusFrame\Webpage\Controller\Session;
use Bdd88\AssetsDirector\Model\Logic\GraphFactory;
use Bdd88\AssetsDirector\Model\Logic\TradeFactory;
use Bdd88\AssetsDirector\Model\Logic\TradeListFactory;

class TradesPage
{
    public function __construct(
        private MySql $mySql,
        private Session $session,
        private TradeListFactory $tradeListFactory,
        private TradeFactory $tradeFactory,
        private GraphFactory $graphFactory,
        private Calendar $calendar
        )
    {
    }

    public function exec(): array
    {
        // Get the trades and add running statistics.
        $trades = $this->mySql->read()
            ->table('trades')
            ->addMatch('account_id', 'isEqual', $this->session->getId())
            ->addMatch('assetType', 'isEqual', 'EQUITY')
            ->addMatch('closeTimestamp', 'isBetween', ['2021-01-01T00:00:00+0000', '2021-02-30T00:00:00+0000'])
            ->addSort('openTimestamp', 'ASC')
            ->getResults()
        ;

        // Stop processing if there are no trades.
        if (sizeof($trades) === 0) return array('trades' => array());

        // Create the Trades list and generate statistics.
        $tradeList = $this->tradeListFactory->create();
        foreach ($trades as $row) {
            $trade = $this->tradeFactory->cast($row);
            $tradeList->addTrade($trade);
        }
        $tradeList->addStatistics();

        // Generate the graph of trade data.
        $graph = $this->graphFactory->create();
        $graph->addLine('Returns', 'black', $tradeList->generateGraphData());
        $graph->generate(25);

        // TODO: Calendar calculations should happen here, rather than in the view.
        return array('calendar' => $this->calendar, 'trades' => $tradeList->getTrades(), 'graph' => $graph);
    }
}
