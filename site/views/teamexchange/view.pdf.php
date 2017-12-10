<?php
/**
 * @package Joomla.Administrator
 * @subpackage COM_BADMINTONVLAANDEREN
 */
defined('_JEXEC') or die('Restricted access');

// Load the pdf library
JLoader::register('FPDF', JRoute::_(JPATH_ROOT . "/media/com_badmintonvlaanderen/php/fpdf.php"));

class BadmintonVlaanderenViewTeamExchange extends JViewLegacy
{
	protected $lineh = 6;
	protected $competitionLevel = -1;
	protected $competitionType = -1;
	
	// Generate the PDF
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		
		// Generate the filename
		$txtInput = $app->input->get('teamexchange', '[]', 'STRING');
		$input = $this->setupInput(json_decode($txtInput));
		$filename = $this->generatePdf($input);
		
		// Return the file
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filename));
		readFile($filename);

		// Remove the file
		unlink($filename);
	}
	
	/*
	 * Setup the input values
	 */
	private function setupInput($input) {
		if ($input == null)
			$input = (object) array();
		
		// Extract the type
		if (isset($input->type)) {
			switch ($input->type) {
				case 'mixed': $this->competitionType = 0; break;
				case 'men': $this->competitionType = 1; break;
				case 'women': $this->competitionType = 2; break;
			}
		}
		else
		{
			$this->competitionType = -1;
		}
		
		// Extract the competition level
		if (isset($input->level)) {
			switch ($input->level) {
				case 'league': $this->competitionLevel = 0; break;
				case 'youth': $this->competitionLevel = 1; break;
				case 'adults': $this->competitionLevel = 2; break;
			}
		}
		else
		{
			$this->competitionLevel = -1;
		}
		
		if (!isset($input->division))
			$input->division = "";
		if (!isset($input->series))
			$input->series = "";
		
		// Setup matches
		if (!isset($input->matches))
		{
			$input->matches = array();
			for ($i = 0; $i < 8; $i++)
			{
				$match = (object)array();
				$match->players = array();
				$match->players[] = (object)array("name" => "", "id" => "", "singles" => "", "doubles" => "", "mixed" => "");
				if ($i > 3)
				{
					$match->players[] = (object)array("name" => "", "id" => "", "singles" => "", "doubles" => "", "mixed" => "");
				}
				$match->matchtype = "";
				$input->matches[] = $match;
			}
		}
		
		// Substitutes
		if (!isset($input->substitutes))
		{
			$input->substitutes = array();
			for ($i = 0; $i < 4; $i++)
			{
				$input->substitutes[] = (object)array("name" => "", "id" => "", "singles" => "", "doubles" => "", "mixed" => "");
			}
		}

		// Return input
		return $input;
	}
	
	/**
	 * Output player data to a PDF
	 *
	 * @param $pdf		The pdf object
	 * @param $player	The player object
	 */
	private function outputPlayer($pdf, $player) {

		// Display data
		$pdf->Cell(180, $this->lineh, isset($player->name) ? $this->decode($player->name) : "", 1, 0, 'L', false);
		$pdf->Cell(25, $this->lineh, isset($player->id) ? $this->decode($player->id) : "", 1, 0, 'C', false);
		$pdf->Cell(0, $this->lineh, $this->ranking($player), 1, 0, 'C', false);
	}
	
	/*
	 * Writing vertical text in a PDF
	 */
	private function verticalText($pdf, $left, $top, $width, $height, $spacing, $txt) {
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		
		$characters = str_split($txt);
		$size = $spacing * count($characters);
		for ($i = 0; $i < count($characters); $i++) {
			$pdf->SetXY($left, $top + ($height / 2) - ($size / 2) + ($spacing * $i));
			$pdf->Cell($width, $spacing, $characters[$i], 0, 0, 'C', false);
		}
		
		// Set position back
		$pdf->SetXY($x, $y);
	}
	
	/*
	 * Generate a ranking
	 */
	private function ranking($player) {
		$ranking = '';
		$singles = isset($player->singles) ? $this->decode($player->singles) : '';
		$doubles = isset($player->doubles) ? $this->decode($player->doubles) : '';
		$mixed = isset($player->mixed) ? $this->decode($player->mixed) : '';
		if ($singles === '' || $doubles === '')
			return '';
		if ($this->competitionType == 0)
			return $singles . ' - ' . $doubles . ' - ' . $mixed;
		return $singles . ' - ' . $doubles;
	}
	
	/*
	 * Decode
	 */
	private function decode($txt) {
		return iconv('UTF-8', 'windows-1252', $txt);
	}
	
	/*
	 * Generate a PDF
	 */
	private function generatePdf($input) {
		// Generate a unique filename for the PDF
		$filename = "teamexchange.pdf";
		if (file_exists($filename))
			unlink($filename);
		
		// Generate the PDF file
		$pdf = new FPDF("L", "mm", "A4");
		$pdf->AddPage();
		
		// Draw title
		$pdf->Image(JRoute::_('media/com_badmintonvlaanderen/images/bamadi.jpg'), 10, 5, 50);
		$pdf->SetFont('Helvetica', 'B', 20);
		$pdf->SetXY(60, 8);
		$pdf->SetFillColor(200);
		$pdf->Cell(0, 30, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_PDF_TITLE'), 1, 1, 'C', true);
				
		// Draw options
		$pdf->SetFont('Helvetica', '', 12);
		$pdf->Ln(8);
		$y = $pdf->GetY();

		// LEVEL
		$pdf->Cell(55, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_LEVEL_LEAGUE'), 'LTR');
		$pdf->Cell(10, $this->lineh, $this->competitionLevel == 0 ? "X" : "", 'TR', 1, 'C');
		$pdf->Cell(55, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_LEVEL_YOUTH'), 'LTR');
		$pdf->Cell(10, $this->lineh, $this->competitionLevel == 1 ? "X" : "", 'TR', 1, 'C');
		$pdf->Cell(55, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_LEVEL_ADULTS'), 1);
		$pdf->Cell(10, $this->lineh, $this->competitionLevel == 2 ? "X" : "", 'TRB', 0, 'C');
		
		// TYPE
		$rankings = JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_SINGLES') . " - " . JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_DOUBLES');
		if ($this->competitionType == 0)
			$rankings .= " - " . JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_MIXED');
		$pdf->SetXY(90, $y);
		$pdf->Cell(25, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TYPE_MIXED'), 'LTR');
		$pdf->Cell(10, $this->lineh, $this->competitionType == 0 ? "X" : "", 'TR', 1, 'C'); $pdf->SetX(90);
		$pdf->Cell(25, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TYPE_MEN'), 'LTR');
		$pdf->Cell(10, $this->lineh, $this->competitionType == 1 ? "X" : "", 'TR', 1, 'C'); $pdf->SetX(90);
		$pdf->Cell(25, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TYPE_WOMEN'), 1);
		$pdf->Cell(10, $this->lineh, $this->competitionType == 2 ? "X" : "", 'TRB', 0, 'C');
		
		// DIVISION-SERIES
		$pdf->SetXY(135, $y);
		$pdf->Cell(25, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_DIVISON'), 1);
		$pdf->Cell(35, $this->lineh, isset($input->division) ? $this->decode($input->division) : "", 'TRB', 1, 'C');
		$pdf->SetXY(135, $pdf->GetY() + 6);
		$pdf->Cell(25, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_SERIES'), 1);
		$pdf->Cell(35, $this->lineh, isset($input->series) ? $this->decode($input->series) : "", 'TRB', 0, 'C');
		
		// DATE - START - END
		$pdf->SetXY(210, $y);
		$pdf->Cell(30, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_DATE'), 'LTR');
		$pdf->Cell(0, $this->lineh, isset($input->date) ? $this->decode($input->date) : "", 'TR', 1, 'C'); $pdf->SetX(210);
		$pdf->Cell(30, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_START'), 'LTR');
		$pdf->Cell(0, $this->lineh, isset($input->start) ? $this->decode($input->start) : "", 'TR', 1, 'C'); $pdf->SetX(210);
		$pdf->Cell(30, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_END'), 'LTRB');
		$pdf->Cell(0, $this->lineh, "", 'TRB', 1, 'C');
		$pdf->Ln(2);
		
		// HOME - VISITORS
		$pdf->Cell(30, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_HOME'), "LTB", 0, 'C');
		$pdf->Cell(100, $this->lineh, isset($input->home) ? $this->decode($input->home) : "", 'LTB', 0, 'L');
		$pdf->Cell(30, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_VISITORS'), "LTB", 0, 'C');
		$pdf->Cell(0, $this->lineh, isset($input->visitors) ? $this->decode($input->visitors) : "", 'LTRB', 1, 'L');
		$pdf->Cell(60, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TEAM_CAPTAIN'), 'LTB', 0, 'C');
		$pdf->Cell(0, $this->lineh, isset($input->captain) ? $this->decode($input->captain) : "", "LTRB", 1, 'L');
		$pdf->Ln(2);
		
		// HEADER
		$pdf->Cell(28, $this->lineh, "", 0, 0, 'C', true);
		$pdf->Cell(180, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_NAME'), 1, 0, 'C', false);
		$pdf->Cell(25, $this->lineh, JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_MEMBERID'), 1, 0, 'C', false);
		$pdf->Cell(0, $this->lineh, $this->decode($rankings), 1, 1, 'C', false);
		
		// TITULARISSEN
		$pdf->SetFontSize(10);
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->Cell(8, 74, "", 1, 1, 'C', true);
		$pdf->Cell(0, 2, "", 'T', 1, 'C', true);
		$pdf->Cell(8, 24, "", 1, 1, 'C', true);
		$pdf->SetFontSize(8);
		$this->verticalText($pdf, $x, $y, 8, 74, 2.5, strtoupper(JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_PLAYERS')));
		$this->verticalText($pdf, $x, $y + 76, 8, 24, 2.5, strtoupper(JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_SUBSTITUTES')));
		$pdf->SetFontSize(12);
		
		// PLAYERS
		$cy = $y;
		for ($i = 0; $i < count($input->matches); $i++) {
			
			// Find number of players and display the match type
			$nplayers = count($input->matches[$i]->players);
			$pdf->SetXY($x + 8, $cy);
			$pdf->Cell(20, $nplayers * $this->lineh, $this->decode($input->matches[$i]->matchtype), 1, 1, 'C', false);
			
			// Output the players
			for ($p = 0; $p < $nplayers; $p++) {
				$pdf->SetXY($x + 28, $cy);
				$this->outputPlayer($pdf, $input->matches[$i]->players[$p]);
				$cy += $this->lineh;
			}

			// Add a small spacing to separate doubles from singles
			if ($i == 3)
				$cy += 2;
		}
		
		// SUBSTITUTES
		$cy += 2;
		$type_cy = $cy;
		$pdf->setXY($x + 28, $cy);
		for ($i = 0; $i < count($input->substitutes); $i++) {
			$pdf->SetXY($x + 28, $cy);
			$this->outputPlayer($pdf, $input->substitutes[$i]);
			$cy += $this->lineh;
		}
		$pdf->setXY($x + 8, $type_cy);
		$pdf->Cell(20, count($input->substitutes) * $this->lineh, '', 1, 0, 'C', false);
		
		// Write the file
		$pdf->Output($filename);
		return $filename;
	}
}