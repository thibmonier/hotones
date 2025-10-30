<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Profile;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contributors')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ContributorController extends AbstractController
{
    #[Route('', name: 'contributor_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $contributors = $em->getRepository(Contributor::class)->findBy(
            ['active' => true],
            ['name' => 'ASC'],
        );

        return $this->render('contributor/index.html.twig', [
            'contributors' => $contributors,
        ]);
    }

    #[Route('/new', name: 'contributor_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $contributor = new Contributor();

        if ($request->isMethod('POST')) {
            $contributor->setName($request->request->get('name'));
            $contributor->setEmail($request->request->get('email'));
            $contributor->setPhone($request->request->get('phone'));

            // Gestion des montants (éviter les chaînes vides)
            $cjm = $request->request->get('cjm');
            $contributor->setCjm($cjm !== '' ? (float) $cjm : null);

            $tjm = $request->request->get('tjm');
            $contributor->setTjm($tjm !== '' ? (float) $tjm : null);

            $contributor->setActive((bool) $request->request->get('active', true));
            $contributor->setNotes($request->request->get('notes'));

            // Association avec un utilisateur si sélectionné
            if ($userId = $request->request->get('user_id')) {
                $user = $em->getRepository(User::class)->find($userId);
                if ($user) {
                    $contributor->setUser($user);
                }
            }

            // Gestion des profils
            $profileIds = $request->request->all('profiles');
            if (!empty($profileIds)) {
                foreach ($profileIds as $profileId) {
                    $profile = $em->getRepository(Profile::class)->find($profileId);
                    if ($profile) {
                        $contributor->addProfile($profile);
                    }
                }
            }

            $em->persist($contributor);
            $em->flush();

            $this->addFlash('success', 'Contributeur créé avec succès');

            return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
        }

        $users    = $em->getRepository(User::class)->findBy(['totpEnabled' => true], ['firstName' => 'ASC']);
        $profiles = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

        return $this->render('contributor/new.html.twig', [
            'contributor' => $contributor,
            'users'       => $users,
            'profiles'    => $profiles,
        ]);
    }

    #[Route('/{id}', name: 'contributor_show', methods: ['GET'])]
    public function show(Contributor $contributor): Response
    {
        return $this->render('contributor/show.html.twig', [
            'contributor' => $contributor,
        ]);
    }

    #[Route('/{id}/edit', name: 'contributor_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function edit(Request $request, Contributor $contributor, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $contributor->setName($request->request->get('name'));
            $contributor->setEmail($request->request->get('email'));
            $contributor->setPhone($request->request->get('phone'));

            // Gestion des montants (éviter les chaînes vides)
            $cjm = $request->request->get('cjm');
            $contributor->setCjm($cjm !== '' ? (float) $cjm : null);

            $tjm = $request->request->get('tjm');
            $contributor->setTjm($tjm !== '' ? (float) $tjm : null);

            $contributor->setActive((bool) $request->request->get('active'));
            $contributor->setNotes($request->request->get('notes'));

            // Association avec un utilisateur
            if ($userId = $request->request->get('user_id')) {
                $user = $em->getRepository(User::class)->find($userId);
                $contributor->setUser($user);
            } else {
                $contributor->setUser(null);
            }

            // Gestion des profils
            $contributor->getProfiles()->clear();
            $profileIds = $request->request->all('profiles');
            if (!empty($profileIds)) {
                foreach ($profileIds as $profileId) {
                    $profile = $em->getRepository(Profile::class)->find($profileId);
                    if ($profile) {
                        $contributor->addProfile($profile);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', 'Contributeur modifié avec succès');

            return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
        }

        $users    = $em->getRepository(User::class)->findBy(['totpEnabled' => true], ['firstName' => 'ASC']);
        $profiles = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

        return $this->render('contributor/edit.html.twig', [
            'contributor' => $contributor,
            'users'       => $users,
            'profiles'    => $profiles,
        ]);
    }

    #[Route('/{id}/employment-periods', name: 'contributor_employment_periods', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function employmentPeriods(Contributor $contributor, EntityManagerInterface $em): Response
    {
        $periods = $em->getRepository(\App\Entity\EmploymentPeriod::class)
            ->findBy(['contributor' => $contributor], ['startDate' => 'DESC']);

        return $this->render('contributor/employment_periods.html.twig', [
            'contributor' => $contributor,
            'periods'     => $periods,
        ]);
    }

    #[Route('/{id}/timesheets', name: 'contributor_timesheets', methods: ['GET'])]
    public function timesheets(Request $request, Contributor $contributor, EntityManagerInterface $em): Response
    {
        $month     = $request->query->get('month', date('Y-m'));
        $startDate = new DateTime($month.'-01');
        $endDate   = clone $startDate;
        $endDate->modify('last day of this month');

        $timesheetRepo = $em->getRepository(\App\Entity\Timesheet::class);
        $timesheets    = $timesheetRepo->findByContributorAndDateRange($contributor, $startDate, $endDate);
        $projectTotals = $timesheetRepo->getHoursGroupedByProjectForContributor($contributor, $startDate, $endDate);
        $totalHours    = array_sum(array_map(fn ($t) => $t->getHours(), $timesheets));

        return $this->render('contributor/timesheets.html.twig', [
            'contributor'   => $contributor,
            'timesheets'    => $timesheets,
            'totalHours'    => $totalHours,
            'projectTotals' => $projectTotals,
            'month'         => $month,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
        ]);
    }

    #[Route('/{id}/delete', name: 'contributor_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Contributor $contributor, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contributor->getId(), $request->request->get('_token'))) {
            // Soft delete - marquer comme inactif au lieu de supprimer
            $contributor->setActive(false);
            $em->flush();
            $this->addFlash('success', 'Contributeur désactivé avec succès');
        }

        return $this->redirectToRoute('contributor_index');
    }
}
