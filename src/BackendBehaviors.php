<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use ArrayObject;
use DateTimeZone;
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Datetime,
    Form,
    Hidden,
    Input,
    Label,
    Note,
    Option,
    Para,
    Text,
    Select,
    Submit
};
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       postExpired backend behaviors class.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    /**
     * Add actions to posts page combo.
     *
     * @param   ActionsPosts    $pa     ActionsPosts instance
     */
    public static function adminPostsActions(ActionsPosts $pa): void
    {
        $pa->addAction(
            [
                __('Expired entries') => [
                    __('Add expired date') => 'post_expired_add',
                ],
            ],
            self::callbackAdd(...)
        );

        $pa->addAction(
            [
                __('Expired entries') => [
                    __('Remove expired date') => 'post_expired_remove',
                ],
            ],
            self::callbackRemove(...)
        );
    }

    /**
     * Add javascript for date field and toggle.
     *
     * @return  string  HTML head
     */
    public static function adminPostHeaders(): string
    {
        return My::jsLoad('backend');
    }

    /**
     * Add form to post sidebar.
     *
     * @param   ArrayObject     $main_items     Main items
     * @param   ArrayObject     $sidebar_items  Sidebar items
     * @param   ?MetaRecord     $post           Post record or null
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        if ($post === null) {
            return;
        }

        $sidebar_items['post_expired'] = [
            'title' => __('Expired date'),
            'items' => self::fieldsPostExpired(
                $post->f('post_type'),
                (int) $post->f('post_id'),
                true
            ),
        ];
    }

    /**
     * Delete expired date on post edition.
     *
     * @param   int     $post_id    Post id
     */
    public static function adminBeforePostDelete(int $post_id): void
    {
        self::delPostExpired($post_id);
    }

    /**
     * Add expired date on post edition.
     *
     * @param   Cursor  $cur        Current post Cursor
     * @param   int     $post_id    Post id
     */
    public static function adminAfterPostSave(Cursor $cur, int $post_id): void
    {
        self::delPostExpired($post_id);

        if (!empty($_POST['post_expired_date'])
            && (
                !empty($_POST['post_expired_status'])
                || !empty($_POST['post_expired_category'])
                || !empty($_POST['post_expired_selected'])
                || !empty($_POST['post_expired_comment'])
                || !empty($_POST['post_expired_trackback'])
                || !empty($_POST['post_expired_password'])
            )
        ) {
            self::setPostExpired($post_id, new ArrayObject($_POST));
        }
    }

    /**
     * Posts actions callback to add expired date.
     *
     * @param   ActionsPosts    $pa     ActionsPosts instance
     * @param   ArrayObject     $post   _POST actions
     */
    public static function callbackAdd(ActionsPosts $pa, ArrayObject $post): void
    {
        // No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        // Add expired date
        if (!empty($post['post_expired_date'])
            && (
                !empty($post['post_expired_status'])
                || !empty($post['post_expired_category'])
                || !empty($post['post_expired_selected'])
                || !empty($post['post_expired_comment'])
                || !empty($post['post_expired_trackback'])
                || !empty($post['post_expired_password'])
            )
        ) {
            foreach ($posts_ids as $post_id) {
                self::delPostExpired($post_id);
                self::setPostExpired($post_id, $post);
            }

            Notices::addSuccessNotice(__('Expired date added.'));
            $pa->redirect(true);

            // Display form
        } else {
            // Get records to know post type
            $posts = $pa->getRS();

            $pa->beginPage(
                Page::breadcrumb([
                    Html::escapeHTML(App::blog()->name())    => '',
                    $pa->getCallerTitle()                    => $pa->getRedirection(true),
                    __('Add expired date to this selection') => '',
                ]),
                //Page::jsDatePicker() .
                self::adminPostHeaders()
            );

            echo
            (new Form('peadd'))->method('post')->action($pa->getURI())->fields([
                (new Text('', $pa->getCheckboxes())),
                (new Para())->items([
                    ... self::fieldsPostExpired($posts->f('post_type'), null, false),
                    ... $pa->hiddenFields(),
                    App::nonce()->formNonce(),
                    (new Hidden(['action'], 'post_expired_add')),
                    (new Submit(['do']))->value(__('Save')),
                ]),
            ])->render();

            $pa->endPage();
        }
    }

    /**
     * Posts actions callback to add expired date.
     *
     * @param   ActionsPosts    $pa     ActionsPosts instance
     * @param   ArrayObject     $post   _POST actions
     */
    public static function callbackRemove(ActionsPosts $pa, ArrayObject $post): void
    {
        // No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        // Delete expired date
        foreach ($posts_ids as $post_id) {
            self::delPostExpired($post_id);
        }

        Notices::addSuccessNotice(__('Expired date deleted.'));
        $pa->redirect(true);
    }

    /**
     * Delete expired date.
     *
     * @param   int     $post_id    Post id
     */
    private static function delPostExpired(int $post_id): void
    {
        App::meta()->delPostMeta($post_id, My::META_TYPE);
    }

    /**
     * Save expired date.
     *
     * @param   int             $post_id    Post id
     * @param   ArrayObject     $post       _POST fields
     */
    private static function setPostExpired(int $post_id, ArrayObject $post): void
    {
        $post_expired = [
            'status'      => '',
            'category'    => '',
            'selected'    => '',
            'comment'     => '',
            'trackback'   => '',
            'password'    => '',
            'newpassword' => '',
            'date'        => self::dateFromUser($post['post_expired_date']),
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
        if (!empty($post['post_expired_password'])) {
            $post_expired['password'] = (string) $post['post_expired_password'];
        }
        if (!empty($post['post_expired_newpassword'])) {
            $post_expired['newpassword'] = (string) $post['post_expired_newpassword'];
        }

        App::meta()->setPostMeta(
            $post_id,
            My::META_TYPE,
            My::encode($post_expired)
        );
    }

    /**
     * Expired date form fields.
     *
     * @param   string      $post_type  Posts type
     * @param   null|int    $post_id    Post ID
     * @param   bool        $render     Render fileds to HTML
     * @return  array   Array of object form fields
     */
    private static function fieldsPostExpired(string $post_type, ?int $post_id = null, bool $render = true): array
    {
        $fields = $post_expired = [];

        if ($post_id) {
            $rs = App::meta()->getMetadata([
                'meta_type' => My::META_TYPE,
                'post_id'   => $post_id,
                'limit'     => 1,
            ]);

            if (!$rs->isEmpty()) {
                $post_expired = My::decode($rs->f('meta_id'));
            }
        }

        $fields['post_expired_date'] = (new Para())->items([
            (new Label(__('Date:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_date'),
            (new Datetime('post_expired_date', Html::escapeHTML(self::dateToUser($post_expired['date'] ?? 'now'))))->class(empty($post_expired['date']) ? 'invalid' : ''),
        ]);

        $fields['post_expired_status'] = (new Para())->items([
            (new Text('strong', __('On this date, change:'))),
            (new Text('br')),
            (new Label(__('Status:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_status'),
            (new Select('post_expired_status'))->default(empty($post_expired['status']) ? '' : $post_expired['status'])->items(self::statusCombo()),
        ]);

        if ($post_type == 'post') {
            $fields['post_expired_category'] = (new Para())->items([
                (new Label(__('Category:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_category'),
                (new Select('post_expired_category'))->default(empty($post_expired['category']) ? '' : $post_expired['category'])->items(self::categoriesCombo(
                    App::blog()->getCategories(
                        ['post_type' => 'post']
                    )
                )),
            ]);

            $fields['post_expired_selected'] = (new Para())->items([
                (new Label(__('Selection:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_selected'),
                (new Select('post_expired_selected'))->default(empty($post_expired['selected']) ? '' : $post_expired['selected'])->items(self::selectedCombo()),
            ]);
        }

        $fields['post_expired_comment'] = (new Para())->items([
            (new Label(__('Comments status:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_comment'),
            (new Select('post_expired_comment'))->default(empty($post_expired['comment']) ? '' : $post_expired['comment'])->items(self::commentCombo()),
        ]);

        $fields['post_expired_trackback'] = (new Para())->items([
            (new Label(__('Trackbacks status:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_trackback'),
            (new Select('post_expired_trackback'))->default(empty($post_expired['trackback']) ? '' : $post_expired['trackback'])->items(self::trackbackCombo()),
        ]);

        $fields['post_expired_password'] = (new Para())->items([
            (new Checkbox('post_expired_password', !empty($post_expired['password'])))->value(1),
            (new Label(__('Change password'), Label::OUTSIDE_LABEL_AFTER))->for('post_expired_password')->class('classic'),
            (new Label(__('New password:'), Label::OUTSIDE_LABEL_BEFORE))->for('post_expired_newpassword'),
            (new Input('post_expired_newpassword'))->size(65)->maxlenght(255)->class('maximal')->value(empty($post_expired['newpassword']) ? '' : $post_expired['newpassword']),
            (new Note())->text(__('Leave empty to remove it'))->class('form-note'),
        ]);

        if ($render) {
            foreach ($fields as $k => $v) {
                $fields[$k] = $v->render();
            }
        }

        return $fields;
    }

    /**
     * Custom categories combo.
     *
     * @param   MetaRecord  $categories     Categories recordset
     *
     * @return  array   Categorires combo
     */
    private static function categoriesCombo(MetaRecord $categories): array
    {
        // Getting categories
        $categories_combo = [
            __('Not changed')   => '',
            __('Uncategorized') => '!',
        ];

        try {
            $categories = App::blog()->getCategories(
                ['post_type' => 'post']
            );
            while ($categories->fetch()) {
                $categories_combo[] = new Option(
                    str_repeat('&nbsp;&nbsp;', ((int) $categories->f('level')) - 1) . '&bull; ' . Html::escapeHTML($categories->f('cat_title')),
                    '!' . $categories->f('cat_id')
                );
            }
        } catch (Exception $e) {
            return [];
        }

        return $categories_combo;
    }

    /**
     * Custom status combo.
     *
     * @return  array   Status combo
     */
    private static function statusCombo(): array
    {
        return [
            __('Not changed') => '',
            __('Published')   => '!1',
            __('Pending')     => '!-2',
            __('Unpublished') => '!0',
        ];
    }

    /**
     * Custom selection combo.
     *
     * @return  array   Selection combo
     */
    private static function selectedCombo(): array
    {
        return [
            __('Not changed')  => '',
            __('Selected')     => '!1',
            __('Not selected') => '!0',
        ];
    }

    /**
     * Custom comment status combo.
     *
     * @return  array   Comment status combo
     */
    private static function commentCombo(): array
    {
        return [
            __('Not changed') => '',
            __('Opened')      => '!1',
            __('Closed')      => '!0',
        ];
    }

    /**
     * Custom trackback status combo.
     *
     * @return  array   Trackback status combo
     */
    private static function trackbackCombo(): array
    {
        return [
            __('Not changed') => '',
            __('Opened')      => '!1',
            __('Closed')      => '!0',
        ];
    }

    /**
     * Change a date from user timezone to UTC.
     *
     * @param   string  $date   The date
     *
     * @return  string  The UTC date
     */
    private static function dateFromUser(string $date): string
    {
        $u = App::auth()->getInfo('user_tz') ?? 'UTC';
        $d = date_create($date, new DateTimeZone($u));

        return $d ? date_format($d->setTimezone(new DateTimeZone('UTC')), 'Y-m-d H:i:00') : '';
    }

    /**
     * Change a date from UTC to user timezone.
     *
     * @param   string  $date   The UTC date
     *
     * @return  string  The date
     */
    private static function dateToUser(string $date): string
    {
        $u = App::auth()->getInfo('user_tz') ?? 'UTC';
        $d = date_create($date, new DateTimeZone('UTC'));

        return $d ? date_format($d->setTimezone(new DateTimeZone($u)), 'Y-m-d\TH:i') : '';
    }
}
