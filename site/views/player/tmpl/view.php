<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JLoader::register('JHtmlPlayers', JPATH_COMPONENT . '/helpers/html/players.php');
JHtml::register('players.avatar', array('JHtmlPlayers', 'avatar'));
JHtml::register('players.spacer', array('JHtmlPlayers', 'spacer'));
JHtml::register('players.playergroups', array('JHtmlPlayers', 'playergroups'));
JHtml::register('players.competitive', array('JHtmlPlayers', 'competitive'));
JHtml::register('players.allowed', array('JHtmlPlayers', 'allowed'));
JHtml::register('players.gender', array('JHtmlPlayers', 'gender'));
JHtml::register('players.playerlist', array('JHtmlPlayers', 'playerlist'));
?>

<?php $fields = $this->form->getFieldset(); ?>
<?php if (count($fields)) : ?>
<fieldset id="users-profile-custom">
	<legend><?= JText::_('COM_BADMINTON_PLAYER_DETAILS'); ?></legend>
	<dl class="dl-horizontal">
	<?php foreach ($fields as $field):
		if (!$field->hidden) :?>
		<dt><?= $field->title; ?></dt>
		<dd>
			<?php if ($field->fieldname == 'avatar_image') : ?>
				<?php echo JHtml::_('players.avatar', $this->avatar); ?>
			<?php elseif (JHtml::isRegistered('players.' . $field->id)):?>
				<?php echo JHtml::_('players.' . $field->id, $field->value);?>
			<?php elseif (JHtml::isRegistered('players.' . $field->fieldname)):?>
				<?php echo JHtml::_('players.' . $field->fieldname, $field->value);?>
			<?php elseif (JHtml::isRegistered('players.' . $field->type)):?>
				<?php echo JHtml::_('players.' . $field->type, $field->value);?>
			<?php else:?>
				<?php echo JHtml::_('players.value', $field->value);?>
			<?php endif;?>
		</dd>
		<?php endif;?>
	<?php endforeach;?>
	</dl>
</fieldset>
<?php endif;?>
