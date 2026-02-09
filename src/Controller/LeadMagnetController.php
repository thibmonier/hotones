<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LeadCapture;
use App\Form\LeadCaptureType;
use App\Repository\LeadCaptureRepository;
use App\Security\CompanyContext;
use App\Service\LeadMagnetMailer;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

use function in_array;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ressources')]
class LeadMagnetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LeadCaptureRepository $leadCaptureRepository,
        private readonly LeadMagnetMailer $leadMagnetMailer,
        private readonly CompanyContext $companyContext,
    ) {
    }

    /**
     * Page de présentation du guide "15 KPIs essentiels pour agences web".
     */
    #[Route('/guide-kpis', name: 'lead_magnet_guide_kpis', methods: ['GET', 'POST'])]
    public function guideKpis(Request $request): Response
    {
        // Récupérer la source depuis le paramètre GET (ex: ?source=homepage)
        $source = $request->query->get('source', LeadCapture::SOURCE_OTHER);

        // Vérifier que la source est valide
        $validSources = [
            LeadCapture::SOURCE_HOMEPAGE,
            LeadCapture::SOURCE_PRICING,
            LeadCapture::SOURCE_ANALYTICS,
            LeadCapture::SOURCE_FEATURES,
            LeadCapture::SOURCE_CONTACT,
            LeadCapture::SOURCE_OTHER,
        ];

        if (!in_array($source, $validSources, true)) {
            $source = LeadCapture::SOURCE_OTHER;
        }

        $leadCapture = new LeadCapture();
        $leadCapture->setCompany($this->companyContext->getCurrentCompany());
        $leadCapture->setSource($source);
        $leadCapture->setContentType('guide-kpis');

        $form = $this->createForm(LeadCaptureType::class, $leadCapture, [
            'source'       => $source,
            'content_type' => 'guide-kpis',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingLead = $this->leadCaptureRepository->findOneByEmail($leadCapture->getEmail());

            if ($existingLead) {
                // Lead existant : renvoyer l'email
                $this->leadMagnetMailer->sendGuideKpisEmail($existingLead);

                $this->addFlash('success', 'Un email avec le lien de téléchargement vous a été renvoyé !');

                return $this->redirectToRoute('lead_magnet_thank_you', [
                    'email' => base64_encode($existingLead->getEmail()),
                ]);
            }

            // Nouveau lead : enregistrer
            $this->entityManager->persist($leadCapture);
            $this->entityManager->flush();

            // Envoyer l'email avec le lien de téléchargement
            try {
                $this->leadMagnetMailer->sendGuideKpisEmail($leadCapture);
                $this->addFlash('success', 'Un email avec le lien de téléchargement vous a été envoyé !');
            } catch (Exception) {
                // Log l'erreur mais ne bloque pas l'utilisateur
                $this->addFlash(
                    'warning',
                    'Votre inscription est confirmée ! Si vous ne recevez pas l\'email, vérifiez vos spams.',
                );
            }

            return $this->redirectToRoute('lead_magnet_thank_you', [
                'email' => base64_encode($leadCapture->getEmail()),
            ]);
        }

        return $this->render('lead_magnet/guide_kpis.html.twig', [
            'form'   => $form,
            'source' => $source,
        ]);
    }

    /**
     * Page de remerciement avec lien de téléchargement.
     */
    #[Route('/merci', name: 'lead_magnet_thank_you', methods: ['GET'])]
    public function thankYou(Request $request): Response
    {
        $emailEncoded = $request->query->get('email');

        if (!$emailEncoded) {
            return $this->redirectToRoute('lead_magnet_guide_kpis');
        }

        $email = base64_decode($emailEncoded, true);

        if (!$email) {
            return $this->redirectToRoute('lead_magnet_guide_kpis');
        }

        $lead = $this->leadCaptureRepository->findOneByEmail($email);

        if (!$lead) {
            return $this->redirectToRoute('lead_magnet_guide_kpis');
        }

        return $this->render('lead_magnet/thank_you.html.twig', [
            'lead' => $lead,
        ]);
    }

    /**
     * Téléchargement du guide (enregistre le téléchargement).
     */
    #[Route('/telecharger/guide-kpis', name: 'lead_magnet_download_guide_kpis', methods: ['GET'])]
    public function downloadGuideKpis(Request $request): Response
    {
        $emailEncoded = $request->query->get('email');

        if (!$emailEncoded) {
            throw $this->createNotFoundException('Email non fourni');
        }

        $email = base64_decode($emailEncoded, true);

        if (!$email) {
            throw $this->createNotFoundException('Email invalide');
        }

        $lead = $this->leadCaptureRepository->findOneByEmail($email);

        if (!$lead) {
            throw $this->createNotFoundException('Lead non trouvé');
        }

        // Marquer comme téléchargé
        $lead->markAsDownloaded();
        $this->entityManager->flush();

        // Chemin vers le fichier PDF
        $pdfPath = $this->getParameter('kernel.project_dir').'/public/downloads/guide-kpis-agences-web.pdf';

        // Vérifier que le fichier existe
        if (!file_exists($pdfPath)) {
            $this->addFlash(
                'error',
                'Le guide n\'est pas encore disponible. Nous vous l\'enverrons par email dès qu\'il sera prêt.',
            );

            return $this->redirectToRoute('lead_magnet_thank_you', [
                'email' => $emailEncoded,
            ]);
        }

        // Retourner le fichier PDF en téléchargement
        return $this->file($pdfPath, 'guide-15-kpis-agences-web.pdf');
    }
}
