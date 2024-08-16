<?php
namespace App\EventListener;

use KevinPapst\TablerBundle\Event\MenuEvent;
use KevinPapst\TablerBundle\Model\MenuItemModel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class MenuEventListener implements EventSubscriberInterface {
    public function __construct(private Security $security)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            MenuEvent::class => ['onSetupMenu', 100],
        ];
    }

    public function onSetupMenu(MenuEvent $event) {

        $request = $event->getRequest();

        foreach ($this->getMenu($request) as $item) {
            $event->addItem($item);
        }

    }

    protected function getMenu(Request $request) {
        // retrieve your menuItem models/entities here
        $items = [];
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {


            $items = [
                'dashboard' => 'Dashboard',
                'profile' => 'Profile',
                //'envelope_budgets' => "Budgets",
                'envelope_budgettransactions' => "Budget Transactions",
//                'bank_transactions' => [
//                    "label" => "Bank Transactions",
//                    'children' => [
//                        'envelope_transactions_unbalanced' => [
//                            'label' => 'Unbalanced Transactions',
//                        ],
//                        'envelope_transactions' => [
//                            'label' => 'View Transactions',
//                            'children' => [
//                                'envelope_transaction' => ['label' => 'A']
//                            ],
//                        ],
//                        'envelope_transaction_new' => [
//                            'label' => 'New Transaction',
//                            'route_args' => ['id' => 'new']
//                        ]
//                    ],
//                ],
//                'envelope_budget_templates' => "Budget Templates",
//                'envelope_budget_apply_template' => "Apply Budget Template",
//                'envelope_import' => "Import",
//                'envelope_autocode' => 'Auto Code Transactions',
//                'stats' => [
//                    'label' => "Statistics",
//                     'children' => [
//                         'envelope_budget_stats'=> ['label'=>'Fortnight Trends'],
//                         'envelope_budget_stats_spending'=> ['label'=>'Spending Breakdown'],
//                     ]
//                ]

            ];
        }
        $menuItems = [];
        foreach($items as $key => $label)
        {
            if(is_array($label))
            {
                $menuItems[] = $this->buildMenuItem($key, $label);
            } else {
                $menuItems[] = new MenuItemModel($key, $label, $key);
            }
        }


        return $this->activateByRoute($request->get('_route'), $menuItems);
    }

    protected function buildMenuItem($route, $item)
    {
        $menuitem = new MenuItemModel($route, $item['label'], $route);
        if(isset($item['route_args']))
        {
            $menuitem->setRouteArgs($item['route_args']);
        }
        if(isset($item['children']))
        {
            foreach($item['children'] as $child_route => $child_item)
            {
                $childitem = $this->buildMenuItem($child_route, $child_item);
                $menuitem->addChild($childitem);
            }
        }
        return $menuitem;
    }

    protected function activateByRoute($route, $items) {

        foreach($items as $item) {
            if($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            }
            //else {
                if($item->getRoute() == $route) {
                    $item->setIsActive(true);
                }
            //}
        }

        return $items;
    }
}