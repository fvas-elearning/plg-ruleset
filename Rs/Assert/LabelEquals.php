<?php
namespace Rs\Assert;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
class LabelEquals extends Iface
{

    /**
     * Given the rule and company record return a bool
     *
     * @param \Rs\Db\Rule $rule
     * @param \App\Db\Company $company
     * @return boolean
     */
    public function execute($rule, $company)
    {
        try {
            $catList = $company->getCategoryList()->toArray('class');
            return in_array($rule->getLabel(), $catList);
        } catch (\Exception $e) { }
        return false;
    }
}