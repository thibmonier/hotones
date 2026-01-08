<?php

declare(strict_types=1);

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    public function __construct(
        private readonly Environment $twig
    ) {
    }

    /**
     * Génère un PDF à partir d'un template Twig.
     *
     * @param string $template   Le chemin du template Twig
     * @param array  $data       Les données à passer au template
     * @param array  $pdfOptions Options DomPDF (format, orientation, etc.)
     * @param bool   $inline     Si true, affiche le PDF dans le navigateur. Si false, force le téléchargement
     *
     * @return string Le contenu PDF généré
     */
    public function generatePdf(string $template, array $data, array $pdfOptions = [], bool $inline = false): string
    {
        // Rendu du template Twig
        $html = $this->twig->render($template, $data);

        // Configuration DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);

        // Options personnalisées
        foreach ($pdfOptions as $key => $value) {
            $options->set($key, $value);
        }

        // Génération du PDF
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($pdfOptions['paper'] ?? 'A4', $pdfOptions['orientation'] ?? 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Génère et retourne une réponse HTTP avec le PDF.
     *
     * @param string $template   Le chemin du template Twig
     * @param array  $data       Les données à passer au template
     * @param string $filename   Le nom du fichier PDF
     * @param bool   $inline     Si true, affiche le PDF dans le navigateur
     * @param array  $pdfOptions Options DomPDF
     */
    public function createPdfResponse(
        string $template,
        array $data,
        string $filename,
        bool $inline = false,
        array $pdfOptions = []
    ): \Symfony\Component\HttpFoundation\Response {
        $pdfContent = $this->generatePdf($template, $data, $pdfOptions, $inline);

        $response = new \Symfony\Component\HttpFoundation\Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');

        $disposition = $inline ? 'inline' : 'attachment';
        $response->headers->set('Content-Disposition', sprintf('%s; filename="%s"', $disposition, $filename));

        return $response;
    }
}
