# pdfmerger

PHP library for merging multiple PDFs using [FPDI](https://github.com/Setasign/FPDI)

Based on PDFMerger created by Jarrod Nettles December 2009

https://pdfmerger.codeplex.com/

Class for easily merging PDFs (or specific pages of PDFs) together into one. Output to a file, browser, download, or return as a string.

Unfortunately, this class does not preserve many of the enhancements your original PDF might contain. It treats your PDF page as an image and then concatenates them all together.

Note that your PDFs are merged in the order that you provide them using the addPDF function, same as the pages.

If you put pages 12-14 before 1-5 then 12-15 will be placed first in the output.

* Uses FPDI 1.4.4 from Setasign
* Uses FPDF 1.6 by Olivier Plathey with FPDF_TPL extension 1.2.3 by Setasign
* FPDI extension to preserve external hyperlinks https://gist.github.com/andreyvit/2020422

All of these packages are free and open source software, bundled with this class for ease of use.

Installation
------------
Install using [composer](http://getcomposer.org/).

    composer require erc/pdfmerger

Usage
-----

```php
use erc\pdfmerger\PDFMerger;

$pdf = new PDFMerger;
try {
  $pdf->addPDF('a.pdf')
	  ->addPDF('b.pdf')
	  ->addPDF('c.pdf')
	  ->merge('file', 'd.pdf');
} catch(Exception $e){
  echo $e->getMessage();
}
