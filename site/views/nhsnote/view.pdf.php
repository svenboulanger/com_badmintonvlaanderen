<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

// Load the pdf library
require_once(JRoute::_("media/com_badminton/php/fpdf.php"));
define('EURO', chr(128));

class BadmintonViewNHSNote extends JViewLegacy
{
	/**
	 * Generate the PDF
	 *
	 * @param	string $tpl		The template name
	 */
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$idlist = $this->getIdList($user);
		if (!$idlist)
			return false;
		
		// Generate the filename
		$filename = $this->generatePdf($idlist);
		
		// Return the file
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="nhsnote.pdf"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filename));
		readFile($filename);

		// Remove the file
		unlink($filename);
	}
	
	/**
	 * Find the player ID from the user
	 *
	 * @param mixed $user		The user object
	 * @return int 				The player ID of the user
	 */
	protected function getIdList($user)
	{
		$result = array();
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('id')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('user_id') . '=' . $db->quote($user->id));
		$baseid = $db->setQuery($query)->loadResult();
		if (!$baseid)
			return false;
		$result[] = $baseid;

		$query = $db->getQuery(true);
		$query->select('id')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('guardian') . '=' . $db->quote($baseid) . ' OR ' . $db->quoteName('guardian2') . '=' . $db->quote($baseid));
		$guarded = $db->setQuery($query)->loadAssocList();
		foreach ($guarded as $i => $id)
			$result[] = $id['id'];
		return $result;
	}
	
	/**
	 * Extract information
	 *
	 * @return array	The information for the document
	 */
	protected function extractInfo($id)
	{
		// Get the current user
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		
		// Find the player from the database
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('id') . '=' . $db->quote($id));
		$result = $db->setQuery($query)->loadObject();
		if (!$result)
			return false;
		
		// Setup the information
		$info = array();
		$info['name'] = $result->lastname . ' ' . $result->firstname;
		$info['address1'] = $result->address1;
		$info['address2'] = $result->address2;
		$info['address3'] = $result->postalcode . ' ' . $result->city;
		
		$info['fee'] = $result->paidamount;
		$info['year'] = $result->paidyear;
		$info['downloaded'] = date("d/m/Y");
		
		// If the paid year is not this year, then this file is invalid
		if ($info['year'] != date("Y"))
			return false;
		return $info;
	}
	
	/*
	 * Decode
	 */
	private function decode($txt) {
		return iconv('UTF-8', 'windows-1252', $txt);
	}
	
	/**
	 * Build a PDF
	 *
	 * @param	array ids		The ID's that should be included in the file
	 */
	protected function generatePdf($ids)
	{
		// Get the temp folder
		$app = JFactory::getApplication();
		
		// Generate a unique filename for the PDF
		$filename = tempnam($app->getCfg('tmp_path'), "nhs_");
		if (!$filename)
			return null;
		
		// Generate the PDF file
		$pdf = new FPDF("P", "mm", "A4");
		
		// Generate for all id's
		foreach ($ids as $i => $id)
		{
			$input = $this->extractInfo($id);
			if (!$input)
				continue;
			$this->nhsform($pdf, $input);
		}

		// Write the file
		$pdf->Output($filename);
		return $filename;
	}
	
	/**
	 * Add a form for the national health service to the pdf object
	 *
	 * @param	mixed $pdf		The PDF object
	 * @param	array $input	The input values for the form
	 */
	protected function nhsform($pdf, $input)
	{
		$pdf->AddPage();
		$pdf->SetFont('Helvetica');
		
		// Draw header
		$pdf->Image(JRoute::_('media/com_badminton/images/bamadi.jpg'), 10, 5, 50);
		$pdf->SetFont('Helvetica');
		$pdf->SetFontSize(20);
		$pdf->SetXY(60, 8);
		$pdf->Cell(0, 30, 'Badmintonclub Machelen-Diegem VZW', 0, 1, 'C', false);
		
		// Draw title
		$pdf->SetXY(10, 45);
		$pdf->SetFontSize(16);
		$pdf->Cell(0, 10, 'ATTEST VAN LIDMAATSCHAP SPORTCLUB ' . $this->decode($input['year']), 'B', 1, 'L');
		
		// ** Personal data **
		$this->subtitle($pdf, 'PERSOONLIJKE GEGEVENS');
		$pdf->Cell(50, 6, 'Naam en voornaam', 0, 0, 'L');
		$pdf->Cell(0, 6, $this->decode($input['name']), 1, 1, 'L');
		$pdf->Cell(50, 6, 'Adres', 0, 0, 'L');
		$x = $pdf->GetX();
		$pdf->Cell(0, 6, $this->decode($input['address1']), 'LTR', 1, 'L');
		$pdf->SetX($x); $pdf->Cell(0, 6, $this->decode($input['address2']), 'LR', 1, 'L');
		$pdf->SetX($x); $pdf->Cell(0, 6, $this->decode($input['address3']), 'LBR', 1, 'L');
		$pdf->SetY($pdf->GetY() + 2.5);
		$pdf->Cell(0, 6, 'Rekeningnummer waarop de tegemoetkoming mag gestort worden', 0, 1, 'L');
		$pdf->SetY($pdf->GetY() + 2.5);
		$cwidth = 190.0 / 16.0;
		$content = array('B', 'E');
		for ($i = 0; $i < 16; $i = $i + 1)
		{
			if ($i < count($content))
				$char = $content[$i];
			else
				$char = '';
			
			if ($i < 15)
				$pdf->Cell($cwidth, 8, $char, 'LTB', 0, 'C');
			else
				$pdf->Cell(0, 8, '', 1, 1, 'C');
		}
		$pdf->SetY($pdf->GetY() + 5);
		$pdf->Cell(50, 8, 'Klevertje ziekenfonds', 0, 0, 'L');
		$pdf->Cell(0, 30, '', 1, 1, 'C');
		
		// ** Organization **
		$this->subtitle($pdf, 'DE ORGANISATIE');
		$pdf->Cell(60, 6, 'Naam van de organisatie', 0, 0, 'L');
		$pdf->Cell(0, 6, 'Badmintonclub Machelen-Diegem VZW', 'LTR', 1, 'L');
		$pdf->Cell(60, 6, 'Adres', 0, 0, 'L');
		$x = $pdf->GetX();
		$pdf->Cell(0, 6, 'Bosveld 10', 'LTR', 1, 'L');
		$pdf->SetX($x);
		$pdf->Cell(0, 6, '1830 Machelen (Bt.)', 'LR', 1, 'L');
		$pdf->Cell(60, 6, 'Emailadres', 0, 0, 'L');
		$pdf->Cell(0, 6, 'info@bamadi.be', 'LTR', 1, 'L');
		$pdf->Cell(60, 6, 'Website', 0, 0, 'L');
		$pdf->Cell(0, 6, 'www.bamadi.be', 'LTR', 1, 'L');
		$pdf->Cell(60, 6, 'Type sportclub', 0, 0, 'L');
		$pdf->Cell(0, 6, 'Badmintonclub', 1, 1, 'L');
		
		// ** Membership **
		$this->subtitle($pdf, 'LIDMAATSCHAP');
		$pdf->Cell(60, 6, 'Periode van lidmaatschap', 0, 0, 'L');
		$pdf->Cell(0, 6, '01/01/' . $this->decode($input['year']) . ' tot 31/12/' . $this->decode($input['year']), 'LTR', 1, 'L');
		$pdf->Cell(60, 6, 'Lidgeld', 0, 0, 'L');
		$pdf->Cell(0, 6, $this->decode($input['fee']) . ' ' . EURO, 1, 1, 'L');
		$pdf->SetY($pdf->GetY() + 2.5);
		$pdf->SetFont('Helvetica', 'B');
		$pdf->Cell(30, 6, 'Datum', 0, 0, 'L');
		$pdf->SetFont('Helvetica', '');
		$pdf->Cell(30, 6, $this->decode($input['downloaded']), 0, 0, 'L');
		$pdf->SetFont('Helvetica', 'B');
		$x = $pdf->GetX();
		$pdf->Cell(0, 6, 'Handtekening en stempel', 0, 1, 'L');
		$y = $pdf->GetY();
		$pdf->SetFont('Helvetica', '');
		$pdf->Image(JRoute::_('media/com_badminton/images/bamadi_small.jpg'), $x + 1, $y + 1, 25);
		$pdf->Image(JRoute::_('media/com_badminton/images/nhs_signature.jpg'), $x + 30, $y + 1, 40);
		$pdf->SetXY($x, $y + 25);
		$pdf->Cell(0, 6, 'Walter Rottie', 0, 1, 'L');
		$pdf->SetX($x); $pdf->Cell(0, 6, 'Voorzitter', 0, 1, 'L');
		$pdf->SetX($x); $pdf->Cell(0, 6, 'Badmintonclub Machelen-Diegem VZW', 0, 1, 'L');
	}
	
	/**
	 * Draw a subtitle
	 *
	 * @param mixed $pdf		The PDF object
	 * @param string $txt		The header string
	 */
	protected function subtitle($pdf, $txt)
	{
		$pdf->SetY($pdf->GetY() + 4);
		$pdf->SetFont('Helvetica', 'B', 12);
		$pdf->Cell(0, 8, $txt, 1, 1, 'L');
		$pdf->SetY($pdf->GetY() + 2.5);
		$pdf->SetFont('Helvetica', '', 10);
	}
}