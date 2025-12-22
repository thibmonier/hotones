<?php

namespace App\Menu;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilder
{
    private AuthorizationCheckerInterface $security;

    public function __construct(AuthorizationCheckerInterface $security)
    {
        $this->security = $security;
    }

    public function buildMainMenu(): array
    {
        $menu     = [];
        $fullMenu = $this->getMenuDefinition();

        foreach ($fullMenu as $item) {
            // Si l'item a une condition de rôle et que l'utilisateur ne l'a pas, on saute.
            if (isset($item['role']) && !$this->security->isGranted($item['role'])) {
                continue;
            }

            // Si l'item a des enfants, on filtre les enfants
            if (isset($item['children'])) {
                $visibleChildren = [];
                foreach ($item['children'] as $child) {
                    if (!isset($child['role']) || $this->security->isGranted($child['role'])) {
                        $visibleChildren[] = $child;
                    }
                }

                // Si, après filtrage, il n'y a plus d'enfants visibles, on ne montre pas le parent.
                if (empty($visibleChildren)) {
                    continue;
                }
                $item['children'] = $visibleChildren;
            }

            $menu[] = $item;
        }

        return $menu;
    }

    private function getMenuDefinition(): array
    {
        return [
            // Tableau de bord
            ['is_title' => true, 'label' => 'Tableau de bord'],
            ['label' => 'Accueil', 'route' => 'home', 'icon' => 'bx-home-circle'],

            // Commerce
            ['is_title' => true, 'label' => 'Commerce', 'role' => 'ROLE_CHEF_PROJET'],
            [
                'label'    => 'Commerce',
                'icon'     => 'bx-store',
                'role'     => 'ROLE_CHEF_PROJET',
                'children' => [
                    ['label' => 'KPIs commerce', 'route' => 'sales_dashboard_index'],
                    ['label' => 'Clients', 'route' => 'client_index'],
                    ['label' => 'Devis', 'route' => 'order_index'],
                ],
            ],

            // Delivery
            ['is_title' => true, 'label' => 'Delivery', 'role' => 'ROLE_INTERVENANT'],
            [
                'label'    => 'Delivery',
                'icon'     => 'bx-package',
                'role'     => 'ROLE_INTERVENANT',
                'children' => [
                    ['label' => 'Projets', 'route' => 'project_index', 'role' => 'ROLE_CHEF_PROJET'],
                    ['label' => 'Planning', 'route' => 'planning_index', 'role' => 'ROLE_CHEF_PROJET'],
                    ['label'   => 'Optimisation', 'route' => 'planning_optimization_index',
                        'role' => 'ROLE_MANAGER', 'icon' => 'bx-bulb'],
                    ['label'   => 'Projets à risque', 'route' => 'risk_projects_dashboard',
                        'role' => 'ROLE_MANAGER', 'icon' => 'bx-error-circle'],
                    ['label' => 'Mes tâches', 'route' => 'my_tasks_index', 'role' => 'ROLE_INTERVENANT'],
                    ['label' => 'Saisir mes temps', 'route' => 'timesheet_index', 'role' => 'ROLE_INTERVENANT'],
                    ['label' => 'Mon historique', 'route' => 'timesheet_my_time', 'role' => 'ROLE_INTERVENANT'],
                    ['label' => 'Mes congés', 'route' => 'vacation_request_index', 'role' => 'ROLE_INTERVENANT'],
                    ['label' => 'Validation congés', 'route' => 'vacation_approval_index', 'role' => 'ROLE_MANAGER'],
                    ['label'   => 'Validation notes de frais', 'route' => 'expense_report_pending',
                        'role' => 'ROLE_MANAGER'],
                    ['label' => 'Tous les temps', 'route' => 'timesheet_all', 'role' => 'ROLE_ADMIN'],
                ],
            ],

            // Comptabilité
            ['is_title' => true, 'label' => 'Comptabilité', 'role' => 'ROLE_COMPTA'],
            [
                'label'    => 'Comptabilité',
                'icon'     => 'bx-receipt',
                'role'     => 'ROLE_COMPTA',
                'children' => [
                    ['label' => 'Facturation mensuelle', 'route' => 'billing_index'],
                    ['label' => 'Factures', 'route' => 'invoice_index'],
                    ['label' => 'Notes de frais', 'route' => 'expense_report_index'],
                    ['label' => 'Trésorerie', 'route' => 'treasury_dashboard'],
                ],
            ],

            // Administration
            ['is_title' => true, 'label' => 'Administration', 'role' => 'ROLE_COMPTA'],
            [
                'label'    => 'Administration',
                'icon'     => 'bx-briefcase',
                'role'     => 'ROLE_COMPTA',
                'children' => [
                    ['label' => 'Collaborateurs', 'route' => 'contributor_index'],
                    ['label' => 'Utilisateurs et rôles', 'route' => 'admin_users'],
                    ['label' => 'Périodes d\'emploi', 'route' => 'employment_period_index'],
                ],
            ],

            // RH & Satisfaction
            ['is_title' => true, 'label' => 'RH & Satisfaction', 'role' => 'ROLE_USER'],
            [
                'label'    => 'RH & Satisfaction',
                'icon'     => 'bx-heart',
                'role'     => 'ROLE_USER',
                'children' => [
                    ['label' => 'Ma satisfaction', 'route' => 'satisfaction_index'],
                    ['label' => 'Classement & XP', 'route' => 'leaderboard_index'],
                    ['label' => 'Mes évaluations', 'route' => 'performance_review_index'],
                    ['label' => 'Satisfaction client (NPS)', 'route' => 'nps_index', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Stats satisfaction équipe', 'route' => 'satisfaction_stats', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Onboarding équipe', 'route' => 'onboarding_team', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Gestion des badges', 'route' => 'badge_index', 'role' => 'ROLE_ADMIN'],
                ],
            ],

            // Backoffice
            ['is_title' => true, 'label' => 'Backoffice', 'role' => 'ROLE_ADMIN'],
            ['label' => 'Backoffice', 'route' => 'backoffice', 'icon' => 'bx-cog', 'role' => 'ROLE_ADMIN'],

            // Analytics
            ['is_title' => true, 'label' => 'Analytics', 'role' => 'ROLE_ADMIN'],
            [
                'label'    => 'Analytics',
                'icon'     => 'bx-bar-chart-alt-2',
                'role'     => 'ROLE_ADMIN',
                'children' => [
                    ['label' => 'Dashboard KPIs', 'route' => 'analytics_dashboard'],
                    ['label' => 'Prédictions & Alertes', 'route' => 'analytics_predictions', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Prévisions CA', 'route' => 'forecasting_index', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Prévisions CA (Simple)', 'route' => 'forecasting_dashboard', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Prédiction de charge', 'route' => 'staffing_prediction', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Dashboard RH', 'route' => 'hr_dashboard', 'role' => 'ROLE_MANAGER'],
                    ['label' => 'Staffing & TACE', 'route' => 'staffing_dashboard', 'role' => 'ROLE_ADMIN'],
                ],
            ],

            // CRM HotOnes
            ['is_title' => true, 'label' => 'CRM HotOnes', 'role' => 'ROLE_ADMIN'],
            [
                'label'    => 'CRM HotOnes',
                'icon'     => 'bx-user-check',
                'role'     => 'ROLE_ADMIN',
                'children' => [
                    ['label' => 'Statistiques', 'route' => 'admin_crm_statistics', 'icon' => 'bx-bar-chart'],
                    ['label' => 'Tous les leads', 'route' => 'admin_crm_leads_index', 'icon' => 'bx-list-ul'],
                ],
            ],

            // Gestion
            ['is_title' => true, 'label' => 'Gestion', 'role' => 'ROLE_ADMIN'],
            [
                'label'    => 'Abonnements SaaS',
                'icon'     => 'bx-cloud',
                'role'     => 'ROLE_ADMIN',
                'children' => [
                    ['label' => 'Dashboard', 'route' => 'saas_dashboard', 'icon' => 'bx-line-chart'],
                ],
            ],

            // Informations
            ['is_title' => true, 'label' => 'Informations'],
            ['label' => 'Marvin - Assistant IA', 'route' => 'chatbot_index', 'icon' => 'bx-bot'],
            ['label' => 'À propos', 'route' => 'about', 'icon' => 'bx-info-circle'],
        ];
    }
}
