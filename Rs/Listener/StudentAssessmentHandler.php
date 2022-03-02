<?php
namespace Rs\Listener;

use Rs\Plugin;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;
use Tk\Log;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StudentAssessmentHandler implements Subscriber
{
    use ConfigTrait;


    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onInit(\Tk\Event\Event $event)
    {
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');
        if (!Plugin::getInstance()->isZonePluginEnabled(Plugin::ZONE_COURSE, $studentAssessment->getSubject()->getCourseId())) {
            return;
        }

        $calc = \Rs\Calculator::createFromPlacementList($studentAssessment->getPlacementList());
        if (!$calc) return;
        $ruleList = $calc->getRuleList();
        /** @var \App\Db\Placement $placement */
        foreach ($studentAssessment->getPlacementList() as $placement) {
            $placementRules = \Rs\Calculator::findPlacementRuleList($placement);
            //vd($placementRules->toArray('name'));
            /** @var \Rs\Db\Rule $rule */
            foreach ($ruleList as $rule) {
                $units = 0;
                if (\Rs\Calculator::hasRule($rule, $placementRules)) {
                    $units = $placement->getUnits();
                }
                $css = '';

                $companyRulesLabel = $placement->getCompany()->getCategoryList()->toArray('name');
                // Highlight companies that have multiple categories using this css class
                if ($this->getConfig()->getAuthUser() && !$this->getConfig()->getAuthUser()->isStudent()) {
                    if (in_array($rule->getName(), $companyRulesLabel)) {
                        $css = 'in-company';
                    }
                }
                $studentAssessment->addUnitColumn($rule->getLabel(), $placement->getId(), $units, $css);
            }
        }

        $label = $calc->getSubject()->getCourseProfile()->getUnitLabel();
        $totals = $calc->getRuleTotals();

        /** @var \Rs\Db\Rule $rule */
        foreach ($ruleList as $i => $rule) {
            if ($i == 0) {  // Unit totals
                if (!$studentAssessment->isMinMode())
                    $studentAssessment->addTotal('Pending', $label, $totals['total']['pending']);
                $studentAssessment->addTotal('Completed', $label, $totals['total']['completed']);
                if (!$studentAssessment->isMinMode()) {
                    $studentAssessment->addTotal('Min Targets', $label, $calc->getSubject()->getMinUnitsTotal());
                    $studentAssessment->addTotal('Max Targets', $label, $calc->getSubject()->getMaxUnitsTotal());
                }
            }
            $t = $totals[$rule->getLabel()];
            if (!$studentAssessment->isMinMode())
                $studentAssessment->addTotal('Pending', $rule->getLabel(), $t['pending']);
            $studentAssessment->addTotal('Completed', $rule->getLabel(), $t['completed']);
            if (!$studentAssessment->isMinMode()) {
                //vd( $rule->getLabel(), $rule->getMin(),$rule->getMax() );
                $studentAssessment->addTotal('Min Targets', $rule->getLabel(), $rule->getMin().'');
                $studentAssessment->addTotal('Max Targets', $rule->getLabel(), $rule->getMax().'');
            }
        }

    }

    private function getValidCss($validValue)
    {
        if ($validValue < 0) return 'less';
        if ($validValue > 0) return 'grater';
        return 'equal';
    }


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\UiEvents::STUDENT_ASSESSMENT_INIT      => array('onInit', 0)
        );
    }

}