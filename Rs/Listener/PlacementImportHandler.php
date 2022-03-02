<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementImportHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onPlacementImport($event)
    {
        /** @var \App\Db\Placement $placement */
        $placement = $event->get('placement');
        /** @var \App\Db\CsvLog $csvLog */
        $csvLog = $event->get('csvLog');
        $csvRow = $csvLog->getCsvRow();
        if (!$csvRow) return;

//        $rules = \Rs\Calculator::findPlacementRuleList($placement);
//        foreach ($rules as $rule) {
//            \Rs\Db\RuleMap::create()->addPlacement($rule->getId(), $placement->getVolatileId());
//        }
        $rule = \Rs\Calculator::findDefaultPlacementRule($placement);
        if ($rule) {
            //\Rs\Db\RuleMap::create()->removePlacement(0, $placement->getVolatileId());
            \Rs\Db\RuleMap::create()->removeFromPlacement($placement);
            \Rs\Db\RuleMap::create()->addPlacement($rule->getId(), $placement->getVolatileId());
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\AppEvents::PLACEMENT_CSV_IMPORT => array('onPlacementImport', 0)
        );
    }
    
}