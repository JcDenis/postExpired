<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of postExpired, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {

	return null;
}

# Check plugin version
if ($core->getVersion('postExpired') != $core->plugins->moduleInfo('postExpired', 'version')) {

	return null;
}

# Check user right
if (!$core->auth->check('contentadmin', $core->blog->id)) {

	return null;
}

# Admin behaviors
$core->addBehavior(
	'adminPostsActionsPage',
	array('adminBehaviorPostExpired', 'adminPostsActionsPage')
);
$core->addBehavior(
	'adminPagesActionsPage',
	array('adminBehaviorPostExpired', 'adminPostsActionsPage')
);
$core->addBehavior(
	'adminPostHeaders',
	array('adminBehaviorPostExpired', 'adminPostHeaders')
);
$core->addBehavior(
	'adminPageHeaders',
	array('adminBehaviorPostExpired', 'adminPostHeaders')
);
$core->addBehavior(
	'adminPostFormItems',
	array('adminBehaviorPostExpired', 'adminPostFormItems')
);
$core->addBehavior(
	'adminPageFormItems',
	array('adminBehaviorPostExpired', 'adminPostFormItems')
);
$core->addBehavior(
	'adminBeforePostDelete',
	array('adminBehaviorPostExpired', 'adminBeforePostDelete')
);
$core->addBehavior(
	'adminBeforePageDelete',
	array('adminBehaviorPostExpired', 'adminBeforePostDelete')
);
$core->addBehavior(
	'adminAfterPostUpdate',
	array('adminBehaviorPostExpired', 'adminAfterPostSave')
);
$core->addBehavior(
	'adminAfterPageUpdate',
	array('adminBehaviorPostExpired', 'adminAfterPostSave')
);
$core->addBehavior(
	'adminAfterPostCreate',
	array('adminBehaviorPostExpired', 'adminAfterPostSave')
);
$core->addBehavior(
	'adminAfterPageCreate',
	array('adminBehaviorPostExpired', 'adminAfterPostSave')
);

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - admin methods.
 * @since 2.6
 */
class adminBehaviorPostExpired
{
	/**
	 * Add actions to posts page combo
	 * 
	 * @param  dcCore             $core dcCore instance
	 * @param  dcPostsActionsPage $ap   dcPostsActionsPage instance
	 */
	public static function adminPostsActionsPage(dcCore $core, dcPostsActionsPage $pa)
	{
		$pa->addAction(
			array(
				__('Expired entries') => array(
					__('Add expired date') => 'post_expired_add'
				)
			),
			array('adminBehaviorPostExpired', 'callbackAdd')
		);

		$pa->addAction(
			array(
				__('Expired entries') => array(
					__('Remove expired date') => 'post_expired_remove'
				)
			),
			array('adminBehaviorPostExpired', 'callbackRemove')
		);
	}

	/**
	 * Add javascript for date field and toggle
	 * 
	 * @return string HTML head
	 */
	public static function adminPostHeaders()
	{
		return dcPage::jsLoad('index.php?pf=postExpired/js/postexpired.js');
	}

	/**
	 * Add form to post sidebar
	 * 
	 * @param  ArrayObject $main_items    Main items
	 * @param  ArrayObject $sidebar_items Sidebar items
	 * @param  record      $post          Post record or null
	 */
	public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, $post)
	{
		if ($post === null) {

			return null;
		}

		$sidebar_items['post_expired'] = array(
			'title' => __('Expired date'),
			'items' => self::fieldsPostExpired(
				$GLOBALS['core'],
				$post->post_type,
				$post->post_id
			)
		);
	}

	/**
	 * Delete expired date on post edition
	 * 
	 * @param  integer $post_id Post id
	 */
	public static function adminBeforePostDelete($post_id)
	{
		self::delPostExpired($GLOBALS['core'], $post_id);
	}

	/**
	 * Add expired date on post edition
	 * 
	 * @param  cursor $cur      Current post cursor
	 * @param  integer $post_id Post id
	 */
	public static function adminAfterPostSave(cursor $cur, $post_id)
	{
		global $core;

		self::delPostExpired($core, $post_id);

		if (!empty($_POST['post_expired_date'])
		 && (!empty($_POST['post_expired_status'])
		  || !empty($_POST['post_expired_category'])
		  || !empty($_POST['post_expired_selected'])
		  || !empty($_POST['post_expired_comment'])
		  || !empty($_POST['post_expired_trackback']))
		) {
			self::setPostExpired($core, $post_id, $_POST);
		}
	}

	/**
	 * Posts actions callback to add expired date
	 * 
	 * @param  dcCore             $core dcCore instance
	 * @param  dcPostsActionsPage $pa   dcPostsActionsPage instance
	 * @param  ArrayObject        $post _POST actions
	 */
	public static function callbackAdd(dcCore $core, dcPostsActionsPage $pa, ArrayObject $post)
	{
		# No entry
		$posts_ids = $pa->getIDs();
		if (empty($posts_ids)) {
			throw new Exception(__('No entry selected'));
		}

		# Add epired date
		if (!empty($post['post_expired_date'])
		 && (!empty($post['post_expired_status'])
		  || !empty($post['post_expired_category'])
		  || !empty($post['post_expired_selected'])
		  || !empty($post['post_expired_comment'])
		  || !empty($post['post_expired_trackback']))
		) {
			foreach($posts_ids as $post_id) {
				self::delPostExpired($core, $post_id);
				self::setPostExpired($core, $post_id, $post);
			}

			dcPage::addSuccessNotice(__('Expired date added.'));
			$pa->redirect(true);
		}

		# Display form
		else {
			# Get records to know post type
			$posts = $pa->getRS();

			$pa->beginPage(
				dcPage::breadcrumb(array(
					html::escapeHTML($core->blog->name) => '',
					$pa->getCallerTitle() => $pa->getRedirection(true),
					__('Add expired date to this selection') => '' 
				)),
				dcPage::jsDatePicker().
				self::adminPostHeaders()
			);

			echo
			'<form action="'.$pa->getURI().'" method="post">'.
			$pa->getCheckboxes().

			implode('', self::fieldsPostExpired($core, $posts->post_type)).

			$core->formNonce().
			$pa->getHiddenFields().
			form::hidden(array('action'), 'post_expired_add').
			'<input type="submit" value="'.__('Save').'" /></p>'.
			'</form>';

			$pa->endPage();
		}
	}

	/**
	 * Posts actions callback to add expired date
	 * 
	 * @param  dcCore             $core dcCore instance
	 * @param  dcPostsActionsPage $pa   dcPostsActionsPage instance
	 * @param  ArrayObject        $post _POST actions
	 */
	public static function callbackRemove(dcCore $core, dcPostsActionsPage $pa, ArrayObject $post)
	{
		# No entry
		$posts_ids = $pa->getIDs();
		if (empty($posts_ids)) {
			throw new Exception(__('No entry selected'));
		}

		# Delete expired date
		foreach($posts_ids as $post_id) {
			self::delPostExpired($core, $post_id);
		}

		dcPage::addSuccessNotice(__('Expired date deleted.'));
		$pa->redirect(true);
	}

	/**
	 * Delete expired date
	 * 
	 * @param  dcCore  $core    dcCore instance
	 * @param  integer $post_id Post id
	 */
	protected static function delPostExpired(dcCore $core, $post_id)
	{
		$core->meta->delPostMeta($post_id, 'post_expired');
	}

	/**
	 * Save expired date
	 * 
	 * @param dcCore  $core    dcCore instance
	 * @param integer $post_id Post id
	 * @param array   $post    _POST fields
	 */
	protected static function setPostExpired(dcCore $core, $post_id, $post)
	{
		$post_expired = array(
			'status'		=> '',
			'category'	=> '',
			'selected'	=> '',
			'comment'		=> '',
			'trackback'	=> '',
			'date'		=> date(
				'Y-m-d H:i:00',
				strtotime($post['post_expired_date'])
			)
		);

		if (!empty($post['post_expired_status'])) {
			$post_expired['status'] =
				(string) $post['post_expired_status'];
		}
		if (!empty($post['post_expired_category'])) {
			$post_expired['category'] =
				(string) $post['post_expired_category'];
		}
		if (!empty($post['post_expired_selected'])) {
			$post_expired['selected'] =
				(string) $post['post_expired_selected'];
		}
		if (!empty($post['post_expired_comment'])) {
			$post_expired['comment'] =
				(string) $post['post_expired_comment'];
		}
		if (!empty($post['post_expired_trackback'])) {
			$post_expired['trackback'] =
				(string) $post['post_expired_trackback'];
		}

		$core->meta->setPostMeta(
			$post_id,
			'post_expired',
			encodePostExpired($post_expired)
		);
	}

	/**
	 * Expired date form fields
	 * 
	 * @param  dcCore $core      dcCore instance
	 * @param  string $post_type Posts type
	 * @return array             Array of HTML form fields
	 */
	protected static function fieldsPostExpired(dcCore $core, $post_type, $post_id=null)
	{
		$fields = $post_expired = array();

		if ($post_id) {

			$rs = $core->meta->getMeta(
				'post_expired',
				1,
				null,
				$post_id
			);

			if (!$rs->isEmpty()) {
				$post_expired = decodePostExpired($rs->meta_id);
			}
		}

		$fields['post_expired_date'] =
			'<p><label for="post_expired_date">'.
			__('Date:').'</label>'.
			form::field(
				'post_expired_date',
				16,
				16,
				empty($post_expired['date']) ? 
					'' : $post_expired['date']
			).'</p>';

		$fields['post_expired_status'] =
			'<h5>'.__('On this date, change:').'</h5>'.
			'<p><label for="post_expired_status">'.
			__('Status:').'</label>'.
			form::combo(
				'post_expired_status',
				self::statusCombo(),
				empty($post_expired['status']) ? 
					'' : $post_expired['status']
			).'</p>';

		if ($post_type == 'post') {

			$fields['post_expired_category'] =
				'<p><label for="post_expired_category">'.
				__('Category:').'</label>'.
				form::combo(
					'post_expired_category',
					self::categoriesCombo(
						$core->blog->getCategories(
							array('post_type' => 'post')
						)
					),
					empty($post_expired['category']) ? 
						'' : $post_expired['category']
				).'</p>';

			$fields['post_expired_selected'] =
				'<p><label for="post_expired_selected">'.
				__('Selection:').'</label>'.
				form::combo(
					'post_expired_selected',
					self::selectedCombo(),
					empty($post_expired['selected']) ? 
						'' : $post_expired['selected']
				).'</p>';
		}

		$fields['post_expired_comment'] =
			'<p><label for="post_expired_comment">'.
			__('Comments status:').'</label>'.
			form::combo(
				'post_expired_comment',
				self::commentCombo(),
				empty($post_expired['comment']) ? 
					'' : $post_expired['comment']
			).'</p>';

		$fields['post_expired_trackback'] =
			'<p><label for="post_expired_trackback">'.
			__('Trackbacks status:').'</label>'.
			form::combo(
				'post_expired_trackback',
				self::trackbackCombo(),
				empty($post_expired['trackback']) ? 
					'' : $post_expired['trackback']
			).'</p>';

		return $fields;
	}

	/**
	 * Custom categories combo
	 * 
	 * @param  record $categories Categories recordset
	 * @return array              Categorires combo
	 */
	protected static function categoriesCombo(record $categories)
	{
		# Getting categories
		$categories_combo = array(
			__('Not changed')	=> '',
			__('Uncategorized')	=> '!'
		);
		try {
			$categories = $GLOBALS['core']->blog->getCategories(
				array('post_type' => 'post')
			);
			while ($categories->fetch()) {
				$categories_combo[] = new formSelectOption(
					str_repeat('&nbsp;&nbsp;', $categories->level-1).'&bull; '.html::escapeHTML($categories->cat_title),
					'!'.$categories->cat_id
				);
			}
		}
		catch (Exception $e) {

			return array();
		}

		return $categories_combo;
	}

	/**
	 * Custom status combo
	 * 
	 * @return array Status combo
	 */
	protected static function statusCombo()
	{
		return array(
			__('Not changed')	=> '',
			__('Published')	=> '!1',
			__('Pending')		=> '!-2',
			__('Unpublished')	=> '!0'
		);
	}

	/**
	 * Custom selection combo
	 * 
	 * @return array Selection combo
	 */
	protected static function selectedCombo()
	{
		return array(
			__('Not changed')	=> '',
			__('Selected')		=> '!1',
			__('Not selected')	=> '!0'
		);
	}

	/**
	 * Custom comment status combo
	 * 
	 * @return array Comment status combo
	 */
	protected static function commentCombo()
	{
		return array(
			__('Not changed')	=> '',
			__('Opened')		=> '!1',
			__('Closed')		=> '!0'
		);
	}

	/**
	 * Custom trackback status combo
	 * 
	 * @return array Trackback status combo
	 */
	protected static function trackbackCombo()
	{
		return array(
			__('Not changed')	=> '',
			__('Opened')		=> '!1',
			__('Closed')		=> '!0'
		);
	}
}
