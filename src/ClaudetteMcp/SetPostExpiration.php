<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired\ClaudetteMcp;

use DateTimeImmutable;
use DateTimeZone;
use Dotclear\App;
use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\ClaudetteMcp\Tool\PostTool;
use Dotclear\Plugin\postExpired\My;
use Exception;

/**
 * @brief       postExpired tool for the plugin ClaudetteMcp: set the expired date of an entry.
 * @ingroup     postExpired
 *
 * ClaudetteMcp connects an AI assistant to the blog. Same data as the entry form of the admin
 * (meta of type post_expired, date in UTC), except the password change: no password through the assistant.
 * These classes are only loaded by ClaudetteMcp, which is not required by postExpired.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class SetPostExpiration extends PostTool
{
    /**
     * Statuses the plugin knows, as in the admin form.
     *
     * @var     array<string, int>  STATUSES
     */
    private const STATUSES = [
        'published'   => 1,
        'pending'     => -2,
        'unpublished' => 0,
    ];

    public function name(): string
    {
        return 'set_post_expiration';
    }

    public function title(): string
    {
        return 'Set the expired date of an entry';
    }

    public function description(): string
    {
        return 'Set the expired date of a post or a page (plugin postExpired): at this date, the blog changes by itself the options given here ' .
            '(status, category, selection, comments, trackbacks). It replaces the previous expired date of the entry. ' .
            'Give at least one change. Give an empty date to remove the expired date. ' .
            'An entry can be changed only by its author or by a content administrator. Only do this when the user explicitly asks for it.';
    }

    public function properties(): array
    {
        return [
            'id' => [
                'type'        => 'integer',
                'description' => 'The entry ID (see list_posts).',
            ],
            'date' => [
                'type'        => 'string',
                'description' => 'The expired date as "YYYY-MM-DD HH:MM", in the timezone of the user (see get_blog), in the future. Empty to remove the expired date.',
            ],
            'status' => [
                'type'        => 'string',
                'enum'        => array_keys(self::STATUSES),
                'description' => 'The new status of the entry at this date.',
            ],
            'category_id' => [
                'type'        => 'integer',
                'description' => 'Posts only: the new category ID at this date (see list_categories), 0 for no category.',
            ],
            'selected' => [
                'type'        => 'boolean',
                'description' => 'Posts only: the new selection of the entry at this date.',
            ],
            'open_comments' => [
                'type'        => 'boolean',
                'description' => 'Accept comments or not from this date.',
            ],
            'open_trackbacks' => [
                'type'        => 'boolean',
                'description' => 'Accept trackbacks or not from this date.',
            ],
        ];
    }

    public function required(): array
    {
        return ['id', 'date'];
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function available(): bool
    {
        return $this->canWrite();
    }

    public function call(array $args): array
    {
        $id   = $this->int($args, 'id');
        $post = $this->fetch($id);
        $type = $post->strField('post_type');

        if (!in_array($type, ['post', 'page'], true)) {
            throw new McpToolException('Only posts and pages can have an expired date');
        }
        if (!$this->editable($post)) {
            throw new McpToolException('You are not allowed to update this entry');
        }

        $date = $this->str($args, 'date');
        if ($date === '') {
            App::meta()->delPostMeta($id, My::META_TYPE);

            return ['id' => $id, 'expiration' => null];
        }

        $utc = $this->utc($this->date($date));
        if ($utc <= gmdate('Y-m-d H:i:s')) {
            throw new McpToolException('The expired date must be in the future');
        }

        $expired = [
            'status'      => '',
            'category'    => '',
            'selected'    => '',
            'comment'     => '',
            'trackback'   => '',
            'password'    => '',
            'newpassword' => '',
            'date'        => $utc,
        ];

        // Values are prefixed by "!", as in the admin form
        if ($this->has($args, 'status')) {
            $status = $this->str($args, 'status');
            if (!isset(self::STATUSES[$status])) {
                throw new McpToolException('Unknown status, use one of: ' . implode(', ', array_keys(self::STATUSES)));
            }
            $expired['status'] = '!' . self::STATUSES[$status];
        }
        if ($type === 'post' && $this->has($args, 'category_id')) {
            $cat_id = $this->int($args, 'category_id');
            if ($cat_id > 0 && App::blog()->getCategory($cat_id)->isEmpty()) {
                throw new McpToolException('Unknown category, see list_categories');
            }
            $expired['category'] = '!' . ($cat_id > 0 ? $cat_id : '');
        }
        if ($type === 'post' && $this->has($args, 'selected')) {
            $expired['selected'] = '!' . (int) $this->bool($args, 'selected');
        }
        if ($this->has($args, 'open_comments')) {
            $expired['comment'] = '!' . (int) $this->bool($args, 'open_comments');
        }
        if ($this->has($args, 'open_trackbacks')) {
            $expired['trackback'] = '!' . (int) $this->bool($args, 'open_trackbacks');
        }

        if ($expired['status'] . $expired['category'] . $expired['selected'] . $expired['comment'] . $expired['trackback'] === '') {
            throw new McpToolException('Give at least one change to do at this date');
        }

        App::meta()->delPostMeta($id, My::META_TYPE);
        App::meta()->setPostMeta($id, My::META_TYPE, My::encode($expired));

        return [
            'id'         => $id,
            'expiration' => $this->describeExpiration($expired),
        ];
    }

    /**
     * Change a date from user timezone to UTC, as the admin form does.
     */
    private function utc(string $date): string
    {
        try {
            $tz = new DateTimeZone(is_string($tz = App::auth()->getInfo('user_tz')) && $tz !== '' ? $tz : 'UTC');
            $dt = new DateTimeImmutable($date, $tz);
        } catch (Exception) {
            throw new McpToolException('Invalid date, use "YYYY-MM-DD HH:MM"');
        }

        return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:00');
    }

    /**
     * Describe an expired date for the assistant, date in the timezone of the user.
     *
     * @param   array<string, string>   $expired
     *
     * @return  array<string, mixed>
     */
    private function describeExpiration(array $expired): array
    {
        try {
            $tz   = new DateTimeZone(is_string($tz = App::auth()->getInfo('user_tz')) && $tz !== '' ? $tz : 'UTC');
            $date = (new DateTimeImmutable($expired['date'], new DateTimeZone('UTC')))->setTimezone($tz)->format('Y-m-d H:i');
        } catch (Exception) {
            $date = '';
        }

        $value   = fn (string $key): ?int => $expired[$key] === '' ? null : (int) substr($expired[$key], 1);
        $changes = [];
        if (($status = $value('status')) !== null) {
            $changes['status'] = array_search($status, self::STATUSES, true) ?: 'unknown';
        }
        if (($category = $value('category')) !== null) {
            $changes['category_id'] = $category;
        }
        foreach (['selected' => 'selected', 'comment' => 'open_comments', 'trackback' => 'open_trackbacks'] as $key => $name) {
            if (($bool = $value($key)) !== null) {
                $changes[$name] = $bool === 1;
            }
        }

        return [
            'date'    => $date,
            'changes' => $changes,
        ];
    }
}
