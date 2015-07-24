<?php
namespace EnvelopeBundle\EventListener;

use Avanzu\AdminThemeBundle\Model\MenuItemModel;
use Avanzu\AdminThemeBundle\Event\SidebarMenuEvent;
use Symfony\Component\HttpFoundation\Request;

class MyMenuItemListListener {

    // ...

    public function onSetupMenu(SidebarMenuEvent $event) {

        $request = $event->getRequest();

        foreach ($this->getMenu($request) as $item) {
            $event->addItem($item);
        }

    }

    protected function getMenu(Request $request) {
        // retrieve your menuItem models/entities here
        $items = [

            'envelope_budgets' => "Budgets",
            'envelope_budgettransactions' => "Budget Transactions",
            'envelope_transactions' => "Bank Transactions",
            'envelope_budget_templates' => "Budget Templates",
            'envelope_budget_apply_template' => "Apply Budget Template",
            'envelope_import' => "Import",
            'envelope_autocode' => 'Auto Code Transactions',
        ];
        $menuItems = array();
        foreach($items as $key => $label)
        {
            $menuItems[] = new MenuItemModel($key, $label, $key);
        }


        return $this->activateByRoute($request->get('_route'), $menuItems);
    }

    protected function activateByRoute($route, $items) {

        foreach($items as $item) {
            if($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            }
            else {
                if($item->getRoute() == $route) {
                    $item->setIsActive(true);
                }
            }
        }

        return $items;
    }

}