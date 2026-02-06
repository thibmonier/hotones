<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\ClientFactory;
use App\Factory\ContributorFactory;
use App\Factory\OrderFactory;
use App\Factory\OrderTaskFactory;
use App\Factory\PlanningFactory;
use App\Factory\ProfileFactory;
use App\Factory\ProjectFactory;
use App\Factory\ProjectSubTaskFactory;
use App\Factory\ProjectTaskFactory;
use App\Factory\TimesheetFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Volume configuration
        $V = [
            'users'             => 15,
            'clients'           => 30,
            'profiles_extra'    => 8,
            'contributors'      => 40,
            'projects'          => 30,
            'order_tasks_min'   => 4,
            'order_tasks_max'   => 10,
            'project_tasks_min' => 8,
            'project_tasks_max' => 16,
            'subtasks_min'      => 2,
            'subtasks_max'      => 8,
            'timesheets_min'    => 150,
            'timesheets_max'    => 300,
            'plannings_min'     => 2,
            'plannings_max'     => 5,
        ];

        // Users
        UserFactory::createOne([
            'email'     => 'admin@hotones.com',
            'roles'     => ['ROLE_ADMIN', User::ROLE_SUPERADMIN],
            'firstName' => 'Admin',
            'lastName'  => 'Hotones',
        ]);
        UserFactory::createMany($V['users']);

        // Clients
        ClientFactory::createMany($V['clients']);

        // Profiles (base set)
        $baseProfiles = [
            ['name' => 'Developer', 'defaultDailyRate' => '600'],
            ['name' => 'Lead Developer', 'defaultDailyRate' => '700'],
            ['name' => 'Project Manager', 'defaultDailyRate' => '750'],
            ['name' => 'Product Owner', 'defaultDailyRate' => '700'],
            ['name' => 'Designer', 'defaultDailyRate' => '550'],
        ];
        foreach ($baseProfiles as $p) {
            ProfileFactory::createOne($p);
        }
        // Extra random profiles
        ProfileFactory::createMany($V['profiles_extra']);

        // Contributors with random profiles
        ContributorFactory::createMany($V['contributors'], fn (): array => [
            'profiles' => ProfileFactory::randomRange(1, 3),
        ]);

        // Projects
        $projects = ProjectFactory::createMany($V['projects'], fn (): array => [
            'client'            => ClientFactory::random(),
            'keyAccountManager' => UserFactory::random(),
            'projectManager'    => UserFactory::random(),
            'projectDirector'   => UserFactory::random(),
            'salesPerson'       => UserFactory::random(),
        ]);

        // For each project, create order, tasks, subtasks and timesheets
        foreach ($projects as $project) {
            // One order per project with some order tasks
            $order = OrderFactory::createOne([
                'project' => $project,
            ]);
            $orderTasks = OrderTaskFactory::createMany(random_int($V['order_tasks_min'], $V['order_tasks_max']), fn (): array => [
                'order'   => $order,
                'profile' => ProfileFactory::random(),
            ]);
            // Update order total from order tasks
            $sum = '0';
            foreach ($orderTasks as $ot) {
                $sum = bcadd($sum, $ot->getTotalAmount(), 2);
            }
            $order->setTotalAmount($sum);

            // Project tasks
            $tasks = ProjectTaskFactory::createMany(random_int($V['project_tasks_min'], $V['project_tasks_max']), fn (): array => [
                'project'             => $project,
                'assignedContributor' => random_int(0, 1) ? ContributorFactory::random() : null,
                'requiredProfile'     => random_int(0, 1) ? ProfileFactory::random() : null,
            ]);

            // Subtasks for regular tasks
            $regularTasks = array_filter($tasks, fn ($t): bool => $t->getType() === \App\Entity\ProjectTask::TYPE_REGULAR);
            foreach ($regularTasks as $task) {
                ProjectSubTaskFactory::createMany(random_int($V['subtasks_min'], $V['subtasks_max']), fn (): array => [
                    'task'     => $task,
                    'assignee' => random_int(0, 1) ? ContributorFactory::random() : null,
                ]);
            }

            // Timesheets: random entries for last 3 months
            $allProjectTasks = $tasks; // array of ProjectTask objects
            foreach (range(1, random_int($V['timesheets_min'], $V['timesheets_max'])) as $i) {
                $withTask = (bool) random_int(0, 1);
                $task     = $withTask ? $allProjectTasks[array_rand($allProjectTasks)] : null;

                // If task chosen, try to pick a subtask for it
                $subTask = null;
                if ($task) {
                    $subs = $task->getSubTasks()->toArray();
                    if (!empty($subs) && random_int(0, 1)) {
                        $subTask = $subs[array_rand($subs)];
                    }
                }

                // Choose contributor: bias to assigned contributor if available
                $contrib = $task && $task->getAssignedContributor() && random_int(1, 100) <= 60
                    ? $task->getAssignedContributor()
                    : ContributorFactory::random();

                TimesheetFactory::createOne([
                    'project'     => $project,
                    'contributor' => $contrib,
                    'task'        => $task,
                    'subTask'     => $subTask,
                    // hours/date/notes from defaults
                ]);
            }

            // Plannings: assign contributors to the project
            $planningCount = random_int($V['plannings_min'], $V['plannings_max']);
            for ($p = 0; $p < $planningCount; ++$p) {
                PlanningFactory::createOne([
                    'project'     => $project,
                    'contributor' => ContributorFactory::random(),
                    'profile'     => random_int(0, 1) ? ProfileFactory::random() : null,
                ]);
            }
        }

        $manager->flush();
    }
}
