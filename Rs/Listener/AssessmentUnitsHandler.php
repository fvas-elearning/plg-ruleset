<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AssessmentUnitsHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onShow(\Tk\Event\Event $event)
    {
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');

        $calc = \Rs\Calculator::createFromPlacementList($studentAssessment->getPlacementList());
        if (!$calc) return;
        $ruleList = $calc->getRuleList();

        $label = $calc->getSubject()->getCourseProfile()->getUnitLabel();
        $totals = $calc->getRuleTotals();

        //vd($ruleList->toArray('name'));

        /** @var \Rs\Db\Rule $rule */
        foreach ($ruleList as $i => $rule) {
            $t = $totals[$rule->getLabel()];
            $studentAssessment->addTotal('Total', $rule->getLabel(), $t['total'], $this->getValidCss($t['validTotal']), $t['validMsg']);
        }
        $studentAssessment->addTotal('Total', $label, $totals['total']['total'],
            $this->getValidCss($totals['total']['validTotal']), $totals['total']['validMsg']);

        //vd($totals);

        $event->stopPropagation();
    }

    private function getValidCss($validValue)
    {
        if ($validValue < 0) return 'less';
        if ($validValue > 0) return 'grater';
        return 'equal';
    }

    /**
     * getSubscribedEvents
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\UiEvents::STUDENT_ASSESSMENT_SHOW => array('onShow', 10)
        );
    }

}


