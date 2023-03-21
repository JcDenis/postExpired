<?php
/**
 * @brief postExpired, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and Contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use ArrayObject;
use cursor;
use dcCore;
use dcPostsActions;
use dcPage;
use dcRecord;
use Exception;
use dt;
use html;
use form;
use formSelectOption;

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - admin methods.
 * @since 2.6
 */
class BackendBehaviors
{
    /**
     * Add actions to posts page combo
     *
     * @param  dcPostsActions $pa   dcPostsActions instance
     */
    public static function adminPostsActions(dcPostsActions $pa): void
    {
        $pa->addAction(
            [
                __('Expired entries') => [
                    __('Add expired date') => 'post_expired_add',
                ],
            ],
            [self::class, 'callbackAdd']
        );

        $pa->addAction(
            [
                __('Expired entries') => [
                    __('Remove expired date') => 'post_expired_remove',
                ],
            ],
            [self::class, 'callbackRemove']
        );
    }

    /**
     * Add javascript for date field and toggle
     *
     * @return string HTML head
     */
    public static function adminPostHeaders(): string
    {
        return dcPage::jsModuleLoad(My::id() . '/js/backend.js');
    }

    /**
     * Add form to post sidebar
     *
     * @param  ArrayObject $main_items    Main items
     * @param  ArrayObject $sidebar_items Sidebar items
     * @param  ?dcRecord   $post          Post record or null
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?dcRecord $post): void
    {
        if ($post === null) {
            return;
        }

        $sidebar_items['post_expired'] = [
            'title' => __('Expired date'),
            'items' => self::fieldsPostExpired(
                $post->f('post_type'),
                (int) $post->f('post_id')
            ),
        ];
    }

    /**
     * Delete expired date on post edition
     *
     * @param  integer $post_id Post id
     */
    public static function adminBeforePostDelete(int $post_id): void
    {
        self::delPostExpired($post_id);
    }

    /**
     * Add expired date on post edition
     *
     * @param  cursor $cur      Current post cursor
     * @param  integer $post_id Post id
     */
    public static function adminAfterPostSave(cursor $cur, int $post_id): void
    {
        self::delPostExpired($post_id);

        if (!empty($_POST['post_expired_date'])
         && (!empty($_POST['post_expired_status'])
          || !empty($_POST['post_expired_category'])
          || !empty($_POST['post_expired_selected'])
          || !empty($_POST['post_expired_comment'])
          || !empty($_POST['post_expired_trackback']))) {
            self::setPostExpired($post_id, new ArrayObject($_POST));
        }
    }

    /**
     * Posts actions callback to add expired date
     *
     * @param  dcPostsActions   $pa   dcPostsActions instance
     * @param  ArrayObject        $post _POST actions
     */
    public static function callbackAdd(dcPostsActions $pa, ArrayObject $post): void
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
          || !empty($post['post_expired_trackback']))) {
            foreach ($posts_ids as $post_id) {
                self::delPostExpired($post_id);
                self::setPostExpired($post_id, $post);
            }

            dcPage::addSuccessNotice(__('Expired date added.'));
            $pa->redirect(true);

        # Display form
        } else {
            # Get records to know post type
            $posts = $pa->getRS();

            $pa->beginPage(
                dcPage::breadcrumb([
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    $pa->getCallerTitle()                       => $pa->getRedirection(true),
                    __('Add expired date to this selection')    => '',
                ]),
                //dcPage::jsDatePicker() .
                self::adminPostHeaders()
            );

            echo
            '<form action="' . $pa->getURI() . '" method="post">' .
            $pa->getCheckboxes() .

            implode('', self::fieldsPostExpired($posts->f('post_type'))) .

            dcCore::app()->formNonce() .
            $pa->getHiddenFields() .
            form::hidden(['action'], 'post_expired_add') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
            '</form>';

            $pa->endPage();
        }
    }

    /**
     * Posts actions callback to add expired date
     *
     * @param  dcPostsActions   $pa   dcPostsActions instance
     * @param  ArrayObject        $post _POST actions
     */
    public static function callbackRemove(dcPostsActions $pa, ArrayObject $post): void
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        # Delete expired date
        foreach ($posts_ids as $post_id) {
            self::delPostExpired($post_id);
        }

        dcPage::addSuccessNotice(__('Expired date deleted.'));
        $pa->redirect(true);
    }

    /**
     * Delete expired date
     *
     * @param  integer $post_id Post id
     */
    protected static function delPostExpired(int $post_id): void
    {
        dcCore::app()->meta->delPostMeta($post_id, My::META_TYPE);
    }

    /**
     * Save expired date
     *
     * @param integer $post_id Post id
     * @param ArrayObject   $post    _POST fields
     */
    protected static function setPostExpired(int $post_id, ArrayObject $post): void
    {
        $post_expired = [
            'status'    => '',
            'category'  => '',
            'selected'  => '',
            'comment'   => '',
            'trackback' => '',
            'date'      => date(
                'Y-m-d H:i:00',
                strtotime((string) $post['post_expired_date'])
            ),
        ];

        if (!empty($post['post_expired_status'])) {
            $post_expired['status'] = (string) $post['post_expired_status'];
        }
        if (!empty($post['post_expired_category'])) {
            $post_expired['category'] = (string) $post['post_expired_category'];
        }
        if (!empty($post['post_expired_selected'])) {
            $post_expired['selected'] = (string) $post['post_expired_selected'];
        }
        if (!empty($post['post_expired_comment'])) {
            $post_expired['comment'] = (string) $post['post_expired_comment'];
        }
        if (!empty($post['post_expired_trackback'])) {
            $post_expired['trackback'] = (string) $post['post_expired_trackback'];
        }

        dcCore::app()->meta->setPostMeta(
            $post_id,
            My::META_TYPE,
            My::encode($post_expired)
        );
    }

    /**
     * Expired date form fields
     *
     * @param  string $post_type Posts type
     * @return array             Array of HTML form fields
     */
    protected static function fieldsPostExpired(string $post_type, ?int $post_id = null): array
    {
        $fields = $post_expired = [];

        if ($post_id) {
            $rs = dcCore::app()->meta->getMetadata([
                'meta_type' => My::META_TYPE,
                'post_id'   => $post_id,
                'limit'     => 1,
            ]);

            if (!$rs->isEmpty()) {
                $post_expired = My::decode($rs->f('meta_id'));
            }
        }

        $fields['post_expired_date'] = '<p><label for="post_expired_date">' .
            __('Date:') . '</label>' .
            form::datetime('post_expired_date', [
                'default' => html::escapeHTML(dt::str('%Y-%m-%dT%H:%M', (int) strtotime((string) ($post_expired['date'] ?? 'now')))),
                'class'   => (empty($post_expired['date']) ? 'invalid' : ''),
            ])
             . '</p>';

        $fields['post_expired_status'] = '<h5>' . __('On this date, change:') . '</h5>' .
            '<p><label for="post_expired_status">' .
            __('Status:') . '</label>' .
            form::combo(
                'post_expired_status',
                self::statusCombo(),
                empty($post_expired['status']) ?
                    '' : $post_expired['status']
            ) . '</p>';

        if ($post_type == 'post') {
            $fields['post_expired_category'] = '<p><label for="post_expired_category">' .
                __('Category:') . '</label>' .
                form::combo(
                    'post_expired_category',
                    self::categoriesCombo(
                        dcCore::app()->blog->getCategories(
                            ['post_type' => 'post']
                        )
                    ),
                    empty($post_expired['category']) ?
                        '' : $post_expired['category']
                ) . '</p>';

            $fields['post_expired_selected'] = '<p><label for="post_expired_selected">' .
                __('Selection:') . '</label>' .
                form::combo(
                    'post_expired_selected',
                    self::selectedCombo(),
                    empty($post_expired['selected']) ?
                        '' : $post_expired['selected']
                ) . '</p>';
        }

        $fields['post_expired_comment'] = '<p><label for="post_expired_comment">' .
            __('Comments status:') . '</label>' .
            form::combo(
                'post_expired_comment',
                self::commentCombo(),
                empty($post_expired['comment']) ?
                    '' : $post_expired['comment']
            ) . '</p>';

        $fields['post_expired_trackback'] = '<p><label for="post_expired_trackback">' .
            __('Trackbacks status:') . '</label>' .
            form::combo(
                'post_expired_trackback',
                self::trackbackCombo(),
                empty($post_expired['trackback']) ?
                    '' : $post_expired['trackback']
            ) . '</p>';

        return $fields;
    }

    /**
     * Custom categories combo
     *
     * @param  dcRecord $categories Categories recordset
     * @return array              Categorires combo
     */
    protected static function categoriesCombo(dcRecord $categories): array
    {
        # Getting categories
        $categories_combo = [
            __('Not changed')   => '',
            __('Uncategorized') => '!',
        ];

        try {
            $categories = dcCore::app()->blog->getCategories(
                ['post_type' => 'post']
            );
            while ($categories->fetch()) {
                $categories_combo[] = new formSelectOption(
                    str_repeat('&nbsp;&nbsp;', ((int) $categories->f('level')) - 1) . '&bull; ' . html::escapeHTML($categories->f('cat_title')),
                    '!' . $categories->f('cat_id')
                );
            }
        } catch (Exception $e) {
            return [];
        }

        return $categories_combo;
    }

    /**
     * Custom status combo
     *
     * @return array Status combo
     */
    protected static function statusCombo(): array
    {
        return [
            __('Not changed') => '',
            __('Published')   => '!1',
            __('Pending')     => '!-2',
            __('Unpublished') => '!0',
        ];
    }

    /**
     * Custom selection combo
     *
     * @return array Selection combo
     */
    protected static function selectedCombo(): array
    {
        return [
            __('Not changed')  => '',
            __('Selected')     => '!1',
            __('Not selected') => '!0',
        ];
    }

    /**
     * Custom comment status combo
     *
     * @return array Comment status combo
     */
    protected static function commentCombo(): array
    {
        return [
            __('Not changed') => '',
            __('Opened')      => '!1',
            __('Closed')      => '!0',
        ];
    }

    /**
     * Custom trackback status combo
     *
     * @return array Trackback status combo
     */
    protected static function trackbackCombo(): array
    {
        return [
            __('Not changed') => '',
            __('Opened')      => '!1',
            __('Closed')      => '!0',
        ];
    }
}
