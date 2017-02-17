<?php

namespace erc\pdfmerger;

use erc\pdfmerger\fpdi\fpdi_with_annots;

/**
 * Based on PDFMerger created by Jarrod Nettles December 2009
 * https://pdfmerger.codeplex.com/
 * 
 * Class for easily merging PDFs (or specific pages of PDFs) together into one. Output to a file, browser, download, or return as a string.
 * Unfortunately, this class does not preserve many of the enhancements your original PDF might contain. It treats
 * your PDF page as an image and then concatenates them all together.
 * 
 * Note that your PDFs are merged in the order that you provide them using the addPDF function, same as the pages.
 * If you put pages 12-14 before 1-5 then 12-15 will be placed first in the output.
 * 
 * Uses FPDI 1.4.4 from Setasign
 * Uses FPDF 1.6 by Olivier Plathey with FPDF_TPL extension 1.2.3 by Setasign
 * FPDI extension to preserve external hyperlinks https://gist.github.com/andreyvit/2020422
 * 
 * All of these packages are free and open source software, bundled with this class for ease of use.
 * 
 */

class PDFMerger {

  /**
   * Array of files to be merged.
   *
   * Values for each files are filename, Pages object and a boolean value
   * indicating if the file should be deleted after merging is complete.
   *
   * @var array
   */
  private $_files = array();

  /**
   * @var \FPDI Fpdi object
   */
  private $_fpdi;

  /**
   * @var string Directory path used for temporary files
   */
  private $_tempDir;

  /**
   * @var boolean Clean files after merged
   */
  private $_clean;
	
  /**
   * Merge PDFs.
   * @return void
   */
  public function __construct($clean = false) {
  	$this->_clean = $clean;
  }

  /**
   * Add a PDF for inclusion in the merge with a valid file path. Pages should be formatted: 1,3,6, 12-16.
   * @param $filepath
   * @param $pages
   * @return void
   */
  public function addPDF($filepath, $pages = 'all') {
    if (file_exists($filepath)) {
      if (strtolower($pages) != 'all') {
        $pages = $this->_rewritepages($pages);
      }
      $this->_files[] = array($filepath, $pages);
    }
    else {
      throw new Exception("Could not locate PDF on '$filepath'");
    }
    return $this;
  }

  /**
   * Add raw PDF from string
   *
   * Note that your PDFs are merged in the order that you add them
   *
   * @param  string    $pdf
   * @param  string     $pages
   * @throws Exception if unable to create temporary file
   */
  public function addRaw($pdf, $pages = 'all') {
    assert('is_string($pdf)');

    // Create temporary file
    $fname = $this->getTempFname();
    if (@file_put_contents($fname, $pdf) === false) {
      throw new Exception('Unable to create temporary file');
    }

    $this->addPDF($fname, $pages);
  }

  /**
   * Create temporary file and return name
   *
   * @return string
   */
  public function getTempFname() {
    return tempnam($this->getTempDir(), "pdfmerger");
  }

  /**
   * Get directory path for temporary files
   *
   * Set path using setTempDir(), defaults to sys_get_temp_dir().
   *
   * @return string
   */
  public function getTempDir() {
    return $this->_tempDir ?: sys_get_temp_dir();
  }

  /**
   * Set directory path for temporary files
   *
   * @param  string $dirname
   * @return void
   */
  public function setTempDir($dirname) {
    $this->_tempDir = $dirname;
  }

  /**
   * Merges your provided PDFs and outputs to specified location.
   * @param $outputmode
   * @param $outputname
   * @return PDF
   */
  public function merge($outputmode = 'browser', $outputpath = 'newfile.pdf') {
    if (empty($this->_files)) {
      throw new Exception('No PDFs to merge.');
    }

    $fpdi = new fpdi_with_annots;
    foreach ($this->_files as $file) {
      $filename  = $file[0];
      $filepages = $file[1];

      $count = $fpdi->setSourceFile($filename);

      // Add the pages.
      if ($filepages == 'all') {
        for ($i = 1; $i <= $count; $i++) {
          $template = $fpdi->importPage($i);
          $size = $fpdi->getTemplateSize($template);

          $fpdi->AddPage('P', array($size['w'], $size['h']));
          $fpdi->useTemplate($template);
        }
      }
      else {
        foreach ($filepages as $page) {
          if (!$template = $fpdi->importPage($page)) {
            throw new Exception("Could not load page '$page' in PDF '$filename'. Check that the page exists.");
          }
          $size = $fpdi->getTemplateSize($template);
          $fpdi->AddPage('P', array($size['w'], $size['h']));
          $fpdi->useTemplate($template);
        }
      }
    }

    if ($this->_clean) {
      foreach ($this->_files as $file) {
        unlink($file[0]);
      }
      $this->_files = array();
    }

    $mode = $this->_switchmode($outputmode);
    if ($mode == 'S') {
      return $fpdi->Output($outputpath, 'S');
    }
    try {
      $fpdi->Output($outputpath, $mode);
      return true;
    } catch (Exception $e) {
      throw new Exception("Error outputting PDF to '$outputmode'.");
      return false;
    }
  }

  /**
   * FPDI uses single characters for specifying the output location.
   * Change our more descriptive string into proper format.
   * @param $mode
   * @return Character
   */
  private function _switchmode($mode) {
    switch (strtolower($mode)) {
      case 'download':
        return 'D';
      case 'file':
        return 'F';
      case 'string':
        return 'S';
      default:
        return 'I';
    }
  }

  /**
   * Takes our provided pages in the form of 1,3,4,16-50 and creates an array of all pages.
   * @param $pages
   * @return unknown_type
   */
  private function _rewritepages($pages) {
    $pages = str_replace(' ', '', $pages);
    $part = explode(',', $pages);

    foreach ($part as $i) {
      $ind = explode('-', $i);

      if (count($ind) == 2) {
        // Start page.
        $x = $ind[0];
        // End page.
        $y = $ind[1];

        if ($x > $y) {
          throw new Exception("Starting page, '$x' is greater than ending page '$y'.");
          return false;
        }
        // Add middle pages.
        while ($x <= $y) {
          $newpages[] = (int) $x;
          $x++;
        }
      }
      else {
        $newpages[] = (int) $ind[0];
      }
    }
    return $newpages;
  }
}