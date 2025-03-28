<?php
namespace ImageCleaner;

use Dompdf\Dompdf;
use Dompdf\Options;

class Image_Cleaner_PDF {

    private $dompdf;

    public function __construct() {
        $this->initialize_dompdf();
    }

    // Initialize Dompdf with options
    private function initialize_dompdf() {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $this->dompdf = new Dompdf($options);
    }

    /**
     * Generate a PDF report
     *
     * @param string $htmlContent The HTML content to include in the PDF.
     * @param string $filename The name of the generated PDF file.
     * @return string Path to the generated PDF file.
     */
    public function generate_pdf($htmlContent, $filename = 'report.pdf') {
        try {
            $upload_dir = wp_upload_dir();
            $pdf_dir = trailingslashit($upload_dir['basedir']) . 'image-cleaner-pdfs/';

            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }

            $pdf_path = $pdf_dir . $filename;

            // Load HTML content
            $this->dompdf->loadHtml($htmlContent);

            // Set paper size and orientation
            $this->dompdf->setPaper('A4', 'portrait');

            // Render the PDF
            $this->dompdf->render();

            // Save the PDF to the filesystem
            file_put_contents($pdf_path, $this->dompdf->output());

            return $pdf_path;
        } catch (\Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Download a PDF file
     *
     * @param string $pdf_path The path to the PDF file to be downloaded.
     */
    public function download_pdf($pdf_path) {
        if (file_exists($pdf_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($pdf_path) . '"');
            readfile($pdf_path);
            exit;
        } else {
            wp_die(__('The requested PDF file does not exist.', 'image-cleaner'));
        }
    }
}

// Example usage within the plugin
// $pdf = new Image_Cleaner_PDF();
// $htmlContent = '<h1>Image Cleaner Report</h1><p>This is a test PDF.</p>';
// $pdf->generate_pdf($htmlContent, 'test-report.pdf');
