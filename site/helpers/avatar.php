<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonAvatarHelper
{
	/*
	 * Check for an uploaded avatar image
	 *
	 * @param array $validImageTypes		The accepted image types
	 *
	 * @return array						Returns an array with the avatar info, false if no image was uploaded
	 */
	public static function checkAvatarUpload($validImageTypes) {
		// Initialize
		$app = JFactory::getApplication();
		
		// Check for a new avatar file - only run once ($this->uploaded is set when uploading)
		$files = $app->input->files->get('jform');
		if (isset($files['main']))
			$files = $files['main'];
		if (isset($files['avatar_image']) && 
			file_exists($files['avatar_image']['tmp_name']) && 
			is_uploaded_file($files['avatar_image']['tmp_name']))
		{
			return BadmintonAvatarHelper::uploadImage($files['avatar_image']);
		}
		return false;
	}
	
	/**
	 * Upload an image
	 *
	 * @param string $file				The filename
	 * @param array $validImageTypes	The accepted image types
	 *
	 * @return mixed					Returns an array with the avatar info if succeeded, false otherwise
	 */
	protected function uploadImage($file, $validImageTypes) {
		$db = JFactory::getDbo();
		$user = JFactory::getUser();
		$params = JFactory::getApplication()->getParams('com_badminton');
		
		try
		{
			// Note: Taken from http://php.net/manual/en/features.file-upload.php
			
			// Undefined | Multiple Files | $_FILES Corruption Attack
			// If this request falls under any of them, treat it invalid
			if (!isset($file['error']) || is_array($file['error']))
				return array("success" => false, "error" => JText::_('PLG_JGALLERY_NOACCESS'));
			
			// Check $data['error'] value
			switch ($file['error']) {
				case UPLOAD_ERR_OK: break;
				case UPLOAD_ERR_NO_FILE: return array("success" => false, "error" => JText::_('PLG_JGALLERY_NO_FILE'));
				case UPLOAD_ERR_FORM_SIZE: return array("success" => false, "error" => JText::_('PLG_JGALLERY_FILESIZE_EXCEEDED'));
				default: return array("success" => false, "error" => JText::_('PLG_JGALLERY_UNKNOWN_ERROR'));
			}
			
			// Check the filesize limit here
			if ($file['size'] > 10000000)
				return array("success" => false, "error" => JText::_('PLG_JGALLERY_FILESIZE_EXCEEDED'));
			
			// Do not trust $data['mime'] VALUE !!
			// Check MIME Type by yourself
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($file['tmp_name']);
			if (($ext = array_search($mime, $validImageTypes, true)) === false)
				return array("success" => false, "error" => JText::_('PLG_JGALLERY_INVALID_FORMAT'));
			
			// Generate a filename and thumbnail path
			while (file_exists($params->get('avatar_path', '') . '/' . ($nurl = $user->id . '_' . uniqid() . '.jpg')));

			// Upload the file
			$image = new JImage($file['tmp_name']);
			$image->resize(
				$params->get('avatar_width', 256, 'int'),
				$params->get('avatar_height', 256, 'int'),
				false);
				
			$exif = exif_read_data($file['tmp_name']);
			if (!empty($exif['Orientation']))
			{
				switch ($exif['Orientation'])
				{
					case 3: $image->rotate(180, -1, false); break;
					case 6: $image->rotate(-90, -1, false); break;
					case 8: $image->rotate(90, -1, false); break;
				}
			}
			
			// Create the image path if it doesn't exist
			if (!empty($params->get('avatar_path', '')))
			{
				if (!file_exists($params->get('avatar_path')))
					mkdir($params->get('avatar_path'), 0777, true);
			}
				
			if ($image->toFile($params->get('avatar_path', '') . '/' . $nurl))
			{
				// Make sure this can't be run again
				unlink($file['tmp_name']);
				return array('success' => true, 'avatar' => $nurl);
			}
			return array('success' => false, 'error' => 'Unknown');
		}
		catch (Exception $ex)
		{
			return array(
				'error' => $ex->getMessage(),
				'success' => false
			);
		}
		
		// Build the returned values
		return array(
			'success' => true,
			'item' => $data
		);
	}
}