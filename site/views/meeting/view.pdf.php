<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

// Load the pdf library
require_once(JRoute::_("media/com_badminton/php/fpdf.php"));

class BadmintonViewMeeting extends JViewLegacy
{
	protected $competitionLevel = -1;
	protected $competitionType = -1;
	
	// Some page parameters
	protected $lineh = 7;
	protected $lw = 185;
	protected $rw = 90;
	protected $typew = 12;
	protected $namew = 60;
	protected $rankw = 8;
	protected $memberw = 18;
	protected $total;
	
	// Generate the PDF
	public function display($tpl = null) {
		
		// Authorise user
		$app = JFactory::getApplication();
		if (!JFactory::getUser()->authorise('badminton.meeting', 'com_badminton')) {
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			return false;
		}
		
		// Generate the filename
		$txtInput = $app->input->get('meeting', '[]', 'STRING');
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
		
		// Initialize totals
		$this->total = new stdClass();
		$this->total->hscore = 0;
		$this->total->hgames = 0;
		$this->total->hmatch = 0;
		$this->total->vscore = 0;
		$this->total->vgames = 0;
		$this->total->vmatch = 0;
		
		if ($input == null)
			return new stdClass();
		
		// Extract the type
		if (isset($input->type)) {
			switch ($input->type) {
				case 'mixed': $this->competitionType = 0; break;
				case 'men': $this->competitionType = 1; break;
				case 'women': $this->competitionType = 2; break;
				case 'youth': $this->competitionType = 3; break;
			}
		}
		
		// Extract the competition level
		if (isset($input->level)) {
			switch ($input->level) {
				case 'national': $this->competitionLevel = 0; break;
				case 'league': $this->competitionLevel = 1; break;
				case 'regional': $this->competitionLevel = 2; break;
			}
		}
		
		// Return input
		return $input;
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
	 * Write game
	 */
	private function writeGame($pdf, $match) {

		// Store left-top position
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		if (!isset($match->home) || !isset($match->visitor))
			die('Incomplete data');

		// Write players
		$p = 1;
		$player = 'player' . $p;
		$height = 0;
		$cy = $y;
		while (isset($match->home->$player) && isset($match->visitor->$player)) {
			$pdf->SetXY($x + $this->typew, $cy);
			
			// Write second player
			$pdf->cell($this->namew, $this->lineh, $this->decode($match->home->$player->lastname) . ' ' . $this->decode($match->home->$player->firstname), 'BR', 0, 'L');
			$pdf->cell($this->rankw, $this->lineh, $this->decode($match->home->$player->rank), 'BR', 0, 'L');
			$pdf->Cell($this->memberw, $this->lineh, $this->decode($match->home->$player->memberid), 'BR', 0, 'L');
			$pdf->Cell($this->lw - 2 * ($this->namew + $this->rankw + $this->memberw) - $this->typew, 6, "", '', 0, 'L'); // Space
			$pdf->cell($this->namew, $this->lineh, $this->decode($match->visitor->$player->lastname) . ' ' . $this->decode($match->visitor->$player->firstname), 'LBR', 0, 'L');
			$pdf->cell($this->rankw, $this->lineh, $this->decode($match->visitor->$player->rank), 'BR', 0, 'L');
			$pdf->Cell($this->memberw, $this->lineh, $this->decode($match->visitor->$player->memberid), 'BR', 0, 'L');
			$cy += $this->lineh;
			$height += $this->lineh;
			$player = 'player' . (++$p);
		}
		$pdf->SetXY($x, $y);
		$pdf->Cell($this->typew, $height, $this->decode($match->matchtype), 1, 0, 'C');
		
		// Write scores
		$pdf->SetXY($x + $this->lw, $y);
		$pdf->SetLineWidth(0.2);
		$hpoints = 0; $hgames = 0; $hmatch = 0;
		$vpoints = 0; $vgames = 0; $vmatch = 0;
		for ($i = 0; $i < 3; $i++) {
			$h = -1; $v = -1;
			if ($match->scores[$i][0] !== '') {
				$h = intval($match->scores[$i][0]);
				$hpoints += $h;
				$this->total->hscore += $h;
			}
			else
				$h = -1;
			if ($match->scores[$i][1] !== '') {
				$v = intval($match->scores[$i][1]);
				$vpoints += $v;
				$this->total->vscore += $v;
			}
			else
				$v = -1;
			$pdf->Cell($this->rw / 12.0, $height, $h >= 0 ? $h : "", 'BR', 0, 'C');
			$pdf->Cell($this->rw / 12.0, $height, $v >= 0 ? $v : "", 'BR', 0, 'C');
			
			// Get winner of the game
			if ($h > $v) {
				$hgames++;
				$this->total->hgames++;
			}
			if ($v > $h) {
				$vgames++;
				$this->total->vgames++;
			}
		}
		
		// Total games
		$pdf->Cell($this->rw / 12.0, $height, $hgames, 'BR', 0, 'C');
		$pdf->Cell($this->rw / 12.0, $height, $vgames, 'BR', 0, 'C');
		
		// Total matches
		if ($hgames > $vgames && $hgames >= 2) {
			$hmatch = 1;
			$this->total->hmatch++;
		}
		if ($vgames > $hgames && $vgames >= 2) {
			$vmatch = 1;
			$this->total->vmatch++;
		}
		$pdf->Cell($this->rw / 12, $height, $hmatch, 'BR', 0, 'C');
		$pdf->Cell($this->rw / 12, $height, $vmatch, 'BR', 0, 'C');
		$pdf->Cell($this->rw / 12, $height, $hpoints, 'BR', 0, 'C');
		$pdf->Cell($this->rw / 12, $height, $vpoints, 'BR', 0, 'C');
		$pdf->SetXY($x, $y + $height);
	}
	
	/*
	 * Generate a PDF
	 */
	private function generatePdf($input = array()) {
		
		// Calculate some display parameters
		$lh = 15 * $this->lineh + 4;
		$rh = 15 * $this->lineh + 4;
		$rh2 = 2 * $this->lineh;
		$hgamestot = 0;
		$vgamestot = 0;
		$hmatchtot = 0;
		$vmatchtot = 0;
		$hpointstot = 0;
		$vpointstot = 0;
		
		// Generate a unique filename for the PDF
		$filename = "teamexchange.pdf";
		
		// Generate the PDF file
		$pdf = new FPDF("L", "mm", "A4");
		$pdf->AddPage();
		
		// Draw title
		$pdf->Image(JRoute::_('media/com_badminton/images/bamadi.jpg'), 10, 5, 20);
		$pdf->SetFont('Helvetica', '', 10);
		$pdf->SetXY(82, 11);
		$pdf->Cell(54, 7, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_TITLE'), 'LT', 0, 'C');
		
		// LEVEL
		$pdf->Cell(26, 7, JText::_('COM_BADMINTON_VIEW_MEETING_NATIONAL'), 'LT', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionLevel == 0 ? "X" : "", 'LT', 0, 'C');
		$pdf->Cell(10, 7, JText::_('COM_BADMINTON_VIEW_MEETING_LEAGUE'), 'LT', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionLevel == 1 ? "X" : "", 'LT', 0, 'C');
		$pdf->Cell(21, 7, JText::_('COM_BADMINTON_VIEW_MEETING_PROVINCE'), 'LT', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionLevel == 2 ? "X" : "", 'LTR', 1, 'C');
		
		// TYPE
		$pdf->SetX(82);
		$pdf->Cell(26, 7, JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_MIXED'), 'LTB', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionType == 0 ? "X" : "", 'LTB', 0, 'C');
		$pdf->Cell(19, 7, JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_MEN'), 'LTB', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionType == 1 ? "X" : "", 'LTB', 0, 'C');
		$pdf->Cell(29, 7, JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_WOMEN'), 'LTB', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionType == 2 ? "X" : "", 'LTB', 0, 'C');
		$pdf->Cell(28, 7, JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_YOUTH'), 'LTB', 0, 'C');
		$pdf->Cell(9, 7, $this->competitionType == 3 ? "X" : "", 'LTRB', 1, 'C');

		// Store position
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->SetLineWidth(0.4);
		$pdf->Cell($this->lw, $lh, "", "LTRB", 0, 'C');
		$pdf->Cell($this->rw, $rh, "", "LTRB", 2, 'C');
		$pdf->Cell($this->rw, $rh2, "", "LTRB", 2, 'C');
		
		// Players
		$pdf->SetLineWidth(0.2);
		$pdf->SetXY($x, $y);
		$pdf->Cell($this->typew, $this->lineh * 3, "", "BR", 0, 'C');
		$pdf->Cell($this->lw - $this->typew, $this->lineh, strtoupper(JText::_('COM_BADMINTON_VIEW_MEETING_MATCH')), "", 0, 'C');
		$pdf->Cell($this->rw / 3 * 2 - 5, $this->lineh,
			JText::_('COM_BADMINTON_VIEW_MEETING_PDF_DIVISON_SERIES') . ' '
			. $this->decode($input->division) . "/" . $this->decode($input->series), "B", 0, 'L');
		$pdf->Cell($this->rw / 3 + 5, $this->lineh, "Datum: " . $this->decode($input->date), "B", 1, 'L');
		$pdf->SetX($x + $this->typew);
		$pdf->Cell($this->namew + $this->rankw + $this->memberw, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_HOME') . ': ' . $this->decode($input->home), 'BR', 0, 'L');
		$pdf->Cell($this->lw - $this->typew - 2 * ($this->namew + $this->rankw + $this->memberw), $this->lineh, '', '', 0, 'L');
		$pdf->Cell($this->namew + $this->rankw + $this->memberw, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_VISITORS') . ': ' . $this->decode($input->visitors), 'LB', 0, 'L');
		$pdf->Cell($this->rw, $this->lineh, strtoupper(JText::_('COM_BADMINTON_VIEW_MEETING_PDF_RESULTS')), "", 1, 'C');
		
		// NAME-MEMBERID
		$pdf->SetX($x + $this->typew);
		$pdf->Cell($this->namew, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_NAME'), 'BR', 0, 'L');
		$pdf->Cell($this->rankw, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_RANK'), 'BR', 0, 'L');
		$pdf->Cell($this->memberw, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_MEMBERID'), 'BR', 0, 'L');
		$pdf->Cell($this->lw - $this->typew - 2 * ($this->namew + $this->rankw + $this->memberw), $this->lineh, "", '', 0, 'L');
		$pdf->Cell($this->namew, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_NAME'), 'LBR', 0, 'L');
		$pdf->Cell($this->rankw, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_RANK'), 'BR', 0, 'L');
		$pdf->Cell($this->memberw, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_PDF_MEMBERID'), 'BR', 0, 'L');
		
		// Fat lines
		$x2 = $pdf->GetX();
		$pdf->SetLineWidth(0.4);
		for ($i = 0; $i < 6; $i++)
			$pdf->Cell($this->rw / 6, $rh - 2 * $this->lineh, "", 'R', 0, 'L');
		$pdf->SetLineWidth(0.2);
		$pdf->SetX($x2);
		$pdf->Cell($this->rw / 6, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_GAME') . ' 1', 'BR', 0, 'C');
		$pdf->Cell($this->rw / 6, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_GAME') . ' 2', 'BR', 0, 'C');
		$pdf->Cell($this->rw / 6, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_GAME') . ' 3', 'BR', 0, 'C');
		$pdf->Cell($this->rw / 6, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_GAMES'), 'BR', 0, 'C');
		$pdf->Cell($this->rw / 6, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_MATCH'), 'BR', 0, 'C');
		$pdf->Cell($this->rw / 6, $this->lineh, JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS_POINTS'), 'BR', 1, 'C');
		
		// Wedstrijden
		$pdf->SetX($x);
		$pdf->SetFont('Helvetica', '', 9);
		for ($i = 0; $i < count($input->matches); $i++) {
			if ($i == 4) {
				$pdf->Cell($this->typew + $this->namew + $this->rankw + $this->memberw, 4, "", 'B', 0, 'C');
				$pdf->Cell($this->lw - $this->typew - 2 * ($this->namew + $this->rankw + $this->memberw), 4, "", '', 0, 'C');
				$pdf->Cell($this->namew + $this->rankw + $this->memberw + $this->rw, 4, "", 'B', 1, 'C');
			}
			$this->writeGame($pdf, $input->matches[$i]);
		}
		
		// Results
		$pdf->SetX($x + $this->lw);
		$pdf->SetLineWidth(0.4);
		$pdf->Cell($this->rw / 2, $this->lineh, strtoupper(JText::_('COM_BADMINTON_VIEW_MEETING_PDF_WINNER')), 'RT', 0, 'L');
		$pdf->Cell($this->rw / 6, $this->lineh * 2, "", 'R', 0, 'L');
		$pdf->Cell($this->rw / 6, $this->lineh * 2, "", 'R', 0, 'L');
		$pdf->SetX($x + $this->lw + $this->rw / 2);
		$pdf->SetLineWidth(0.2);
		$pdf->Cell($this->rw / 12, $this->lineh * 2, $this->total->hgames, 'R', 0, 'C');
		$pdf->Cell($this->rw / 12, $this->lineh * 2, $this->total->vgames, 'R', 0, 'C');
		$pdf->Cell($this->rw / 12, $this->lineh * 2, $this->total->hmatch, 'R', 0, 'C');
		$pdf->Cell($this->rw / 12, $this->lineh * 2, $this->total->vmatch, 'R', 0, 'C');
		$pdf->Cell($this->rw / 12, $this->lineh * 2, $this->total->hscore, 'R', 0, 'C');
		$pdf->Cell($this->rw / 12, $this->lineh * 2, $this->total->vscore, '', 1, 'C');
		$pdf->SetXY($x + $this->lw, $y + $rh + $this->lineh);
		$pdf->SetLineWidth(0.4);
		if ($this->total->hmatch > $this->total->vmatch)
			$pdf->Cell($this->rw / 2, $this->lineh, $this->decode($input->home), 'R', 0, 'C');
		else if ($this->total->vmatch > $this->total->hmatch)
			$pdf->Cell($this->rw / 2, $this->lineh, $this->decode($input->visitors), 'R', 0, 'C');
		else
			$pdf->Cell($this->rw / 2, $this->lineh, "", 'R', 0, 'C');
		
		// Write the file
		$pdf->Output($filename);
		return $filename;
	}
}