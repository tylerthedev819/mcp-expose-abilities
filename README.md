# MCP Expose Abilities

**Let AI assistants edit your WordPress site.** 67 abilities for content, menus, users, media, filesystem, Elementor, system, and more.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-expose-abilities)](https://github.com/bjornfix/mcp-expose-abilities/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)

## What It Does

This plugin exposes WordPress functionality through MCP (Model Context Protocol), enabling AI assistants like Claude to directly interact with your WordPress site. No more copy-pasting between chat and admin.

**Example:** "Fix the phone numbers in these 25 articles to be clickable tel: links." - Done in 30 seconds, all 25 articles.

## Requirements

- WordPress 6.9+
- PHP 8.0+
- [Abilities API](https://github.com/WordPress/abilities-api) plugin (WordPress core team)
- [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin (WordPress core team)

## Installation

1. Install and activate the required plugins (Abilities API and MCP Adapter)
2. Download the latest release from [Releases](https://github.com/bjornfix/mcp-expose-abilities/releases)
3. Upload via WordPress Admin → Plugins → Add New → Upload Plugin
4. Activate the plugin

## All 67 Abilities

### Content Management (18)

| Ability | Description |
|---------|-------------|
| `content/list-posts` | List posts with filtering by status, category, author, search |
| `content/get-post` | Get single post by ID or slug |
| `content/create-post` | Create new post |
| `content/update-post` | Update existing post |
| `content/delete-post` | Delete post (trash or permanent) |
| `content/patch-post` | Find/replace in post content |
| `content/list-pages` | List pages with filtering |
| `content/get-page` | Get single page by ID or slug |
| `content/create-page` | Create new page |
| `content/update-page` | Update existing page |
| `content/delete-page` | Delete page |
| `content/patch-page` | Find/replace in page content |
| `content/list-categories` | List all categories |
| `content/list-tags` | List all tags |
| `content/create-tag` | Create new tag |
| `content/list-media` | List media items |
| `content/list-users` | List users |
| `content/search` | Search across posts, pages, media |

### Menu Management (7)

| Ability | Description |
|---------|-------------|
| `menus/list` | List all menus and theme locations |
| `menus/get-items` | Get items from a menu |
| `menus/create` | Create new menu |
| `menus/add-item` | Add item to menu |
| `menus/update-item` | Update menu item |
| `menus/delete-item` | Delete menu item |
| `menus/assign-location` | Assign menu to theme location |

### User Management (5)

| Ability | Description |
|---------|-------------|
| `users/list` | List users with roles |
| `users/get` | Get user by ID, login, or email |
| `users/create` | Create new user |
| `users/update` | Update user |
| `users/delete` | Delete user (can reassign content) |

### Media Library (4)

| Ability | Description |
|---------|-------------|
| `media/upload` | Upload media from URL |
| `media/get` | Get media item details and sizes |
| `media/update` | Update title, alt, caption |
| `media/delete` | Delete media item |

### Widget Management (3)

| Ability | Description |
|---------|-------------|
| `widgets/list-sidebars` | List all widget areas |
| `widgets/get-sidebar` | Get widgets in a sidebar |
| `widgets/list-available` | List available widget types |

### Plugin Management (2)

| Ability | Description |
|---------|-------------|
| `plugins/upload` | Upload plugin from URL |
| `plugins/list` | List installed plugins |

### Elementor (6)

| Ability | Description |
|---------|-------------|
| `elementor/get-data` | Get Elementor JSON for a page |
| `elementor/update-data` | Replace Elementor JSON |
| `elementor/patch-data` | Find/replace in Elementor JSON |
| `elementor/update-element` | Update specific element by ID |
| `elementor/list-templates` | List saved templates |
| `elementor/clear-cache` | Clear CSS cache |

### GeneratePress (2)

| Ability | Description |
|---------|-------------|
| `generatepress/get-settings` | Get theme settings |
| `generatepress/update-settings` | Update theme settings |

### GenerateBlocks (3)

| Ability | Description |
|---------|-------------|
| `generateblocks/get-global-styles` | Get global styles |
| `generateblocks/update-global-styles` | Update global styles |
| `generateblocks/clear-cache` | Clear CSS cache |

### System (3)

| Ability | Description |
|---------|-------------|
| `system/get-transient` | Get transient value |
| `system/debug-log` | Read debug.log file |
| `system/toggle-debug` | Toggle WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY in wp-config.php |

### Options (3)

| Ability | Description |
|---------|-------------|
| `options/get` | Get option value |
| `options/update` | Update option (protected options blocked) |
| `options/list` | List all options |

### Filesystem (10)

| Ability | Description |
|---------|-------------|
| `filesystem/get-changelog` | Get plugin/theme changelog |
| `filesystem/read-file` | Read file contents (security hardened) |
| `filesystem/write-file` | Write file (PHP code blocked) |
| `filesystem/append-file` | Append to file |
| `filesystem/list-directory` | List directory contents |
| `filesystem/delete-file` | Delete file (creates backup) |
| `filesystem/file-info` | Get file metadata |
| `filesystem/create-directory` | Create directory |
| `filesystem/copy-file` | Copy file |
| `filesystem/move-file` | Move/rename file |

### Cloudflare (1)

| Ability | Description |
|---------|-------------|
| `cloudflare/clear-cache` | Clear Cloudflare cache |

## Usage with Claude Code

### 1. Create Application Password

WordPress Admin → Users → Your Profile → Application Passwords

### 2. Add MCP Server

```bash
claude mcp add wordpress-mysite "https://yoursite.com/wp-json/mcp/mcp-adapter-default-server" \
  -t http \
  -H "Authorization: Basic $(echo -n 'username:application-password' | base64)"
```

### 3. Start Using

Now Claude can directly edit your WordPress site through conversation.

## Examples

### Create a new page

```json
{
  "ability_name": "content/create-page",
  "parameters": {
    "title": "About Us",
    "content": "<!-- wp:paragraph --><p>Hello world!</p><!-- /wp:paragraph -->",
    "status": "publish"
  }
}
```

### Add menu item

```json
{
  "ability_name": "menus/add-item",
  "parameters": {
    "menu_id": 5,
    "title": "Contact",
    "url": "/contact/"
  }
}
```

### Upload media from URL

```json
{
  "ability_name": "media/upload",
  "parameters": {
    "url": "https://example.com/image.jpg",
    "title": "Hero Image",
    "alt_text": "Beautiful sunset"
  }
}
```

### Batch find/replace

```json
{
  "ability_name": "content/patch-post",
  "parameters": {
    "id": 123,
    "find": "+44 203 3181 832",
    "replace": "<a href=\"tel:+442033181832\">+44 203 3181 832</a>"
  }
}
```

## Security

- **Authentication required** - Uses WordPress application passwords
- **Permission checks** - Every ability verifies user capabilities
- **Your server** - AI connects to your site, you control access

## Architecture

Three-plugin stack:

1. **[Abilities API](https://github.com/WordPress/abilities-api)** - Framework for registering abilities (WordPress core team)
2. **[MCP Adapter](https://github.com/WordPress/mcp-adapter)** - MCP protocol layer (WordPress core team)
3. **MCP Expose Abilities** (this plugin) - The actual abilities

## Contributing

PRs welcome! If you add useful abilities, share them.

## Changelog

### 2.2.12
- Security: Added protected options blocklist (active_plugins, siteurl, admin_email, etc.)
- Security: Prevents accidental site breakage via options/update
- Total: 67 abilities

### 2.2.11
- Security: Added UTF-7 and UTF-16 encoding bypass detection
- Security: Blocks encoded PHP injection attempts

### 2.2.10
- Security: Major filesystem security hardening
- Security: PHP code detection in file writes
- Security: Path traversal protection
- Security: Restricted to wp-content directory

### 2.1.0
- Added: Filesystem abilities (read-file, write-file, append-file, delete-file, list-directory, file-info, create-directory, copy-file, move-file, get-changelog)
- Added: Options abilities (get, update, list)
- Added: System abilities (get-transient, debug-log)
- Added: Cloudflare cache clear ability
- Added: `elementor/update-element` for targeted element updates
- Added: `content/list-media`, `content/list-users`
- Total: 67 abilities

### 2.0.2
- Added: `content/create-tag` ability to create post tags via MCP
- Total: 56 abilities

### 2.0.1
- Added: `system/toggle-debug` ability to toggle WP_DEBUG settings via MCP
- Total: 55 abilities

### 2.0.0
- Added: Menu abilities (list, get-items, create, add-item, update-item, delete-item, assign-location)
- Added: User abilities (list, get, create, update, delete)
- Added: Media abilities (upload, get, update, delete)
- Added: Widget abilities (list-sidebars, get-sidebar, list-available)
- Added: Page abilities (list-pages, get-page, create-page, update-page, delete-page, patch-page)
- Total: 54 abilities

### 1.0.0
- Initial release
- Content abilities for posts
- Plugin management
- Elementor integration
- GeneratePress/GenerateBlocks support

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/)
- [Blog Post](https://devenia.com/we-built-a-wordpress-plugin-that-lets-ai-edit-your-site/)
- [Abilities API](https://github.com/WordPress/abilities-api) (WordPress core team)
- [MCP Adapter](https://github.com/WordPress/mcp-adapter) (WordPress core team)
