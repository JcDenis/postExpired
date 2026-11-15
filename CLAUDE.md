# postExpired

Notes for AI assistants working on the Dotclear plugin postExpired.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Add options to the sidebar of post edition page to change some options of a post at a given time.

No admin page. Public side: behaviors.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendBehaviors.php`: public behaviors.
* `src/FrontendTemplate.php`: template tags.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/PostExpired.php`: applies the changes of expired dates (public pages and admin pages).
* `src/RsExtPostExpired.php`: record extension class (must not extend the core Post extension).
* `js/`: `backend.js`.
* Behaviors used: `publicPrependV2`, `coreBlogGetPosts`, `adminPostsActions`, `adminPagesActions`, `adminPostHeaders`, `adminPageHeaders`, `adminPostFormItems`, `adminPageFormItems`, `adminBeforePostDelete`, `adminBeforePageDelete`, `adminAfterPostUpdate`, `adminAfterPageUpdate`, `adminAfterPostCreate`, `adminAfterPageCreate`.

## Pitfalls

* The record extension must not extend `Dotclear\Schema\Extension\Post`: its inherited methods would replace those of other extensions (FrontendSession `commentsActive()`).
* A password removed by an expired date must be set to `null`, not `''`, or the core hides the entry.

## Testing

* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
