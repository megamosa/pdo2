<?php
namespace MagoArab\PdfGenerator\Model;

use Magento\Sales\Model\Order\Pdf\AbstractPdf;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class PdfGenerator extends AbstractPdf
{
    protected $fpdf;
    protected $filesystem;
    protected $fontPath;

    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
        $this->fontPath = $this->filesystem->getDirectoryRead(DirectoryList::APP)->getAbsolutePath() 
            . 'code/MagoArab/PdfGenerator/fonts/';
        
        $this->fpdf = new \FPDF('P', 'mm', 'A4');
        // Enable UTF-8 support
        $this->fpdf->SetAutoPageBreak(true, 15);
    }

    protected function initializeFonts()
    {
        // Add Arabic font support
        $this->fpdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $this->fpdf->AddFont('DejaVu', 'B', 'DejaVuSansCondensed-Bold.ttf', true);
    }

    public function getPdf($invoices = [])
    {
        $this->initializeFonts();
        $this->fpdf->AddPage();
        
        // Set RTL mode for Arabic
        $this->fpdf->SetRTL(true);
        $this->fpdf->SetFont('DejaVu', '', 14);

        foreach ($invoices as $invoice) {
            $order = $invoice->getOrder();
            
            // Header
            $this->fpdf->SetFont('DejaVu', 'B', 16);
            $this->fpdf->Cell(0, 10, 'فاتورة رقم: ' . $invoice->getIncrementId(), 0, 1, 'R');
            
            // Customer Info
            $this->fpdf->SetFont('DejaVu', '', 12);
            $this->fpdf->Cell(0, 8, 'العميل: ' . $order->getCustomerName(), 0, 1, 'R');
            $this->fpdf->Cell(0, 8, 'التاريخ: ' . $order->getCreatedAt(), 0, 1, 'R');
            
            // Items Table Header
            $this->fpdf->Ln(5);
            $this->fpdf->SetFont('DejaVu', 'B', 12);
            $this->fpdf->Cell(30, 10, 'السعر', 1, 0, 'C');
            $this->fpdf->Cell(30, 10, 'الكمية', 1, 0, 'C');
            $this->fpdf->Cell(130, 10, 'المنتج', 1, 1, 'C');
            
            // Items
            $this->fpdf->SetFont('DejaVu', '', 12);
            foreach ($invoice->getAllItems() as $item) {
                if (!$item->getOrderItem()->getParentItem()) {
                    $this->fpdf->Cell(30, 8, number_format($item->getPrice(), 2) . ' ' . $order->getOrderCurrencyCode(), 1, 0, 'C');
                    $this->fpdf->Cell(30, 8, (int)$item->getQty(), 1, 0, 'C');
                    $this->fpdf->Cell(130, 8, $item->getName(), 1, 1, 'R');
                }
            }
            
            // Totals
            $this->fpdf->Ln(5);
            $this->fpdf->SetFont('DejaVu', 'B', 12);
            $this->fpdf->Cell(160, 8, 'المجموع الفرعي:', 0, 0, 'L');
            $this->fpdf->Cell(30, 8, number_format($invoice->getSubtotal(), 2) . ' ' . $order->getOrderCurrencyCode(), 0, 1, 'R');
            
            if ($invoice->getTaxAmount() > 0) {
                $this->fpdf->Cell(160, 8, 'الضريبة:', 0, 0, 'L');
                $this->fpdf->Cell(30, 8, number_format($invoice->getTaxAmount(), 2) . ' ' . $order->getOrderCurrencyCode(), 0, 1, 'R');
            }
            
            if ($invoice->getShippingAmount() > 0) {
                $this->fpdf->Cell(160, 8, 'الشحن:', 0, 0, 'L');
                $this->fpdf->Cell(30, 8, number_format($invoice->getShippingAmount(), 2) . ' ' . $order->getOrderCurrencyCode(), 0, 1, 'R');
            }
            
            $this->fpdf->SetFont('DejaVu', 'B', 14);
            $this->fpdf->Cell(160, 10, 'الإجمالي:', 0, 0, 'L');
            $this->fpdf->Cell(30, 10, number_format($invoice->getGrandTotal(), 2) . ' ' . $order->getOrderCurrencyCode(), 0, 1, 'R');
        }

        return $this->fpdf->Output('S');
    }
}